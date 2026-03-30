<?php

namespace App\Http\Controllers\Admin\SiteSections;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SiteSections\WhoWeAre\UpdateRequest;
use App\Models\SiteSection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class WhoWeAreSectionController extends Controller
{
    public function index(): View {
        $section = SiteSection::where('slug', 'who-we-are')->first();

        return view('admin.site-sections.who-we-are', compact('section'));
    }

    public function update(UpdateRequest $request, SiteSection $siteSection): RedirectResponse {
        $data = $request->validated();

        try {
            DB::beginTransaction();

            if ($request->hasFile('back_image')) {
                $siteSection->clearMediaCollection('back-image');
                $siteSection->addMediaFromRequest('back_image')
                    ->toMediaCollection('back-image');
            }

            if ($request->hasFile('front_image')) {
                $siteSection->clearMediaCollection('front-image');
                $siteSection->addMediaFromRequest('front_image')
                    ->toMediaCollection('front-image');
            }

            $this->preserveTranslations($siteSection, $data);
            $siteSection->updateOrFail($data);

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
