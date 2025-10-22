<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('submission_id')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('INR');
            $table->string('transaction_id')->unique()->nullable()->comment('Internal transaction ID');
            $table->string('order_id')->unique()->nullable()->comment('Order ID from Payment Provider');
            $table->string('provider_payment_id')->unique()->nullable()->comment('Payment ID from Payment Provider');
            $table->string('payment_method')->nullable();
            $table->enum('status', ['pending', 'paid', 'failed'])->default('pending');
            $table->string('receipt_path')->nullable()->comment('Path to the generated PDF receipt');
            $table->json('gateway_response')->nullable()->comment('Full JSON response from provider');

            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
