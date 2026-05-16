<?php

use App\Models\User;

test('register form renders', function () {
    $response = $this->get('/register');
    $response->assertOk();
});

test('user can register with single-word name', function () {
    $email = 'pestreg_' . uniqid() . '@example.com';

    $response = $this->post('/register', [
        'name'                  => 'Madonna',
        'email'                 => $email,
        'password'              => 'PassWord123!',
        'password_confirmation' => 'PassWord123!',
    ]);

    $response->assertRedirect();

    $user = User::where('email', $email)->first();
    expect($user)->not->toBeNull()
        ->and($user->first_name)->toBe('Madonna')
        ->and($user->last_name)->toBeNull()
        ->and($user->role)->toBe('customer');

    $user->forceDelete();
});

test('user can register with first + last name', function () {
    $email = 'pestreg2_' . uniqid() . '@example.com';

    $response = $this->post('/register', [
        'name'                  => 'John Doe Smith',
        'email'                 => $email,
        'password'              => 'PassWord123!',
        'password_confirmation' => 'PassWord123!',
    ]);

    $response->assertRedirect();

    $user = User::where('email', $email)->first();
    expect($user)->not->toBeNull()
        ->and($user->first_name)->toBe('John')
        ->and($user->last_name)->toBe('Doe Smith')
        ->and($user->role)->toBe('customer');

    $user->forceDelete();
});

test('customer login redirects to akun bookings via dashboard', function () {
    $customer = User::where('role', 'customer')->first();
    $response = $this->actingAs($customer)->get('/dashboard');
    $response->assertRedirect(route('akun.bookings'));
});

test('salon owner login redirects to owner panel via dashboard', function () {
    $owner = User::where('role', 'salon_owner')->where('is_active', true)->first();
    $response = $this->actingAs($owner)->get('/dashboard');
    $response->assertRedirect('/owner');
});

test('admin login redirects to admin panel via dashboard', function () {
    $admin = User::where('role', 'admin')->first();
    $response = $this->actingAs($admin)->get('/dashboard');
    $response->assertRedirect('/admin');
});
