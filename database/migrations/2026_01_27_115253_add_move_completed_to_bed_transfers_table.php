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
        Schema::table('bed_transfers', function (Blueprint $table) {
            $table->boolean('is_move_completed')->default(false)->after('transfer_type')->comment('For move type - indicates if patient has returned or moved to new bed');
            $table->unsignedBigInteger('parent_move_transfer_id')->nullable()->after('swap_ipd_id')->comment('For move completion - references the original move transfer');
            $table->enum('move_completion_type', ['back_to_origin', 'new_bed'])->nullable()->after('parent_move_transfer_id')->comment('How the move was completed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bed_transfers', function (Blueprint $table) {
            $table->dropColumn(['is_move_completed', 'parent_move_transfer_id', 'move_completion_type']);
        });
    }
};
