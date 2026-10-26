<?php

namespace App\Landing;

use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

/**
 * The organiser's event setup screen (port of the prototype's create-event.php), translated
 * to and from the events/schema.json config shape, so YAML and the editor write the same thing.
 *
 * Standard questions are the prototype's show/hide toggles; anything else is a custom question.
 * The editor writes distances, age_bands and fields explicitly, so what the organiser sees is
 * what registrants get (sport pack defaults only prefill a new event).
 */
final class EventForm
{
    /** The prototype's show/hide registration fields. */
    public const STANDARD_FIELDS = [
        'age' => ['key' => 'age', 'label' => 'Age', 'type' => 'number', 'required' => true],
        'gender' => ['key' => 'gender', 'label' => 'Gender', 'type' => 'select', 'required' => true, 'options' => ['Male', 'Female', 'Other']],
        'city' => ['key' => 'city', 'label' => 'City', 'type' => 'text', 'required' => false],
        'emergency_contact_name' => ['key' => 'emergency_contact_name', 'label' => 'Emergency contact name', 'type' => 'text', 'required' => true],
        'emergency_contact_phone' => ['key' => 'emergency_contact_phone', 'label' => 'Emergency contact phone', 'type' => 'tel', 'required' => true],
        'tshirt_size' => ['key' => 'tshirt_size', 'label' => 'T-shirt size', 'type' => 'select', 'required' => true, 'options' => ['XS', 'S', 'M', 'L', 'XL', 'XXL']],
    ];

    public const FIELD_TYPES = ['text', 'email', 'tel', 'number', 'date', 'select', 'radio', 'checkbox', 'textarea'];

    public const MAX_CUSTOM_FIELDS = 12;

    /** Hero / share image upload limit (Session P1). */
    public const UPLOAD_MAX_KB = 5120;

    /** Social profile bases: "@myrace" or "myrace" becomes the full profile URL. */
    public const SOCIAL_BASES = [
        'instagram' => 'https://instagram.com/',
        'facebook' => 'https://facebook.com/',
        'x' => 'https://x.com/',
        'youtube' => 'https://youtube.com/@',
    ];

    /** Rows rendered for each repeatable list (blank rows are ignored on save). */
    public const ROWS = ['distances' => 6, 'age_bands' => 8, 'custom' => 4, 'faq' => 6];

