<?php

namespace App\Certificates;

/**
 * Everything printed on one certificate, already formatted. Templates read
 * only this (as $c) plus its palette ($c->palette).
 */
final readonly class CertificateData
{
    /**
     * @param  array<int, array{name: ?string, role: string}>  $signatories  Always two slots
     */
    public function __construct(
        public string $template,
        public string $orientation,
        public string $variant,
        public Palette $palette,
        public string $credential,
        public string $participant,
        public string $eventName,
        public ?string $date,
        public ?string $venue,
        public ?string $city,
        public ?string $distance,
        public ?string $category,
        public ?string $sport,
        public ?string $bib,
        public ?string $rank,
        public ?string $time,
        public string $orgName,
        public ?string $logo,
        public string $certNo,
        public ?string $verifyUrl,
        public ?string $qr,
        public array $signatories,
        public bool $stamp,
        public bool $sample = false,
        public ?string $background = null,
    ) {}

    public function portrait(): bool
    {
        return $this->orientation === 'portrait';
    }

    /** Achievement facts that exist, in print order: label => value. */
    public function facts(): array
    {
        return array_filter([
            __('Distance') => $this->distance,
            __('Category') => $this->category,
            __('Time') => $this->time,
            __('Rank') => $this->rank,
            __('Bib') => $this->bib,
        ], fn ($v) => $v !== null && $v !== '');
    }

    /** "for completing the Half Marathon (21.1K) in 01:42:18" style line. */
    public function achievement(): string
    {
        $verb = match (true) {
            str_contains(strtolower($this->credential), 'participation') => __('for participating in'),
            str_contains(strtolower($this->credential), 'merit') => __('in recognition of outstanding performance in'),
            default => __('for successfully completing'),
        };

        return trim($verb.' '.($this->distance ? __('the :distance at', ['distance' => $this->distance]) : ''));
    }

    /** Name size in pt, stepped down for long names so they stay on one line. */
    public function nameSize(float $base): float
    {
        $len = mb_strlen($this->participant);

        return round(match (true) {
            $len <= 18 => $base,
            $len <= 24 => $base * 0.86,
            $len <= 30 => $base * 0.74,
            $len <= 38 => $base * 0.62,
            default => $base * 0.52,
        }, 1);
    }

    /** Short distance for big numerals: "21.1K" from "Half Marathon (21.1K)". */
    public function shortDistance(): ?string
    {
        if ($this->distance === null) {
            return null;
        }
        if (preg_match('/\(([^)]{1,10})\)/', $this->distance, $m)) {
            return $m[1];
        }

        return mb_strlen($this->distance) <= 9 ? $this->distance : null;
    }

    /** Initials for the stamp centre (e.g. "DR" for Demo Runners). */
    public function initials(): string
    {
        $words = preg_split('/\s+/', trim($this->orgName)) ?: [];

        return mb_strtoupper(implode('', array_map(fn ($w) => mb_substr($w, 0, 1), array_slice($words, 0, 3))));
    }
}
