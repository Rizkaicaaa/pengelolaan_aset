<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
{
    // Ganti 'procurements' jadi 'procurement_requests'
    Schema::table('procurement_requests', function (Blueprint $table) {
        $table->string('image_reference')->nullable()->after('reason'); 
    });
}

public function down()
{
    // Ganti 'procurements' jadi 'procurement_requests'
    Schema::table('procurement_requests', function (Blueprint $table) {
        $table->dropColumn('image_reference');
    });
}
};
