{{-- FAQ accordion. $page, $dark --}}
@if ($page->config->faq)
    <x-ri.accordion :dark="$dark ?? false" :open="0" :items="array_map(fn ($f) => ['title' => $f->q, 'body' => $f->a], $page->config->faq)" />
@endif
