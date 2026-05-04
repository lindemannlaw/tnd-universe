<?php

namespace App\Http\Controllers\Admin\SiteSections;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SiteSections\WhoWeAre\UpdateRequest;
use App\Models\SiteSection;
use App\Services\MediaPickerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class WhoWeAreSectionController extends Controller
{
    public function __construct(private MediaPickerService $mediaPicker) {}

    public function index(): View {
        $section = SiteSection::where('slug', 'who-we-are')->first();

        return view('admin.site-sections.who-we-are', compact('section'));
    }

    public function update(UpdateRequest $request, SiteSection $siteSection): RedirectResponse {
        $data = $request->validated();

        try {
            DB::beginTransaction();

            $this->mediaPicker->applyToCollection($siteSection, 'back-image', $request, 'back_image');
            $this->mediaPicker->applyToCollection($siteSection, 'front-image', $request, 'front_image');

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
