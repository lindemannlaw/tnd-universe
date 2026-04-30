<?php

namespace App\Services;

use App\Models\NewsArticle;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Protects media files from being deleted by Spatie's owner-cascade when
 * a model that owns the media is deleted but other models still reference
 * the same media row via foreign key (link_top_media_id / link_bottom_media_id).
 *
 * Strategy: before delete, find each media row owned by the model. If any
 * other record references that media via FK, transfer ownership to one of
 * the referencing records — Spatie then leaves the file alone on disk
 * because the owner being deleted no longer owns it.
 */
class MediaTransferService
{
    /**
     * NewsArticle FK columns that may reference a media row owned by another article.
     */
    private const NEWS_ARTICLE_FK_COLUMNS = ['link_top_media_id', 'link_bottom_media_id'];

    public function protectFromOwnerCascade(Model $model): void
    {
        if ($model instanceof NewsArticle) {
            $this->protectNewsArticleMedia($model);
            return;
        }
    }

    private function protectNewsArticleMedia(NewsArticle $article): void
    {
        $ownedMedia = Media::query()
            ->where('model_type', NewsArticle::class)
            ->where('model_id', $article->id)
            ->get(['id']);

        if ($ownedMedia->isEmpty()) {
            return;
        }

        foreach ($ownedMedia as $media) {
            $newOwnerId = $this->findReferencingNewsArticleId($article->id, (int) $media->id);

            if ($newOwnerId === null) {
                continue;
            }

            DB::table('media')
                ->where('id', $media->id)
                ->update(['model_id' => $newOwnerId]);
        }
    }

    /**
     * Locate another news_article (excluding the one being deleted) whose
     * link_top_media_id or link_bottom_media_id points at $mediaId.
     */
    private function findReferencingNewsArticleId(int $excludeArticleId, int $mediaId): ?int
    {
        $row = DB::table('news_articles')
            ->whereNull('deleted_at')
            ->where('id', '!=', $excludeArticleId)
            ->where(function ($q) use ($mediaId) {
                foreach (self::NEWS_ARTICLE_FK_COLUMNS as $col) {
                    $q->orWhere($col, $mediaId);
                }
            })
            ->orderBy('id')
            ->first(['id']);

        return $row ? (int) $row->id : null;
    }
}
