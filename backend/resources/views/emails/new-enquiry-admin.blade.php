@component('mail::message')
# New Website Enquiry

@component('mail::table')
| Field | Value |
| :--- | :--- |
| Name | {{ $enquiry->name }} |
| Email | {{ $enquiry->email ?? '—' }} |
| Phone | {{ $enquiry->phone }} |
| Subject | {{ $enquiry->subject ?? '—' }} |
| Source | {{ $enquiry->source }} |
@endcomponent

**Message:**

{{ $enquiry->message }}

@component('mail::button', ['url' => config('app.frontend_url', config('app.url'))])
Open Admin Dashboard
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
