<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('parties', function (Blueprint $table) {
            if (Schema::hasColumn('parties', 'role_search')) {
                $table->dropColumn('role_search');
            }
            if (Schema::hasColumn('parties', 'type_search')) {
                $table->dropColumn('type_search');
            }
            $table->dropColumn('type');
            $table->boolean('black_list')->default(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parties', function (Blueprint $table) {
            $table->text('role_search')->nullable();
            $table->text('type_search')->nullable();
        });
    }
};
