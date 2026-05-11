<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Public\Concerns\EmitsSeoHeaders;
use App\Models\Leader;
use App\Models\Page;
use App\Models\SiteSection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;

class AboutPageController extends Controller
{
    use EmitsSeoHeaders;

    public function index(): Response|View|RedirectResponse
    {
        if ($redirect = static_page_canonical_redirect('about')) {
            return $redirect;
        }

        $page = Page::where('slug', 'about')->first();
        $leaders = Leader::where('active', 1)->orderByDesc('sort')->get();
        $whoWeAreSection = SiteSection::where('slug', 'who-we-are')->first();
        $contactUsSection = SiteSection::where('slug', 'contact-us')->first();

        return $this->seoResponse(
            'public.pages.about',
            compact('page', 'leaders', 'whoWeAreSection', 'contactUsSection'),
            $page?->updated_at,
        );
    }
}
