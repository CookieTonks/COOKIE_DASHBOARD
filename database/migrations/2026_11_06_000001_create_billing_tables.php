<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('tax_id', 20)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index('name');
        });

        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('email');
            $table->string('phone', 30)->nullable();
            $table->string('role', 100)->nullable();
            $table->boolean('receives_reminders')->default(true);
            $table->timestamps();
        });

        Schema::create('payment_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('concept');
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('MXN');
            $table->string('cycle', 20);
            // Fecha ancla: todas las ocurrencias se calculan desde aquí para que
            // un día 31 regrese a 31 después de pasar por febrero.
            $table->date('anchor_date');
            $table->date('ends_on')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('payment_dues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_plan_id')->constrained()->cascadeOnDelete();
            $table->date('due_date');
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3);
            $table->string('status', 20)->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('reminder_sent_at')->nullable();
            $table->timestamps();

            $table->unique(['payment_plan_id', 'due_date']);
            $table->index(['status', 'due_date']);
        });

        // Guarda una copia de los datos: el historial sobrevive si se borra el cliente.
        Schema::create('reminder_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_due_id')->nullable()->constrained()->nullOnDelete();
            $table->string('client_name');
            $table->string('concept');
            $table->date('due_date');
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3);
            $table->json('recipients');
            $table->string('trigger', 10);
            $table->string('status', 10);
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index('created_at');
        });

        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();
            $table->string('name');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('holidays');
        Schema::dropIfExists('reminder_logs');
        Schema::dropIfExists('payment_dues');
        Schema::dropIfExists('payment_plans');
        Schema::dropIfExists('contacts');
        Schema::dropIfExists('clients');
    }
};