    /** @return array<string, mixed> Laravel rules for the posted form (shape only; the schema checks the rest) */
    public static function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:160'],
            'sport' => ['required', 'string'],
            'template' => ['nullable', 'in:'.implode(',', array_keys(config('eventflow.landing_templates')))],
            'date' => ['required', 'date_format:Y-m-d\TH:i'],
            'venue' => ['required', 'string', 'max:255'],
            'status' => ['required', 'in:'.implode(',', EventStatus::ALL)],
            'branding.primary' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'branding.secondary' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'branding.logo' => ['nullable', 'string', 'max:255'],
            'hero.title' => ['nullable', 'string', 'max:160'],
            'hero.subtitle' => ['nullable', 'string', 'max:255'],
            'hero.image' => ['nullable', 'string', 'max:255'],
            'hero.og_image' => ['nullable', 'string', 'max:255'],
            // Session P1: uploads (stored on the public disk by EventController) and removals.
            'hero_upload' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.self::UPLOAD_MAX_KB],
            'og_upload' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.self::UPLOAD_MAX_KB],
            'logo_upload' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove' => ['array'],
            'remove.*' => ['boolean'],
            'registration_closes' => ['nullable', 'date_format:Y-m-d\TH:i'],
            'countdown.enabled' => ['nullable', 'boolean'],
            'countdown.target' => ['nullable', 'in:'.implode(',', EventConfig::COUNTDOWN_TARGETS)],
            'contact.email' => ['nullable', 'email', 'max:255'],
            'contact.phone' => ['nullable', 'string', 'max:30'],
            'contact.website' => ['nullable', 'url:https,http', 'max:255'],
            'contact.instagram' => ['nullable', 'string', 'max:255'],
            'contact.facebook' => ['nullable', 'string', 'max:255'],
            'contact.x' => ['nullable', 'string', 'max:255'],
            'contact.youtube' => ['nullable', 'string', 'max:255'],
            'contact.terms_url' => ['nullable', 'url:https,http', 'max:255'],
            'contact.privacy_url' => ['nullable', 'url:https,http', 'max:255'],
            'distances' => ['array'],
            'distances.*.code' => ['nullable', 'string', 'max:20'],
            'distances.*.label' => ['nullable', 'required_with:distances.*.code', 'string', 'max:80'],
            'distances.*.price_inr' => ['nullable', 'required_with:distances.*.code', 'integer', 'min:0'],
            'distances.*.capacity' => ['nullable', 'integer', 'min:1'],
            'age_bands' => ['array'],
            'age_bands.*.label' => ['nullable', 'string', 'max:40'],
            'age_bands.*.min' => ['nullable', 'required_with:age_bands.*.label', 'integer', 'min:0', 'max:120'],
            'age_bands.*.max' => ['nullable', 'required_with:age_bands.*.label', 'integer', 'min:0', 'max:120', 'gte:age_bands.*.min'],
            'show' => ['array'],
            'show.*' => ['boolean'],
            'custom' => ['array', 'max:'.self::MAX_CUSTOM_FIELDS],
            'custom.*.key' => ['nullable', 'string', 'max:40'],
            'custom.*.label' => ['nullable', 'string', 'max:80'],
            'custom.*.type' => ['nullable', 'in:'.implode(',', self::FIELD_TYPES)],
            'custom.*.required' => ['nullable', 'boolean'],
            'custom.*.options' => ['nullable', 'string', 'max:1000'],
            'faq' => ['array'],
            'faq.*.q' => ['nullable', 'string', 'max:255'],
            'faq.*.a' => ['nullable', 'required_with:faq.*.q', 'string', 'max:2000'],
            'certificate.template' => ['nullable', 'string', 'max:40'],
            'certificate.orientation' => ['nullable', 'in:portrait,landscape'],
            'race_sheet.heat_size' => ['nullable', 'integer', 'min:2', 'max:64'],
            'race_sheet.group_by' => ['nullable', 'string', 'max:255'],
            'race_sheet.seeding' => ['nullable', 'in:registration_order,random'],
        ];
    }

    /**
     * Posted form to an authored config (events/schema.json shape).
     *
     * @param  array<string, mixed>  $input  Validated with rules()
     * @param  array<string, mixed>  $previous  The stored config, to keep keys the editor does not show
     * @return array<string, mixed>
     */
    public static function toConfig(array $input, string $org, string $slug, array $previous = []): array
    {
        $config = [
            'org' => $org,
            'slug' => $slug,
            'name' => $input['name'],
            'sport' => $input['sport'],
            'template' => ($input['template'] ?? null) ?: ($previous['template'] ?? null),
            'date' => CarbonImmutable::createFromFormat('Y-m-d\TH:i', $input['date'], ConfigCompiler::TIMEZONE)->toIso8601String(),
            'venue' => $input['venue'],
            'branding' => array_filter([
                'primary' => strtolower($input['branding']['primary']),
                'secondary' => strtolower($input['branding']['secondary']),
                'logo' => $input['branding']['logo'] ?? null,
            ], fn ($v) => $v !== null),
            'hero' => array_filter([
                'title' => ($input['hero']['title'] ?? null) ?: $input['name'],
                'subtitle' => $input['hero']['subtitle'] ?? null,
                'image' => $input['hero']['image'] ?? null,
                'og_image' => $input['hero']['og_image'] ?? null,
            ], fn ($v) => $v !== null),
            'distances' => self::distances($input['distances'] ?? []),
            'age_bands' => self::ageBands($input['age_bands'] ?? []),
            'fields' => self::fields($input['show'] ?? [], $input['custom'] ?? []),
        ];

        if (filled($input['registration_closes'] ?? null)) {
            $config['registration_closes'] = CarbonImmutable::createFromFormat('Y-m-d\TH:i', $input['registration_closes'], ConfigCompiler::TIMEZONE)->toIso8601String();
        }
        if (! empty($input['countdown']['enabled'])) {
            $config['countdown'] = ['enabled' => true, 'target' => $input['countdown']['target'] ?? EventConfig::COUNTDOWN_TARGETS[0]];
        }
        if ($contact = self::contact($input['contact'] ?? [])) {
            $config['contact'] = $contact;
        }
        // Keys the editor does not show stay as authored (sponsors, schedule, charity).
        foreach (['sponsors', 'schedule', 'charity'] as $key) {
            if (isset($previous[$key])) {
                $config[$key] = $previous[$key];
            }
        }

        if ($config['template'] === null) {
            unset($config['template']); // the sport pack's default applies
        }

        if ($faq = self::faq($input['faq'] ?? [])) {
            $config['faq'] = $faq;
        }

        if (! empty($input['race_sheet']['heat_size'])) {
            $config['race_sheet'] = array_filter([
                'heat_size' => (int) $input['race_sheet']['heat_size'],
                'group_by' => self::list($input['race_sheet']['group_by'] ?? '') ?: null,
                'seeding' => $input['race_sheet']['seeding'] ?? null,
            ]);
        }

        if (! empty($input['certificate']['template'])) {
            $certificate = array_filter([
                'template' => $input['certificate']['template'],
                'orientation' => $input['certificate']['orientation'] ?? null,
            ]);
            // Uploaded backgrounds are managed by the certificates stream; keep the stored one.
            if ($certificate['template'] === 'upload' && isset($previous['certificate']['background'])) {
                $certificate['background'] = $previous['certificate']['background'];
            }
            // Design options are set on the certificates page; this form must not drop them.
            foreach (['palette', 'credential', 'variant', 'signatories', 'stamp'] as $key) {
                if (isset($previous['certificate'][$key])) {
                    $certificate[$key] = $previous['certificate'][$key];
                }
            }
            $config['certificate'] = $certificate;
        }

        return $config;
    }

    /**
     * A resolved config (pack defaults applied) to the form's input shape.
     *
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    public static function toInput(array $config, string $status = EventStatus::Draft): array
    {
        $show = [];
        $custom = [];

        foreach ($config['fields'] ?? [] as $field) {
            $standard = self::STANDARD_FIELDS[$field['key']] ?? null;

            if ($standard !== null && self::sameField($standard, $field)) {
                $show[$field['key']] = true;
            } else {
                $custom[] = [
                    'key' => $field['key'],
                    'label' => $field['label'],
                    'type' => $field['type'],
                    'required' => (bool) ($field['required'] ?? false),
                    'options' => implode(', ', $field['options'] ?? []),
                ];
            }
        }

        return [
            'name' => $config['name'] ?? '',
            'sport' => $config['sport'] ?? 'generic',
            'template' => $config['template'] ?? EventConfig::DEFAULT_TEMPLATE,
            'date' => isset($config['date'])
                ? CarbonImmutable::parse(ConfigCompiler::withOffset($config['date']))->setTimezone(ConfigCompiler::TIMEZONE)->format('Y-m-d\TH:i')
                : '',
            'venue' => $config['venue'] ?? '',
            'status' => $status,
            'branding' => ($config['branding'] ?? []) + ['primary' => '#4b3fe0', 'secondary' => '#f2a93b', 'logo' => null],
            'hero' => ($config['hero'] ?? []) + ['title' => '', 'subtitle' => null, 'image' => null, 'og_image' => null],
            'registration_closes' => isset($config['registration_closes'])
                ? CarbonImmutable::parse(ConfigCompiler::withOffset($config['registration_closes']))->setTimezone(ConfigCompiler::TIMEZONE)->format('Y-m-d\TH:i')
                : '',
            'countdown' => ($config['countdown'] ?? []) + ['enabled' => false, 'target' => EventConfig::COUNTDOWN_TARGETS[0]],
            'contact' => ($config['contact'] ?? []) + array_fill_keys(EventConfig::CONTACT_KEYS, null),
            'distances' => $config['distances'] ?? [],
            'age_bands' => $config['age_bands'] ?? [],
            'show' => $show,
            'custom' => $custom,
            'faq' => $config['faq'] ?? [],
            'certificate' => $config['certificate'] ?? ['template' => 'classic', 'orientation' => 'landscape'],
            'race_sheet' => isset($config['race_sheet']) ? [
                'heat_size' => $config['race_sheet']['heat_size'],
                'group_by' => implode(', ', $config['race_sheet']['group_by'] ?? []),
                'seeding' => $config['race_sheet']['seeding'] ?? 'registration_order',
            ] : ['heat_size' => null, 'group_by' => '', 'seeding' => 'registration_order'],
        ];
    }

    /**
     * Schema error paths ("distances[0].price_inr") to form input keys ("distances.0.price_inr").
     *
     * @param  list<array{property: string, message: string}>  $errors
     * @return array<string, list<string>>
     */
    public static function errorBag(array $errors): array
    {
        $bag = [];

        foreach ($errors as $error) {
            $key = preg_replace('/\[(\d+)\]/', '.$1', $error['property']);
            $key = preg_replace('/^fields\.\d+.*/', 'custom', $key);
            $bag[$key !== '' ? $key : 'name'][] = $error['message'];
        }

        return $bag;
    }

    /** @return array<string, string> Footer contact with blanks dropped and socials as URLs. */
    private static function contact(array $input): array
    {
        $contact = [];
        foreach (EventConfig::CONTACT_KEYS as $key) {
            $value = trim((string) ($input[$key] ?? ''));
            if ($value === '') {
                continue;
            }
            if (isset(self::SOCIAL_BASES[$key]) && ! preg_match('#^https?://#i', $value)) {
                $value = self::SOCIAL_BASES[$key].ltrim($value, '@/');
            }
            $contact[$key] = $value;
        }

        return $contact;
    }

    /** @return list<array<string, mixed>> */
    private static function distances(array $rows): array
    {
        $distances = [];

        foreach ($rows as $row) {
            if (blank($row['code'] ?? null)) {
                continue;
            }
            $distances[] = array_filter([
                'code' => trim($row['code']),
                'label' => trim($row['label']),
                'price_inr' => (int) $row['price_inr'],
                'capacity' => filled($row['capacity'] ?? null) ? (int) $row['capacity'] : null,
            ], fn ($v) => $v !== null);
        }

        return $distances;
    }

    /** @return list<array<string, mixed>> */
    private static function ageBands(array $rows): array
    {
        $bands = [];

        foreach ($rows as $row) {
            if (blank($row['label'] ?? null)) {
                continue;
            }
            $bands[] = ['label' => trim($row['label']), 'min' => (int) $row['min'], 'max' => (int) $row['max']];
        }

        return $bands;
    }

    /** @return list<array<string, mixed>> */
    private static function fields(array $show, array $custom): array
    {
        $fields = [];

        foreach (self::STANDARD_FIELDS as $key => $definition) {
            if (! empty($show[$key])) {
                $fields[] = $definition;
            }
        }

        $taken = array_column($fields, 'key');
        foreach ($custom as $row) {
            if (blank($row['label'] ?? null)) {
                continue;
            }
            $key = filled($row['key'] ?? null) ? $row['key'] : Str::limit(Str::snake(preg_replace('/[^A-Za-z0-9 ]+/', ' ', $row['label'])), 40, '');
            $key = preg_replace('/^[^a-z]+/', '', $key) ?: 'question';
            for ($base = $key, $n = 2; in_array($key, $taken, true); $n++) {
                $key = "{$base}_{$n}";
            }
            $taken[] = $key;

            $type = $row['type'] ?? 'text';
            $field = ['key' => $key, 'label' => trim($row['label']), 'type' => $type, 'required' => (bool) ($row['required'] ?? false)];
            if (in_array($type, ['select', 'radio', 'checkbox'], true) && ($options = self::list($row['options'] ?? ''))) {
                $field['options'] = $options;
            }
            $fields[] = $field;
        }

        return $fields;
    }

    /** @return list<array{q: string, a: string}> */
    private static function faq(array $rows): array
    {
        $faq = [];

        foreach ($rows as $row) {
            if (filled($row['q'] ?? null)) {
                $faq[] = ['q' => trim($row['q']), 'a' => trim($row['a'])];
            }
        }

        return $faq;
    }

    /** @return list<string> */
    private static function list(?string $csv): array
    {
        return array_values(array_unique(array_filter(array_map('trim', explode(',', (string) $csv)), 'strlen')));
    }

    private static function sameField(array $standard, array $field): bool
    {
        return ($field['label'] ?? null) === $standard['label']
            && ($field['type'] ?? null) === $standard['type']
            && (bool) ($field['required'] ?? false) === $standard['required']
            && ($field['options'] ?? []) === ($standard['options'] ?? []);
    }
}
