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
        Schema::table('matter_requests', function (Blueprint $table) {
            $table->longText('comment')->change()->comment('Comment for the matter request');
            $table->longText('approved_comment')->nullable()->change()->comment('Comment for the matter request approval');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('matter_requests', function (Blueprint $table) {
            $table->string('comment')->change()->comment('Comment for the matter request');
            $table->string('approved_comment')->nullable()->change()->comment('Comment for the matter request approval');
        });
    }
};
