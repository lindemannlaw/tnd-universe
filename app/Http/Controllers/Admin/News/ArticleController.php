<?php

namespace App\Http\Controllers\Admin\News;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\News\Article\StoreRequest;
use App\Http\Requests\Admin\News\Article\UpdateRequest;
use App\Models\NewsArticle;
use App\Models\NewsCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class ArticleController extends Controller
{
    public function index(): View {
        $articles = NewsArticle::latest()->get();

        return view('admin.news.articles.index', compact('articles'));
    }

    public function create(Request $request): View|JsonResponse|string {
        $categories = NewsCategory::all();

        if ($request->ajax()) {
            return view('admin.news.articles.create', compact('categories'))->render();
        }

        abort(404);
    }

    private const CONTENT_FIELDS = ['title', 'short_description', 'description', 'link_top_text', 'link_bottom_text'];

    public function store(StoreRequest $request): View|JsonResponse|string {
        $data = $request->validated();

        try {
            DB::beginTransaction();

            $article = NewsArticle::create($data);
            $article->categories()->sync([$data['category_id']]);

            $article->description = $article->processImagesInDescription($article->getAttributes()['description']);
            $article->save();

            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            Log::error(__('errors.article_store_failed'), ['exception' => $exception]);

            if ($request->ajax()) {
                return response()->json([
                    'message' => __('errors.article_store_failed'),
                    'error' => $exception->getMessage(),
                ], 500);
            }

            report($exception);
            return redirect()->back()->with('error', __('errors.general'));
        }

        if ($request->ajax()) {
            return response()->json([
                'toast' => ['type' => 'success', 'message' => __('admin.success_create_data')],
                'html'  => $this->getViewArticles(),
                'autoTranslate' => $this->buildAutoTranslatePayload(
                    $article, 'news_article', self::CONTENT_FIELDS, true,
                    'admin.news.article.edit', [$article]
                ),
            ]);
        }

        abort(404);
    }

    public function edit(Request $request, NewsArticle $newsArticle): View|JsonResponse|string {
        $data = [
            'article' => $newsArticle,
            'categories' => NewsCategory::all(),
        ];

        if ($request->ajax()) {
            return view('admin.news.articles.edit', $data)->render();
        }

        abort(404);
        //return view('admin.news.articles.test-edit', $data)->render();
    }

    public function update(UpdateRequest $request, NewsArticle $newsArticle): View|JsonResponse|string {
        $data = $request->validated();
        $sourceLang = config('app.fallback_locale', 'en');
        $oldValues  = $this->captureSourceValues($newsArticle, array_merge(self::CONTENT_FIELDS, ['seo_title', 'seo_description', 'seo_keywords', 'geo_text']), $sourceLang);

        try {
            DB::beginTransaction();

            $this->preserveTranslations($newsArticle, $data);
            $newsArticle->updateOrFail($data);
            $newsArticle->categories()->sync([$data['category_id']]);

            $newsArticle->description = $newsArticle->processImagesInDescription($newsArticle->getAttributes()['description']);
            $newsArticle->save();

            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            Log::error(__('errors.article_update_failed'), ['exception' => $exception]);

            if ($request->ajax()) {
                return response()->json([
                    'message' => __('errors.article_update_failed'),
                    'error' => $exception->getMessage(),
                ], 500);
            }

            report($exception);
            return redirect()->back()->with('error', __('errors.general'));
        }

        if ($request->ajax()) {
            $newsArticle->refresh();
            $payload = $this->buildAutoTranslateUpdatePayload(
                $newsArticle, $oldValues, 'news_article', self::CONTENT_FIELDS, true,
                'admin.news.article.edit', [$newsArticle]
            );
            return response()->json([
                'toast' => ['type' => 'success', 'message' => __('admin.success_update_data')],
                'html'  => $this->getViewArticles(),
                'autoTranslate' => $payload['changedFields'] ? $payload : null,
            ]);
        }

        abort(404);
    }

    public function delete(Request $request, NewsArticle $newsArticle) {
        try {
            DB::beginTransaction();

            $newsArticle->deleteOrFail();

            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            Log::error(__('errors.article_delete_failed'), ['exception' => $exception]);

            if ($request->ajax()) {
                return response()->json([
                    'message' => __('errors.article_delete_failed'),
                    'error' => $exception->getMessage(),
                ], 500);
            }

            report($exception);
            return redirect()->back()->with('error', __('errors.general'));
        }

        if ($request->ajax()) {
            return response()->json([
                'toast' => [
                    'type' => 'success',
                    'message' => __('admin.success_delete_data'),
                ],
                'html' => $this->getViewArticles(),
            ]);
        }

        abort(404);
    }

    public function getViewArticles(): View|string {
        $articles = NewsArticle::all();

        return view('admin.news.articles.list', compact('articles'))->render();
    }

}
