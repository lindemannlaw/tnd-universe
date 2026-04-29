<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Page\UpdateRequest;
use App\Models\Page;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TermsOfUsePageController extends Controller
{
    public function index(): View {
        $page = Page::where('slug', 'terms-of-use')->first();

        return view('admin.terms-of-use.index', compact('page'));
    }

    public function update(UpdateRequest $request, Page $page): RedirectResponse {
        $data = $request->validated();

        try {
            DB::beginTransaction();

            $this->preserveTranslations($page, $data);
            $page->updateOrFail($data);

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
