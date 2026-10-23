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
        Schema::create('mock_insurer_policies', function (Blueprint $table) {
            $table->id();
            $table->string('provider_reference')->unique();
            $table->string('client_request_id');
            $table->string('agent_id');
            $table->string('policy_number')->unique();
            $table->string('status')->default('ISSUED'); // ISSUED, PENDING, FAILED
            $table->decimal('premium', 10, 2);
            $table->string('customer_name');
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['agent_id', 'client_request_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mock_insurer_policies');
    }
};
