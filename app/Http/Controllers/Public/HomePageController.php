<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Public\Concerns\EmitsSeoHeaders;
use App\Models\NewsArticle;
use App\Models\NewsCategory;
use App\Models\Page;
use App\Models\Project;
use App\Models\ServiceCategory;
use App\Models\SiteSection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;

class HomePageController extends Controller
{
    use EmitsSeoHeaders;

    public function index(): Response|View|RedirectResponse
    {
        if ($redirect = static_page_canonical_redirect('home')) {
            return $redirect;
        }

        $page = Page::where('slug', 'home')->first();
        $serviceCategories = ServiceCategory::whereHas('services', function ($query) {
            $query->where('active', 1);
        })->with(['services' => function ($query) {
            $query->where('active', 1)->orderByDesc('sort');
        }])->where('active', 1)->orderByDesc('sort')->get();
        $projects = Project::where('active', 1)->latest()->orderByDesc('sort')->limit(4)->get();
        $newsCategories = NewsCategory::where('active', '1')->get();
        $newsArticles = NewsArticle::where('active', '1')->latest()->orderByDesc('sort')->limit(6)->get();
        $whoWeAreSection = SiteSection::where('slug', 'who-we-are')->first();
        $contactUsSection = SiteSection::where('slug', 'contact-us')->first();

        return $this->seoResponse(
            'public.pages.home',
            compact('page', 'serviceCategories', 'projects', 'newsCategories', 'newsArticles', 'whoWeAreSection', 'contactUsSection'),
            $page?->updated_at,
        );
    }
}
