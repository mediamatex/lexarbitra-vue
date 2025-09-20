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
        Schema::table('users', function (Blueprint $table) {
            // Add additional fields for the lexarbitra user model
            $table->string('title')->nullable()->after('name');
            $table->string('law_firm')->nullable()->after('title');
            $table->string('phone')->nullable()->after('law_firm');
            $table->text('address')->nullable()->after('phone');
            $table->string('bar_number')->nullable()->after('address');
            $table->string('avatar_url')->nullable()->after('bar_number');
            $table->boolean('is_active')->default(true)->after('avatar_url');
            $table->boolean('is_super_admin')->default(false)->after('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'title',
                'law_firm',
                'phone',
                'address',
                'bar_number',
                'avatar_url',
                'is_active',
                'is_super_admin'
            ]);
        });
    }
};
