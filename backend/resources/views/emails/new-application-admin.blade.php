@component('mail::message')
# New Admission Application

@component('mail::table')
| Field | Value |
| :--- | :--- |
| Application Number | {{ $application->application_number }} |
| Name | {{ $application->full_name }} |
| Email | {{ $application->email }} |
| Phone | {{ $application->phone }} |
| Programme | {{ $application->program->title ?? '—' }} |
@endcomponent

@component('mail::button', ['url' => config('app.frontend_url', config('app.url'))])
Open Admin Dashboard
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
