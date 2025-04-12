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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id'); // Admin who performed the action
            $table->unsignedBigInteger('target_id')->nullable(); // The user affected
            $table->string('action'); // The action performed (approve, reject, etc.)
            $table->string('entity_type'); // Type of entity affected (User, etc.)
            $table->json('old_values')->nullable(); // Old values before change
            $table->json('new_values')->nullable(); // New values after change
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};