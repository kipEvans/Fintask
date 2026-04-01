<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('cache')) {
            Schema::create('cache', function (Blueprint $table) {
                $table->string('key')->unique();
                $table->text('value');
                $table->unsignedBigInteger('expiration')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cache');
    }
};
