# GTIN Import - Implementation Stages

> **Project**: GTIN Bulk Import & National Catalog Sync
> **Stack**: Laravel 12 · Livewire 3 · Alpine.js · Queue-based Processing
> **Estimated Time**: ~29 hours (including Stage 0)

---

## Stage 0: User Registration & Authentication ⏱️ 6 hours

### Goal
Implement user registration with SMS verification, dual login (email/phone), optional company management, and batch ownership.

### Features
- Custom Livewire authentication (register, login, logout)
- SMS verification via Mobizon API for phone numbers
- Users can login with email OR phone number
- Optional company registration (one company per user)
- Company belongs to User and City
- Batches belong to Users (user_id foreign key)
- Kazakhstan cities seeded

### Tasks
- [x] Create database migrations (users, companies, cities, sms_verification_codes)
- [x] Build Mobizon SMS service integration
- [x] Create authentication Livewire components (Register, Login, Logout, CompanySetup)
- [x] Add form request validation
- [x] Implement SMS rate limiting
- [x] Add route protection with auth middleware
- [x] Update GtinImport for user ownership
- [x] Schedule SMS code cleanup command

### Models Created
- City (id, name)
- Company (user_id, name, bin_or_iin, city_id, address)
- SmsVerificationCode (phone_number, code_hash, expires_at, verified)

### User Flow
1. User registers → Sends SMS code → Verifies phone → Creates account
2. Optional: Setup company (or skip for later)
3. Login with email OR phone → Access GTIN import
4. All batches belong to user (isolated by user_id)

### Verification
- [x] Can register with phone verification
- [x] Can login with email
- [x] Can login with phone number
- [x] SMS codes expire after 10 minutes
- [x] Rate limiting prevents SMS abuse
- [x] Company is optional
- [x] Users only see their own batches

### Files Created
- Migrations: 5 new migration files
- Models: City, Company, SmsVerificationCode
- Services: MobizonSmsService, SmsVerificationService
- Livewire: Auth/Register, Auth/Login, Auth/Logout, CompanySetup
- Form Requests: RegisterRequest, CompanyRequest
- Commands: CleanupExpiredVerificationCodes

### Files Modified
- app/Models/User.php (add phone fields, relationships)
- app/Models/ImportBatch.php (add user relationship)
- app/Livewire/GtinImport.php (add user_id to batches)
- app/Services/GtinImportService.php (accept user_id parameter)
- routes/web.php (auth routes, middleware)
- bootstrap/app.php (guest/auth redirects)
- config/services.php (Mobizon config)
- .env.example (Mobizon variables)
- routes/console.php (schedule cleanup)

---

## Stage 1: Install Dependencies ⏱️ 30 min

### Goal
Add required packages for Livewire, Alpine.js, and Excel processing

### Tasks
- [ ] Install Livewire 3: `composer require livewire/livewire`
- [ ] Install Laravel Excel: `composer require maatwebsite/excel`
- [ ] Note: Alpine.js will be added via CDN in layout template (Stage 6)

### Verification
- [ ] Run `composer show` to confirm Livewire and Laravel Excel installed
- [ ] Check `/livewire/livewire.js` route is accessible

### Files Modified
- `composer.json`

---

## Stage 2: Database Schema ⏱️ 2 hours

### Goal
Create Product model and tracking tables for batch processing

### Database Design
**Three-table approach for efficient progress tracking:**

1. **products** - Permanent product storage
   - id, gtin (unique indexed), ntin, nameKk, nameRu, nameEn, shortNameKk, shortNameRu, shortNameEn, createdDate, updatedDate, timestamps

2. **import_batches** - Batch-level metadata for UI
   - id, filename, total_gtins, processed_count, success_count, failed_count, status, started_at, completed_at, timestamps

3. **import_batch_items** - Granular item tracking
   - id, import_batch_id (FK), gtin, status, error_message, product_id (FK), timestamps

