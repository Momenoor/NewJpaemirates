<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('matters', function (Blueprint $table) {
            $table->dropForeign(['expert_id']);
            $table->dropColumn('expert_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('matters', function (Blueprint $table) {
            $table->unsignedBigInteger('expert_id')->nullable();
            $table->foreign('expert_id')->references('id')->on('experts')->onDelete('cascade');
        });
    }
};
