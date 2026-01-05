<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // FHIR Endpoints
        Schema::create('fhir_endpoints', function (Blueprint $table) {
            $table->id('endpoint_id');
            $table->unsignedBigInteger('hospital_id');
            $table->string('endpoint_name', 100);
            $table->enum('endpoint_type', ['sender', 'receiver', 'bidirectional'])->default('bidirectional');
            $table->string('base_url', 255);
            $table->enum('auth_type', ['none', 'basic', 'oauth2', 'api_key'])->default('none');
            $table->json('auth_credentials')->nullable();
            $table->string('fhir_version', 10)->default('R4');
            $table->json('supported_resources')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('hospital_id')->references('hospital_id')->on('hospitals')->cascadeOnDelete();
        });

        // HL7 Messages
        Schema::create('hl7_messages', function (Blueprint $table) {
            $table->id('message_id');
            $table->unsignedBigInteger('hospital_id');
            $table->string('message_type', 10);
            $table->string('message_event', 10)->nullable();
            $table->enum('direction', ['inbound', 'outbound']);
            $table->unsignedBigInteger('endpoint_id')->nullable();
            $table->text('raw_message');
            $table->json('parsed_message')->nullable();
            $table->string('reference_type', 50)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->enum('status', ['pending', 'processed', 'failed', 'acknowledged'])->default('pending');
            $table->text('ack_message')->nullable();
            $table->text('error_message')->nullable();
            $table->datetime('processed_at')->nullable();
            $table->timestamps();

            $table->foreign('hospital_id')->references('hospital_id')->on('hospitals')->cascadeOnDelete();
            $table->foreign('endpoint_id')->references('endpoint_id')->on('fhir_endpoints')->nullOnDelete();
            $table->index(['hospital_id', 'message_type', 'status']);
        });

        // FHIR Resources
        Schema::create('fhir_resources', function (Blueprint $table) {
            $table->id('resource_id');
            $table->unsignedBigInteger('hospital_id');
            $table->string('resource_type', 50);
            $table->string('resource_identifier', 100);
            $table->string('local_reference_type', 50);
            $table->unsignedBigInteger('local_reference_id');
            $table->json('resource_json');
            $table->integer('version')->default(1);
            $table->datetime('last_updated');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('hospital_id')->references('hospital_id')->on('hospitals')->cascadeOnDelete();
            $table->unique(['hospital_id', 'resource_type', 'resource_identifier'], 'fhir_resource_unique');
            $table->index(['local_reference_type', 'local_reference_id']);
        });

        // FHIR Subscriptions
        Schema::create('fhir_subscriptions', function (Blueprint $table) {
            $table->id('subscription_id');
            $table->unsignedBigInteger('hospital_id');
            $table->unsignedBigInteger('endpoint_id');
            $table->string('resource_type', 50);
            $table->string('criteria', 255)->nullable();
            $table->enum('channel_type', ['rest_hook', 'websocket', 'email'])->default('rest_hook');
            $table->string('channel_endpoint', 255);
            $table->json('headers')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('hospital_id')->references('hospital_id')->on('hospitals')->cascadeOnDelete();
            $table->foreign('endpoint_id')->references('endpoint_id')->on('fhir_endpoints')->cascadeOnDelete();
        });

        // Data Mappings
        Schema::create('data_mappings', function (Blueprint $table) {
            $table->id('mapping_id');
            $table->unsignedBigInteger('hospital_id');
            $table->enum('mapping_type', ['hl7_to_local', 'local_to_fhir', 'fhir_to_local', 'local_to_hl7']);
            $table->string('source_field', 100);
            $table->string('target_field', 100);
            $table->string('source_table', 50)->nullable();
            $table->string('target_table', 50)->nullable();
            $table->text('transformation')->nullable();
            $table->text('default_value')->nullable();
            $table->boolean('is_required')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('hospital_id')->references('hospital_id')->on('hospitals')->cascadeOnDelete();
            $table->index(['hospital_id', 'mapping_type']);
        });

        // ICD Codes Master (for coding)
        Schema::create('icd_codes', function (Blueprint $table) {
            $table->id('icd_id');
            $table->string('icd_code', 20);
            $table->string('short_description', 255);
            $table->text('long_description')->nullable();
            $table->enum('icd_version', ['ICD-10', 'ICD-11'])->default('ICD-10');
            $table->string('category', 100)->nullable();
            $table->string('chapter', 100)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['icd_code', 'icd_version']);
            $table->index('short_description');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('icd_codes');
        Schema::dropIfExists('data_mappings');
        Schema::dropIfExists('fhir_subscriptions');
        Schema::dropIfExists('fhir_resources');
        Schema::dropIfExists('hl7_messages');
        Schema::dropIfExists('fhir_endpoints');
    }
};
