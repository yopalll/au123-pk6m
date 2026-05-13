<?php

use App\Models\User;
use Illuminate\Support\Facades\Config;

beforeEach(function () {
    Config::set('database.default', 'mysql');
    Config::set('database.connections.mysql', [
        'driver'    => 'mysql',
        'host'      => '127.0.0.1',
        'port'      => '3306',
        'database'  => 'viygo-db3',
        'username'  => 'root',
        'password'  => 'vitermk',
        'charset'   => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix'    => '',
        'strict'    => true,
        'engine'    => null,
    ]);
    \DB::purge('mysql');
    \DB::reconnect('mysql');
});

test('admin login page renders', function () {
    $response = $this->get('/admin/login');
    $response->assertOk();
    $response->assertSee('Email address');
});

test('admin dashboard loads after login', function () {
    $admin = User::where('role', 'admin')->where('is_active', true)->first();
    expect($admin)->not->toBeNull();
    $response = $this->actingAs($admin)->get('/admin');
    $response->assertOk();
});

test('admin salons resource loads', function () {
    $admin = User::where('role', 'admin')->where('is_active', true)->first();
    $response = $this->actingAs($admin)->get('/admin/salons');
    $response->assertOk();
    $response->assertDontSee('Whoops');
});

test('admin kategoris resource loads', function () {
    $admin = User::where('role', 'admin')->where('is_active', true)->first();
    $response = $this->actingAs($admin)->get('/admin/kategoris');
    $response->assertOk();
    $response->assertDontSee('Whoops');
});

test('admin kotas resource loads', function () {
    $admin = User::where('role', 'admin')->where('is_active', true)->first();
    $response = $this->actingAs($admin)->get('/admin/kotas');
    $response->assertOk();
    $response->assertDontSee('Whoops');
});

test('admin services resource loads', function () {
    $admin = User::where('role', 'admin')->where('is_active', true)->first();
    $response = $this->actingAs($admin)->get('/admin/services');
    $response->assertOk();
    $response->assertDontSee('Whoops');
});

test('admin orders resource loads', function () {
    $admin = User::where('role', 'admin')->where('is_active', true)->first();
    $response = $this->actingAs($admin)->get('/admin/orders');
    $response->assertOk();
    $response->assertDontSee('Whoops');
});

test('admin reviews resource loads', function () {
    $admin = User::where('role', 'admin')->where('is_active', true)->first();
    $response = $this->actingAs($admin)->get('/admin/reviews');
    $response->assertOk();
    $response->assertDontSee('Whoops');
});

test('admin promos resource loads', function () {
    $admin = User::where('role', 'admin')->where('is_active', true)->first();
    $response = $this->actingAs($admin)->get('/admin/promos');
    $response->assertOk();
    $response->assertDontSee('Whoops');
});

test('admin users resource loads', function () {
    $admin = User::where('role', 'admin')->where('is_active', true)->first();
    $response = $this->actingAs($admin)->get('/admin/users');
    $response->assertOk();
    $response->assertDontSee('Whoops');
});

test('admin mitra applications resource loads', function () {
    $admin = User::where('role', 'admin')->where('is_active', true)->first();
    $response = $this->actingAs($admin)->get('/admin/mitra-applications');
    $response->assertOk();
    $response->assertDontSee('Whoops');
});

test('non-admin is denied admin panel', function () {
    $customer = User::where('role', 'customer')->first();
    $response = $this->actingAs($customer)->get('/admin');
    $response->assertForbidden();
});
