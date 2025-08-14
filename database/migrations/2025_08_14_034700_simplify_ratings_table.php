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
        Schema::table('ratings', function (Blueprint $table) {
            // First drop foreign key constraints
            $table->dropForeign(['conversation_id']);
            $table->dropForeign(['message_id']);
        });
        
        Schema::table('ratings', function (Blueprint $table) {
            // Then drop the columns and other fields
            $table->dropColumn(['conversation_id', 'message_id', 'type', 'category', 'metadata']);
            
            // Keep only essential fields for simple app rating
            // user_id - for authenticated users (nullable for guests)
            // session_id - for guest users
            // rating - 1-5 stars
            // feedback - optional comment
            // ip_address - for spam prevention
            // submitted_at - when rating was given
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ratings', function (Blueprint $table) {
            // Add back the dropped columns
            $table->foreignId('conversation_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('message_id')->nullable()->constrained()->onDelete('cascade');
            $table->enum('type', ['app', 'conversation', 'message']);
            $table->string('category')->nullable();
            $table->json('metadata')->nullable();
        });
    }
};
