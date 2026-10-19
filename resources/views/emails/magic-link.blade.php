@php($locale = app()->getLocale() ?: 'en')
@component('emails.layout')
    <h1 style="font-size:22px;margin:0 0 12px;color:#ffffff;">{{ __('app.auth.magic_mail_title', [], $locale) }}</h1>
    <p style="line-height:1.5;color:#c5d6c8;">{{ __('app.auth.magic_mail_body', [], $locale) }}</p>
    <p style="margin:24px 0;">
        <a href="{{ $loginUrl }}" style="display:inline-block;padding:12px 18px;background:#c6ff3d;color:#05070a;text-decoration:none;font-weight:700;border-radius:8px;">
            {{ __('app.auth.magic_mail_button', [], $locale) }}
        </a>
    </p>
    <p style="color:#9aa89c;font-size:13px;line-height:1.5;">{{ __('app.auth.magic_mail_hint', [], $locale) }}</p>
@endcomponent
