<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ImprintPageController extends Controller
{
    public function index(): View|RedirectResponse {
        if ($redirect = static_page_canonical_redirect('imprint')) {
            return $redirect;
        }

        $page = Page::where('slug', 'imprint')->first();

        return view('public.pages.imprint', compact('page'));
    }
}
