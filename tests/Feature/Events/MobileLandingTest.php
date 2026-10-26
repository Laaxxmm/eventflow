<?php

/*
 * Mobile polish for the Race Ink event pages: one Register button on screen at a time.
 * The sticky mobile bar waits for the hero's own button ([data-hero-cta]) to scroll away.
 */

dataset('landing templates', ['gun-lap', 'stadium-split', 'equipment-close', 'night-circuit', 'heritage-crest']);

it('marks the hero Register button and ships the sticky bar that waits for it', function (string $template) {
    $html = $this->get(route('templates.show', $template))->assertOk()->getContent();

    expect(preg_match_all('/<a [^>]*data-hero-cta/', $html))->toBe(1)
        ->and($html)->toContain('data-sticky-register')
        ->toContain("querySelectorAll('[data-hero-cta], #register')")
        ->toContain('.js .ri-sticky-register:not(.is-on)');
})->with('landing templates');

it('gives Gun Lap one quiet line of facts on phones instead of stacked boxes', function () {
    $this->get(route('templates.show', 'gun-lap'))->assertOk()
        ->assertSee('data-hero-meta', false)
        ->assertSee('Entries from')
        ->assertSeeInOrder(['data-hero-meta', 'Limited bibs', 'hidden flex-wrap gap-2 sm:flex'], false);
});
