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
        Schema::table('matters', function (Blueprint $table) {
            $table->renameColumn('received_at', 'distributed_at');
            $table->date('received_at')->nullable()->after('next_session_date')->comment('تاريخ استلام القضية من المحكمة');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('matters', function (Blueprint $table) {
            $table->dropColumn('received_at');
            $table->renameColumn('distributed_at', 'received_at');
        });
    }
};
