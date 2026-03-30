<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Page\UpdateRequest;
use App\Models\Page;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AboutPageController extends Controller
{
    public function index(): View {
        $page = Page::where('slug', 'about')->first();

        return view('admin.about.index', compact('page'));
    }

    public function update(UpdateRequest $request, Page $page): RedirectResponse {
        $data = $request->validated();

        try {
            DB::beginTransaction();

            if ($request->hasFile('hero_image')) {
                $page->clearMediaCollection('hero-image');
                $page->addMediaFromRequest('hero_image')
                    ->toMediaCollection('hero-image');
            }

            $this->preserveTranslations($page, $data);
            $page->updateOrFail($data);

            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            if(app()->environment('local')) dd($exception);
            return redirect()->back()->with('error', __('errors.general'));
        }

        return redirect()
            ->back()
            ->with('success', __('admin.success_update_data'));
    }
}
