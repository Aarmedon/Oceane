<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('directives')) {
            Schema::create('directives', function (Blueprint $table) {
                $table->unsignedInteger('id')->primary(); // Predefined IDs from config
                $table->string('code')->unique();
                $table->string('name');
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }

        // Seed directives from config mapping
        $map = config('oceane.directives', []);
        $now = now();
        $rows = [];
        foreach ($map as $code => $id) {
            $rows[] = [
                'id' => (int) $id,
                'code' => (string) $code,
                'name' => Str::title(str_replace('_', ' ', (string) $code)),
                'description' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        if (!empty($rows)) {
            // Ensure deterministic order by id
            usort($rows, fn($a, $b) => $a['id'] <=> $b['id']);
            // Upsert to be idempotent if migration partially ran before
            DB::table('directives')->upsert($rows, ['id'], ['code','name','description','updated_at']);
        }

        // Backfill fleets with default directive if null
        $defaultId = (int) config('oceane.directives.patrol', 0);
        DB::table('fleets')->whereNull('directive_id')->update(['directive_id' => $defaultId]);

        // Add FK and index on fleets.directive_id (portable). Column nullability left as-is for portability.
        Schema::table('fleets', function (Blueprint $table) {
            try { $table->index('directive_id'); } catch (\Throwable $e) {}
            try { $table->foreign('directive_id')->references('id')->on('directives')->restrictOnDelete(); } catch (\Throwable $e) {}
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop FK and index
        Schema::table('fleets', function (Blueprint $table) {
            try { $table->dropForeign(['directive_id']); } catch (\Throwable $e) {}
            try { $table->dropIndex(['fleets_directive_id_index']); } catch (\Throwable $e) {}
            try { $table->dropIndex(['directive_id']); } catch (\Throwable $e) {}
        });
        Schema::dropIfExists('directives');
    }
};
