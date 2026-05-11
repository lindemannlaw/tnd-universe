<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Public\Concerns\EmitsSeoHeaders;
use App\Models\Page;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;

class TermsOfUsePageController extends Controller
{
    use EmitsSeoHeaders;

    public function index(): Response|View|RedirectResponse
    {
        if ($redirect = static_page_canonical_redirect('terms-of-use')) {
            return $redirect;
        }

        $page = Page::where('slug', 'terms-of-use')->first();

        return $this->seoResponse('public.pages.terms-of-use', compact('page'), $page?->updated_at);
    }
}
