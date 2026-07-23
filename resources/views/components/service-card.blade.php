@props(['service', 'image', 'position' => '50% 50%', 'wide' => false])
{{-- A services card (layout A — image-forward). Opens the enquiry modal
     for its service via the window `enquiry-open` event. The kicker and
     body sit directly in the button's flex flow, above the absolutely-
     positioned photo and shade. --}}
<button type="button"
        {{ $attributes->class(['service-card', 'service-card-wide' => $wide]) }}
        @click="window.dispatchEvent(new CustomEvent('enquiry-open', { detail: { service: @js($service) } }))">
    <span class="card-img" style="background-image:url('{{ asset($image) }}');background-position:{{ $position }};" aria-hidden="true"></span>
    <span class="card-shade" aria-hidden="true"></span>
    <span class="card-kicker">{{ __('services.'.$service.'.kicker') }}</span>
    <span class="card-body">
        <span class="card-title">{{ __('services.'.$service.'.title') }}</span>
        <span class="card-desc">{{ __('services.'.$service.'.desc') }}</span>
        <span class="card-cta">{{ __('services.enquire') }} <span aria-hidden="true">→</span></span>
    </span>
</button>
