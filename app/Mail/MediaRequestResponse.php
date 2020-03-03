<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class MediaRequestResponse extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * The mediarequest object instance.
     *
     * @var MediaRequest
     */
    public $mediarequest;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($mediarequest)
    {
        $this->mediarequest = $mediarequest;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->replyTo('info@sagecapita.com')
            ->view('mails.mediarequest_response');
    }
}
