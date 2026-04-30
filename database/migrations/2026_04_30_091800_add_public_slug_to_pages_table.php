<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->string('public_slug')->nullable()->unique()->after('slug');
        });

        DB::table('pages')
            ->whereIn('slug', ['about', 'contacts', 'imprint', 'privacy-notice', 'terms-of-use'])
            ->update(['public_slug' => DB::raw('slug')]);

        DB::table('pages')
            ->where('slug', 'home')
            ->update(['public_slug' => null]);
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropUnique('pages_public_slug_unique');
            $table->dropColumn('public_slug');
        });
    }
};
