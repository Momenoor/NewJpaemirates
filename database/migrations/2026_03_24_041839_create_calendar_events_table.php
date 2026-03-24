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
        Schema::create('calendar_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('matter_id')->nullable()->constrained('matters')->nullOnDelete();
            $table->string('outlook_event_id')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->datetime('start_datetime');
            $table->datetime('end_datetime')->nullable();
            $table->string('location')->nullable();
            $table->enum('type', ['single', 'bulk'])->default('single');
            $table->boolean('update_next_session_date')->default(true);
            $table->boolean('synced_to_outlook')->default(false);
            $table->boolean('imported_from_outlook')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calendar_events');
    }
};
