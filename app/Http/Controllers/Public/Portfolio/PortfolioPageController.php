<?php

namespace App\Http\Controllers\Public\Portfolio;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Public\Concerns\EmitsSeoHeaders;
use App\Models\Page;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class PortfolioPageController extends Controller
{
    use EmitsSeoHeaders;

    public function index(Request $request): Response|View|JsonResponse|RedirectResponse
    {
        if ($redirect = static_page_canonical_redirect('portfolio')) {
            return $redirect;
        }

        $page = Page::where('slug', 'portfolio')->first();
        $projects = Project::where('active', 1)
            ->latest()
            ->orderByDesc('sort')
            ->paginate(10);

        return $this->seoResponse(
            'public.pages.portfolio.page',
            compact('page', 'projects'),
            $page?->updated_at,
        );
    }
}
