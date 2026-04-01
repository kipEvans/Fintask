<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 15, 2);
            $table->enum('type', ['income', 'expense']);
            $table->string('category', 50);
            $table->string('description', 500)->nullable();
            $table->date('date');
            $table->string('reference', 100)->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes for reports and aggregations
            $table->index(['user_id', 'type', 'date']);
            $table->index(['user_id', 'category', 'date']);
            $table->index(['user_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
