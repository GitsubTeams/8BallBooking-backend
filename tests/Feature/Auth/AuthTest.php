<?php

use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

it('registers a new user and issues a token', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'Budi Santoso',
        'email' => 'budi@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'device_name' => 'pest',
    ]);

    $response->assertCreated()
        ->assertJson([
            'success' => true,
            'message' => 'Registration successful.',
        ])
        ->assertJsonPath('data.token_type', 'Bearer')
        ->assertJsonPath('data.user.email', 'budi@example.com')
        ->assertJsonPath('data.user.roles', ['user'])
        ->assertJsonStructure([
            'success',
            'message',
            'data' => ['user' => ['id', 'name', 'email', 'roles'], 'token', 'token_type'],
        ]);

    $this->assertDatabaseHas('users', ['email' => 'budi@example.com']);
    expect(User::where('email', 'budi@example.com')->first()->hasRole('user'))->toBeTrue();
});

it('rejects registration with invalid payload using the response envelope', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'name' => '',
        'email' => 'not-an-email',
        'password' => 'short',
    ]);

    $response->assertStatus(422)
        ->assertJson(['success' => false, 'message' => 'Validation failed.'])
        ->assertJsonStructure(['success', 'message', 'data' => ['name', 'email', 'password']]);
});

it('logs in with valid credentials', function () {
    User::factory()->create([
        'email' => 'owner@example.com',
        'password' => 'password',
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'owner@example.com',
        'password' => 'password',
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure(['data' => ['user', 'token', 'token_type']]);
});

it('rejects login with invalid credentials', function () {
    User::factory()->create([
        'email' => 'owner@example.com',
        'password' => 'password',
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'owner@example.com',
        'password' => 'wrong-password',
    ]);

    $response->assertStatus(422)
        ->assertJson(['success' => false, 'message' => 'Validation failed.']);
});

it('returns the authenticated user', function () {
    $user = User::factory()->create();
    $user->assignRole('user');

    $response = $this->actingAs($user, 'api')->getJson('/api/v1/auth/me');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.email', $user->email);
});

it('requires authentication to access protected routes', function () {
    $this->getJson('/api/v1/auth/me')
        ->assertUnauthorized()
        ->assertJson(['success' => false, 'message' => 'Unauthenticated.']);
});

it('revokes the current token on logout', function () {
    $user = User::factory()->create();

    $token = $user->createToken('pest')->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/auth/logout')
        ->assertOk()
        ->assertJsonPath('success', true);

    expect($user->tokens()->count())->toBe(0);
});
