<?php

use Database\Seeders\PortfolioBviVillasSeoSeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        (new PortfolioBviVillasSeoSeeder())->run();
    }

    public function down(): void
    {
        // Data migration: rollback would clobber user-edited values, so leave as no-op.
    }
};
