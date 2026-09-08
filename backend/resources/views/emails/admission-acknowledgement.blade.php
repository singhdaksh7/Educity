@component('mail::message')
# Application Received

Dear {{ $application->full_name }},

Thank you for applying to {{ $institutionName }}. Your application has been received successfully.

@component('mail::table')
| Field | Value |
| :--- | :--- |
| Application Number | {{ $application->application_number }} |
| Programme | {{ $application->program->title ?? '—' }} |
| Status | {{ ucfirst(str_replace('_', ' ', $application->status)) }} |
@endcomponent

Please keep your application number for future reference. Our admissions team will contact you with the next steps.

Thanks,<br>
{{ $institutionName }}
@endcomponent
