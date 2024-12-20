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
        Schema::create('bookings', function (Blueprint $table) {
            $table->bigIncrements('id'); // Primary key
            $table->string('booking_number')->nullable(); // Foreign key to properties table
            $table->unsignedBigInteger('property_id'); // Foreign key to properties table
            $table->unsignedBigInteger('user_id'); // Foreign key to users table
            $table->date('start_date')->nullable(); // Date the booking starts
            $table->date('end_date')->nullable(); // Date the booking ends
            $table->text('guest_details')->nullable(); // Details of guests (e.g., "1 adult, 1 child")
            $table->unsignedInteger('guest_count')->nullable(); // Total number of guests (calculated)
            $table->string('booking_status')->default('pending'); // Status of the booking (e.g., pending, confirmed, canceled)
            $table->text('cancellation_reason')->nullable(); // Status of the booking (e.g., pending, confirmed, canceled)
            $table->date('cancellation_date')->nullable(); // Status of the booking (e.g., pending, confirmed, canceled)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking');
    }
};
