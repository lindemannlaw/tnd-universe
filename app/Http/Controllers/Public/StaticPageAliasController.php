<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\PageSlugRedirect;
use Illuminate\Http\Response;

class StaticPageAliasController extends Controller
{
    public function __invoke(string $pageAlias): Response|\Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse
    {
        $pageAlias = trim($pageAlias, '/');

        $page = Page::query()
            ->whereIn('slug', static_page_editable_slugs())
            ->where('public_slug', $pageAlias)
            ->first();

        if ($page) {
            return $this->renderPage($page);
        }

        $redirect = PageSlugRedirect::query()
            ->with('page')
            ->where('old_slug', $pageAlias)
            ->first();

        if ($redirect?->page) {
            return redirect(static_page_url($redirect->page->slug), 301);
        }

        abort(404);
    }

    private function renderPage(Page $page): Response|\Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse
    {
        return match ($page->slug) {
            'home' => app(HomePageController::class)->index(),
            'about' => app(AboutPageController::class)->index(),
            'portfolio' => app(\App\Http\Controllers\Public\Portfolio\PortfolioPageController::class)->index(request()),
            'contacts' => app(ContactsPageController::class)->index(),
            'imprint' => app(ImprintPageController::class)->index(),
            'privacy-notice' => app(PrivacyNoticePageController::class)->index(),
            'terms-of-use' => app(TermsOfUsePageController::class)->index(),
            default => abort(404),
        };
    }
}
