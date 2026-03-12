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
        Schema::table('attachments', function (Blueprint $table) {
            $table->foreignIdFor(\App\Models\User::class)->after('matter_id')->constrained()->onDelete('cascade');
            $table->string('path')->after('name')->nullable();
            $table->string('type')->after('name')->nullable();
            $table->renameColumn('mime', 'size');
            $table->renameColumn('extention', 'extension');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attachments', function (Blueprint $table) {
            $table->dropColumn('type');
            $table->dropColumn('path');
            $table->dropConstrainedForeignId('user_id');
            $table->renameColumn('size', 'mime');
            $table->renameColumn('extension', 'extentions');
            $table->dropTimestamps();
        });
    }
};
