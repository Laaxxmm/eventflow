<?php

use Illuminate\Cache\RateLimiter;

test('login, signup and password reset allow 5 attempts per minute per IP', function (string $uri, array $payload) {
    foreach (range(1, 5) as $i) {
        expect($this->post($uri, $payload)->status())->not->toBe(429);
    }

    $this->post($uri, $payload)->assertStatus(429);
})->with([
    'login' => ['/login', ['email' => 'nobody@example.com', 'password' => 'wrong']],
    'signup' => ['/register', ['organization_name' => '', 'email' => 'bad']],
    'forgot password' => ['/forgot-password', ['email' => 'nobody@example.com']],
    'reset password' => ['/reset-password', ['token' => 'x', 'email' => 'nobody@example.com', 'password' => 'password', 'password_confirmation' => 'password']],
]);

test('the limit is per IP', function () {
    foreach (range(1, 6) as $i) {
        $this->post('/login', ['email' => 'nobody@example.com', 'password' => 'wrong']);
    }

    $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.2'])
        ->post('/login', ['email' => 'nobody@example.com', 'password' => 'wrong'])
        ->assertSessionHasErrors('email');
});

test('a registration limiter is defined for public registration routes', function () {
    expect(app(RateLimiter::class)->limiter('registration'))->not->toBeNull();
});
