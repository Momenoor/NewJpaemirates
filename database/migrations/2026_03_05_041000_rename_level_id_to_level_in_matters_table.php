<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Drop the old level column (confirmed to exist by tinker)
        Schema::table('matters', function (Blueprint $table) {
            if (Schema::hasColumn('matters', 'level')) {
                $table->dropColumn('level');
            }
        });

        // 2. Rename level_id to level
        Schema::table('matters', function (Blueprint $table) {
            $table->renameColumn('level_id', 'level');
        });

        // 3. Update numeric values to enum strings
        // 1 = first_instance, 2 = appeal
        DB::table('matters')->where('level', '1')->update(['level' => \App\Enums\MatterLevel::FIRST_INSTANCE]);
        DB::table('matters')->where('level', '2')->update(['level' => \App\Enums\MatterLevel::APPEAL]);
        DB::table('matters')->where('level', '3')->update(['level' => \App\Enums\MatterLevel::CONGESTION]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('matters', function (Blueprint $table) {
            $table->renameColumn('level', 'level_id');
            $table->string('level')->nullable();
        });
    }
};
