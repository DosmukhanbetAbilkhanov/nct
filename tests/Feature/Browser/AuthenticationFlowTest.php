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

it('redirects to import page after login when accessing download route', function () {
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

    // Wait a moment for navigation
    $page->wait(2);

    // Should be redirected to the import page instead of the download URL
    // This prevents the user from ending up on a blank download page
    $page->assertSee('GTIN Import')
        ->assertSee($this->user->name)
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

it('shows auth modal when guest tries to start import', function () {
    $page = visit('/');

    // Guest should see login and register buttons
    $page->assertSee('Login')
        ->assertSee('Register')
        ->assertNoJavascriptErrors();

    // TODO: Add test for file upload and auth modal when Pest browser testing supports it
    // This would involve:
    // 1. Upload a file as guest
    // 2. See GTIN count preview
    // 3. Click "Start Import"
    // 4. See authentication modal
    // 5. Click "Login" in modal
    // 6. Complete login
    // 7. Return to import page with file preserved
})->skip('File upload in browser tests requires additional setup')->group('browser', 'auth');

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
