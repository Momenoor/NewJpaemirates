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
            $table->json('role')->nullable()->after('type');
            $table->integer('old_id')->nullable()->after('role');
            // role_search will extract "expert" from {"role": "expert"}
            $table->string('role_search')
                ->virtualAs('role->"$.role"')
                ->after('old_id')
                ->index('parties_role_search_idx');

            // type_search will extract "main" from {"type": "main"}
            $table->string('type_search')
                ->virtualAs('role->"$.type"')
                ->after('role_search')
                ->index('parties_type_search_idx');
        });
        Schema::table('matter_party', function (Blueprint $table) {
            $table->string('role')->nullable()->after('parent_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parties', function (Blueprint $table) {
            $table->dropIndex('parties_role_search_idx');
            $table->dropIndex('parties_type_search_idx');
            $table->dropColumn('role');
            $table->dropColumn('old_id');
        });
        Schema::table('matter_party', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};
