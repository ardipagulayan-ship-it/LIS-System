<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('report_layouts', function (Blueprint $table) {
            if (!Schema::hasColumn('report_layouts', 'page_config')) {
                $table->json('page_config')->nullable()->after('name');
            }
            if (!Schema::hasColumn('report_layouts', 'sections_json')) {
                $table->json('sections_json')->nullable()->after('page_config');
            }
        });

        // Backfill page_config from existing page_size column and layout_json
        try {
            $layouts = DB::table('report_layouts')->get();
            foreach ($layouts as $layout) {
                $pageConfig = [
                    'page_size' => $layout->page_size ?? 'A4',
                    'orientation' => 'portrait',
                    'margins' => [
                        'top' => '20mm',
                        'bottom' => '20mm',
                        'left' => '15mm',
                        'right' => '15mm',
                    ],
                ];

                $sections = null;

                if (!empty($layout->layout_json)) {
                    // try decode layout_json and extract sections if present
                    $decoded = json_decode($layout->layout_json, true);
                    if (is_array($decoded) && isset($decoded['sections'])) {
                        $sections = $decoded['sections'];
                    }
                }

                DB::table('report_layouts')
                    ->where('id', $layout->id)
                    ->update([
                        'page_config' => json_encode($pageConfig),
                        'sections_json' => $sections ? json_encode($sections) : null,
                    ]);
            }
        } catch (\Exception $e) {
            // If DB not available at migration time, fail gracefully but continue
            \Log::warning('Backfill for report_layouts failed: ' . $e->getMessage());
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('report_layouts', function (Blueprint $table) {
            if (Schema::hasColumn('report_layouts', 'sections_json')) {
                $table->dropColumn('sections_json');
            }
            if (Schema::hasColumn('report_layouts', 'page_config')) {
                $table->dropColumn('page_config');
            }
        });
    }
};
