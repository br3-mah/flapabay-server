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
        Schema::create('properties', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('title', 100)->nullable();
            $table->text('description')->nullable(); // Updated to text for longer descriptions
            $table->string('location', 150)->nullable();

            $table->string('address')->nullable();
            $table->string('county')->nullable();
            $table->decimal('latitude', 10, 7)->nullable(); // Store latitude with decimal precision
            $table->decimal('longitude', 10, 7)->nullable(); // Store longitude with decimal precision

            $table->time('check_in_hour')->nullable(); // Changed to 'time' type for better validation
            $table->time('check_out_hour')->nullable(); // Changed to 'time' type for better validation

            $table->integer('num_of_guests')->nullable();
            $table->integer('num_of_children')->nullable();
            $table->integer('maximum_guests')->nullable();
            $table->boolean('allow_extra_guests')->default(false);
            $table->string('neighborhood_area', 100)->nullable();
            $table->string('country', 100)->nullable();

            $table->boolean('show_contact_form_instead_of_booking')->default(false);
            $table->boolean('allow_instant_booking')->default(false);
            $table->string('currency', 3)->default('USD'); // Changed to string to store currency like USD, EUR, etc.

            $table->json('price_range')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->decimal('price_per_night', 10, 2)->nullable();
            $table->decimal('additional_guest_price', 10, 2)->nullable();
            $table->decimal('children_price', 10, 2)->nullable(); // Fixed extra space in the column name

            $table->json('amenities')->nullable();
            $table->json('house_rules')->nullable();

            $table->integer('page')->nullable();
            $table->decimal('rating', 3, 2)->nullable();
            $table->boolean('favorite')->default(false);
            $table->json('images')->nullable();
            $table->json('video_link')->nullable();
            $table->boolean('verified')->default(false);
            $table->enum('property_type', ['Featured', 'Guest Favorite', 'Others'])->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('property');
    }
};
