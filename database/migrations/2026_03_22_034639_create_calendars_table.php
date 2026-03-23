<?php

use App\Models\Matter;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('calendars', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Matter::class)->nullable()->constrained()->cascadeOnDelete();
            $table->string('outlook_event_id')->nullable()->unique()
                ->comment('Microsoft Graph event ID for the shared Outlook calendar');
            $table->string('title')->nullable()->comment('Title of the event');
            $table->dateTime('start_at')->nullable()->comment('Start time of the event');
            $table->dateTime('end_at')->nullable()->comment('End time of the event');
            $table->string('location')->nullable()->comment('Location of the event');
            $table->text('description')->nullable()->comment('Description of the event');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calendars');
    }
};
