<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Leader\StoreRequest;
use App\Http\Requests\Admin\Leader\UpdateRequest;
use App\Models\Leader;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class LeaderController extends Controller
{
    public function index(): View {
        $leaders = Leader::orderByDesc('sort')->get();

        return view('admin.leaders.index', compact('leaders'));
    }

    public function create(Request $request): View|JsonResponse|string {
        if ($request->ajax()) {
            return view('admin.leaders.create')->render();
        }

        abort(404);
    }

    public function store(StoreRequest $request): View|JsonResponse|string {
        $data = $request->validated();

        try {
            DB::beginTransaction();

            $project = Leader::create($data);

            if ($request->hasFile('photo')) {
                $project->addMediaFromRequest('photo')
                    ->toMediaCollection($project->mediaPhoto);
            }

            if ($request->hasFile('resume')) {
                $project->addMediaFromRequest('resume')
                    ->toMediaCollection($project->mediaResume);
            }

            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            Log::error(__('errors.leader_store_failed'), ['exception' => $exception]);

            if ($request->ajax()) {
                return response()->json([
                    'message' => __('errors.leader_store_failed'),
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
                    'message' => __('admin.success_create_data'),
                ],
                'html' => $this->getViewLeaders(),
            ]);
        }

        abort(404);
    }

    public function edit(Request $request, Leader $leader): View|JsonResponse|string {
        if ($request->ajax()) {
            return view('admin.leaders.edit', compact('leader'))->render();
        }

        abort(404);
    }

    public function update(UpdateRequest $request, Leader $leader): View|JsonResponse|string {
        $data = $request->validated();

        try {
            DB::beginTransaction();

            $this->preserveTranslations($leader, $data);
            $leader->updateOrFail($data);

            if ($request->hasFile('photo')) {
                $leader->clearMediaCollection($leader->mediaPhoto);
                $leader->addMediaFromRequest('photo')
                    ->toMediaCollection($leader->mediaPhoto);
            }

            if ($request->hasFile('resume')) {
                $leader->clearMediaCollection($leader->mediaResume);
                $leader->addMediaFromRequest('resume')
                    ->toMediaCollection($leader->mediaResume);
            }

            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            Log::error(__('errors.leader_update_failed'), ['exception' => $exception]);

            if ($request->ajax()) {
                return response()->json([
                    'message' => __('errors.leader_update_failed'),
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
                    'message' => __('admin.success_update_data'),
                ],
                'html' => $this->getViewLeaders(),
            ]);
        }

        abort(404);
    }

    public function delete(Request $request, Leader $leader) {
        try {
            DB::beginTransaction();

            $leader->deleteOrFail();

            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            Log::error(__('errors.leader_delete_failed'), ['exception' => $exception]);

            if ($request->ajax()) {
                return response()->json([
                    'message' => __('errors.leader_delete_failed'),
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
                'html' => $this->getViewLeaders(),
            ]);
        }

        abort(404);
    }

    public function getViewLeaders(): View|string {
        $leaders = Leader::orderByDesc('sort')->get();

        return view('admin.leaders.list', compact('leaders'))->render();
    }
}
