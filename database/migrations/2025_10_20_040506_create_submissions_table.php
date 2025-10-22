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
        Schema::create('form_submissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->unsignedBigInteger('form_id')->nullable();
            $table->foreign('form_id')->references('id')->on('forms')->onDelete('cascade');
            $table->json('form_data')->nullable();
            $table->string('document_path')->nullable()->comment('User uploaded file path');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->unsignedBigInteger('processed_by')->nullable()->comment('Admin who last updated the status');
            $table->text('admin_notes')->nullable();
            $table->unsignedBigInteger('payment_id')->nullable()->comment('Links to the final successful payment record');
            $table->string('transaction_id')->nullable()->comment('Payment Gateway transaction ID');
            $table->enum('payment_status', ['initiated', 'success', 'failed'])->default('initiated');
            $table->timestamps();
        });

    }
    public function down(): void
    {
        Schema::dropIfExists('submissions');
    }
};
