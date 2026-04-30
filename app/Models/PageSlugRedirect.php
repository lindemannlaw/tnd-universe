<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageSlugRedirect extends Model
{
    protected $fillable = [
        'page_id',
        'old_slug',
    ];

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }
}
