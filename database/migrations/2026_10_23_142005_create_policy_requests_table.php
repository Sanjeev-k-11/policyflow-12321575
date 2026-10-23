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
        Schema::create('policy_requests', function (Blueprint $table) {
            $table->id();
            
            // Intake identification
            $table->string('client_request_id');
            $table->string('agent_id');
            $table->string('customer_name');
            $table->date('date_of_birth');
            
            // Trip parameters
            $table->string('destination'); // DOMESTIC, INTERNATIONAL
            $table->date('trip_start');
            $table->date('trip_end');
            $table->string('plan_code');   // BASIC, PLUS, PREMIUM
            $table->unsignedInteger('sum_insured'); // 100000, 300000, 500000
            
            // Pricing factors breakdown & immutable snapshot
            $table->unsignedInteger('travel_days');
            $table->unsignedInteger('age_at_trip_start');
            $table->decimal('base_daily_rate', 10, 2);
            $table->decimal('age_multiplier', 5, 2);
            $table->decimal('destination_multiplier', 5, 2);
            $table->decimal('coverage_multiplier', 5, 2);
            $table->decimal('calculated_premium', 10, 2);
            
            // Lifecycle state
            // Supported states: RECEIVED, VALIDATED, SUBMITTED, PENDING_CONFIRMATION, ISSUED, FAILED, NEEDS_RECONCILIATION
            $table->string('status')->default('RECEIVED');
            $table->string('policy_number')->nullable()->unique();
            $table->string('provider_reference')->nullable()->index();
            $table->json('last_response')->nullable();
            $table->unsignedInteger('attempt_count')->default(0);
            $table->text('failure_reason')->nullable();
            
            // Development/testing scenario override for mock insurer
            $table->string('scenario')->nullable();
            
            $table->timestamp('issued_at')->nullable();
            $table->timestamps();
            
            // Concurrency & Idempotency: Unique compound key enforces that repeated submissions
            // with the same agent_id + client_request_id cannot duplicate records at the database level.
            $table->unique(['agent_id', 'client_request_id'], 'unique_agent_client_request');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('policy_requests');
    }
};
