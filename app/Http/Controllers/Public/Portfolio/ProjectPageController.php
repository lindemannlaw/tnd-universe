<?php

namespace App\Http\Controllers\Public\Portfolio;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectSlugRedirect;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProjectPageController extends Controller
{
    public function index(string $projectSlug): View|RedirectResponse {
        $project = $this->resolveProjectOrRedirect($projectSlug);
        if ($project instanceof RedirectResponse) {
            return $project;
        }

        $currentPrefix = trim((string) request()->segment(1), '/');
        $expectedPrefix = static_page_path('portfolio');
        if ($currentPrefix !== $expectedPrefix) {
            return redirect(portfolio_project_url($project), 301);
        }

        return $this->renderProjectPage($project);
    }

    public function indexByAlias(string $portfolioAlias, string $projectSlug): View|RedirectResponse
    {
        if ($portfolioAlias !== static_page_path('portfolio')) {
            abort(404);
        }

        $project = $this->resolveProjectOrRedirect($projectSlug);
        if ($project instanceof RedirectResponse) {
            return $project;
        }

        return $this->renderProjectPage($project);
    }

    private function resolveProjectOrRedirect(string $projectSlug): Project|RedirectResponse
    {
        $projectSlug = trim($projectSlug, '/');

        $project = Project::query()->where('slug', $projectSlug)->first();
        if ($project) {
            return $project;
        }

        $redirect = ProjectSlugRedirect::query()
            ->with('project')
            ->where('old_slug', $projectSlug)
            ->first();

        if ($redirect?->project) {
            return redirect(portfolio_project_url($redirect->project), 301);
        }

        abort(404);
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
