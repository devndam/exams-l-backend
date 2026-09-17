<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidate_exam_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained('candidates')->cascadeOnDelete();
            $table->foreignId('exam_type_id')->constrained('exam_types')->cascadeOnDelete();
            $table->string('status')->default('pending');
            $table->timestamp('assigned_at')->useCurrent();

            $table->unique(['candidate_id', 'exam_type_id']);
            $table->index('candidate_id');
            $table->index('exam_type_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_exam_assignments');
    }
};
