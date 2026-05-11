<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Public\Concerns\EmitsSeoHeaders;
use App\Models\Page;
use App\Models\SiteSection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;

class ContactsPageController extends Controller
{
    use EmitsSeoHeaders;

    public function index(): Response|View|RedirectResponse
    {
        if ($redirect = static_page_canonical_redirect('contacts')) {
            return $redirect;
        }

        $page = Page::where('slug', 'contacts')->first();
        $contactUsSection = SiteSection::where('slug', 'contact-us')->first();

        return $this->seoResponse(
            'public.pages.contacts',
            compact('page', 'contactUsSection'),
            $page?->updated_at,
        );
    }
}
