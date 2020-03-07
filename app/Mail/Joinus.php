<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class Joinus extends Mailable
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
        return $this->view('mails.joinus')
            ->from('jobs@sagecapita.com')
            // ->text('mails.joinus_plain')
            // ->with(
            //     [
            //         'testVarOne' => '1',
            //         'testVarTwo' => '2',
            //     ]
            // )
            ->attach(
                $this->joinus->cv,
                [
                    'as' => 'cv.pdf',
                    'mime' => $this->joinus->cvMime,
                ]
            );
    }
}
