<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
    {
        Schema::create('procurement_requests', function (Blueprint $table) {
            $table->id();

            // User who submits the request
            $table->foreignId('user_id')
                  ->constrained()
                  ->onDelete('cascade');

            // Requested asset info
            $table->string('asset_name');
            $table->integer('quantity');

            // Category (follow asset categories)
            $table->enum('category', ['electronics', 'furniture', 'stationary']);

            // Reason for procurement
            $table->text('reason');

            // Status of request
            $table->enum('request_status', [
                'pending',
                'approved',
                'rejected',
            ])->default('pending');

            // Optional rejection reason
            $table->text('rejection_reason')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('procurement_requests');
    }
};