<?php

use App\Enums\Role;
use App\Models\Org;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;

function signupPayload(array $overrides = []): array
{
    return [
        'organization_name' => 'Sunrise Sports Club',
        'name' => 'Asha Rao',
        'email' => 'asha@sunrise.test',
        'phone' => '+91 98450 12345',
        'password' => 'password',
        'password_confirmation' => 'password',
        ...$overrides,
    ];
}

test('registration screen can be rendered', function () {
    $this->get('/register')
        ->assertOk()
        ->assertSee('Organisation name');
});

test('signup creates an org with a slug and makes the organiser its owner', function () {
    Notification::fake();

    $response = $this->post('/register', signupPayload());

    $this->assertAuthenticated();
    $response->assertRedirect(route('account.plan', ['welcome' => 1], absolute: false));

    $org = Org::sole();
    $user = User::sole();

    expect($org->slug)->toBe('sunrise-sports-club')
        ->and($org->phone)->toBe('+91 98450 12345')
        ->and($user->org_id)->toBe($org->id)
        ->and($user->role)->toBe(Role::Owner)
        ->and($user->hasVerifiedEmail())->toBeFalse();

    Notification::assertSentTo($user, VerifyEmail::class);
});

test('a second org with the same name gets a suffixed slug', function () {
    Org::factory()->create(['name' => 'Sunrise Sports Club', 'slug' => 'sunrise-sports-club']);

    $this->post('/register', signupPayload());

    expect(Org::latest('id')->first()->slug)->toBe('sunrise-sports-club-2');
});

test('signup requires an organization name and rejects a malformed phone', function () {
    $this->post('/register', signupPayload(['organization_name' => '', 'phone' => 'call me']))
        ->assertSessionHasErrors(['organization_name', 'phone']);

    $this->assertGuest();
    expect(Org::count())->toBe(0);
});

test('unverified organisers are sent to verify their email before the dashboard', function () {
    $this->post('/register', signupPayload());

    $this->get('/dashboard')->assertRedirect(route('verification.notice'));
});
