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
        Schema::create('ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade'); // null for guest ratings
            $table->foreignId('conversation_id')->nullable()->constrained()->onDelete('cascade'); // specific conversation rating
            $table->foreignId('message_id')->nullable()->constrained()->onDelete('cascade'); // specific message rating
            $table->string('session_id')->nullable(); // for guest users
            $table->enum('type', ['app', 'conversation', 'message']); // what is being rated
            $table->integer('rating')->unsigned(); // 1-5 star rating
            $table->text('feedback')->nullable(); // optional text feedback
            $table->string('category')->nullable(); // feedback category (ui, response_quality, speed, etc.)
            $table->json('metadata')->nullable(); // additional data (browser, device, etc.)
            $table->ipAddress('ip_address')->nullable(); // for spam prevention
            $table->timestamp('submitted_at')->useCurrent();
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['user_id', 'type']);
            $table->index(['conversation_id', 'type']);
            $table->index(['message_id', 'type']);
            $table->index(['session_id', 'type']);
            $table->index(['rating', 'type']);
            $table->index('submitted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ratings');
    }
};
