<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ship_designs', function (Blueprint $table) {
            if (!Schema::hasColumn('ship_designs', 'is_stackable')) {
                $table->boolean('is_stackable')->default(true)->after('is_public');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ship_designs', function (Blueprint $table) {
            if (Schema::hasColumn('ship_designs', 'is_stackable')) {
                $table->dropColumn('is_stackable');
            }
        });
    }
};
