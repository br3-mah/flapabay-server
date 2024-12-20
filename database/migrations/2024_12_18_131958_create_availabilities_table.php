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

        Schema::create('availabilities', function (Blueprint $table) {
            $table->bigIncrements('id'); // Auto-increment primary key
            $table->unsignedBigInteger('property_id'); // Foreign key referencing properties
            $table->json('date_range')->nullable(); // JSON column for date range (start and end dates)
            $table->json('price_dates')->nullable(); // JSON column for price overrides on specific dates
            $table->timestamps(); // Created at and updated at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('availabilities');
    }
};
