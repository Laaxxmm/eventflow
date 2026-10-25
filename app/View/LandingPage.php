<?php

namespace App\View;

use App\Landing\AgeBand;
use App\Landing\ConfigCompiler;
use App\Landing\Distance;
use App\Landing\EventConfig;
use App\Landing\RegistrationForm;
use App\Landing\Theme;
use Carbon\CarbonImmutable;

/**
 * What every landing template shows, computed once: templates only do layout.
 * $remaining: places left per distance code (null = unlimited).
 */
final readonly class LandingPage
{
    /** @var list<array{distance: Distance, left: int|null, full: bool, price: string}> */
    public array $distances;

    public CarbonImmutable $date;

    public function __construct(
        public EventConfig $config,
        public string $orgName,
        public string $orgSlug,
        public RegistrationForm $form,
        public Theme $theme,
        array $remaining,
        public bool $open = true,
        public bool $preview = false,
    ) {
        $this->date = $config->date->setTimezone(ConfigCompiler::TIMEZONE);
        $this->distances = array_map(fn (Distance $d) => [
            'distance' => $d,
            'left' => $remaining[$d->code] ?? null,
            'full' => ($remaining[$d->code] ?? null) === 0,
            'price' => self::inr($d->price_inr),
        ], $config->distances);
    }

    public function logoUrl(): ?string
    {
        return Theme::url($this->config->branding->logo);
    }

    public function heroUrl(): ?string
    {
        return Theme::url($this->config->hero->image);
    }

    public function fromPrice(): string
    {
        return self::inr(min(array_map(fn (Distance $d) => $d->price_inr, $this->config->distances)));
    }

    public function mapUrl(): string
    {
        return 'https://www.google.com/maps/search/?api=1&query='.urlencode($this->config->venue);
    }

    /** "Junior 12–17 · Open 18–39 …", or null without age bands. */
    public function ageCategories(): ?string
    {
        return $this->config->ageBands
            ? implode(' · ', array_map(fn (AgeBand $b) => "{$b->label} {$b->min}–{$b->max}", $this->config->ageBands))
            : null;
    }

    /** "1,200 places left", "Full" or "Open entry". */
    public function placesLabel(?int $left): string
    {
        return match (true) {
            $left === null => __('Open entry'),
            $left === 0 => __('Full'),
            default => trans_choice(':count place left|:count places left', $left, ['count' => number_format($left)]),
        };
    }

    public function registerUrl(): string
    {
        return '#register';
    }

    public static function inr(int $amount): string
    {
        return $amount === 0 ? __('Free') : '₹'.number_format($amount);
    }
}
