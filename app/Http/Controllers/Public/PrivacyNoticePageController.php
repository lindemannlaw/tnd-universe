<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PrivacyNoticePageController extends Controller
{
    public function index(): View|RedirectResponse {
        if ($redirect = static_page_canonical_redirect('privacy-notice')) {
            return $redirect;
        }

        $page = Page::where('slug', 'privacy-notice')->first();

        return view('public.pages.privacy-notice', compact('page'));
    }
}
