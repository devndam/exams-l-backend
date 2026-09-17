<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('face_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->unique()->constrained('candidates')->cascadeOnDelete();
            $table->binary('embedding_encrypted')->nullable();
            $table->string('embedding_iv')->nullable();
            $table->string('embedding_auth_tag')->nullable();
            $table->integer('embedding_dimension')->nullable();
            $table->longText('capture_image')->nullable();
            $table->string('status')->default('pending');
            $table->timestamp('reviewed_at')->nullable();
            $table->string('review_note')->nullable();
            $table->timestamp('enrolled_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('face_enrollments');
    }
};
