<?php

use App\Landing\EventStatus;
use App\Models\Event;
use App\Models\EventAddon;
use App\Models\Org;
use App\Models\User;
use Tests\Feature\Reports\ReportFixtures;

/** A published event on an org without a plan (so add-ons are locked), and its owner. */
function overviewEvent(array $attributes = []): Event
{
    $event = ReportFixtures::event(Org::factory()->create(), ['template' => 'gun-lap']);
    $event->update(['template_id' => 'gun-lap', ...$attributes]);

    return $event->fresh();
}

it('shows the overview with a live preview, public URL and every action', function () {
    $event = overviewEvent();
    $owner = ReportFixtures::owner($event);
    $publicUrl = route('landing.show', [$event->org->slug, $event->slug]);

    $this->actingAs($owner)->get(route('organiser.events.show', $event))
        ->assertOk()
        ->assertSee('Live preview')
        ->assertSee('src="'.route('organiser.events.preview', $event).'"', false)
        ->assertSee('data-device="desktop"', false)->assertSee('data-device="mobile"', false)
        ->assertSee('href="'.$publicUrl.'"', false)->assertSee('Open public URL')
        ->assertSee('Change template')->assertSee('Stadium Split')
        ->assertSee('href="'.route('organiser.events.edit', $event).'"', false)->assertSee('Edit content')
        ->assertSee('Unpublish')
        ->assertSee('Race sheet download')->assertSee('₹599 · locked')
        ->assertSee('Certificates')->assertSee('₹799 · locked');
});

it('renders the event page in the preview with its template and brand colours, drafts included', function () {
    $event = overviewEvent(['status' => EventStatus::Draft]);
    $owner = ReportFixtures::owner($event);

    $this->actingAs($owner)->get(route('organiser.events.preview', $event))
        ->assertOk()
        ->assertSee('data-template="gun-lap"', false)
        ->assertSee('Fixture Skating Open')
        ->assertSee('Gun Lap · preview')
        ->assertSee('--ri-heat', false); // theme style from the event's branding

    // ?template= tries another layout without saving it.
    $this->actingAs($owner)->get(route('organiser.events.preview', [$event, 'template' => 'night-circuit']))
        ->assertOk()->assertSee('data-template="night-circuit"', false);
    expect($event->fresh()->template_id)->toBe('gun-lap');

    // The public page still 404s for a draft, and the overview says so.
    $this->get(route('landing.show', [$event->org->slug, $event->slug]))->assertNotFound();
    $this->actingAs($owner)->get(route('organiser.events.show', $event))
        ->assertSee('Not live yet: only you can see this preview.')
        ->assertDontSee('Open public URL')
        ->assertSee('>Publish</button>', false);
});

it('applies a new template to the event', function () {
    $event = overviewEvent();
    $owner = ReportFixtures::owner($event);

    $this->actingAs($owner)->patch(route('organiser.events.template', $event), ['template' => 'heritage-crest'])
        ->assertRedirect(route('organiser.events.show', $event));

    $event->refresh();
    expect($event->template_id)->toBe('heritage-crest')->and($event->config['template'])->toBe('heritage-crest');
    $this->get(route('landing.show', [$event->org->slug, $event->slug]))->assertOk()->assertSee('data-template="heritage-crest"', false);

    $this->actingAs($owner)->patch(route('organiser.events.template', $event), ['template' => 'comic-sans'])->assertSessionHasErrors('template');
});

it('publishes and unpublishes from the overview', function () {
    $event = overviewEvent();
    $owner = ReportFixtures::owner($event);
    $url = route('landing.show', [$event->org->slug, $event->slug]);

    $this->actingAs($owner)->patch(route('organiser.events.status', $event), ['status' => 'draft'])->assertRedirect();
    expect($event->fresh()->status)->toBe(EventStatus::Draft);
    $this->get($url)->assertNotFound();

    $this->actingAs($owner)->patch(route('organiser.events.status', $event), ['status' => 'published'])->assertRedirect();
    expect($event->fresh()->status)->toBe(EventStatus::Published);
    $this->get($url)->assertOk();

    $this->actingAs($owner)->patch(route('organiser.events.status', $event), ['status' => 'closed'])->assertSessionHasErrors('status');
});

it('keeps the overview and preview to the event\'s own org and away from volunteers', function () {
    $event = overviewEvent(['status' => EventStatus::Draft]);
    $stranger = User::factory()->create(); // owner of another org
    $volunteer = User::factory()->for($event->org)->volunteer()->create();

    foreach (['organiser.events.show', 'organiser.events.preview'] as $route) {
        $this->actingAs($stranger)->get(route($route, $event))->assertNotFound();
        $this->actingAs($volunteer)->get(route($route, $event))->assertRedirect('/checkin');
    }
    $this->actingAs($stranger)->patch(route('organiser.events.status', $event), ['status' => 'published'])->assertNotFound();
    expect($event->fresh()->status)->toBe(EventStatus::Draft);
});

it('shows add-ons as included by the plan or bought for the event', function () {
    $event = overviewEvent();
    $owner = ReportFixtures::owner($event);
    EventAddon::withoutGlobalScopes()->create(['org_id' => $event->org_id, 'event_id' => $event->id, 'addon' => 'race_sheet']);

    $this->actingAs($owner)->get(route('organiser.events.show', $event))
        ->assertSee('Added to this event')->assertSee('₹799 · locked');

    $planned = ReportFixtures::event(); // org pass includes every module
    $html = $this->actingAs(ReportFixtures::owner($planned))->get(route('organiser.events.show', $planned))
        ->assertSee('Included in your plan')->getContent();
    // Race sheet and certificates come with the modules; the custom domain is a plan feature (Session P1).
    foreach (['race_sheet', 'certificates'] as $key) {
        preg_match('#data-event-addon="'.$key.'".*?</li>#s', $html, $row);
        expect($row[0])->toContain('Included in your plan')->not->toContain('locked');
    }
});

it('links the overview from the events list, the dashboard and the published page', function () {
    $event = overviewEvent();
    $owner = ReportFixtures::owner($event);
    $owner->forceFill(['email_verified_at' => now()])->save();
    $href = 'href="'.route('organiser.events.show', $event).'"';

    $this->actingAs($owner)->get(route('organiser.events.index'))->assertSee($href, false);
    $this->actingAs($owner)->get(route('dashboard'))->assertSee($href, false);
    $this->actingAs($owner)->get(route('organiser.events.published', $event))->assertSee($href, false);
});
