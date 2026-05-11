<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Public\Concerns\EmitsSeoHeaders;
use App\Models\Page;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;

class ImprintPageController extends Controller
{
    use EmitsSeoHeaders;

    public function index(): Response|View|RedirectResponse
    {
        if ($redirect = static_page_canonical_redirect('imprint')) {
            return $redirect;
        }

        $page = Page::where('slug', 'imprint')->first();

        return $this->seoResponse('public.pages.imprint', compact('page'), $page?->updated_at);
    }
}
