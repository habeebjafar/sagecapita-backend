<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class JoinusResponse extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * The joinus object instance.
     *
     * @var Joinus
     */
    public $joinus;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($joinus)
    {
        $this->joinus = $joinus;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->replyTo('info@sagecapita.com')
            ->view('mails.joinus_response');
    }
}
