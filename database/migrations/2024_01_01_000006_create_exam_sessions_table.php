<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained('candidates')->cascadeOnDelete();
            $table->foreignId('exam_type_id')->constrained('exam_types')->cascadeOnDelete();
            $table->json('shuffled_question_ids');
            $table->integer('current_question_index')->default(0);
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();
            $table->integer('score')->nullable();
            $table->integer('total_questions');
            $table->string('termination_reason')->nullable();
            $table->boolean('terminated_by_face')->default(false);
            $table->integer('face_warning_count')->default(0);
            $table->boolean('terminated_by_audio')->default(false);
            $table->integer('audio_warning_count')->default(0);

            $table->index('candidate_id');
            $table->index('exam_type_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_sessions');
    }
};
