<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OtpMail extends Mailable
{
    use SerializesModels;

    public $otp;

    /**
     * Create a new message instance.
     *
     * @param  string  $otp
     * @return void
     */
    public function __construct($otp)
    {
        $this->otp = $otp;

    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.otp') // Point to the email view
                    ->subject('Your OTP Code')
                    ->with([
                        'otp' => $this->otp, // Pass OTP to the view
                    ]);
    }
}
