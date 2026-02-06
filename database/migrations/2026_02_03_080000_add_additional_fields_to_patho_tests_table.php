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
        Schema::table('patho_tests', function (Blueprint $table) {
            $table->string('test_code')->nullable()->after('test_name');
            $table->string('short_name')->nullable()->after('test_code');
            $table->unsignedBigInteger('sample_type_id')->nullable()->after('container_id');
            $table->unsignedBigInteger('category_id')->nullable()->after('sample_type_id');
            $table->unsignedBigInteger('group_id')->nullable()->after('category_id');
            $table->unsignedBigInteger('analyzer_id')->nullable()->after('group_id');
            $table->unsignedBigInteger('external_lab_id')->nullable()->after('analyzer_id');
            $table->integer('tat_hours')->nullable()->after('external_lab_id');
            $table->string('specimen_volume')->nullable()->after('tat_hours');
            $table->integer('test_sequence')->nullable()->after('specimen_volume');
            $table->decimal('min_value', 10, 2)->nullable()->after('deviation_value');
            $table->decimal('max_value', 10, 2)->nullable()->after('min_value');
            $table->decimal('critical_low', 10, 2)->nullable()->after('max_value');
            $table->decimal('critical_high', 10, 2)->nullable()->after('critical_low');
            $table->text('test_instruction')->nullable()->after('critical_high');
            $table->boolean('is_outsourced')->default(false)->after('is_subtest_calculated');
            $table->boolean('is_culture')->default(false)->after('is_outsourced');
            $table->boolean('is_profile')->default(false)->after('is_culture');
            $table->boolean('is_formula')->default(false)->after('is_profile');
            $table->text('formula_expression')->nullable()->after('is_formula');
            $table->text('remarks')->nullable()->after('formula_expression');

            // Add foreign keys
            $table->foreign('sample_type_id')->references('sample_type_id')->on('patho_sample_types')->onDelete('set null');
            $table->foreign('category_id')->references('category_id')->on('patho_test_categories')->onDelete('set null');
            $table->foreign('group_id')->references('group_id')->on('patho_test_groups')->onDelete('set null');
            $table->foreign('analyzer_id')->references('analyzer_id')->on('analyzers')->onDelete('set null');
            $table->foreign('external_lab_id')->references('lab_id')->on('external_lab_centers')->onDelete('set null');

            // Add indexes
            $table->index('test_code');
            $table->index('sample_type_id');
            $table->index('category_id');
            $table->index('group_id');
            $table->index('analyzer_id');
            $table->index('external_lab_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patho_tests', function (Blueprint $table) {
            $table->dropForeign(['sample_type_id']);
            $table->dropForeign(['category_id']);
            $table->dropForeign(['group_id']);
            $table->dropForeign(['analyzer_id']);
            $table->dropForeign(['external_lab_id']);

            $table->dropIndex(['test_code']);
            $table->dropIndex(['sample_type_id']);
            $table->dropIndex(['category_id']);
            $table->dropIndex(['group_id']);
            $table->dropIndex(['analyzer_id']);
            $table->dropIndex(['external_lab_id']);

            $table->dropColumn([
                'test_code',
                'short_name',
                'sample_type_id',
                'category_id',
                'group_id',
                'analyzer_id',
                'external_lab_id',
                'tat_hours',
                'specimen_volume',
                'test_sequence',
                'min_value',
                'max_value',
                'critical_low',
                'critical_high',
                'test_instruction',
                'is_outsourced',
                'is_culture',
                'is_profile',
                'is_formula',
                'formula_expression',
                'remarks',
            ]);
        });
    }
};
