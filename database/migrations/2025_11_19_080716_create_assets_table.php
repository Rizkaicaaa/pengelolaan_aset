<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up()
{
    Schema::create('assets', function (Blueprint $table) {
        $table->id();

        $table->string('name');

        // Category enum (electronics, furniture, stationery)
        $table->enum('category', [
            'electronics',
            'furniture',
            'stationery'
        ]);

        // Represents how many total item units exist
        $table->integer('total_quantity')->default(0);

        $table->timestamps();
    });
}

public function down()
{
    Schema::dropIfExists('assets');
}
};