<?php

namespace App\Http\Controllers\Public\News;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Public\Concerns\EmitsSeoHeaders;
use App\Models\NewsArticle;
use Illuminate\Http\Response;
use Illuminate\View\View;

class ArticlePageController extends Controller
{
    use EmitsSeoHeaders;

    public function index(NewsArticle $newsArticle): Response|View
    {

        $relatedArticles = NewsArticle::query()
            ->whereKeyNot($newsArticle->id)
            ->where('active', true)
            ->latest()
            ->limit(4)
            ->get();

        return $this->seoResponse(
            'public.pages.news.article',
            [
                'article' => $newsArticle,
                'relatedArticles' => $relatedArticles,
            ],
            $newsArticle->updated_at,
        );
    }
}
