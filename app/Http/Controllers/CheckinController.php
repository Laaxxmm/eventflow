<?php

namespace App\Http\Controllers;

use App\Models\Registration;
use App\Passes\SignedPassQr;
use App\Payments\PaymentStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CheckinController extends Controller
{
    public function index(Request $request): View
    {
        $this->ensureVolunteer($request);

        return view('checkin.index', [
            'result' => session('checkin'),
        ]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $this->ensureVolunteer($request);

        $data = $request->validate([
            'payload' => ['nullable', 'string', 'max:512'],
            'pass_no' => ['nullable', 'string', 'max:32'],
        ]);

        $passNo = null;
        if (filled($data['payload'] ?? null)) {
            $passNo = SignedPassQr::verify((string) $data['payload']);
            if ($passNo === null) {
                return $this->fail($request, 'This QR does not match a signed EventFlow pass.');
            }
        } elseif (filled($data['pass_no'] ?? null)) {
            $passNo = strtoupper(trim((string) $data['pass_no']));
            $pattern = (string) config('eventflow.pass.pattern');
            if (preg_match($pattern, $passNo) !== 1) {
                return $this->fail($request, 'Enter a pass number like EF-1A2B3C4D5E6F.');
            }
        } else {
            return $this->fail($request, 'Scan a pass or enter the pass number.');
        }

        $result = DB::transaction(function () use ($request, $passNo) {
            $query = Registration::query()->where('pass_no', $passNo)->lockForUpdate();

            if ($request->user()->org_id) {
                $orgId = $request->user()->org_id;
                $query->whereHas('event', fn ($q) => $q->where('org_id', $orgId));
            }

            $registration = $query->first();

            if (! $registration) {
                return ['ok' => false, 'message' => 'No paid registration matches that pass.'];
            }

            if ($registration->status !== PaymentStatus::Paid) {
                return ['ok' => false, 'message' => 'This registration is not paid yet.'];
            }

            if ($registration->checked_in_at !== null) {
                return [
                    'ok' => true,
                    'duplicate' => true,
                    'message' => 'Already checked in at '.$registration->checked_in_at->timezone('Asia/Kolkata')->format('d M Y, h:i A').' IST.',
                    'registration' => $registration,
                ];
            }

            $registration->forceFill([
                'checked_in_at' => now(),
                'checked_in_by' => $request->user()->id,
            ])->save();

            return [
                'ok' => true,
                'duplicate' => false,
                'message' => 'Checked in.',
                'registration' => $registration->fresh(),
            ];
        });

        if (! ($result['ok'] ?? false)) {
            return $this->fail($request, (string) $result['message']);
        }

        /** @var Registration $registration */
        $registration = $result['registration'];
        $payload = [
            'ok' => true,
            'duplicate' => (bool) $result['duplicate'],
            'message' => $result['message'],
            'pass_no' => $registration->pass_no,
            'participant_name' => $registration->participant_name,
            'checked_in_at' => $registration->checked_in_at?->timezone('Asia/Kolkata')->format('d M Y, h:i A'),
        ];

        if ($request->expectsJson() || $request->filled('payload')) {
            return response()->json($payload);
        }

        return redirect()->route('checkin.index')->with('checkin', $payload);
    }

    private function ensureVolunteer(Request $request): void
    {
        $role = (string) $request->user()?->role;
        abort_unless(in_array($role, ['volunteer', 'staff', 'owner'], true), 403);
    }

    private function fail(Request $request, string $message): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson() || $request->filled('payload')) {
            return response()->json(['ok' => false, 'message' => $message], 422);
        }

        return redirect()->route('checkin.index')->with('checkin', [
            'ok' => false,
            'message' => $message,
        ]);
    }
}
