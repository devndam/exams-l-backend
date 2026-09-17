<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('face_monitoring_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('exam_sessions')->cascadeOnDelete();
            $table->string('event_type');
            $table->double('similarity')->nullable();
            $table->integer('frame_number');
            $table->timestamp('created_at')->useCurrent();

            $table->index('session_id');
            $table->index('event_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('face_monitoring_events');
    }
};
