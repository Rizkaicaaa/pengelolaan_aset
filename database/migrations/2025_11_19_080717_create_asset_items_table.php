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
        Schema::create('asset_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('asset_id')
          ->constrained('assets')
          ->onDelete('cascade'); 
            $table->string('asset_code')->unique(); // kode aset unik

            $table->enum('condition', ['good', 'damaged'])->default('good'); // kondisi
            $table->enum('status', ['available', 'borrowed', 'unavailable'])->default('available'); // status

            $table->date('procurement_date'); // tanggal pengadaan
            $table->text('description')->nullable(); // keterangan

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_items');
    }
};