### Tasks
- [ ] Create migration: `php artisan make:migration create_products_table`
- [ ] Create migration: `php artisan make:migration create_import_batches_table`
- [ ] Create migration: `php artisan make:migration create_import_batch_items_table`
- [ ] Create Product model with factory and seeder: `php artisan make:model Product -f -s`
- [ ] Create ImportBatch model: `php artisan make:model ImportBatch`
- [ ] Create ImportBatchItem model: `php artisan make:model ImportBatchItem`
- [ ] Configure fillable fields in all models
- [ ] Add casts using `casts()` method (Laravel 12 convention)
- [ ] Add relationships: hasMany, belongsTo
- [ ] Run migrations: `php artisan migrate`
- [ ] Test factory: `Product::factory()->create()`

### Verification
- [ ] Migrations run successfully
- [ ] Models instantiate correctly
- [ ] Relationships work (test in Tinker)
- [ ] Factory generates valid products with GTINs

### Testing
- [ ] Create test: `php artisan make:test --pest Models/ProductTest`
- [ ] Create test: `php artisan make:test --pest Models/ImportBatchTest`
- [ ] Run tests: `php artisan test --filter=Product`

### Files Created
- `/database/migrations/YYYY_MM_DD_create_products_table.php`
- `/database/migrations/YYYY_MM_DD_create_import_batches_table.php`
- `/database/migrations/YYYY_MM_DD_create_import_batch_items_table.php`
- `/app/Models/Product.php`
- `/app/Models/ImportBatch.php`
- `/app/Models/ImportBatchItem.php`
- `/database/factories/ProductFactory.php`
- `/database/seeders/ProductSeeder.php`

---

## Stage 3: National Catalog API Service ⏱️ 3 hours

### Goal
Build API integration with error handling and retry logic

### API Configuration
- **Authentication**: Header `X-API-KEY: {api_key}`
- **Base URL**: `https://nationalcatalog.kz/gwp`
- **Rate Limit**: 30 requests/minute (conservative start)
- **Endpoint**: To be discovered during implementation

### Tasks
- [ ] Create service: `php artisan make:class Services/NationalCatalogService`
- [ ] Add National Catalog config to `config/services.php`:
  ```php
  'national_catalog' => [
      'api_key' => env('NATIONAL_CATALOG_API_KEY'),
      'base_url' => env('NATIONAL_CATALOG_BASE_URL', 'https://nationalcatalog.kz/gwp'),
      'timeout' => 30,
      'retry_times' => 3,
  ],
  ```
- [ ] Implement `fetchProductByGtin(string $gtin): ?array` method
- [ ] Add HTTP client with `X-API-KEY` header
- [ ] Handle response codes:
  - [ ] 200: Return product data
  - [ ] 404: Return null (not found)
  - [ ] 429: Throw RateLimitException
  - [ ] 500: Throw ApiException
- [ ] Add retry logic with exponential backoff
- [ ] Log all API calls to `storage/logs/laravel.log`
- [ ] **DISCOVERY**: Test API to find exact endpoint for GTIN lookup
- [ ] **DISCOVERY**: Map API response structure to Product model fields
- [ ] **DISCOVERY**: Verify actual rate limits

### Verification
- [ ] Service instantiates with API key from config
- [ ] Can make successful test API call with real GTIN
- [ ] 404 returns null gracefully
- [ ] Exceptions thrown for errors
- [ ] API calls appear in logs

### Testing
- [ ] Create test: `php artisan make:test --pest Services/NationalCatalogServiceTest`
- [ ] Mock HTTP with `Http::fake()`
- [ ] Test: Successful fetch
- [ ] Test: 404 handling
- [ ] Test: Rate limit detection
- [ ] Test: Network error handling
- [ ] Run tests: `php artisan test --filter=NationalCatalogService`

### Files Created
- `/app/Services/NationalCatalogService.php`

### Files Modified
- `/config/services.php`

---

## Stage 4: Queue Job with Rate Limiting ⏱️ 3 hours

