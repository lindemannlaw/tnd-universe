<?php

use App\Models\Project;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Red Rock was seeded with the same placeholder coords as Salt Spring
        // (18.4524 / -64.4407 — the Virgin Gorda island centroid). Replace
        // with the actual place-marker resolved from the Google Maps share
        // link maps.app.goo.gl/o4cJupsZmJq2iHMA9
        // (final URL contained !3d18.435482!4d-64.430588).
        $project = Project::query()
            ->where('slug', 'like', 'red-rock%')
            ->first();

        if (! $project) {
            return;
        }

        // Only overwrite if still at the seed coords — preserves any manual
        // admin edit that may have happened between the seed and this fix.
        $stillAtSeed = abs((float) $project->lat - 18.4524) < 0.0001
            && abs((float) $project->lon - (-64.4407)) < 0.0001;

        if (! $stillAtSeed) {
            return;
        }

        $project->lat = 18.435482;
        $project->lon = -64.430588;
        $project->save();
    }

    public function down(): void
    {
        // Restoring the wrong seed coords would be actively harmful — no-op.
    }
};
