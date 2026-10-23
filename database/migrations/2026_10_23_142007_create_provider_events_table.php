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
        Schema::create('provider_events', function (Blueprint $table) {
            $table->id();
            // Database-level uniqueness guarantees that duplicate callbacks with the same event_id
            // cannot be recorded or executed twice.
            $table->string('provider_event_id')->unique();
            $table->string('provider_reference')->index();
            $table->string('event_type');
            $table->json('payload');
            $table->string('status')->default('PROCESSED'); // PROCESSED, IGNORED_DUPLICATE, REJECTED_REGRESSION, FAILED
            $table->string('rejection_reason')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('provider_events');
    }
};
