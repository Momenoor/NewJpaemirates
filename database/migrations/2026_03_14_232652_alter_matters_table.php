<?php

use App\Enums\MatterDifficulty;
use App\Enums\MatterLevel;
use App\Models\Cash;
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
        Schema::table('matters', function (Blueprint $table) {
            $table->dropConstrainedForeignId('expert_id');
            $table->dropColumn('external_marketing_rate');
            $table->dropColumn('assign');
            $table->dropColumn('last_action_date');
            $table->dropColumn('status');
            $table->renameColumn('reported_date', 'initial_report_at');
            $table->renameColumn('submitted_date', 'final_report_at');
            $table->renameColumn('received_date', 'received_at');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('Account_id');
        });
        Schema::dropIfExists('cashes');
        Schema::dropIfExists('claims');
        Schema::dropIfExists('matter_expert');
        Schema::dropIfExists('experts');
        Schema::dropIfExists('levels');
        Schema::dropIfExists('marketers');
        Schema::dropIfExists('matter_marketing');
        Schema::dropIfExists('matter_statuses');
        Schema::dropIfExists('procedures');
        Schema::dropIfExists('request_attachments');
        Schema::dropIfExists('accounts');
        Matter::whereNotNull('final_report_at')
            ->whereNull('initial_report_at')
            ->update(['initial_report_at' => DB::raw('final_report_at')]);
        Matter::whereNull('level')
            ->update(['level' => MatterLevel::FIRST_INSTANCE]);
        Matter::whereNull('difficulty')
            ->update(['difficulty' => MatterDifficulty::EASY]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
