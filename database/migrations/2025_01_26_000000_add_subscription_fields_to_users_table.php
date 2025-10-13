<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('subscription_plan')->default('free');
            $table->timestamp('subscription_expires_at')->nullable();
            $table->string('stripe_customer_id')->nullable();
            $table->string('stripe_subscription_id')->nullable();
            $table->json('subscription_features')->nullable();
            $table->integer('weekly_bookings_used')->default(0);
            $table->timestamp('weekly_reset_at')->nullable();
        });

    }
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'subscription_plan',
                'subscription_expires_at',
                'stripe_customer_id',
                'stripe_subscription_id',
                'subscription_features',
                'weekly_bookings_used',
                'weekly_reset_at'
            ]);
        });
    }
};
