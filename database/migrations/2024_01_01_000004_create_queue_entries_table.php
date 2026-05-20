<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('queue_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('barber_id')->constrained()->cascadeOnDelete();
            $table->string('customer_name');
            $table->string('customer_whatsapp');
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('position');
            $table->enum('status', [
                'waiting',
                'notified',
                'confirmed',
                'serving',
                'done',
                'cancelled',
                'no_show',
            ])->default('waiting');
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('notified_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('served_at')->nullable();
            $table->date('date');
            $table->timestamps();

            // فهرس للبحث السريع
            $table->index(['barber_id', 'date', 'status']);
            $table->index(['barber_id', 'date', 'customer_whatsapp']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('queue_entries');
    }
};
