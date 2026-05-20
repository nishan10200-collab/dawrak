<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('barbers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('shop_name');
            $table->string('phone');
            $table->string('whatsapp')->nullable();
            $table->string('email')->unique();
            $table->string('password');
            $table->enum('subscription_status', ['active', 'inactive', 'trial'])->default('trial');
            $table->timestamp('subscription_expires_at')->nullable();
            $table->boolean('is_open')->default(false);
            $table->unsignedInteger('avg_service_minutes')->default(20);
            $table->string('address')->nullable();
            $table->string('logo')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('barbers');
    }
};
