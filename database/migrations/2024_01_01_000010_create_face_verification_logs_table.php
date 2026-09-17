<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('face_verification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained('candidates')->cascadeOnDelete();
            $table->foreignId('session_id')->nullable()->constrained('exam_sessions')->nullOnDelete();
            $table->double('similarity');
            $table->boolean('passed');
            $table->string('image_hash')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('candidate_id');
            $table->index('session_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('face_verification_logs');
    }
};
