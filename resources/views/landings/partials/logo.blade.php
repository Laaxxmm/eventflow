{{-- Organiser logo in a fixed slot, or the org name. $page, $class (img height), $textClass --}}
@if ($page->logoUrl())
    <img src="{{ $page->logoUrl() }}" alt="{{ $page->orgName }}" class="{{ $class ?? 'h-9' }} w-auto" onerror="this.replaceWith(Object.assign(document.createElement('span'), {textContent: this.alt, className: '{{ $textClass ?? 'font-bold' }}'}))">
@else
    <span class="{{ $textClass ?? 'font-bold' }}">{{ $page->orgName }}</span>
@endif
