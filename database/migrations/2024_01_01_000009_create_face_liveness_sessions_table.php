<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('face_liveness_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained('candidates')->cascadeOnDelete();
            $table->string('aws_session_id')->unique();
            $table->string('purpose');
            $table->string('status')->default('pending');
            $table->double('confidence')->nullable();
            $table->boolean('passed')->nullable();
            $table->longText('reference_image')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();

            $table->index('candidate_id');
            $table->index('aws_session_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('face_liveness_sessions');
    }
};
