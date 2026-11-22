<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qa_replies', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->foreignUlid('qa_thread_id')
                ->constrained('qa_threads');

            $table->foreignUlid('user_id')
                ->constrained('users');

            $table->text('body');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qa_replies');
    }
};
