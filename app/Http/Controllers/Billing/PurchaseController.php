<?php

namespace App\Http\Controllers\Billing;

use App\Billing\Catalog;
use App\Billing\Checkout;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Purchase;
use App\Saas\Plans;
use App\Support\Contact;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Throwable;

/**
 * Buy page for participant packs and per-event add-ons (Session P1). Every "Buy a participant
 * pack" / "Unlock" / "Add Custom domain" button lands here: it pays online on the platform
 * gateway when the item has a price and checkout is ready, else it records a request that
 * Indefine follows up. Never a dead end.
 */
class PurchaseController extends Controller
{
    public function __construct(private Checkout $checkout) {}

    public function show(Request $request, string $sku): View|RedirectResponse
    {
        abort_unless(Catalog::exists($sku), 404);
        $events = Event::query()->orderByDesc('date')->get(['id', 'org_id', 'name', 'date', 'slug']);
        $event = $request->filled('event') ? Event::findOrFail($request->integer('event')) : $events->first();

        if ($event === null) {
            return redirect()->route('organiser.events.create')->with('status', 'create-event-first');
        }

        $item = Catalog::item($sku, $event, $request->integer('participants') ?: null) ?? Catalog::item($sku, $event);
        abort_if($item === null, 404);

        return view('billing.show', [
            'org' => $request->user()->org,
            'event' => $event,
            'events' => $events,
            'sku' => $sku,
            'item' => $item,
            'sizes' => $sku === Catalog::PACK ? Catalog::packSizes() : [],
            'owned' => Catalog::owned($sku, $event),
            'online' => $item['price_inr'] !== null && Checkout::ready(),
            'history' => Purchase::query()->where('event_id', $event->id)->where('sku', $sku)->latest('id')->limit(5)->get(),
            'includedIn' => $this->includedIn($sku),
        ]);
    }

    public function store(Request $request, string $sku): View|RedirectResponse
    {
        abort_unless(Catalog::exists($sku), 404);
        $data = $request->validate([
            'event' => ['required', 'integer'],
            'participants' => ['nullable', 'integer', 'min:1'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);
        $event = Event::findOrFail($data['event']);
        $item = Catalog::item($sku, $event, $data['participants'] ?? null);
        abort_if($item === null, 422, 'Unknown pack size');

        if (Catalog::owned($sku, $event)) {
            return redirect()->route('billing.show', ['sku' => $sku, 'event' => $event->id])->with('billing', 'owned');
        }

        $purchase = Purchase::create([
            'event_id' => $event->id,
            'user_id' => $request->user()->id,
            'sku' => $sku,
            'quantity' => $item['quantity'],
            'amount_paise' => $item['price_inr'] !== null ? $item['price_inr'] * 100 : null,
            'status' => Purchase::PENDING,
            'note' => $data['note'] ?? null,
        ]);

        if ($item['price_inr'] === null || ! Checkout::ready()) {
            $purchase->forceFill(['status' => Purchase::REQUESTED])->save();
            $this->notifyIndefine($purchase, $item, $event);

            return redirect()->route('billing.show', ['sku' => $sku, 'event' => $event->id, 'participants' => $item['quantity']])
                ->with('billing', 'requested');
        }

        try {
            $order = $this->checkout->start($purchase, $item['name'].' · '.$event->name);
        } catch (Throwable $e) {
            report($e);
            $purchase->forceFill(['status' => Purchase::FAILED, 'note' => mb_substr('Checkout could not start: '.$e->getMessage(), 0, 500)])->save();

            return redirect()->route('billing.show', ['sku' => $sku, 'event' => $event->id])->with('billing', 'gateway-error');
        }

        return view('billing.checkout', ['purchase' => $purchase, 'order' => $order, 'item' => $item, 'event' => $event]);
    }

    /** Back from the gateway: confirm with the provider (the webhook also does this). */
    public function return(Purchase $purchase): View
    {
        $this->checkout->refresh($purchase);
        $purchase->refresh();

        return view('billing.return', ['purchase' => $purchase, 'event' => $purchase->event]);
    }

    /** Email Indefine the request (platform mailer); the request is stored either way. */
    private function notifyIndefine(Purchase $purchase, array $item, Event $event): void
    {
        $org = $event->org;
        $lines = [
            "Purchase request #{$purchase->id}",
            "Org: {$org->name} ({$org->slug}), plan: ".($org->plan ?? 'none'),
            "Event: {$event->name} ({$event->slug})",
            "Item: {$item['name']}".($item['price_inr'] !== null ? ' at '.Plans::inr($item['price_inr']) : ' (to quote)'),
            'From: '.($purchase->user?->name ?? '?').' <'.($purchase->user?->email ?? '?').'>',
            'Note: '.($purchase->note ?: '-'),
            'Grant: '.($purchase->sku === Catalog::PACK
                ? "php artisan plans:pack {$org->slug} {$event->slug} {$item['quantity']}"
                : "php artisan plans:event-addon {$org->slug} {$event->slug} {$purchase->sku}"),
        ];

        try {
            Mail::raw(implode("\n", $lines), fn ($m) => $m->to(Contact::email())->subject("EventFlow request: {$item['name']} for {$event->name}"));
        } catch (Throwable $e) {
            Log::warning('Could not email the purchase request to Indefine', ['purchase' => $purchase->id, 'error' => $e->getMessage()]);
        }
    }

    /** Plans that include $sku for every event, for "or move to Club Season" copy. */
    private function includedIn(string $sku): array
    {
        if ($sku === Catalog::PACK) {
            return [];
        }
        $addon = Plans::eventAddons()[$sku];

        return collect(Plans::all())
            ->filter(fn ($p) => filled($addon['module'] ?? null) ? in_array($addon['module'], $p['modules'], true) : ($p['custom_domain'] ?? false))
            ->filter(fn ($p) => $p['self_serve'])
            ->pluck('name')->all();
    }
}
