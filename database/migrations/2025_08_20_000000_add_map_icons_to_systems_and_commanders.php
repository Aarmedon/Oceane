<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('star_systems', function (Blueprint $table) {
            if (!Schema::hasColumn('star_systems', 'map_icon_path')) {
                $table->string('map_icon_path')->nullable()->after('star_type');
            }
        });

        Schema::table('commanders', function (Blueprint $table) {
            if (!Schema::hasColumn('commanders', 'fleet_icon_path')) {
                $table->string('fleet_icon_path')->nullable()->after('avatar_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('star_systems', function (Blueprint $table) {
            if (Schema::hasColumn('star_systems', 'map_icon_path')) {
                $table->dropColumn('map_icon_path');
            }
        });

        Schema::table('commanders', function (Blueprint $table) {
            if (Schema::hasColumn('commanders', 'fleet_icon_path')) {
                $table->dropColumn('fleet_icon_path');
            }
        });
    }
};
