<?php

namespace App\Mail;

use App\Models\AdmissionApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewAdmissionApplicationAdminMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public AdmissionApplication $application)
    {
    }

    public function build(): self
    {
        return $this->subject('New admission application — '.$this->application->application_number)
            ->markdown('emails.new-application-admin', ['application' => $this->application]);
    }
}
