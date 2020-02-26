<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class NewsletterSignupResponse extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * The newslettersignup object instance.
     *
     * @var NewsletterSignup
     */
    public $newslettersignup;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->replyTo('info@sagecapita.com')
            ->view('mails.newslettersignup_response');
    }
}
