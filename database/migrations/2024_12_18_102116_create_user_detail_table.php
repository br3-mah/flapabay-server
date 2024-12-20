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
        Schema::create('user_details', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->text('bio')->nullable(); // Text to allow larger bio descriptions
            $table->string('live_in', 100)->nullable(); // Assuming 'live_in' is a location or city name
            $table->string('contact_email', 191)->nullable(); // Email with max length for indexing
            $table->string('phone')->nullable(); // Email with max length for indexing
            $table->string('phone_2')->nullable(); // Email with max length for indexing
            $table->json('languages')->nullable(); // JSON array for multiple language support
            $table->string('website', 191)->nullable(); // URL for website (up to 191 characters)
            $table->string('skype', 50)->nullable(); // Assuming Skype usernames aren't too long
            $table->string('facebook', 191)->nullable(); // URL for Facebook profile
            $table->string('twitter', 191)->nullable(); // URL for Twitter profile
            $table->string('linkedin', 191)->nullable(); // URL for LinkedIn profile (renamed from 'linked')
            $table->string('youtube', 191)->nullable(); // URL for YouTube channel
            $table->string('profile_picture_url', 191)->nullable(); // URL for Picture
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_detail');
    }
};