### Goal
Create job to process individual GTINs asynchronously with rate limiting

### Rate Limiting Strategy
- Laravel `RateLimited` middleware on job
- 30 requests/minute limit
- 3 retry attempts with backoff: [60s, 300s, 900s]
- Jobs released back to queue when throttled

### Tasks
- [ ] Create job: `php artisan make:job FetchProductFromNationalCatalog`
- [ ] Implement `ShouldQueue` interface
- [ ] Set queue: `'national-catalog'`
- [ ] Add constructor: `public ImportBatchItem $batchItem`
- [ ] Add middleware: `RateLimited('national-catalog-api')`
- [ ] Set properties: `$tries = 3`, `$backoff = [60, 300, 900]`
- [ ] Implement job `handle()` logic:
  - [ ] Update batch item status to 'processing'
  - [ ] Validate GTIN (13 digits, numeric)
  - [ ] Check if product exists in DB by GTIN
  - [ ] If exists: Link to product, mark success
  - [ ] If not exists: Call `NationalCatalogService::fetchProductByGtin()`
  - [ ] Save product data from API response
  - [ ] Update batch item with product_id and success status
  - [ ] Increment ImportBatch counters atomically
  - [ ] Handle errors with specific messages
- [ ] Implement `failed()` method for permanent failures
- [ ] Configure rate limiter in `bootstrap/app.php`:
  ```php
  use Illuminate\Support\Facades\RateLimiter;
  use Illuminate\Cache\RateLimiting\Limit;

  RateLimiter::for('national-catalog-api', function (object $job) {
      return Limit::perMinute(30)->by('national-catalog');
  });
  ```

### Verification
- [ ] Job can be dispatched successfully
- [ ] Rate limiting prevents exceeding API limits
- [ ] Failed jobs go to `failed_jobs` table
- [ ] Job processes valid GTIN successfully
- [ ] Errors handled gracefully

### Testing
- [ ] Create test: `php artisan make:test --pest Jobs/FetchProductFromNationalCatalogTest`
- [ ] Test: Successful product fetch and save
- [ ] Test: Skip fetch if product exists in DB
- [ ] Test: GTIN validation failures
- [ ] Test: API 404 handling
- [ ] Test: Rate limit error handling
- [ ] Test: ImportBatch counter updates
- [ ] Run tests: `php artisan test --filter=FetchProductFromNationalCatalog`

### Files Created
- `/app/Jobs/FetchProductFromNationalCatalog.php`

### Files Modified
- `/bootstrap/app.php` (rate limiter configuration)

---

## Stage 5: Excel Import Logic ⏱️ 2 hours

### Goal
Parse Excel files and extract GTINs from column A

### Tasks
- [ ] Create import class: `php artisan make:import GtinsImport --model=Product`
- [ ] Implement `ToCollection` interface
- [ ] Extract values from column A (first column)
- [ ] Trim whitespace and normalize
- [ ] Validate format: 13 digits, numeric only
- [ ] Remove duplicates within file
- [ ] Return collection of validated GTINs
- [ ] Create orchestrator: `php artisan make:class Services/GtinImportService`
- [ ] Implement `processUpload(UploadedFile $file): ImportBatch`:
  - [ ] Create ImportBatch record (status: pending)
  - [ ] Parse Excel using `GtinsImport`
  - [ ] Create ImportBatchItem for each unique GTIN
  - [ ] Update ImportBatch.total_gtins
  - [ ] Dispatch `FetchProductFromNationalCatalog` job for each item
  - [ ] Update ImportBatch.status to 'processing'
  - [ ] Return ImportBatch

### Verification
- [ ] Can parse sample Excel file with GTINs
- [ ] Invalid GTINs rejected with clear errors
- [ ] ImportBatch created with correct total count
- [ ] ImportBatchItems created for each GTIN
- [ ] Jobs dispatched to queue successfully

