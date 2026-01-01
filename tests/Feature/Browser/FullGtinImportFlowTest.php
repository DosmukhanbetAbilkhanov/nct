<?php

use App\Models\ImportBatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create a test user
    $this->user = User::factory()->create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => bcrypt('password123'),
        'phone_verified_at' => now(),
    ]);
});

it('completes full GTIN import flow with authentication', function () {
    Queue::fake();
    Storage::fake('local');

    // Visit homepage as guest
    $page = visit('/');

    // Should see login button
    $page->assertSee('Login')
        ->assertNoJavascriptErrors();

    // Click login button
    $page->click('Login');

    // Wait for redirect
    $page->wait(2);

    // Should be on login page
    $page->assertSee('Login')
        ->assertSee('Email or Phone Number')
        ->assertNoJavascriptErrors();

    // Fill in login credentials and submit
    $page->type('#login', 'test@example.com')
        ->type('#password', 'password123')
        ->submit();

    // Wait for navigation to complete
    $page->wait(2);

    // Should be redirected to import page
    $page->assertSee('NTIN Import')
        ->assertSee('Test User')
        ->assertNoJavascriptErrors();

    // Should see upload interface
    $page->assertSee('Upload a file')
        ->assertSee('Download Template')
        ->assertNoJavascriptErrors();
})->group('browser', 'e2e');

it('displays import progress for authenticated users with existing batch', function () {
    // Create an in-progress import batch for the user
    $batch = ImportBatch::factory()->create([
        'user_id' => $this->user->id,
        'filename' => 'test-import.csv',
        'total_gtins' => 100,
        'processed_count' => 45,
        'success_count' => 40,
        'failed_count' => 5,
        'status' => 'processing',
        'started_at' => now()->subMinutes(5),
    ]);

    // Visit page as authenticated user
    $this->actingAs($this->user);
    $page = visit('/');

    // Should see import progress
    $page->assertSee('Import Progress')
        ->assertSee('test-import.csv')
        ->assertSee('100') // total GTINs
        ->assertSee('45') // processed
        ->assertSee('40') // success
        ->assertSee('5') // failed
        ->assertSee('45%') // percentage
        ->assertNoJavascriptErrors();

    // Should see processing status badge
    $page->assertSee('processing')
        ->assertNoJavascriptErrors();
})->group('browser', 'e2e');

it('displays completed batch with success and failed counts', function () {
    // Create a completed import batch
    $batch = ImportBatch::factory()->create([
        'user_id' => $this->user->id,
        'filename' => 'completed-import.csv',
        'total_gtins' => 50,
        'processed_count' => 50,
        'success_count' => 45,
        'failed_count' => 5,
        'status' => 'completed',
        'started_at' => now()->subMinutes(10),
        'completed_at' => now()->subMinutes(2),
    ]);

    // Create some failed items to display
    $batch->items()->createMany([
        [
            'gtin' => '1234567890123',
            'status' => 'failed',
            'error_message' => 'Product not found in National Catalog',
        ],
        [
            'gtin' => '9876543210987',
            'status' => 'failed',
            'error_message' => 'Invalid GTIN format',
        ],
    ]);

    // Visit page as authenticated user
    $this->actingAs($this->user);
    $page = visit('/');

    // Should see completed batch
    $page->assertSee('Import Progress')
        ->assertSee('completed-import.csv')
        ->assertSee('50') // total
        ->assertSee('45') // success
        ->assertSee('5') // failed
        ->assertSee('100%') // percentage
        ->assertSee('completed')
        ->assertNoJavascriptErrors();

    // Should see upload new file button
    $page->assertSee('Upload New File')
        ->assertNoJavascriptErrors();

    // Should see failed items section
    $page->assertSee('Failed Items (5)')
        ->assertNoJavascriptErrors();
})->group('browser', 'e2e');

it('allows user to reset and upload new file after completion', function () {
    // Create a completed import batch
    $batch = ImportBatch::factory()->create([
        'user_id' => $this->user->id,
        'filename' => 'first-import.csv',
        'total_gtins' => 10,
        'processed_count' => 10,
        'success_count' => 10,
        'failed_count' => 0,
        'status' => 'completed',
        'started_at' => now()->subMinutes(5),
        'completed_at' => now()->subMinute(),
    ]);

    // Visit page as authenticated user
    $this->actingAs($this->user);
    $page = visit('/');

    // Should see completed batch
    $page->assertSee('Import Progress')
        ->assertSee('first-import.csv')
        ->assertSee('completed')
        ->assertNoJavascriptErrors();

    // Click "Upload New File" button
    $page->click('Upload New File');

    // Wait for UI to update
    $page->wait(1);

    // Should show upload interface again
    $page->assertSee('Upload a file')
        ->assertSee('Download Template')
        ->assertNoJavascriptErrors();

    // Should not see old batch info anymore
    $page->assertDontSee('first-import.csv')
        ->assertNoJavascriptErrors();
})->group('browser', 'e2e');

it('displays real-time logs during processing', function () {
    // Create an in-progress batch
    $batch = ImportBatch::factory()->create([
        'user_id' => $this->user->id,
        'filename' => 'processing.csv',
        'total_gtins' => 20,
        'processed_count' => 5,
        'success_count' => 4,
        'failed_count' => 1,
        'status' => 'processing',
        'started_at' => now()->subMinutes(2),
    ]);

    // Visit page as authenticated user
    $this->actingAs($this->user);
    $page = visit('/');

    // Should see real-time logs section
    $page->assertSee('Real-time Processing Logs')
        ->assertNoJavascriptErrors();

    // Logs section should be expandable
    $page->assertSee('Real-time Processing Logs')
        ->assertNoJavascriptErrors();
})->group('browser', 'e2e');

it('shows download buttons for export files when batch is completed', function () {
    // Create a completed batch with export files
    $batch = ImportBatch::factory()->create([
        'user_id' => $this->user->id,
        'filename' => 'export-test.csv',
        'total_gtins' => 30,
        'processed_count' => 30,
        'success_count' => 25,
        'failed_count' => 5,
        'status' => 'completed',
        'started_at' => now()->subMinutes(5),
        'completed_at' => now()->subMinute(),
        'success_file_path' => 'imports/batch-1/successful-products.xlsx',
        'failed_file_path' => 'imports/batch-1/failed-gtins.xlsx',
    ]);

    // Visit page as authenticated user
    $this->actingAs($this->user);
    $page = visit('/');

    // Should see download export files section
    $page->assertSee('Download Export Files')
        ->assertSee('Download Successful Products')
        ->assertSee('Download Failed GTINs')
        ->assertNoJavascriptErrors();
})->group('browser', 'e2e');

it('redirects unauthenticated users to login when accessing the page', function () {
    $page = visit('/');

    // Guest users should see the page but with login/register buttons
    $page->assertSee('NTIN Import')
        ->assertSee('Login')
        ->assertSee('Register')
        ->assertDontSee('Logout')
        ->assertNoJavascriptErrors();
})->group('browser', 'e2e');
