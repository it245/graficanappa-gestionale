<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ConsegnaInRitardo extends Mailable
{
    use Queueable, SerializesModels;

    public array $ritardi;

    public function __construct(array $ritardi)
    {
        $this->ritardi = $ritardi;
    }

    public function build()
    {
        $count = count($this->ritardi);

        return $this->subject("BRT: {$count} consegn" . ($count === 1 ? 'a' : 'e') . " in ritardo")
                    ->view('mail.consegna_ritardo');
    }
}
