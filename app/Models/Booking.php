<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'bookings';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'property_id',
        'user_id',
        'start_date',
        'end_date',
        'guest_details',
        'guest_count',
        'booking_status',
        'cancellation_reason',
        'cancellation_date',
    ];

    /**
     * Get the property associated with the booking.
     */
    // public function property()
    // {
    //     return $this->belongsTo(Property::class, 'property_id');
    // }

    /**
     * Get the user who made the booking.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
