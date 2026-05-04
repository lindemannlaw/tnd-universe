<?php

namespace App\Http\Controllers\Admin\Services;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Services\Service\StoreRequest;
use App\Http\Requests\Admin\Services\Service\UpdateRequest;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Services\MediaPickerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function __construct(private MediaPickerService $mediaPicker) {}

    public function index(): View {
        $services = Service::latest()->get();

        return view('admin.services.services.index', compact('services'));
    }

    public function create(Request $request): View|JsonResponse|string {
        $data = [
            'categories' => ServiceCategory::all(),
        ];

        if ($request->ajax()) {
            return view('admin.services.services.create', $data)->render();
        }

        abort(404);
    }

    private const CONTENT_FIELDS = ['title', 'inner_title', 'short_description', 'description'];

    public function store(StoreRequest $request): View|JsonResponse|string {
        $data = $request->validated();

        try {
            DB::beginTransaction();

            $service = Service::create($data);

            $this->mediaPicker->applyToCollection($service, $service->mediaHero, $request, 'hero_image');
            $this->mediaPicker->applyToCollection($service, $service->mediaInfo, $request, 'info_image');

            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            Log::error(__('errors.service_store_failed'), ['exception' => $exception]);

            if ($request->ajax()) {
                return response()->json([
                    'message' => __('errors.service_store_failed'),
                    'error' => $exception->getMessage(),
                ], 500);
            }

            report($exception);
            return redirect()->back()->with('error', __('errors.general'));
        }

        if ($request->ajax()) {
            return response()->json([
                'toast' => ['type' => 'success', 'message' => __('admin.success_create_data')],
                'html'  => $this->getViewServices(),
                'autoTranslate' => $this->buildAutoTranslatePayload(
                    $service, 'service', self::CONTENT_FIELDS, true,
                    'admin.services.service.edit', [$service]
                ),
            ]);
        }

        abort(404);
    }

    public function edit(Request $request, Service $service): View|JsonResponse|string {
        $data = [
            'service' => $service,
            'categories' => ServiceCategory::all(),
        ];

        if ($request->ajax()) {
            return view('admin.services.services.edit', $data)->render();
        }

        abort(404);
    }

    public function update(UpdateRequest $request, Service $service): View|JsonResponse|string {
        $data = $request->validated();
        $sourceLang = config('app.fallback_locale', 'en');
        $oldValues  = $this->captureSourceValues($service, array_merge(self::CONTENT_FIELDS, ['seo_title', 'seo_description', 'seo_keywords', 'geo_text']), $sourceLang);

        try {
            DB::beginTransaction();

            $this->mediaPicker->applyToCollection($service, $service->mediaHero, $request, 'hero_image');
            $this->mediaPicker->applyToCollection($service, $service->mediaInfo, $request, 'info_image');

            $this->preserveTranslations($service, $data);
            $service->updateOrFail($data);

            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            Log::error(__('errors.service_update_failed'), ['exception' => $exception]);

            if ($request->ajax()) {
                return response()->json([
                    'message' => __('errors.service_update_failed'),
                    'error' => $exception->getMessage(),
                ], 500);
            }

            report($exception);
            return redirect()->back()->with('error', __('errors.general'));
        }

        if ($request->ajax()) {
            $service->refresh();
            $payload = $this->buildAutoTranslateUpdatePayload(
                $service, $oldValues, 'service', self::CONTENT_FIELDS, true,
                'admin.services.service.edit', [$service]
            );
            return response()->json([
                'toast' => ['type' => 'success', 'message' => __('admin.success_update_data')],
                'html'  => $this->getViewServices(),
                'autoTranslate' => $payload['changedFields'] ? $payload : null,
            ]);
        }

        abort(404);
    }

    public function delete(Request $request, Service $service) {
        try {
            DB::beginTransaction();

            $service->deleteOrFail();

            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            Log::error(__('errors.service_delete_failed'), ['exception' => $exception]);

            if ($request->ajax()) {
                return response()->json([
                    'message' => __('errors.service_delete_failed'),
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
                'html' => $this->getViewServices(),
            ]);
        }

        abort(404);
    }

    public function getViewServices(): View|string {
        $services = Service::all();

        return view('admin.services.services.list', compact('services'))->render();
    }
}
