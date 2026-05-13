<?php

use App\Models\User;

test('owner login page renders', function () {
    $response = $this->get('/owner/login');
    $response->assertOk();
});

test('owner can access panel after login', function () {
    $owner = User::where('role', 'salon_owner')->where('is_active', true)->first();
    $response = $this->actingAs($owner)->get('/owner');
    $response->assertOk();
});

test('owner salons list loads', function () {
    $owner = User::where('role', 'salon_owner')->where('is_active', true)->first();
    $response = $this->actingAs($owner)->get('/owner/salons');
    $response->assertOk();
});

test('owner services list loads', function () {
    $owner = User::where('role', 'salon_owner')->where('is_active', true)->first();
    $response = $this->actingAs($owner)->get('/owner/services');
    $response->assertOk();
});

test('owner staff list loads', function () {
    $owner = User::where('role', 'salon_owner')->where('is_active', true)->first();
    $response = $this->actingAs($owner)->get('/owner/staff');
    $response->assertOk();
});

test('owner orders list loads', function () {
    $owner = User::where('role', 'salon_owner')->where('is_active', true)->first();
    $response = $this->actingAs($owner)->get('/owner/orders');
    $response->assertOk();
});

test('owner salon images list loads', function () {
    $owner = User::where('role', 'salon_owner')->where('is_active', true)->first();
    $response = $this->actingAs($owner)->get('/owner/salon-images');
    $response->assertOk();
});

test('customer cannot access owner panel', function () {
    $customer = User::where('role', 'customer')->first();
    $response = $this->actingAs($customer)->get('/owner');
    $response->assertForbidden();
});

test('admin cannot access owner panel', function () {
    $admin = User::where('role', 'admin')->first();
    $response = $this->actingAs($admin)->get('/owner');
    $response->assertForbidden();
});

test('owner can view own salon detail (View page)', function () {
    $owner = User::where('role', 'salon_owner')->where('is_active', true)->first();
    $salon = $owner->salons()->first();
    expect($salon)->not->toBeNull();

    $response = $this->actingAs($owner)->get("/owner/salons/{$salon->slug}");
    $response->assertOk();
});

test('owner can edit own salon (Edit page renders)', function () {
    $owner = User::where('role', 'salon_owner')->where('is_active', true)->first();
    $salon = $owner->salons()->first();
    expect($salon)->not->toBeNull();

    $response = $this->actingAs($owner)->get("/owner/salons/{$salon->slug}/edit");
    $response->assertOk();
});
