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
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('hotel_id')->index();
            $table->uuid('user_id')->index();
            $table->string('event_type')->index();
            $table->string('entity_type');
            $table->uuid('entity_id');
            $table->string('deep_link');
            $table->string('title');
            $table->text('message');
            $table->json('context_json')->nullable();
            $table->boolean('is_read')->default(false)->index();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('read_at')->nullable();

            $table->foreign('hotel_id')->references('id')->on('hotels')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();

            // Composite index for deduplication lookups
            $table->index(['hotel_id', 'user_id', 'event_type', 'entity_type', 'entity_id'], 'notifications_dedup_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
