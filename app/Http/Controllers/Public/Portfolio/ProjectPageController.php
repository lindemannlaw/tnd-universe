<?php

namespace App\Http\Controllers\Public\Portfolio;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProjectPageController extends Controller
{
    public function index(Project $project): View|RedirectResponse {
        $currentPrefix = trim((string) request()->segment(1), '/');
        $expectedPrefix = static_page_path('portfolio');
        if ($currentPrefix !== $expectedPrefix) {
            return redirect(portfolio_project_url($project), 301);
        }

        return $this->renderProjectPage($project);
    }

    public function indexByAlias(string $portfolioAlias, Project $project): View|RedirectResponse
    {
        if ($portfolioAlias !== static_page_path('portfolio')) {
            abort(404);
        }

        return $this->renderProjectPage($project);
    }

    private function renderProjectPage(Project $project): View
    {
        $page = $project;
        $projects = Project::where('id', '!=', $project->id)
        ->where('active', 1)
        ->latest()
        ->orderByDesc('sort')
        ->take(4)
        ->get();

        return view('public.pages.portfolio.project', compact('page', 'project', 'projects'));
    }
}
