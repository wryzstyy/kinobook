<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('screenings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('movie_id')->constrained()->restrictOnDelete();
            $table->foreignId('hall_id')->constrained()->restrictOnDelete();
            $table->dateTime('starts_at')->index();
            $table->decimal('price', 8, 2);
            $table->timestamps();

            $table->index(['hall_id', 'starts_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('screenings');
    }
};