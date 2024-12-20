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
        Schema::create('user_reviews', function (Blueprint $table) {
            $table->bigIncrements('id'); // Auto-incrementing ID
            $table->unsignedBigInteger('user_id'); // Foreign key for user
            $table->unsignedBigInteger('listing_id')->nullable(); // Foreign key for listing
            $table->unsignedBigInteger('property_id')->nullable(); // Foreign key for listing
            $table->tinyInteger('rating'); // Rating (1-5, for example)
            $table->text('review')->nullable(); // Review text
            $table->timestamps(); // Created at and updated at timestamps
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_reviews');
    }
};
