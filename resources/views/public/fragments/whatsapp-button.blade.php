@php
    $variant = $variant ?? 'default';
    $label = $label ?? null;
    $whatsappRaw = data_get($contacts->getTranslation('content_data', config('app.fallback_locale')), 'whatsapp');
@endphp

@if(!empty(trim((string) $whatsappRaw)))
    <a
        href="https://wa.me/{{ get_only_numbers($whatsappRaw) }}"
        target="_blank"
        rel="noopener"
        aria-label="WhatsApp"
        class="whatsapp-btn whatsapp-btn--{{ $variant }}"
    >
        @if($label)
            <span class="whatsapp-btn-label">{{ $label }}</span>
        @endif
    </a>
@endif
