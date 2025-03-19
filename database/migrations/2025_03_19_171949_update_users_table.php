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
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            $table->boolean('active_subscriptions')->default(false);
            $table->integer('connected_devices_count')->default(0);
            $table->string('referral_code')->nullable()->unique();
            $table->timestamp('trial_ends_at')->nullable();
            $table->string('google_id')->nullable()->unique();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'status',
                'active_subscriptions',
                'connected_devices_count',
                'referral_code',
                'trial_ends_at',
                'google_id'
            ]);
        });
    }
};
