<?php

namespace App\Landing;

use Carbon\CarbonImmutable;
use Illuminate\Validation\Rule;

/**
 * The public registration form, generated from an event's fields, distances and age_bands.
 * The same object renders the form and builds its server-side validation rules.
 *
 * Core inputs: participant_name, email, phone, distance, and dob whenever the event has
 * age bands or an "age" field. Age is never typed in: it is computed from dob on race day
 * and stored as answers.age (plus answers.age_band). Other fields post as answers[<key>].
 */
final class RegistrationForm
{
    public const PHONE_PATTERN = '/^\+?[0-9][0-9 \-]{6,18}$/';

    public function __construct(public readonly EventConfig $config) {}

    public function asksDob(): bool
    {
        return $this->config->ageBands !== [] || $this->ageField() !== null;
    }

    public function dobRequired(): bool
    {
        return $this->config->ageBands !== [] || ($this->ageField()?->required ?? false);
    }

    /**
     * Fields shown as answers[<key>] (the computed "age" field excluded).
     *
     * @return list<FormField>
     */
    public function fields(): array
    {
        return array_values(array_filter(
            $this->config->fields,
            fn (FormField $f) => ! ($f->key === 'age' && $this->asksDob()),
        ));
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $rules = [
            'participant_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:20', 'regex:'.self::PHONE_PATTERN],
            'distance' => ['required', 'string', Rule::in(array_map(fn (Distance $d) => $d->code, $this->config->distances))],
        ];

        if ($this->asksDob()) {
            $rules['dob'] = [$this->dobRequired() ? 'required' : 'nullable', 'date_format:Y-m-d', 'before:today', 'after:1900-01-01'];
        }

        foreach ($this->fields() as $field) {
            $rules = [...$rules, ...$this->fieldRules($field)];
        }

        return $rules;
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        $names = ['participant_name' => __('full name'), 'dob' => __('date of birth')];

        foreach ($this->fields() as $field) {
            $names["answers.{$field->key}"] = $field->label;
            $names["answers.{$field->key}.*"] = $field->label;
        }

        return $names;
    }

    public function distance(string $code): Distance
    {
        foreach ($this->config->distances as $distance) {
            if ($distance->code === $code) {
                return $distance;
            }
        }

        throw new \InvalidArgumentException("Unknown distance {$code}");
    }

    /** Completed years on race day. */
    public function ageOnRaceDay(string $dob): int
    {
        $raceDay = $this->config->date->setTimezone(ConfigCompiler::TIMEZONE)->startOfDay();

        return (int) CarbonImmutable::parse($dob, ConfigCompiler::TIMEZONE)->diffInYears($raceDay);
    }

    public function bandFor(int $age): ?AgeBand
    {
        foreach ($this->config->ageBands as $band) {
            if ($age >= $band->min && $age <= $band->max) {
                return $band;
            }
        }

        return null;
    }

    /** "12–99", for messages. */
    public function ageRange(): string
    {
        $bands = $this->config->ageBands;

        return min(array_map(fn (AgeBand $b) => $b->min, $bands)).'–'.max(array_map(fn (AgeBand $b) => $b->max, $bands));
    }

    /** @return array<string, mixed> */
    private function fieldRules(FormField $field): array
    {
        $key = "answers.{$field->key}";
        $presence = $field->required ? 'required' : 'nullable';

        return match ($field->type) {
            'email' => [$key => [$presence, 'email', 'max:255']],
            'tel' => [$key => [$presence, 'string', 'max:20', 'regex:'.self::PHONE_PATTERN]],
            'number' => [$key => [$presence, 'numeric']],
            'date' => [$key => [$presence, 'date_format:Y-m-d']],
            'textarea' => [$key => [$presence, 'string', 'max:2000']],
            'select', 'radio' => [$key => [$presence, 'string', Rule::in($field->options)]],
            'checkbox' => $field->options === []
                ? [$key => [$field->required ? 'accepted' : 'nullable', 'boolean']]
                : [$key => [$presence, 'array', $field->required ? 'min:1' : 'min:0'], "{$key}.*" => ['string', Rule::in($field->options)]],
            default => [$key => [$presence, 'string', 'max:255']],
        };
    }
}
