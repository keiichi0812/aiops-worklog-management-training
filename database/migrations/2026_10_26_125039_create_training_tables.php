<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('user');
        });
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('name', 150);
            $table->string('contact_name', 150)->nullable();
            $table->string('email')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('name', 150);
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->string('division', 20);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
        Schema::create('work_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->date('worked_on');
            $table->unsignedInteger('half_hours');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'worked_on']);
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('work_logs');
        Schema::dropIfExists('projects');
        Schema::dropIfExists('customers');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role']);
        });
    }
};
