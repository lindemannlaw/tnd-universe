<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Public\Concerns\EmitsSeoHeaders;
use App\Models\Page;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;

class PrivacyNoticePageController extends Controller
{
    use EmitsSeoHeaders;

    public function index(): Response|View|RedirectResponse
    {
        if ($redirect = static_page_canonical_redirect('privacy-notice')) {
            return $redirect;
        }

        $page = Page::where('slug', 'privacy-notice')->first();

        return $this->seoResponse('public.pages.privacy-notice', compact('page'), $page?->updated_at);
    }
}
