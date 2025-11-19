<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
      public function up(): void
    {
        Schema::create('loans', function (Blueprint $table) {
            $table->id();

            // User who makes the loan
            $table->foreignId('user_id')
                  ->constrained()
                  ->onDelete('cascade');

            // Asset item being borrowed
            $table->foreignId('asset_item_id')
                  ->constrained()
                  ->onDelete('cascade');

            // Purpose of borrowing
            $table->string('loan_purpose');

            // Dates
            $table->date('loan_date');
            $table->date('return_date')->nullable();

            // Loan status
            $table->enum('loan_status', [
                'pending',
                'approved',
                'rejected',
                'borrowed',
                'returned',
            ])->default('pending');

            // Rejection reason (nullable)
            $table->text('rejection_reason')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};