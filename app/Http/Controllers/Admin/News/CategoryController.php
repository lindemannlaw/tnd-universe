<?php

namespace App\Http\Controllers\Admin\News;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\News\Category\StoreRequest;
use App\Http\Requests\Admin\News\Category\UpdateRequest;
use App\Models\NewsCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View {
        $categories = NewsCategory::all();

        return view('admin.news.categories.index', compact('categories'));
    }

    public function create(Request $request): View|JsonResponse|string {
        if ($request->ajax()) {
            return view('admin.news.categories.create')->render();
        }

        abort(404);
    }

    private const CONTENT_FIELDS = ['name', 'description'];

    public function store(StoreRequest $request): View|JsonResponse|string {
        $data = $request->validated();

        try {
            DB::beginTransaction();

            $category = NewsCategory::create($data);

            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            Log::error(__('errors.category_store_failed'), ['exception' => $exception]);

            if ($request->ajax()) {
                return response()->json([
                    'message' => __('errors.category_store_failed'),
                    'error' => $exception->getMessage(),
                ], 500);
            }

            if(app()->environment('local')) dd($exception);
            return redirect()->back()->with('error', __('errors.general'));
        }

        if ($request->ajax()) {
            return response()->json([
                'toast' => ['type' => 'success', 'message' => __('admin.success_create_data')],
                'html'  => $this->getViewCategories(),
                'autoTranslate' => $this->buildAutoTranslatePayload(
                    $category, 'news_category', self::CONTENT_FIELDS, true,
                    'admin.news.category.edit', [$category]
                ),
            ]);
        }

        abort(404);
    }

    public function edit(Request $request, NewsCategory $newsCategory): View|JsonResponse|string {
        if ($request->ajax()) {
            return view('admin.news.categories.edit', [
                'category' => $newsCategory,
            ])->render();
        }

        abort(404);
    }

    public function update(UpdateRequest $request, NewsCategory $newsCategory): View|JsonResponse|string {
        $data = $request->validated();
        $sourceLang = config('app.fallback_locale', 'en');
        $oldValues  = $this->captureSourceValues($newsCategory, array_merge(self::CONTENT_FIELDS, ['seo_title', 'seo_description', 'seo_keywords', 'geo_text']), $sourceLang);

        try {
            DB::beginTransaction();

            $this->preserveTranslations($newsCategory, $data);
            $newsCategory->updateOrFail($data);

            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            Log::error(__('errors.category_update_failed'), ['exception' => $exception]);

            if ($request->ajax()) {
                return response()->json([
                    'message' => __('errors.category_update_failed'),
                    'error' => $exception->getMessage(),
                ], 500);
            }

            if(app()->environment('local')) dd($exception);
            return redirect()->back()->with('error', __('errors.general'));
        }

        if ($request->ajax()) {
            $newsCategory->refresh();
            $payload = $this->buildAutoTranslateUpdatePayload(
                $newsCategory, $oldValues, 'news_category', self::CONTENT_FIELDS, true,
                'admin.news.category.edit', [$newsCategory]
            );
            return response()->json([
                'toast' => ['type' => 'success', 'message' => __('admin.success_update_data')],
                'html'  => $this->getViewCategories(),
                'autoTranslate' => ($payload['changedFields'] || $payload['changedSeoFields']) ? $payload : null,
            ]);
        }

        abort(404);
    }

    public function delete(Request $request, NewsCategory $newsCategory) {
        try {
            DB::beginTransaction();

            $newsCategory->deleteOrFail();

            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            Log::error(__('errors.category_delete_failed'), ['exception' => $exception]);

            if ($request->ajax()) {
                return response()->json([
                    'message' => __('errors.category_delete_failed'),
                    'error' => $exception->getMessage(),
                ], 500);
            }

            if(app()->environment('local')) dd($exception);
            return redirect()->back()->with('error', __('errors.general'));
        }

        if ($request->ajax()) {
            return response()->json([
                'toast' => [
                    'type' => 'success',
                    'message' => __('admin.success_delete_data'),
                ],
                'html' => $this->getViewCategories(),
            ]);
        }

        abort(404);
    }

    public function getViewCategories(): View|string {
        $categories = NewsCategory::all();

        return view('admin.news.categories.list', compact('categories'))->render();
    }
}
