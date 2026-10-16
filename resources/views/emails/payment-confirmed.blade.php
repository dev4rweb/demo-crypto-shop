@php($locale = $order->locale ?: 'en')
@component('emails.layout')
    <h1 style="font-size:22px;margin:0 0 12px;color:#ffffff;">{{ __('app.mail.paid_title', [], $locale) }}</h1>
    <p style="line-height:1.5;color:#c5d6c8;">{{ __('app.mail.paid_body', ['name' => $order->name, 'number' => substr($order->uuid, 0, 8)], $locale) }}</p>
    @foreach ($order->items as $item)
        <div style="margin:16px 0;padding:12px;border:1px solid #1f3d2a;border-radius:8px;">
            <strong>{{ $item->name }}</strong>
            @if (!empty($item->delivery_payload['key']))
                <p style="font-family:monospace;color:#c6ff3d;margin:8px 0;">{{ $item->delivery_payload['key'] }}</p>
            @endif
            @if (!empty($item->delivery_payload['url']))
                <p style="margin:0;"><a href="{{ $item->delivery_payload['url'] }}" style="color:#32f0ff;">{{ $item->delivery_payload['url'] }}</a></p>
            @endif
        </div>
    @endforeach
@endcomponent
