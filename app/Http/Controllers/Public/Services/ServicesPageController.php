<?php

namespace App\Http\Controllers\Public\Services;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Public\Concerns\EmitsSeoHeaders;
use App\Models\Page;
use App\Models\ServiceCategory;
use App\Models\SiteSection;
use Illuminate\Http\Response;
use Illuminate\View\View;

class ServicesPageController extends Controller
{
    use EmitsSeoHeaders;

    public function index(): Response|View
    {
        $page = Page::where('slug', 'services')->first();
        $serviceCategories = ServiceCategory::whereHas('services', function ($query) {
            $query->where('active', 1);
        })->with(['services' => function ($query) {
            $query->where('active', 1)->orderByDesc('sort');
        }])->where('active', 1)->orderByDesc('sort')->get();
        $contactUsSection = SiteSection::where('slug', 'contact-us')->first();

        return $this->seoResponse(
            'public.pages.services.page',
            compact('page', 'serviceCategories', 'contactUsSection'),
            $page?->updated_at,
        );
    }
}
