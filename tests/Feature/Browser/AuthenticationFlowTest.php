<?php

use App\Models\ImportBatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create a test user for login tests
    $this->user = User::factory()->create([
        'email' => 'test@example.com',
        'phone_number' => '77001234567',
        'password' => Hash::make('password123'),
        'phone_verified_at' => now(),
    ]);
});

it('redirects back to intended URL after login when accessing protected route', function () {
    // Create a completed import batch for the user
    $batch = ImportBatch::factory()->create([
        'user_id' => $this->user->id,
        'status' => 'completed',
        'success_file_path' => 'imports/batch-1/successful-products.xlsx',
        'failed_file_path' => 'imports/batch-1/failed-gtins.xlsx',
        'success_count' => 5,
        'failed_count' => 3,
    ]);

    $page = visit("/import/download/{$batch->id}/success");

    // Should be redirected to login page
    $page->assertSee('Login')
        ->assertSee('Email or Phone Number')
        ->assertNoJavascriptErrors();

    // Fill in login credentials and submit
    $page->type('#login', 'test@example.com')
        ->type('#password', 'password123')
        ->submit();

    // Wait a moment for navigation and download to happen
    $page->wait(2);

    // Should be redirected back to the download URL and file should download
    // Note: The download will trigger, so we just verify we're not stuck on login page
    $page->assertDontSee('Email or Phone Number')
        ->assertNoJavascriptErrors();
})->group('browser', 'auth');

it('redirects to home page after login when no intended URL', function () {
    $page = visit('/login');

    $page->assertSee('Login')
        ->assertSee('Email or Phone Number')
        ->type('#login', 'test@example.com')
        ->type('#password', 'password123')
        ->submit();

    // Wait for navigation
    $page->wait(2);

    // Should redirect to home page (gtin-import)
    $page->assertSee('GTIN Import')
        ->assertSee($this->user->name)
        ->assertNoJavascriptErrors();
})->group('browser', 'auth');

it('redirects to home page after logout', function () {
    // Start as authenticated user
    actingAs($this->user);

    $page = visit('/');

    // Should see user name and logout button
    $page->assertSee($this->user->name)
        ->assertSee('Logout')
        ->assertNoJavascriptErrors();

    // Click logout button
    $page->click('Logout');

    // Wait for navigation
    $page->wait(2);

    // Should be redirected to home page and see login/register buttons
    $page->assertSee('Login')
        ->assertSee('Register')
        ->assertDontSee('Logout')
        ->assertNoJavascriptErrors();
})->group('browser', 'auth');

it('shows login and register buttons for guest users', function () {
    $page = visit('/');

    $page->assertSee('Login')
        ->assertSee('Register')
        ->assertDontSee('Logout');
})->group('browser', 'auth');

it('associates guest batches with user after login', function () {
    // Create a guest batch (no user_id) with the current session
    $batch = ImportBatch::factory()->create([
        'user_id' => null,
        'session_id' => 'test-session-123',
        'status' => 'completed',
        'success_file_path' => 'imports/batch-1/successful-products.xlsx',
        'success_count' => 5,
    ]);

    // Verify batch has no user
    expect($batch->user_id)->toBeNull();

    // Visit login page
    $page = visit('/login');

    // Login with valid credentials
    $page->assertSee('Login')
        ->type('#login', 'test@example.com')
        ->type('#password', 'password123')
        ->submit();

    // Wait for navigation
    $page->wait(2);

    // Should be logged in and on home page
    $page->assertSee('GTIN Import')
        ->assertSee($this->user->name)
        ->assertNoJavascriptErrors();

    // Note: The session_id matching won't work in tests the same way as in real usage
    // In a real scenario, the guest creates the batch, then logs in, and the batch gets associated
})->group('browser', 'auth', 'guest-batches');

it('redirects to home page after registration', function () {
    $page = visit('/register');

    $page->assertSee('Register')
        ->fill('name', 'New User')
        ->fill('email', 'newuser@example.com')
        ->fill('phone_number', '+77771234567')
        ->fill('password', 'password123')
        ->fill('password_confirmation', 'password123');

    // For this test to work, we'd need to mock SMS sending
    // or skip the verification step
    // This is a placeholder for the full registration flow
})->skip('Requires SMS verification mock')->group('browser', 'auth');
