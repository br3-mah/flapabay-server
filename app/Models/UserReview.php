<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserReview extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user_reviews';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'listing_id',
        'property_id',
        'rating',
        'review'
    ];

}
