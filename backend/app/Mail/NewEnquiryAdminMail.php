<?php

namespace App\Mail;

use App\Models\Enquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewEnquiryAdminMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Enquiry $enquiry)
    {
    }

    public function build(): self
    {
        return $this->subject('New website enquiry from '.$this->enquiry->name)
            ->markdown('emails.new-enquiry-admin', ['enquiry' => $this->enquiry]);
    }
}
