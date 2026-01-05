<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Stores
        Schema::create('stores', function (Blueprint $table) {
            $table->id('store_id');
            $table->unsignedBigInteger('hospital_id');
            $table->string('store_code', 20);
            $table->string('store_name', 100);
            $table->enum('store_type', ['main', 'sub', 'pharmacy', 'lab', 'radiology', 'ot', 'emergency'])->default('main');
            $table->string('location', 100)->nullable();
            $table->unsignedBigInteger('in_charge_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('hospital_id')->references('hospital_id')->on('hospitals')->cascadeOnDelete();
            $table->foreign('in_charge_id')->references('user_id')->on('users')->nullOnDelete();
            $table->unique(['hospital_id', 'store_code']);
        });

        // Item Categories
        Schema::create('item_categories', function (Blueprint $table) {
            $table->id('category_id');
            $table->unsignedBigInteger('hospital_id');
            $table->string('category_code', 20);
            $table->string('category_name', 100);
            $table->unsignedBigInteger('parent_category_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('hospital_id')->references('hospital_id')->on('hospitals')->cascadeOnDelete();
            $table->foreign('parent_category_id')->references('category_id')->on('item_categories')->nullOnDelete();
            $table->unique(['hospital_id', 'category_code']);
        });

        // Items
        Schema::create('items', function (Blueprint $table) {
            $table->id('item_id');
            $table->unsignedBigInteger('hospital_id');
            $table->string('item_code', 30);
            $table->string('item_name', 200);
            $table->string('generic_name', 200)->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->enum('item_type', ['consumable', 'equipment', 'implant', 'reagent', 'general'])->default('consumable');
            $table->string('unit_of_measure', 20);
            $table->integer('pack_size')->default(1);
            $table->string('hsn_code', 20)->nullable();
            $table->decimal('gst_percent', 5, 2)->default(0);
            $table->string('manufacturer', 100)->nullable();
            $table->integer('reorder_level')->default(10);
            $table->integer('reorder_quantity')->default(50);
            $table->integer('minimum_stock')->default(5);
            $table->integer('maximum_stock')->nullable();
            $table->boolean('is_batch_tracked')->default(true);
            $table->boolean('is_expiry_tracked')->default(true);
            $table->boolean('is_serialized')->default(false);
            $table->string('storage_conditions', 100)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('hospital_id')->references('hospital_id')->on('hospitals')->cascadeOnDelete();
            $table->foreign('category_id')->references('category_id')->on('item_categories')->nullOnDelete();
            $table->unique(['hospital_id', 'item_code']);
        });

        // Suppliers
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id('supplier_id');
            $table->unsignedBigInteger('hospital_id');
            $table->string('supplier_code', 20);
            $table->string('supplier_name', 150);
            $table->string('contact_person', 100)->nullable();
            $table->string('phone', 20);
            $table->string('email', 100)->nullable();
            $table->text('address')->nullable();
            $table->string('city', 50)->nullable();
            $table->string('state', 50)->nullable();
            $table->string('pincode', 10)->nullable();
            $table->string('gst_number', 20)->nullable();
            $table->string('pan_number', 20)->nullable();
            $table->string('payment_terms', 100)->nullable();
            $table->decimal('credit_limit', 12, 2)->default(0);
            $table->integer('credit_days')->default(30);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('hospital_id')->references('hospital_id')->on('hospitals')->cascadeOnDelete();
            $table->unique(['hospital_id', 'supplier_code']);
        });

        // Item Stock
        Schema::create('item_stock', function (Blueprint $table) {
            $table->id('stock_id');
            $table->unsignedBigInteger('hospital_id');
            $table->unsignedBigInteger('store_id');
            $table->unsignedBigInteger('item_id');
            $table->string('batch_number', 50)->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('serial_number', 100)->nullable();
            $table->decimal('quantity', 12, 3)->default(0);
            $table->decimal('reserved_quantity', 12, 3)->default(0);
            $table->decimal('purchase_rate', 10, 2)->default(0);
            $table->decimal('mrp', 10, 2)->default(0);
            $table->decimal('selling_rate', 10, 2)->default(0);
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->date('received_date');
            $table->timestamps();

            $table->foreign('hospital_id')->references('hospital_id')->on('hospitals')->cascadeOnDelete();
            $table->foreign('store_id')->references('store_id')->on('stores')->cascadeOnDelete();
            $table->foreign('item_id')->references('item_id')->on('items')->cascadeOnDelete();
            $table->foreign('supplier_id')->references('supplier_id')->on('suppliers')->nullOnDelete();
            $table->index(['store_id', 'item_id', 'batch_number']);
            $table->index(['hospital_id', 'expiry_date']);
        });

        // Indents
        Schema::create('indents', function (Blueprint $table) {
            $table->id('indent_id');
            $table->unsignedBigInteger('hospital_id');
            $table->string('indent_number', 20)->unique();
            $table->unsignedBigInteger('from_store_id');
            $table->unsignedBigInteger('to_store_id');
            $table->date('indent_date');
            $table->date('required_by_date')->nullable();
            $table->enum('priority', ['normal', 'urgent', 'emergency'])->default('normal');
            $table->text('remarks')->nullable();
            $table->enum('status', ['draft', 'submitted', 'approved', 'partially_fulfilled', 'fulfilled', 'rejected', 'cancelled'])->default('draft');
            $table->unsignedBigInteger('submitted_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->datetime('approved_at')->nullable();
            $table->timestamps();

            $table->foreign('hospital_id')->references('hospital_id')->on('hospitals')->cascadeOnDelete();
            $table->foreign('from_store_id')->references('store_id')->on('stores')->cascadeOnDelete();
            $table->foreign('to_store_id')->references('store_id')->on('stores')->cascadeOnDelete();
            $table->foreign('submitted_by')->references('user_id')->on('users')->nullOnDelete();
            $table->foreign('approved_by')->references('user_id')->on('users')->nullOnDelete();
        });

        // Indent Details
        Schema::create('indent_details', function (Blueprint $table) {
            $table->id('detail_id');
            $table->unsignedBigInteger('indent_id');
            $table->unsignedBigInteger('item_id');
            $table->decimal('requested_quantity', 10, 2);
            $table->decimal('approved_quantity', 10, 2)->nullable();
            $table->decimal('issued_quantity', 10, 2)->default(0);
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->foreign('indent_id')->references('indent_id')->on('indents')->cascadeOnDelete();
            $table->foreign('item_id')->references('item_id')->on('items')->cascadeOnDelete();
        });

        // Purchase Orders
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id('po_id');
            $table->unsignedBigInteger('hospital_id');
            $table->string('po_number', 20)->unique();
            $table->unsignedBigInteger('supplier_id');
            $table->unsignedBigInteger('store_id');
            $table->date('po_date');
            $table->date('expected_delivery_date')->nullable();
            $table->string('payment_terms', 100)->nullable();
            $table->text('delivery_address')->nullable();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->enum('status', ['draft', 'submitted', 'approved', 'partially_received', 'received', 'cancelled'])->default('draft');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->datetime('approved_at')->nullable();
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('hospital_id')->references('hospital_id')->on('hospitals')->cascadeOnDelete();
            $table->foreign('supplier_id')->references('supplier_id')->on('suppliers')->cascadeOnDelete();
            $table->foreign('store_id')->references('store_id')->on('stores')->cascadeOnDelete();
            $table->foreign('approved_by')->references('user_id')->on('users')->nullOnDelete();
            $table->foreign('created_by')->references('user_id')->on('users')->nullOnDelete();
        });

        // Purchase Order Details
        Schema::create('purchase_order_details', function (Blueprint $table) {
            $table->id('detail_id');
            $table->unsignedBigInteger('po_id');
            $table->unsignedBigInteger('item_id');
            $table->decimal('quantity', 10, 2);
            $table->decimal('rate', 10, 2);
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->decimal('tax_percent', 5, 2)->default(0);
            $table->decimal('amount', 12, 2);
            $table->decimal('received_quantity', 10, 2)->default(0);
            $table->timestamps();

            $table->foreign('po_id')->references('po_id')->on('purchase_orders')->cascadeOnDelete();
            $table->foreign('item_id')->references('item_id')->on('items')->cascadeOnDelete();
        });

        // Goods Receipt Notes
        Schema::create('goods_receipt_notes', function (Blueprint $table) {
            $table->id('grn_id');
            $table->unsignedBigInteger('hospital_id');
            $table->string('grn_number', 20)->unique();
            $table->unsignedBigInteger('po_id')->nullable();
            $table->unsignedBigInteger('supplier_id');
            $table->unsignedBigInteger('store_id');
            $table->date('grn_date');
            $table->string('invoice_number', 50)->nullable();
            $table->date('invoice_date')->nullable();
            $table->decimal('invoice_amount', 12, 2)->nullable();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->enum('status', ['draft', 'verified', 'approved', 'cancelled'])->default('draft');
            $table->unsignedBigInteger('verified_by')->nullable();
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('hospital_id')->references('hospital_id')->on('hospitals')->cascadeOnDelete();
            $table->foreign('po_id')->references('po_id')->on('purchase_orders')->nullOnDelete();
            $table->foreign('supplier_id')->references('supplier_id')->on('suppliers')->cascadeOnDelete();
            $table->foreign('store_id')->references('store_id')->on('stores')->cascadeOnDelete();
            $table->foreign('verified_by')->references('user_id')->on('users')->nullOnDelete();
            $table->foreign('created_by')->references('user_id')->on('users')->nullOnDelete();
        });

        // GRN Details
        Schema::create('grn_details', function (Blueprint $table) {
            $table->id('detail_id');
            $table->unsignedBigInteger('grn_id');
            $table->unsignedBigInteger('item_id');
            $table->string('batch_number', 50)->nullable();
            $table->date('expiry_date')->nullable();
            $table->decimal('quantity', 10, 2);
            $table->decimal('free_quantity', 10, 2)->default(0);
            $table->decimal('purchase_rate', 10, 2);
            $table->decimal('mrp', 10, 2);
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->decimal('tax_percent', 5, 2)->default(0);
            $table->decimal('amount', 12, 2);
            $table->timestamps();

            $table->foreign('grn_id')->references('grn_id')->on('goods_receipt_notes')->cascadeOnDelete();
            $table->foreign('item_id')->references('item_id')->on('items')->cascadeOnDelete();
        });

        // Stock Movements
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id('movement_id');
            $table->unsignedBigInteger('hospital_id');
            $table->unsignedBigInteger('store_id');
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('stock_id');
            $table->enum('movement_type', ['receipt', 'issue', 'transfer_in', 'transfer_out', 'adjustment_in', 'adjustment_out', 'return', 'damage', 'expired']);
            $table->string('reference_type', 50);
            $table->unsignedBigInteger('reference_id');
            $table->decimal('quantity', 12, 3);
            $table->decimal('balance_after', 12, 3);
            $table->decimal('rate', 10, 2);
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->datetime('movement_date');
            $table->timestamps();

            $table->foreign('hospital_id')->references('hospital_id')->on('hospitals')->cascadeOnDelete();
            $table->foreign('store_id')->references('store_id')->on('stores')->cascadeOnDelete();
            $table->foreign('item_id')->references('item_id')->on('items')->cascadeOnDelete();
            $table->foreign('stock_id')->references('stock_id')->on('item_stock')->cascadeOnDelete();
            $table->foreign('created_by')->references('user_id')->on('users')->nullOnDelete();
            $table->index(['store_id', 'item_id', 'movement_date']);
        });

        // Stock Transfers
        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id('transfer_id');
            $table->unsignedBigInteger('hospital_id');
            $table->string('transfer_number', 20)->unique();
            $table->unsignedBigInteger('from_store_id');
            $table->unsignedBigInteger('to_store_id');
            $table->date('transfer_date');
            $table->unsignedBigInteger('indent_id')->nullable();
            $table->enum('status', ['draft', 'transferred', 'received', 'cancelled'])->default('draft');
            $table->unsignedBigInteger('transferred_by')->nullable();
            $table->unsignedBigInteger('received_by')->nullable();
            $table->datetime('received_at')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->foreign('hospital_id')->references('hospital_id')->on('hospitals')->cascadeOnDelete();
            $table->foreign('from_store_id')->references('store_id')->on('stores')->cascadeOnDelete();
            $table->foreign('to_store_id')->references('store_id')->on('stores')->cascadeOnDelete();
            $table->foreign('indent_id')->references('indent_id')->on('indents')->nullOnDelete();
            $table->foreign('transferred_by')->references('user_id')->on('users')->nullOnDelete();
            $table->foreign('received_by')->references('user_id')->on('users')->nullOnDelete();
        });

        // Stock Transfer Details
        Schema::create('stock_transfer_details', function (Blueprint $table) {
            $table->id('detail_id');
            $table->unsignedBigInteger('transfer_id');
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('stock_id');
            $table->string('batch_number', 50)->nullable();
            $table->date('expiry_date')->nullable();
            $table->decimal('quantity', 10, 2);
            $table->decimal('rate', 10, 2);
            $table->timestamps();

            $table->foreign('transfer_id')->references('transfer_id')->on('stock_transfers')->cascadeOnDelete();
            $table->foreign('item_id')->references('item_id')->on('items')->cascadeOnDelete();
            $table->foreign('stock_id')->references('stock_id')->on('item_stock')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_transfer_details');
        Schema::dropIfExists('stock_transfers');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('grn_details');
        Schema::dropIfExists('goods_receipt_notes');
        Schema::dropIfExists('purchase_order_details');
        Schema::dropIfExists('purchase_orders');
        Schema::dropIfExists('indent_details');
        Schema::dropIfExists('indents');
        Schema::dropIfExists('item_stock');
        Schema::dropIfExists('suppliers');
        Schema::dropIfExists('items');
        Schema::dropIfExists('item_categories');
        Schema::dropIfExists('stores');
    }
};
