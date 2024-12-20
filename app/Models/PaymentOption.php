<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentOption extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'payment_options';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'payment_method',
        'account_number',
        'expiration_date',
        'country_code',
        'currency',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'expiration_date' => 'string', // Cast expiration date as string
        'country_code' => 'string', // Cast country code as string
        'currency' => 'string', // Cast currency as string
    ];

    /**
     * Relationship with the User model.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the payment option in a formatted way (optional accessor).
     */
    public function getFormattedPaymentMethodAttribute()
    {
        return ucfirst($this->payment_method);
    }
}
