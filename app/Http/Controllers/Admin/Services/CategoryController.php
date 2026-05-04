<?php

namespace App\Http\Controllers\Admin\Services;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Services\Category\StoreRequest;
use App\Http\Requests\Admin\Services\Category\UpdateRequest;
use App\Models\ServiceCategory;
use App\Services\MediaPickerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function __construct(private MediaPickerService $mediaPicker) {}

    public function index(): View {
        $categories = ServiceCategory::all();

        return view('admin.services.categories.index', compact('categories'));
    }

    public function create(Request $request): View|JsonResponse|string {
        if ($request->ajax()) {
            return view('admin.services.categories.create')->render();
        }

        abort(404);
    }

    private const CONTENT_FIELDS = ['name', 'short_description', 'description'];

    public function store(StoreRequest $request): View|JsonResponse|string {
        $data = $request->validated();

        try {
            DB::beginTransaction();

            $category = ServiceCategory::create($data);

            $this->mediaPicker->applyToCollection($category, $category->mediaHero, $request, 'hero_image');

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

            report($exception);
            return redirect()->back()->with('error', __('errors.general'));
        }

        if ($request->ajax()) {
            return response()->json([
                'toast' => ['type' => 'success', 'message' => __('admin.success_create_data')],
                'html'  => $this->getViewCategories(),
                'autoTranslate' => $this->buildAutoTranslatePayload(
                    $category, 'service_category', self::CONTENT_FIELDS, true,
                    'admin.services.category.edit', [$category]
                ),
            ]);
        }

        abort(404);
    }

    public function edit(Request $request, ServiceCategory $serviceCategory): View|JsonResponse|string {
        if ($request->ajax()) {
            return view('admin.services.categories.edit', [
                'category' => $serviceCategory,
            ])->render();
        }

        abort(404);
    }

    public function update(UpdateRequest $request, ServiceCategory $serviceCategory): View|JsonResponse|string {
        $data = $request->validated();
        $sourceLang = config('app.fallback_locale', 'en');
        $oldValues  = $this->captureSourceValues($serviceCategory, array_merge(self::CONTENT_FIELDS, ['seo_title', 'seo_description', 'seo_keywords', 'geo_text']), $sourceLang);

        try {
            DB::beginTransaction();

            $this->mediaPicker->applyToCollection($serviceCategory, $serviceCategory->mediaHero, $request, 'hero_image');

            $this->preserveTranslations($serviceCategory, $data);
            $serviceCategory->updateOrFail($data);

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

            report($exception);
            return redirect()->back()->with('error', __('errors.general'));
        }

        if ($request->ajax()) {
            $serviceCategory->refresh();
            $payload = $this->buildAutoTranslateUpdatePayload(
                $serviceCategory, $oldValues, 'service_category', self::CONTENT_FIELDS, true,
                'admin.services.category.edit', [$serviceCategory]
            );
            return response()->json([
                'toast' => ['type' => 'success', 'message' => __('admin.success_update_data')],
                'html'  => $this->getViewCategories(),
                'autoTranslate' => $payload['changedFields'] ? $payload : null,
            ]);
        }

        abort(404);
    }

    public function delete(Request $request, ServiceCategory $serviceCategory) {
        try {
            DB::beginTransaction();

            $serviceCategory->deleteOrFail();

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

            report($exception);
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
        $categories = ServiceCategory::all();

        return view('admin.services.categories.list', compact('categories'))->render();
    }
}
