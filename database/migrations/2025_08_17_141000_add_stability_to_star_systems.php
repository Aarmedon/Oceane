<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('star_systems') && !Schema::hasColumn('star_systems', 'stability')) {
            Schema::table('star_systems', function (Blueprint $table) {
                $table->integer('stability')->default(50)->after('policy_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('star_systems') && Schema::hasColumn('star_systems', 'stability')) {
            Schema::table('star_systems', function (Blueprint $table) {
                $table->dropColumn('stability');
            });
        }
    }
};
