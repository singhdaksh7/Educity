<?php

namespace App\Mail;

use App\Models\AdmissionApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AdmissionAcknowledgementMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public AdmissionApplication $application)
    {
    }

    public function build(): self
    {
        return $this->subject('Application received — '.$this->application->application_number)
            ->markdown('emails.admission-acknowledgement', [
                'application' => $this->application,
                'institutionName' => config('app.name'),
            ]);
    }
}
