<?php

namespace App\Http\Controllers\Admin\SiteSections;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SiteSections\ContactUs\UpdateRequest;
use App\Models\SiteSection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ContactUsSectionController extends Controller
{
    public function index(): View {
        $section = SiteSection::where('slug', 'contact-us')->first();

        return view('admin.site-sections.contact-us', compact('section'));
    }

    public function update(UpdateRequest $request, SiteSection $siteSection): RedirectResponse {
        $data = $request->validated();

        try {
            DB::beginTransaction();

            if ($request->hasFile('bg_image')) {
                $siteSection->clearMediaCollection('bg-image');
                $siteSection->addMediaFromRequest('bg_image')
                    ->toMediaCollection('bg-image');
            }

            $this->preserveTranslations($siteSection, $data);
            $siteSection->updateOrFail($data);

            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            report($exception);
            return redirect()->back()->with('error', __('errors.general'));
        }

        return redirect()
            ->back()
            ->with('success', __('admin.success_update_data'));
    }
}
