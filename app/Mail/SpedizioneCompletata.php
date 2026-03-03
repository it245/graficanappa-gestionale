<?php

namespace App\Mail;

use App\Models\OrdineFase;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SpedizioneCompletata extends Mailable
{
    use Queueable, SerializesModels;

    public OrdineFase $fase;
    public string $nomeOperatore;

    public function __construct(OrdineFase $fase, string $nomeOperatore)
    {
        $this->fase = $fase;
        $this->nomeOperatore = $nomeOperatore;
    }

    public function build()
    {
        $ordine = $this->fase->ordine;

        return $this->subject('Conferma spedizione avvenuta - ' . ($ordine->commessa ?? ''))
                    ->view('mail.spedizione_completata');
    }
}
