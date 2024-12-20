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
        Schema::create('invoices', function (Blueprint $table) {
            $table->bigIncrements('id'); // Auto-increment primary key
            $table->unsignedBigInteger('user_id'); // Foreign key referencing users
            $table->unsignedBigInteger('booking_id'); // Foreign key referencing booking
            $table->unsignedBigInteger('payment_id'); // Foreign key referencing payments
            $table->decimal('amount', 10, 2); // Amount for the invoice
            $table->string('status')->default('pending'); // Status of the invoice (e.g., 'pending', 'paid')
            $table->string('payment_method')->nullable(); // Payment method used (e.g., 'credit_card', 'paypal')
            $table->date('due_date')->nullable(); // Due date for payment
            $table->text('description')->nullable(); // Description of the invoice
            $table->string('currency')->default('USD'); // Currency for the invoice, default is USD
            $table->timestamps(); // Created at and updated at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
