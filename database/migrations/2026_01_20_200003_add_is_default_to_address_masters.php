<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add is_default to countries
        if (!Schema::hasColumn('countries', 'is_default')) {
            Schema::table('countries', function (Blueprint $table) {
                $table->boolean('is_default')->default(false)->after('is_active');
            });
            
            // Set India as default
            DB::table('countries')->where('country_name', 'India')->update(['is_default' => true]);
        }
        
        // Add is_default to states
        if (!Schema::hasColumn('states', 'is_default')) {
            Schema::table('states', function (Blueprint $table) {
                $table->boolean('is_default')->default(false)->after('is_active');
            });
            
            // Set Maharashtra as default
            DB::table('states')->where('state_name', 'Maharashtra')->update(['is_default' => true]);
        }
    }

    public function down(): void
    {
        Schema::table('countries', function (Blueprint $table) {
            if (Schema::hasColumn('countries', 'is_default')) {
                $table->dropColumn('is_default');
            }
        });
        
        Schema::table('states', function (Blueprint $table) {
            if (Schema::hasColumn('states', 'is_default')) {
                $table->dropColumn('is_default');
            }
        });
    }
};
