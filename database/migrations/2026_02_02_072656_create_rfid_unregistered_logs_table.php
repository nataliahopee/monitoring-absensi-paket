<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('rfid_unregistered_logs', function (Blueprint $table) {
            $table->id();
            $table->string('rfid_uid')->index();
            $table->timestamp('detected_at');
            $table->enum('status', ['pending','registered','ignored'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rfid_unregistered_logs');
    }
};
