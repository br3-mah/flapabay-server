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
        Schema::create('payments', function (Blueprint $table) {
            $table->bigIncrements('id'); // Primary key
            $table->unsignedBigInteger('booking_id'); // Foreign key to booking
            $table->string('payment_method'); // Payment method (e.g., credit_card, paypal)
            $table->decimal('amount', 10, 2); // Payment amount with 2 decimal places
            $table->string('status')->default('pending'); // Status of the payment (e.g., pending, completed, failed)
            $table->timestamps(); // Created at & Updated at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment');
    }
};
