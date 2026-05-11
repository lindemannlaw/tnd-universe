<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $t) {
            $t->decimal('lat', 10, 7)->nullable()->after('geo_text');
            $t->decimal('lon', 10, 7)->nullable()->after('lat');
            $t->string('geo_region', 8)->nullable()->after('lon');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $t) {
            $t->dropColumn(['lat', 'lon', 'geo_region']);
        });
    }
};
