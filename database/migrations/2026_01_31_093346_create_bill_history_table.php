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
        Schema::create('bill_history', function (Blueprint $table) {
            $table->id('history_id');
            $table->unsignedBigInteger('bill_id');
            $table->string('action')->default('updated'); // created, updated, deleted
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('adjustment', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->decimal('due_amount', 10, 2)->default(0);
            $table->string('payment_mode')->nullable();
            $table->string('payment_status')->nullable();
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->text('changes')->nullable(); // JSON of what changed
            $table->timestamps();

            $table->foreign('bill_id')->references('bill_id')->on('bills')->onDelete('cascade');
            $table->foreign('changed_by')->references('user_id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bill_history');
    }
};
