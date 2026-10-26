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
        public bool $watermark = false, // "Powered by EventFlow" while the org is on a trial
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

    /** Hero photo: the organiser's upload, else the n-th stock photo for the sport (or null: graphic fallback). */
    public function heroPhoto(int $n = 0): ?string
    {
        return $this->heroUrl() ?? SportImagery::pick($this->config->sport->value, $n);
    }

    /** Extra photo for secondary slots (never the organiser hero twice). */
    public function photo(int $n): ?string
    {
        return SportImagery::pick($this->config->sport->value, $n);
    }

    /** "Register — from ₹599" */
    public function registerLabel(): string
    {
        return __('Register — from :price', ['price' => $this->fromPrice()]);
    }

    /** Big numeral for a distance: "21.1K" from "Half Marathon (21.1K)", else the code. */
    public static function numeral(Distance $d): string
    {
        if (preg_match('/\(([^)]{1,8})\)/', $d->label, $m)) {
            return $m[1];
        }

        return mb_strlen($d->label) <= 6 ? $d->label : $d->code;
    }

    /** Distance label without the numeral in brackets: "Half Marathon". */
    public static function title(Distance $d): string
    {
        return trim((string) preg_replace('/\s*\([^)]*\)\s*$/', '', $d->label)) ?: $d->label;
    }

    /**
     * Race-day schedule: the configured one, else bib collection an hour before the start
     * and one indicative start per distance, 15 minutes apart.
     *
     * @return list<array{time: string, label: string, indicative: bool}>
     */
    public function schedule(): array
    {
        if ($this->config->schedule) {
            return array_map(fn (array $s) => ['time' => $s['time'], 'label' => $s['label'], 'indicative' => false], $this->config->schedule);
        }

        $rows = [['time' => $this->date->subHour()->format('H:i'), 'label' => __('Reporting and bib collection'), 'indicative' => true]];
        foreach ($this->distances as $i => $row) {
            $rows[] = ['time' => $this->date->addMinutes($i * 15)->format('H:i'), 'label' => __(':distance start', ['distance' => $row['distance']->label]), 'indicative' => true];
        }

        return $rows;
    }

    /** @return list<array{name: string, logo: ?string, url: ?string, tier: ?string}> */
    public function sponsors(): array
    {
        return array_map(fn (array $s) => [
            'name' => (string) $s['name'],
            'logo' => Theme::url($s['logo'] ?? null),
            'url' => $s['url'] ?? null,
            'tier' => $s['tier'] ?? null,
        ], $this->config->sponsors);
    }

    /** Places left across capped distances, or null when any distance is unlimited. */
    public function placesLeft(): ?int
    {
        $left = array_column($this->distances, 'left');

        return in_array(null, $left, true) ? null : array_sum($left);
    }

    /** "Limited bibs" urgency: shown when every distance is capped. */
    public function limited(): bool
    {
        return $this->placesLeft() !== null;
    }

    public function daysToGo(): int
    {
        return max(0, (int) now(ConfigCompiler::TIMEZONE)->startOfDay()->diffInDays($this->date->startOfDay(), false));
    }

    public static function inr(int $amount): string
    {
        return $amount === 0 ? __('Free') : '₹'.number_format($amount);
    }
}
