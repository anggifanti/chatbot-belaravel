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
            $table->boolean('is_premium')->default(false)->after('email_verified_at');
            $table->timestamp('subscription_expires_at')->nullable()->after('is_premium');
            $table->unsignedInteger('total_messages')->default(0)->after('subscription_expires_at');
            $table->unsignedInteger('monthly_message_count')->default(0)->after('total_messages');
            $table->timestamp('last_activity_at')->nullable()->after('monthly_message_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'is_premium',
                'subscription_expires_at',
                'total_messages',
                'monthly_message_count',
                'last_activity_at',
            ]);
        });
    }
};