### Testing
- [ ] Create test: `php artisan make:test --pest Services/GtinImportServiceTest`
- [ ] Create test: `php artisan make:test --pest Imports/GtinsImportTest`
- [ ] Create test fixture: `/tests/Fixtures/sample-gtins.xlsx`
- [ ] Test: Excel parsing
- [ ] Test: CSV support
- [ ] Test: GTIN validation
- [ ] Test: Duplicate removal
- [ ] Test: Job dispatching
- [ ] Run tests: `php artisan test --filter=GtinImport`

### Files Created
- `/app/Imports/GtinsImport.php`
- `/app/Services/GtinImportService.php`
- `/tests/Fixtures/sample-gtins.xlsx`

---

## Stage 6: Livewire Component & UI ⏱️ 4 hours

### Goal
Build one-page interface with real-time progress tracking

### UI Architecture
- Single Livewire component
- Alpine.js for transitions and drag-drop
- Polling every 5 seconds during processing
- Progress from ImportBatch model

### Tasks
- [ ] Create component: `php artisan make:livewire GtinImport`
- [ ] Add properties:
  ```php
  public $file;
  public ?ImportBatch $currentBatch = null;
  public bool $isProcessing = false;
  ```
- [ ] Implement methods:
  - [ ] `upload()`: Validate file, call GtinImportService
  - [ ] `loadBatchProgress()`: Refresh currentBatch from DB
  - [ ] `resetImport()`: Clear state for new upload
- [ ] Add validation: `required|file|mimes:xlsx,xls,csv|max:10240`
- [ ] Create layout: `/resources/views/layouts/app.blade.php`
  - [ ] Add `@livewireStyles` in head
  - [ ] Add Alpine.js CDN: `<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>`
  - [ ] Add `@livewireScripts` before closing body
- [ ] Create view: `/resources/views/livewire/gtin-import.blade.php`
  - [ ] File upload section (hidden when processing)
  - [ ] Drag-and-drop zone with Alpine.js
  - [ ] Progress section (visible when batch exists):
    - [ ] Total GTINs count
    - [ ] Processed count with percentage
    - [ ] Success count (green badge)
    - [ ] Failed count (red badge)
    - [ ] Animated progress bar (Tailwind CSS)
  - [ ] Failed items table (expandable)
  - [ ] "Upload New File" button when complete
  - [ ] Add `wire:poll.5s="loadBatchProgress"` when processing
- [ ] Add route: `Route::get('/', App\Livewire\GtinImport::class)->name('gtin-import');`

### UI Features Checklist
- [ ] Tailwind CSS card layout
- [ ] Drag-and-drop file upload
- [ ] Real-time progress updates
- [ ] Color-coded status badges
- [ ] Mobile responsive
- [ ] Smooth Alpine.js transitions

### Verification
- [ ] Page loads at `/` without errors
- [ ] File upload works (form and drag-drop)
- [ ] Progress updates every 5 seconds
- [ ] Failed items display with errors
- [ ] Can upload new file after completion

### Testing
- [ ] Create test: `php artisan make:test --pest Livewire/GtinImportTest`
- [ ] Test: Component renders
- [ ] Test: File validation
- [ ] Test: Upload triggers job dispatch
- [ ] Test: Progress polling
- [ ] Test: Reset functionality
- [ ] Run tests: `php artisan test --filter=GtinImport`

### Files Created
- `/app/Livewire/GtinImport.php`
- `/resources/views/livewire/gtin-import.blade.php`
- `/resources/views/layouts/app.blade.php`

### Files Modified
- `/routes/web.php`

---

## Stage 7: Error Handling & Robustness ⏱️ 3 hours

### Goal
Comprehensive error handling for production readiness

### Error Scenarios
**API Errors:**
- [ ] 429 Rate Limit: Release job back to queue
- [ ] 404 Not Found: Mark failed "Product not found in catalog"
- [ ] 500 Server Error: Retry with exponential backoff
- [ ] Network timeout: Retry with backoff

