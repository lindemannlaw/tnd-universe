<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $tables = [
        'services',
        'service_categories',
        'news_articles',
        'news_categories',
        'pages',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->decimal('lat', 10, 7)->nullable()->after('geo_text');
                $t->decimal('lon', 10, 7)->nullable()->after('lat');
                $t->string('geo_region', 8)->nullable()->after('lon');
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropColumn(['lat', 'lon', 'geo_region']);
            });
        }
    }
};
