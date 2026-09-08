<?php

namespace App\Mail;

use App\Models\Enquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EnquiryConfirmationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Enquiry $enquiry)
    {
    }

    public function build(): self
    {
        return $this->subject('We received your enquiry — '.config('app.name'))
            ->markdown('emails.enquiry-confirmation', [
                'name' => $this->enquiry->name,
                'institutionName' => config('app.name'),
            ]);
    }
}