**File Upload Errors:**
- [ ] Empty file: "File contains no data"
- [ ] Malformed Excel: Catch parsing exceptions
- [ ] No valid GTINs: "No valid GTINs found in file"
- [ ] All duplicates: Process normally

**Queue Failures:**
- [ ] Job `failed()` method implemented
- [ ] Failed jobs logged to `failed_jobs` table
- [ ] Retry command available

### Tasks
- [ ] Enhance `NationalCatalogService` error handling
- [ ] Add try-catch in job with specific error messages
- [ ] Add file validation and parsing error handling
- [ ] Create retry command: `php artisan make:command RetryFailedBatchItems`
- [ ] Log errors with context (GTIN, batch ID)
- [ ] Use database transactions for atomic updates

### Verification
- [ ] All error scenarios have tests
- [ ] Logs provide actionable debugging info
- [ ] Users see friendly error messages
- [ ] Failed jobs can be retried
- [ ] No data corruption under errors

### Testing
- [ ] Create test: `php artisan make:test --pest ErrorHandling/ApiErrorsTest`
- [ ] Create test: `php artisan make:test --pest ErrorHandling/FileUploadErrorsTest`
- [ ] Test all error scenarios with mocked failures
- [ ] Run tests: `php artisan test --filter=ErrorHandling`

### Files Created
- `/app/Console/Commands/RetryFailedBatchItems.php`

### Files Modified
- `/app/Jobs/FetchProductFromNationalCatalog.php`
- `/app/Services/NationalCatalogService.php`

---

## Stage 8: Queue Configuration ⏱️ 1 hour

### Goal
Production-ready queue setup and documentation

### Tasks
- [ ] Update `.env.example`:
  ```
  NATIONAL_CATALOG_API_KEY=
  NATIONAL_CATALOG_BASE_URL=https://nationalcatalog.kz/gwp
  QUEUE_CONNECTION=database
  ```
- [ ] Document queue worker command:
  ```bash
  php artisan queue:work --queue=national-catalog --tries=3 --timeout=90
  ```
- [ ] Note: `composer run dev` already includes queue:listen
- [ ] Document queue monitoring commands
- [ ] Add troubleshooting guide for queue issues

### Configuration Details
- Queue driver: database
- Queue name: `national-catalog`
- Worker count: 1-2 (based on 30 req/min limit)
- Retry after: 180 seconds

### Verification
- [ ] Queue worker runs without errors
- [ ] Jobs process at expected rate
- [ ] Failed jobs can be viewed and retried
- [ ] Queue status is monitorable

### Files Modified
- `.env.example`

---

## Stage 9: Testing & Quality Assurance ⏱️ 3 hours

### Goal
Comprehensive test coverage and code quality

### Testing Strategy
1. Unit Tests: Models, services, import logic
2. Feature Tests: API integration, jobs, Livewire
3. Browser Tests: Full E2E flow

### Tasks
- [ ] Create E2E test: `php artisan make:test --pest --browser Browser/FullGtinImportFlowTest`
- [ ] E2E test steps:
  - [ ] Visit homepage
  - [ ] Upload Excel file (mix of existing/new GTINs)
  - [ ] Verify progress updates
  - [ ] Wait for completion
  - [ ] Verify success/failed counts
  - [ ] Check database for new products
  - [ ] Upload another file to test reset
- [ ] Run code formatter: `vendor/bin/pint --dirty`
- [ ] Run full test suite: `php artisan test`
- [ ] Check for N+1 queries
- [ ] Verify proper error logging
- [ ] Verify user-friendly error messages

### Quality Checklist
- [ ] All tests passing
- [ ] Code formatted with Pint
- [ ] No N+1 queries
- [ ] Proper error logging
- [ ] User-friendly error messages
- [ ] Mobile responsive UI
- [ ] Accessible UI elements

### Verification
- [ ] E2E test passes consistently
- [ ] All unit and feature tests pass
- [ ] Code style follows Laravel conventions
- [ ] No console errors in browser

