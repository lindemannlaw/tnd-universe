<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaController extends Controller
{
    private const SORTABLE = ['name', 'size', 'updated_at'];
    private const VIEWS    = ['table', 'grid'];

    public function index(Request $request): View
    {
        $data = $this->buildListData($request);

        return view('admin.media.index', $data);
    }

    public function show(Request $request, Media $media): JsonResponse|string
    {
        if (!$request->ajax()) {
            abort(404);
        }

        return view('admin.media.show', [
            'media' => $media,
            'owner' => $media->model,
        ])->render();
    }

    public function download(Media $media)
    {
        $path = $media->getPath();

        if (!is_file($path)) {
            abort(404);
        }

        return response()->download($path, $media->file_name, [
            'Content-Type' => $media->mime_type ?? 'application/octet-stream',
        ]);
    }

    public function replace(Request $request, Media $media)
    {
        $request->validate([
            'file' => ['required', 'file', 'max:51200'],
        ]);

        $owner = $media->model;
        if (!$owner) {
            return response()->json([
                'message' => __('errors.media_orphan'),
            ], 422);
        }

        try {
            DB::beginTransaction();

            $collection      = $media->collection_name;
            $customProps     = $media->custom_properties ?? [];
            $orderColumn     = $media->order_column;

            // Capture pivot attachments before delete so we can re-point them to the
            // replacement Media row — otherwise FK cascade drops them and every consumer
            // silently loses the image.
            $oldAttachments = DB::table('model_media')
                ->where('media_id', $media->id)
                ->get()
                ->map(fn ($row) => (array) $row)
                ->toArray();

            $uploaded     = $request->file('file');
            $originalName = pathinfo($uploaded->getClientOriginalName(), PATHINFO_FILENAME) ?: $media->name;

            $media->delete();

            $newMedia = $owner->addMediaFromRequest('file')
                ->usingName($originalName)
                ->withCustomProperties($customProps)
                ->toMediaCollection($collection);

            if (!is_null($orderColumn)) {
                $newMedia->order_column = $orderColumn;
                $newMedia->save();
            }

            if ($oldAttachments) {
                $now = now();
                $reattach = array_map(fn ($r) => [
                    'media_id'        => $newMedia->id,
                    'model_type'      => $r['model_type'],
                    'model_id'        => $r['model_id'],
                    'collection_name' => $r['collection_name'],
                    'order_column'    => $r['order_column'] ?? 0,
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ], $oldAttachments);
                DB::table('model_media')->insertOrIgnore($reattach);
            }

            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            Log::error(__('errors.media_replace_failed'), ['exception' => $exception]);

            if ($request->ajax()) {
                return response()->json([
                    'message' => __('errors.media_replace_failed'),
                    'error'   => $exception->getMessage(),
                ], 500);
            }

            report($exception);
            return redirect()->back()->with('error', __('errors.general'));
        }

        if ($request->ajax()) {
            return response()->json([
                'toast' => ['type' => 'success', 'message' => __('admin.success_update_data')],
                'html'  => $this->getViewMediaList($request),
            ]);
        }

        abort(404);
    }

    public function destroy(Request $request, Media $media)
    {
        try {
            DB::beginTransaction();

            $media->deleteOrFail();

            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            Log::error(__('errors.media_delete_failed'), ['exception' => $exception]);

            if ($request->ajax()) {
                return response()->json([
                    'message' => __('errors.media_delete_failed'),
                    'error'   => $exception->getMessage(),
                ], 500);
            }

            report($exception);
            return redirect()->back()->with('error', __('errors.general'));
        }

        if ($request->ajax()) {
            return response()->json([
                'toast' => ['type' => 'success', 'message' => __('admin.success_delete_data')],
                'html'  => $this->getViewMediaList($request),
            ]);
        }

        abort(404);
    }

    public function picker(Request $request): string
    {
        if (!$request->ajax()) {
            abort(404);
        }

        $data = $this->buildListData($request, pickerMode: true);
        $data['mimeFilter'] = $request->input('mime_filter');
        $data['field']      = $request->input('field');

        return view('admin.media.picker', $data)->render();
    }

    public function pickerList(Request $request): string
    {
        if (!$request->ajax()) {
            abort(404);
        }

        $data = $this->buildListData($request, pickerMode: true);
        $data['mimeFilter'] = $request->input('mime_filter');
        $data['field']      = $request->input('field');

        return view('admin.media.picker-list', $data)->render();
    }

    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:51200'],
        ]);

        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => __('errors.media_upload_failed')], 401);
        }

        try {
            DB::beginTransaction();

            $uploaded     = $request->file('file');
            $originalName = pathinfo($uploaded->getClientOriginalName(), PATHINFO_FILENAME) ?: 'file';

            $media = $user->addMediaFromRequest('file')
                ->usingName($originalName)
                ->toMediaCollection('library');

            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            Log::error(__('errors.media_upload_failed'), ['exception' => $exception]);

            return response()->json([
                'message' => __('errors.media_upload_failed'),
                'error'   => $exception->getMessage(),
            ], 500);
        }

        return response()->json([
            'toast' => ['type' => 'success', 'message' => __('admin.success_upload_data')],
            'media' => [
                'id'        => $media->id,
                'name'      => $media->name,
                'file_name' => $media->file_name,
                'size'      => $media->size,
                'mime_type' => $media->mime_type,
                'url'       => $media->getUrl(),
            ],
        ]);
    }

    private function buildListData(Request $request, bool $pickerMode = false): array
    {
        $query      = trim((string) $request->input('search_query', ''));
        $sortBy     = $request->input('sort_by', 'updated_at');
        $sortDir    = $request->input('sort_dir', 'desc');
        $view       = $request->input('view', 'table');
        $mimeFilter = $request->input('mime_filter');

        if (!in_array($sortBy, self::SORTABLE, true)) {
            $sortBy = 'updated_at';
        }
        if (!in_array($sortDir, ['asc', 'desc'], true)) {
            $sortDir = 'desc';
        }
        if (!in_array($view, self::VIEWS, true)) {
            $view = 'table';
        }

        $media = Media::query()
            ->when($query !== '', function ($q) use ($query) {
                $like = '%' . $query . '%';
                $q->where(function ($qq) use ($like) {
                    $qq->where('name', 'like', $like)
                       ->orWhere('file_name', 'like', $like)
                       ->orWhere('custom_properties->name', 'like', $like);
                });
            })
            ->when($mimeFilter, function ($q) use ($mimeFilter) {
                if (str_ends_with($mimeFilter, '/*')) {
                    $q->where('mime_type', 'like', substr($mimeFilter, 0, -1) . '%');
                } else {
                    $q->where('mime_type', $mimeFilter);
                }
            })
            ->orderBy($sortBy, $sortDir)
            ->orderBy('id', 'desc')
            ->paginate(50)
            ->withQueryString()
            // Always render pagination URLs against the index route, not the
            // current request URL. Otherwise, after a delete or replace
            // refresh, the pagination would point at /admin/media/{id}/delete
            // (DELETE-only) and clicking page 2 would hit 405.
            ->withPath(
                $pickerMode
                    ? route('admin.media.picker.list')
                    : route('admin.media.index')
            );

        return [
            'media'      => $media,
            'query'      => $query,
            'sortBy'     => $sortBy,
            'sortDir'    => $sortDir,
            'view'       => $view,
            'pickerMode' => $pickerMode,
        ];
    }

    private function getViewMediaList(Request $request): string
    {
        return view('admin.media.list', $this->buildListData($request))->render();
    }
}
