<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('face_monitoring_events', function (Blueprint $table) {
            $table->longText('image')->nullable()->after('similarity');
            $table->json('embedding')->nullable()->after('image');
        });
    }

    public function down(): void
    {
        Schema::table('face_monitoring_events', function (Blueprint $table) {
            $table->dropColumn(['image', 'embedding']);
        });
    }
};
