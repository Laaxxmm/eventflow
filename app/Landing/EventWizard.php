<?php

namespace App\Landing;

use App\Contracts\SportPackRepository;
use App\Enums\Sport;
use Carbon\CarbonImmutable;
use Illuminate\Validation\Rule;

/**
 * The 5-step create-event wizard: Basics → Sport pack → Template → Branding & media → Review.
 *
 * It writes the same config shape as event YAML, but only what it asks for. Everything else
 * (age bands, form fields, race sheet, certificate, FAQ) is left out so the sport pack's
 * defaults apply; organisers fine-tune them later in the full editor.
 */
final class EventWizard
{
    /** Which step each input belongs to, to reopen the wizard on the first step with an error. */
    public const STEPS = [
        1 => ['name', 'date', 'venue', 'tagline'],
        2 => ['sport', 'distances'],
        3 => ['template'],
        4 => ['primary', 'secondary', 'logo', 'hero'],
        5 => ['publish'],
    ];

    public const LOGO_MAX_KB = 2048;

    public const HERO_MAX_KB = 6144;

    /** @return array<string, mixed> */
    public static function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:160'],
            'date' => ['required', 'date_format:Y-m-d\TH:i', 'after:now'],
            'venue' => ['required', 'string', 'max:255'],
            'tagline' => ['nullable', 'string', 'max:255'],
            'sport' => ['required', Rule::enum(Sport::class)],
            'distances' => ['required', 'array', 'min:1', 'max:12'],
            'distances.*.code' => ['required', 'string', 'regex:/^[A-Za-z0-9_-]{1,20}$/', 'distinct'],
            'distances.*.label' => ['required', 'string', 'max:80'],
            'distances.*.price_inr' => ['required', 'integer', 'min:0', 'max:1000000'],
            'distances.*.capacity' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'template' => ['required', Rule::in(array_keys(config('eventflow.landing_templates')))],
            'primary' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'secondary' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            // Transparent PNG preferred; no SVG (served from the public disk, scripts could run).
            'logo' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp', 'max:'.self::LOGO_MAX_KB, 'dimensions:min_width=120,min_height=40'],
            'hero' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:'.self::HERO_MAX_KB, 'dimensions:min_width=1200,min_height=600'],
            'publish' => ['required', 'boolean'],
        ];
    }

    /** @return array<string, string> */
    public static function messages(): array
    {
        return [
            'date.after' => __('Pick a date in the future.'),
            'distances.required' => __('Add at least one distance.'),
            'distances.*.price_inr.required' => __('Set a price for every distance (0 for free).'),
            'distances.*.code.distinct' => __('Each distance needs its own code.'),
            'logo.dimensions' => __('The logo must be at least 120 × 40 pixels.'),
            'hero.dimensions' => __('The photo must be at least 1200 × 600 pixels so it stays sharp.'),
        ];
    }

    /**
     * @param  array<string, mixed>  $input  Validated wizard input
     * @return array<string, mixed> Authored config (events/schema.json shape)
     */
    public static function toConfig(array $input, string $org, string $slug, ?string $logo, ?string $hero): array
    {
        return [
            'org' => $org,
            'slug' => $slug,
            'name' => $input['name'],
            'sport' => $input['sport'],
            'template' => $input['template'],
            'date' => CarbonImmutable::createFromFormat('Y-m-d\TH:i', $input['date'], ConfigCompiler::TIMEZONE)->toIso8601String(),
            'venue' => $input['venue'],
            'branding' => array_filter([
                'primary' => strtolower($input['primary']),
                'secondary' => strtolower($input['secondary']),
                'logo' => $logo,
            ]),
            'hero' => array_filter([
                'title' => $input['name'],
                'subtitle' => $input['tagline'] ?? null,
                'image' => $hero,
            ]),
            'distances' => array_values(array_map(fn (array $d) => array_filter([
                'code' => trim($d['code']),
                'label' => trim($d['label']),
                'price_inr' => (int) $d['price_inr'],
                'capacity' => filled($d['capacity'] ?? null) ? (int) $d['capacity'] : null,
            ], fn ($v) => $v !== null), $input['distances'])),
        ];
    }

    /** First step holding one of the failed fields (1 when none match). */
    public static function stepFor(array $errorKeys): int
    {
        foreach (self::STEPS as $step => $fields) {
            foreach ($errorKeys as $key) {
                if (in_array(explode('.', $key)[0], $fields, true)) {
                    return $step;
                }
            }
        }

        return 1;
    }

    /**
     * What the wizard shows per sport pack: label, default template and unpriced distances.
     *
     * @return array<string, array{label: string, template: string, distances: list<array{code: string, label: string}>, faq: int}>
     */
    public static function packs(SportPackRepository $packs): array
    {
        $out = [];
        foreach (Sport::cases() as $sport) {
            $pack = $packs->get($sport);
            $out[$sport->value] = [
                'label' => $pack->label,
                'template' => $pack->defaults['template'] ?? EventConfig::DEFAULT_TEMPLATE,
                'distances' => array_map(fn (array $d) => ['code' => $d['code'], 'label' => $d['label']], $pack->defaults['distances'] ?? []),
                'faq' => count($pack->defaults['faq'] ?? []),
            ];
        }

        return $out;
    }
}
