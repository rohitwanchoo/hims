<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add transfer_type column if not exists
        if (!Schema::hasColumn('bed_transfers', 'transfer_type')) {
            DB::statement("ALTER TABLE bed_transfers ADD COLUMN transfer_type ENUM('transfer', 'swap', 'move') NOT NULL DEFAULT 'transfer' AFTER ipd_id");
        }

        // Add swap_ipd_id column if not exists
        if (!Schema::hasColumn('bed_transfers', 'swap_ipd_id')) {
            Schema::table('bed_transfers', function (Blueprint $table) {
                $table->unsignedBigInteger('swap_ipd_id')->nullable()->after('to_ward_id')->comment('For swap type - the other patient being swapped');
                $table->foreign('swap_ipd_id')->references('ipd_id')->on('ipd_admissions')->onDelete('cascade');
            });
        }

        // Rename transfer_reason to reason if exists
        if (Schema::hasColumn('bed_transfers', 'transfer_reason')) {
            DB::statement("ALTER TABLE bed_transfers CHANGE transfer_reason reason TEXT NOT NULL");
        }

        // Make to_bed_id and to_ward_id nullable
        DB::statement("ALTER TABLE bed_transfers MODIFY to_bed_id BIGINT UNSIGNED NULL");
        DB::statement("ALTER TABLE bed_transfers MODIFY to_ward_id BIGINT UNSIGNED NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('bed_transfers', 'transfer_type')) {
            DB::statement("ALTER TABLE bed_transfers DROP COLUMN transfer_type");
        }

        if (Schema::hasColumn('bed_transfers', 'swap_ipd_id')) {
            DB::statement("ALTER TABLE bed_transfers DROP FOREIGN KEY bed_transfers_swap_ipd_id_foreign");
            DB::statement("ALTER TABLE bed_transfers DROP COLUMN swap_ipd_id");
        }

        if (Schema::hasColumn('bed_transfers', 'reason')) {
            DB::statement("ALTER TABLE bed_transfers CHANGE reason transfer_reason VARCHAR(255) NOT NULL");
        }
    }
};
