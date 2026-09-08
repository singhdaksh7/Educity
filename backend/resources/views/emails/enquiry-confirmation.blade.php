@component('mail::message')
# Thank you, {{ $name }}

We have received your enquiry and a member of the {{ $institutionName }} team will get back to you shortly.

If your enquiry is urgent, feel free to reply directly to this email.

Thanks,<br>
{{ $institutionName }}
@endcomponent
