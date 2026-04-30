<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\Response;

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

    public function download(Media $media): Response
    {
        return $media->toResponse(request())
            ->setContentDisposition('attachment', $media->file_name);
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

    private function buildListData(Request $request): array
    {
        $query   = trim((string) $request->input('search_query', ''));
        $sortBy  = $request->input('sort_by', 'updated_at');
        $sortDir = $request->input('sort_dir', 'desc');
        $view    = $request->input('view', 'table');

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
            ->orderBy($sortBy, $sortDir)
            ->orderBy('id', 'desc')
            ->paginate(50)
            ->withQueryString();

        return [
            'media'   => $media,
            'query'   => $query,
            'sortBy'  => $sortBy,
            'sortDir' => $sortDir,
            'view'    => $view,
        ];
    }

    private function getViewMediaList(Request $request): string
    {
        return view('admin.media.list', $this->buildListData($request))->render();
    }
}
