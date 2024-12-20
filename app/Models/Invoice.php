<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'invoices';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'booking_id',
        'payment_id',
        'amount',
        'status',
        'payment_method',
        'due_date',
        'description',
        'currency',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'amount' => 'decimal:2', // Cast amount as decimal with 2 decimal places
        'due_date' => 'date', // Cast due_date as a date
        'currency' => 'string', // Currency as string
    ];

    /**
     * Relationship with User model.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relationship with Booking model.
     */
    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Relationship with Payment model.
     */
    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * Scope a query to only include paid invoices.
     */
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    /**
     * Scope a query to only include pending invoices.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Get the formatted amount with currency (optional accessor).
     */
    public function getFormattedAmountAttribute()
    {
        return $this->currency . ' ' . number_format($this->amount, 2);
    }
}


// $invoice = Invoice::create([
//     'user_id' => $userId,
//     'booking_id' => $bookingId,
//     'payment_id' => $paymentId,
//     'amount' => 100.50,
//     'status' => 'pending',
//     'payment_method' => 'credit_card',
//     'due_date' => now()->addDays(30),
//     'description' => 'Invoice for booking #12345',
//     'currency' => 'USD',
// ]);

// $paidInvoices = Invoice::paid()->get();
