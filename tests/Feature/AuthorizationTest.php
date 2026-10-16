<?php

use App\Models\User;

test('guest cannot open the admin terminal', function () {
    $this->get('/admin')->assertRedirect('/login');
});

test('customer cannot open the admin terminal', function () {
    $user = User::factory()->create(['role' => 'customer']);

    $this->actingAs($user)->get('/admin')->assertForbidden();
});

test('manager can open the admin terminal', function () {
    $user = User::factory()->manager()->create();

    $this->actingAs($user)->get('/admin')->assertOk();
});
