<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('model_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('media_id')->constrained('media')->cascadeOnDelete();
            $table->morphs('model');
            $table->string('collection_name');
            $table->unsignedInteger('order_column')->default(0);
            $table->timestamps();

            $table->unique(
                ['media_id', 'model_type', 'model_id', 'collection_name'],
                'model_media_unique'
            );
            $table->index(
                ['model_type', 'model_id', 'collection_name'],
                'model_media_lookup'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('model_media');
    }
};
