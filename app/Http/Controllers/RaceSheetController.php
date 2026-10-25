<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Registration;
use App\RaceSheets\HeatRaceSheetBuilder;
use App\RaceSheets\RaceSheet;
use App\RaceSheets\ResolvedConfig;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Race sheets: heats grouped by distance, age band and gender (or the event's
 * race_sheet.group_by), as a page, CSV and printable A4 PDF. Mass-start events
 * without a race_sheet config get start lists with the same grouping.
 */
class RaceSheetController extends Controller
{
    public function __construct(private HeatRaceSheetBuilder $builder) {}

    public function show(Event $event): View
    {
        return view('racesheets.show', $this->data($event));
    }

    public function csv(Event $event): StreamedResponse
    {
        ['sheet' => $sheet, 'entrants' => $entrants, 'groupKeys' => $groupKeys] = $this->data($event);

        return response()->streamDownload(function () use ($sheet, $entrants, $groupKeys) {
            $out = fopen('php://output', 'w');
            fputcsv($out, [...array_map(fn (string $k) => $this->groupHeading($k), $groupKeys), 'Heat', 'Position', 'Pass no', 'Name', 'Age', 'Checked in']);

            foreach ($sheet->heats as $heat) {
                foreach ($heat->registrationIds as $i => $id) {
                    $r = $entrants[$id];
                    fputcsv($out, array_map($this->csvCell(...), [
                        ...array_map(fn (string $k) => $heat->group[$k] ?? '', $groupKeys),
                        $heat->number,
                        $i + 1,
                        $r->pass_no,
                        $r->participant_name,
                        $r->answers['age'] ?? '',
                        $r->checked_in_at ? 'yes' : 'no',
                    ]));
                }
            }

            fclose($out);
        }, $event->slug.'-race-sheet.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function pdf(Event $event): Response
    {
        return Pdf::loadView('racesheets.pdf', $this->data($event))
            ->setPaper('a4', 'portrait')
            ->download($event->slug.'-race-sheet.pdf');
    }

    /**
     * @return array{event: Event, sheet: RaceSheet, entrants: Collection<int, Registration>, groupKeys: list<string>, isStartList: bool, headings: array<string, string>}
     */
    private function data(Event $event): array
    {
        $isStartList = ! is_array(ResolvedConfig::for($event)['race_sheet'] ?? null);
        $sheet = $this->builder->buildOrStartList($event);

        $ids = collect($sheet->heats)->flatMap(fn ($heat) => $heat->registrationIds)->all();
        $groupKeys = $sheet->config->groupBy;

        return [
            'event' => $event,
            'sheet' => $sheet,
            'entrants' => Registration::query()->whereKey($ids)->get()->keyBy('id'),
            'groupKeys' => $groupKeys,
            'isStartList' => $isStartList,
            'headings' => collect($groupKeys)->mapWithKeys(fn (string $k) => [$k => $this->groupHeading($k)])->all(),
        ];
    }

    private function groupHeading(string $key): string
    {
        return match ($key) {
            'distance' => __('Distance'),
            'age_band' => __('Age band'),
            'gender' => __('Gender'),
            default => __(ucfirst(str_replace('_', ' ', $key))),
        };
    }

    /** Neutralise spreadsheet formulas (CSV injection). */
    private function csvCell(mixed $value): string
    {
        $value = (string) $value;

        return $value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true) ? "'".$value : $value;
    }
}
