<?php

namespace App\Http\Controllers\Public\Portfolio;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PortfolioPageController extends Controller
{
    public function index(Request $request): View|JsonResponse|RedirectResponse {
        if ($redirect = static_page_canonical_redirect('portfolio')) {
            return $redirect;
        }

        $page = Page::where('slug', 'portfolio')->first();
        $projects = Project::where('active', 1)
            ->latest()
            ->orderByDesc('sort')
            ->paginate(10);

        return view('public.pages.portfolio.page', compact('page', 'projects'));
    }
}