### Files Created
- `/tests/Browser/FullGtinImportFlowTest.php`

---

## Stage 10: Documentation & Polish ⏱️ 2 hours

### Goal
Production-ready documentation and final polish

### Tasks
**Documentation:**
- [ ] Create comprehensive README.md:
  - [ ] Project overview
  - [ ] Installation steps
  - [ ] Queue worker setup
  - [ ] Environment variables
  - [ ] Usage instructions
  - [ ] Troubleshooting guide
- [ ] Update `.env.example` with all required variables
- [ ] Add inline code comments for complex logic

**UI Polish:**
- [ ] Loading spinners during upload
- [ ] Smooth progress bar animation
- [ ] File preview (show filename before upload)
- [ ] Success message on completion
- [ ] Mobile responsive refinements
- [ ] Error message styling
- [ ] Empty state messaging

### Verification
- [ ] README.md is complete and accurate
- [ ] `.env.example` includes all variables
- [ ] UI feels polished and professional
- [ ] All acceptance criteria met

### Files Created/Modified
- `/README.md`
- `.env.example`

---

## Acceptance Criteria

✅ **Implementation is complete when:**

- [ ] Excel file upload works with validation
- [ ] GTINs extracted from column A, validated (13 digits, numeric)
- [ ] Existing products are not duplicated (DB check before API)
- [ ] Missing products fetched via National Catalog API
- [ ] API calls are queued with rate limiting (30 req/min)
- [ ] Products saved correctly with all fields
- [ ] User sees real-time progress (total, processed, success, failed)
- [ ] Failed items display with error messages
- [ ] No synchronous API calls (all via queue)
- [ ] All tests pass
- [ ] Code formatted with Pint

---

## Discovery Notes

During implementation, discover:
1. ✅ National Catalog API exact endpoint for GTIN lookup
2. ✅ API response structure mapping to Product model
3. ✅ Actual rate limits (adjust from 30 req/min if needed)
4. ✅ Real data testing for GTIN validation

---

## Progress Tracking

| Stage | Status | Started | Completed | Notes |
|-------|--------|---------|-----------|-------|
| Stage 1: Dependencies | ⬜ Not Started | - | - | |
| Stage 2: Database Schema | ⬜ Not Started | - | - | |
| Stage 3: API Service | ⬜ Not Started | - | - | Discovery: endpoint, response format |
| Stage 4: Queue Job | ⬜ Not Started | - | - | |
| Stage 5: Excel Import | ⬜ Not Started | - | - | |
| Stage 6: Livewire UI | ⬜ Not Started | - | - | |
| Stage 7: Error Handling | ⬜ Not Started | - | - | |
| Stage 8: Queue Config | ⬜ Not Started | - | - | |
| Stage 9: Testing & QA | ⬜ Not Started | - | - | |
| Stage 10: Documentation | ⬜ Not Started | - | - | |

**Legend:**
⬜ Not Started · 🔄 In Progress · ✅ Completed · ⚠️ Blocked

---

## Quick Reference

### Key Commands
```bash
# Development
composer run dev              # Starts server, queue, logs, vite

# Queue Worker
php artisan queue:work --queue=national-catalog --tries=3 --timeout=90

# Testing
php artisan test              # Run all tests
php artisan test --filter=X   # Run specific test
vendor/bin/pint --dirty       # Format code

# Queue Management
php artisan queue:failed      # View failed jobs
php artisan queue:retry {id}  # Retry failed job
```

### Critical Files Reference
- API Service: `/app/Services/NationalCatalogService.php`
- Queue Job: `/app/Jobs/FetchProductFromNationalCatalog.php`
- Import Service: `/app/Services/GtinImportService.php`
- Livewire Component: `/app/Livewire/GtinImport.php`
- Models: `/app/Models/Product.php`, `ImportBatch.php`, `ImportBatchItem.php`

---

**Total Estimated Time**: ~23 hours
**Architecture**: Queue-first, rate-limited, real-time UI updates via polling
