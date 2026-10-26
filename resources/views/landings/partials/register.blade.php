{{-- Registration form (shared by every template). $page --}}
@if ($page->open)
    @include('landing.partials.form', [
        'config' => $page->config,
        'form' => $page->form,
        'remaining' => array_column(array_map(fn ($d) => ['code' => $d['distance']->code, 'left' => $d['left']], $page->distances), 'left', 'code'),
        'preview' => $page->preview,
    ])
@elseif ($page->paused)
    <p class="mt-4 rounded-2xl bg-black/5 p-6 text-center font-medium" role="status">{{ __(\App\Http\Controllers\RegistrationController::PAUSED_MESSAGE) }}</p>
@else
    <p class="mt-4 rounded-2xl bg-black/5 p-6 text-center font-medium">{{ __('Registration for this event is closed.') }}</p>
@endif
