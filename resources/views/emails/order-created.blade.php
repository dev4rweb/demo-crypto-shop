@php($locale = $order->locale ?: 'en')
@component('emails.layout')
    <h1 style="font-size:22px;margin:0 0 12px;color:#ffffff;">{{ __('app.mail.created_title', [], $locale) }}</h1>
    <p style="line-height:1.5;color:#c5d6c8;">{{ __('app.mail.created_body', ['name' => $order->name, 'number' => substr($order->uuid, 0, 8)], $locale) }}</p>
    <p style="font-family:monospace;font-size:18px;color:#32f0ff;">{{ format_usd($order->total_usd_cents) }}</p>
    <p style="color:#9aa89c;font-size:13px;">{{ __('app.mail.created_hint', [], $locale) }}</p>
    <p style="margin:20px 0 0;"><a href="{{ $orderUrl }}" style="color:#32f0ff;">{{ __('app.mail.open_order', [], $locale) }}</a></p>
@endcomponent
