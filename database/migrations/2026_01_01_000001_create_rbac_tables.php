<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Roles table
        Schema::create('roles', function (Blueprint $table) {
            $table->id('role_id');
            $table->unsignedBigInteger('hospital_id')->nullable();
            $table->string('role_code', 50);
            $table->string('role_name', 100);
            $table->text('description')->nullable();
            $table->boolean('is_system_role')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('hospital_id')->references('hospital_id')->on('hospitals')->nullOnDelete();
            $table->unique(['hospital_id', 'role_code']);
        });

        // Permissions table
        Schema::create('permissions', function (Blueprint $table) {
            $table->id('permission_id');
            $table->string('permission_code', 100)->unique();
            $table->string('permission_name', 150);
            $table->string('module', 50);
            $table->string('action', 50);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index('module');
        });

        // Role-Permission pivot table
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('permission_id');
            $table->timestamp('created_at')->useCurrent();

            $table->primary(['role_id', 'permission_id']);
            $table->foreign('role_id')->references('role_id')->on('roles')->cascadeOnDelete();
            $table->foreign('permission_id')->references('permission_id')->on('permissions')->cascadeOnDelete();
        });

        // User-Role pivot table
        Schema::create('user_roles', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('assigned_by')->nullable();
            $table->timestamp('assigned_at')->useCurrent();

            $table->primary(['user_id', 'role_id']);
            $table->foreign('user_id')->references('user_id')->on('users')->cascadeOnDelete();
            $table->foreign('role_id')->references('role_id')->on('roles')->cascadeOnDelete();
            $table->foreign('assigned_by')->references('user_id')->on('users')->nullOnDelete();
        });

        // User-Permission override table
        Schema::create('user_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('permission_id');
            $table->boolean('granted')->default(true);
            $table->unsignedBigInteger('assigned_by')->nullable();
            $table->timestamp('assigned_at')->useCurrent();

            $table->primary(['user_id', 'permission_id']);
            $table->foreign('user_id')->references('user_id')->on('users')->cascadeOnDelete();
            $table->foreign('permission_id')->references('permission_id')->on('permissions')->cascadeOnDelete();
            $table->foreign('assigned_by')->references('user_id')->on('users')->nullOnDelete();
        });

        // Add role_id to users table
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id')->nullable()->after('is_super_admin');
            $table->foreign('role_id')->references('role_id')->on('roles')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropColumn('role_id');
        });

        Schema::dropIfExists('user_permissions');
        Schema::dropIfExists('user_roles');
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};
