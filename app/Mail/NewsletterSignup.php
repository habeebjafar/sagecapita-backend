<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class NewsletterSignup extends Mailable
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
    public function __construct($newslettersignup)
    {
        $this->newslettersignup = $newslettersignup;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('mails.newslettersignup');
    }
}
