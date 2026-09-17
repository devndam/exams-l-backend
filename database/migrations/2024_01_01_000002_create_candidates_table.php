<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidates', function (Blueprint $table) {
            $table->id();
            $table->string('candidate_id')->unique();
            $table->string('full_name');
            $table->string('email')->unique();
            $table->string('certificate_name')->nullable();
            $table->string('certificate_email')->nullable();
            $table->string('certificate_phone')->nullable();
            $table->string('photo_url')->nullable();
            $table->string('session_token')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidates');
    }
};
