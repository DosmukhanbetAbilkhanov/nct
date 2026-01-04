═══════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════
IMPLEMENTATION PROGRESS (Last updated: 2026-01-04)
═══════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════

✅ PHASE 1: FOUNDATION - COMPLETED
- ✅ Created enums: OrderStatus, PaymentStatus, PaymentMethod (app/Enums/)
- ✅ Created Order model with relationships and methods (app/Models/Order.php)
- ✅ Created Payment model with relationships and methods (app/Models/Payment.php)
- ✅ Updated ImportBatch model with order relationship (app/Models/ImportBatch.php)
- ✅ Created OrderFactory with states: paid, expired, completed, paymentInitiated (database/factories/)
- ✅ Created PaymentFactory with states: initiated, successful, failed, processing (database/factories/)
- ✅ Note: Database tables already existed, no migrations needed
- ✅ Ran Laravel Pint for code formatting

✅ PHASE 2: SERVICES LAYER - COMPLETED
- ✅ Created PaymentGatewayInterface (app/Services/Contracts/PaymentGatewayInterface.php)
- ✅ Created AsiaPayService implementing interface (app/Services/AsiaPayService.php)
  - Uses HTTP Basic Auth
  - POST /v2/payment/init for initialization
  - POST /v2/payment/confirm for verification
  - Follows NationalCatalogService pattern with fromConfig() factory
- ✅ Created PaymentService (app/Services/PaymentService.php)
  - createOrderForBatch(), initiatePayment(), handleWebhookNotification()
  - processSuccessfulPayment(), handleFailedPayment()
  - cancelExpiredOrders(), checkPendingOrderForUser()
- ✅ Created OrderService (app/Services/OrderService.php)
  - generateUniqueOrderNumber(), validateNoPendingOrders(), expireOrder()
- ✅ Created ReceiptService (app/Services/ReceiptService.php)
  - Placeholder structure (PDF implementation in Phase 8)
- ✅ Created custom exceptions (app/Exceptions/)
  - PaymentException, PaymentGatewayException

✅ PHASE 3: CONTROLLERS & ROUTES - COMPLETED
- ✅ Created PaymentController (app/Http/Controllers/PaymentController.php)
  - initiatePayment(), handleReturn(), handleRetry(), downloadReceipt()
- ✅ Created WebhookController (app/Http/Controllers/WebhookController.php)
  - asiapayWebhook() with signature validation and comprehensive logging
- ✅ Updated routes/web.php with all payment routes
  - Authenticated routes: /payments/initiate, /payments/return, /payments/{order}/retry, /orders/{order}/receipt
  - Public webhook: /api/payments/webhook
- ✅ Ran Laravel Pint for code formatting

🚧 PHASE 4: JOBS, EVENTS & LISTENERS - PENDING
⏳ PHASE 5: UI INTEGRATION (LIVEWIRE) - PENDING
⏳ PHASE 6: EMAIL & NOTIFICATIONS - PENDING
⏳ PHASE 7: LOCALIZATION - PENDING
⏳ PHASE 8: RECEIPT GENERATION - PENDING
⏳ PHASE 9: CONFIGURATION - PENDING
⏳ PHASE 10: TESTING - PENDING

RESUME POINT: Continue with Phase 4 - Jobs, Events & Listeners (ProcessPaidBatch, ExpireUnpaidOrders, Events & Listeners)

═══════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════

 Planning Summary

  Interviewed you about 27 detailed questions covering:
  - API selection and payment methods
  - Pricing structure and payment flow
  - UI/UX requirements and checkout experience
  - Order expiration and post-payment handling
  - Notifications, receipts, and admin features
  - Localization, security, and testing approach
  - Edge cases and concurrency handling

  Explored your codebase to understand:
  - Existing database schema (payments and orders tables already exist)
  - Service-based architecture patterns
  - Livewire component structure
  - Testing framework and patterns
  - Multi-language support setup

  Created a comprehensive implementation plan with:
  - 10 implementation phases (Foundation → Testing → QA)
  - 54 new files to create
  - 7 existing files to modify
  - Complete technical specifications for each component
  - Security checklist and design decisions
  - Estimated 20-day implementation timeline

  The plan is saved at /Users/dos/.claude/plans/giggly-sparking-goblet.md and follows all your existing Laravel 12, Livewire v3, and Pest v4 patterns.

────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────
 Ready to code?

 Here is Claude's plan:
╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌
 AsiaPay V2 Payment System Implementation Plan

 Overview

 Integrate AsiaPay V2 payment gateway for GTIN import processing fees at 10 KZT per GTIN. Payment required after upload, before
  processing. Full multi-language support (EN/KK/RU).

 Requirements Summary

 Payment Flow: Upload → Review (show count & price) → Pay → Auto-process
 Pricing: 10 KZT per GTIN (fixed)
 Payment Method: Card payments only via AsiaPay V2 API
 Order Expiry: 30 minutes for unpaid orders
 Environment: Start with test mode, move to production later
 Notifications: Email only (payment success, payment failure, batch complete)
 Receipts: Basic PDF with order details
 Admin: Prepare data structure for Filament integration later
 Testing: Full Pest coverage (Unit, Feature, Browser tests)
 Security: Standard (HTTPS, webhook validation, secure logging)
 Constraints: One pending order per user at a time, prevent order creation for invalid batches

 ---
 Implementation Phases

 Phase 1: Foundation - Models & Enums

 Create Enums (app/Enums/)

 - OrderStatus.php: Pending, PaymentInitiated, Paid, Processing, Completed, Expired, Cancelled
 - PaymentStatus.php: Pending, Initiated, Processing, Success, Failed, Cancelled, Expired
 - PaymentMethod.php: Card

 Create Models

 app/Models/Order.php
 - Relationships: user(), importBatch(), payments(), latestPayment()
 - Scopes: pending(), expired(), forUser()
 - Methods:
   - isExpired(): Check if expires_at < now
   - isPaid(): Check status is Paid or higher
   - canBePaid(): Check if pending and not expired
   - markAsPaid(): Update status to Paid
   - markAsExpired(): Update status to Expired
   - generateOrderNumber(): Format: ORD-{YYYYMMDD}-{random}
 - Casts: status → OrderStatus, payment_method → PaymentMethod, expires_at → datetime
 - Use casts() method following Laravel 12 pattern

 app/Models/Payment.php
 - Relationship: order()
 - Methods:
   - isSuccess(), isFailed(), isPending()
   - markAsSuccess(), markAsFailed()
 - Casts: status → PaymentStatus, payment_method → PaymentMethod, asiapay_response → array

 Update Existing Model

 app/Models/ImportBatch.php (modify)
 - Add relationship: order() - belongsTo(Order::class)
 - Add methods:
   - requiresPayment(): Check requires_payment flag
   - isPaymentCompleted(): Check payment_completed flag
   - canBeProcessed(): Payment completed OR doesn't require payment
   - markPaymentAsCompleted(): Set payment_completed to true

 Create Factories

 - database/factories/OrderFactory.php
 - database/factories/PaymentFactory.php

 ---
 Phase 2: Services Layer

 Payment Gateway Interface

 app/Services/Contracts/PaymentGatewayInterface.php
 interface PaymentGatewayInterface
 {
     public function initializePayment(Order $order, string $returnUrl): array;
     public function verifyPayment(string $paymentId): array;
     public function validateWebhookSignature(array $payload, string $signature): bool;
 }

 AsiaPay Service

 app/Services/AsiaPayService.php
 - Implements: PaymentGatewayInterface
 - Constructor with Http\Client dependency
 - Static factory: fromConfig() - Creates instance from config
 - Methods:
   - initializePayment(Order $order, string $returnUrl): array
       - POST to /v2/payment/init
     - HTTP Basic Auth (username/password)
     - Return: payment_id, redirect_url, status
   - verifyPaymentStatus(string $asiapayOrderId): array
       - POST to /v2/payment/status
     - Check payment status
   - validateWebhookSignature(array $payload, string $signature): bool
       - Validate webhook authenticity
   - makeRequest(string $method, string $endpoint, array $data): array
       - Generic HTTP request handler
   - getAuthHeaders(): array
       - Generate Basic Auth headers
 - Configuration from: config('services.asiapay.*)
 - Follow pattern of NationalCatalogService (constructor injection, static factory)

 Payment Service

 app/Services/PaymentService.php
 - Constructor dependencies: PaymentGatewayInterface, GtinImportService
 - Methods:
   - createOrderForBatch(ImportBatch $batch, User $user): Order
       - Validate batch has valid GTINs (>0)
     - Check user doesn't have pending order
     - Calculate amount: gtin_count × 10 KZT
     - Create order with 30-minute expiry
     - Update batch requires_payment flag
   - initiatePayment(Order $order): Payment
       - Create payment record
     - Call AsiaPay to initialize
     - Store redirect URL
     - Return payment with redirect URL
   - handleWebhookNotification(array $payload): void
       - Find payment by asiapay_payment_id
     - Update payment status
     - If success: call processSuccessfulPayment()
     - If failed: call handleFailedPayment()
   - processSuccessfulPayment(Payment $payment): void
       - Mark order as paid
     - Mark batch payment as completed
     - Dispatch ProcessPaidBatch job
     - Fire PaymentSuccessful event
   - handleFailedPayment(Payment $payment, string $reason): void
       - Mark payment as failed
     - Fire PaymentFailed event
   - cancelExpiredOrders(): int
       - Find orders where expires_at < now and status = Pending
     - Mark as Expired
     - Return count
   - checkPendingOrderForUser(User $user): ?Order
       - Return pending order or null

 Order Service

 app/Services/OrderService.php
 - Methods:
   - generateUniqueOrderNumber(): string
   - validateNoPendingOrders(User $user): void
   - expireOrder(Order $order): void

 Receipt Service

 app/Services/ReceiptService.php
 - Dependencies: PDF library (barryvdh/laravel-dompdf)
 - Methods:
   - generatePaymentReceipt(Order $order): string - Returns PDF content
   - downloadPaymentReceipt(Order $order): Response - Returns download response

 Custom Exceptions

 - app/Exceptions/PaymentException.php
 - app/Exceptions/PaymentGatewayException.php

 ---
 Phase 3: Controllers & Routes

 Payment Controller

 app/Http/Controllers/PaymentController.php

 Actions:
 1. initiatePayment(Request $request)
   - Validate: import_batch_id required
   - Load batch, check ownership
   - Check no pending order
   - Create order via PaymentService
   - Initiate payment
   - Redirect to AsiaPay payment page
 2. handleReturn(Request $request)
   - User returns from AsiaPay (success or cancel)
   - Load payment by query params
   - Show status message
   - Redirect to batch details page
 3. handleRetry(Order $order)
   - Check order ownership
   - Check order not expired
   - Create new payment attempt
   - Redirect to AsiaPay
 4. downloadReceipt(Order $order)
   - Check ownership
   - Check order is paid
   - Generate PDF via ReceiptService
   - Return download response

 Webhook Controller

 app/Http/Controllers/WebhookController.php

 Actions:
 1. asiapayWebhook(Request $request)
   - Validate webhook signature
   - Extract payload
   - Call PaymentService::handleWebhookNotification()
   - Return 200 OK (empty response)
   - Add comprehensive logging

 Routes

 routes/web.php (add)
 // Authenticated payment routes
 Route::middleware('auth')->group(function () {
     Route::post('/payments/initiate', [PaymentController::class, 'initiatePayment'])
         ->name('payments.initiate');
     Route::get('/payments/return', [PaymentController::class, 'handleReturn'])
         ->name('payments.return');
     Route::post('/payments/{order}/retry', [PaymentController::class, 'handleRetry'])
         ->name('payments.retry');
     Route::get('/orders/{order}/receipt', [PaymentController::class, 'downloadReceipt'])
         ->name('orders.receipt');
 });

 // Public webhook
 Route::post('/api/payments/webhook', [WebhookController::class, 'asiapayWebhook'])
     ->name('payments.webhook');

 ---
 Phase 4: Jobs, Events & Listeners

 Jobs

 app/Jobs/ProcessPaidBatch.php
 - Implements: ShouldQueue
 - Properties: public int $tries = 3;, public array $backoff = [60, 300, 600];
 - Constructor: public function __construct(public ImportBatch $batch)
 - Handle: Call GtinImportService to process batch
 - Failed: Log error, fire BatchProcessingFailed event
 - Dispatch after successful payment

 app/Jobs/ExpireUnpaidOrders.php
 - Implements: ShouldQueue
 - Handle: Call PaymentService::cancelExpiredOrders()
 - Schedule: Every 5 minutes via routes/console.php

 Events

 app/Events/PaymentSuccessful.php
 - Properties: public Order $order, public Payment $payment

 app/Events/PaymentFailed.php
 - Properties: public Payment $payment, public string $reason

 app/Events/BatchProcessingComplete.php
 - Properties: public ImportBatch $batch

 Listeners

 app/Listeners/SendPaymentSuccessNotification.php
 - Listen to: PaymentSuccessful
 - Action: Send PaymentSuccessful notification to user

 app/Listeners/SendPaymentFailedNotification.php
 - Listen to: PaymentFailed
 - Action: Send PaymentFailed notification to user

 app/Listeners/SendBatchCompleteNotification.php
 - Listen to: BatchProcessingComplete
 - Action: Send BatchProcessingComplete notification to user

 Console Schedule

 routes/console.php (modify)
 Schedule::job(new ExpireUnpaidOrders)->everyFiveMinutes();

 ---
 Phase 5: UI Integration (Livewire)

 Modify GtinImport Component

 app/Livewire/GtinImport.php

 Add properties:
 public bool $requiresPayment = false;
 public ?float $estimatedCost = null;
 public ?Order $pendingOrder = null;

 Add methods:
 public function calculateCost(): void
 {
     // Calculate: previewGtinCount × 10 KZT
     $this->estimatedCost = $this->previewGtinCount * 10;
 }

 public function proceedToPayment(): void
 {
     // Check authentication
     // Check no pending order
     // Create order via PaymentService
     // Redirect to payment initiation route
 }

 protected function checkPendingOrder(): void
 {
     // Load user's pending order if exists
 }

 Modify previewFile():
 - After calculating GTIN count, call calculateCost()
 - Set requiresPayment = true

 Modify startImport():
 - Remove direct import start
 - Replace with proceedToPayment() if payment required

 Create BatchDetails Component

 app/Livewire/BatchDetails.php
 - Properties: public ImportBatch $batch
 - Show: Batch info, payment status, processing progress, download links
 - If payment failed: Show retry button
 - Auto-refresh while processing

 resources/views/livewire/batch-details.blade.php
 - Display batch details
 - Payment status badge
 - Processing progress bar
 - Retry payment button (if failed)
 - Download receipt button (if paid)
 - Download export files (if completed)

 Update MyRequests Component

 app/Livewire/MyRequests.php (modify)
 - Add payment status column
 - Show payment status badge
 - Add "Pay Now" button for pending orders
 - Add retry button for failed payments

 ---
 Phase 6: Email & Notifications

 Configure Email

 config/mail.php - Already configured
 .env - Add mail settings:
 MAIL_MAILER=smtp
 MAIL_HOST=your-smtp-host
 MAIL_PORT=587
 MAIL_USERNAME=your-username
 MAIL_PASSWORD=your-password
 MAIL_ENCRYPTION=tls
 MAIL_FROM_ADDRESS="noreply@nct.kz"
 MAIL_FROM_NAME="${APP_NAME}"

 Notification Classes

 app/Notifications/PaymentSuccessful.php
 - Via: mail
 - toMail(): Build email with order details, link to batch
 - Localized subject and content

 app/Notifications/PaymentFailed.php
 - Via: mail
 - toMail(): Build email with error, retry link
 - Localized subject and content

 app/Notifications/BatchProcessingComplete.php
 - Via: mail
 - toMail(): Build email with results, download links
 - Localized subject and content

 Email Templates (Markdown)

 resources/views/emails/payment-successful.blade.php
 resources/views/emails/payment-failed.blade.php
 resources/views/emails/batch-complete.blade.php

 ---
 Phase 7: Localization

 Translation Files

 Create/update for EN, KK, RU:

 lang/en/payment.php (create)
 lang/kz/payment.php (create)
 lang/ru/payment.php (create)

 Keys needed:
 'payment_required' => 'Payment Required',
 'total_cost' => 'Total Cost',
 'cost_per_gtin' => 'Cost per GTIN',
 'gtin_count' => 'GTIN Count',
 'proceed_to_payment' => 'Proceed to Payment',
 'payment_successful' => 'Payment Successful',
 'payment_failed' => 'Payment Failed',
 'retry_payment' => 'Retry Payment',
 'download_receipt' => 'Download Receipt',
 'order_number' => 'Order Number',
 'amount' => 'Amount',
 'status' => 'Status',
 'expires_at' => 'Expires At',
 'expired' => 'Expired',
 'pending' => 'Pending',
 'paid' => 'Paid',
 'order_expired_message' => 'This order has expired. Please create a new order.',

 lang/en/emails.php (create)
 lang/kz/emails.php (create)
 lang/ru/emails.php (create)

 Email subjects and content translations.

 ---
 Phase 8: Receipt Generation

 Install PDF Library

 composer require barryvdh/laravel-dompdf

 Receipt Template

 resources/views/receipts/payment.blade.php
 - Simple table layout
 - Order number, date, GTIN count
 - Amount, payment ID, status
 - User details (name, email)
 - Company info (if available)

 ---
 Phase 9: Configuration

 Services Config

 config/services.php (modify)

 Add:
 'asiapay' => [
     'username' => env('ASIAPAY_USERNAME'),
     'password' => env('ASIAPAY_PASSWORD'),
     'base_url' => env('ASIAPAY_BASE_URL', 'https://apitest.asiapay.kz'),
     'test_mode' => env('ASIAPAY_TEST_MODE', true),
     'payment_timeout' => 1800, // 30 minutes
 ],

 Environment Variables

 .env.example (update)

 Add:
 # AsiaPay Payment Gateway
 ASIAPAY_USERNAME=
 ASIAPAY_PASSWORD=
 ASIAPAY_BASE_URL=https://apitest.asiapay.kz
 ASIAPAY_TEST_MODE=true

 # Payment Settings
 PAYMENT_PRICE_PER_GTIN=10
 PAYMENT_CURRENCY=KZT
 ORDER_EXPIRATION_MINUTES=30

 ---
 Phase 10: Testing

 Unit Tests

 tests/Unit/Services/AsiaPayServiceTest.php
 - Test API request building
 - Test auth headers generation
 - Test response parsing
 - Test error handling

 tests/Unit/Services/PaymentServiceTest.php
 - Test order creation logic
 - Test pricing calculation
 - Test payment initiation
 - Test webhook handling
 - Test expiration logic

 tests/Unit/Models/OrderTest.php
 - Test order number generation
 - Test expiry checking
 - Test status transitions
 - Test relationships

 tests/Unit/Models/PaymentTest.php
 - Test status methods
 - Test JSON casting
 - Test relationships

 Feature Tests

 tests/Feature/Payment/PaymentInitiationTest.php
 - Test complete payment flow
 - Test authorization (only own batches)
 - Test duplicate pending order prevention
 - Test invalid batch handling

 tests/Feature/Payment/WebhookTest.php
 - Test webhook signature validation
 - Test successful payment handling
 - Test failed payment handling
 - Test duplicate webhook handling

 tests/Feature/Payment/OrderExpirationTest.php
 - Test order expiration job
 - Test expired order status
 - Test no double expiration

 tests/Feature/Jobs/ProcessPaidBatchTest.php
 - Test batch processing after payment
 - Test job retry on failure

 tests/Feature/Notifications/PaymentNotificationsTest.php
 - Test payment success email
 - Test payment failed email
 - Test batch complete email
 - Test multi-language emails

 Browser Tests (Pest v4)

 tests/Feature/Browser/PaymentFlowTest.php

 it('completes full payment flow', function () {
     // Create user, login
     // Upload file
     // See preview with price
     // Click "Pay"
     // Mock AsiaPay redirect
     // Webhook callback
     // See success message
     // Batch processes
     // Download receipt
 });

 it('handles failed payment with retry', function () {
     // Upload, pay, fail
     // See error message
     // Click retry
     // Pay again, succeed
 });

 it('prevents multiple pending orders', function () {
     // Create pending order
     // Try to create another
     // See error message
 });

 it('expires unpaid orders', function () {
     // Create order
     // Fast-forward 30 minutes
     // Run expiration job
     // See expired status
 });

 ---
 Critical Files Summary

 Files to Create (54 files)

 Models & Enums (5):
 - app/Enums/OrderStatus.php
 - app/Enums/PaymentStatus.php
 - app/Enums/PaymentMethod.php
 - app/Models/Order.php
 - app/Models/Payment.php

 Services (6):
 - app/Services/Contracts/PaymentGatewayInterface.php
 - app/Services/AsiaPayService.php
 - app/Services/PaymentService.php
 - app/Services/OrderService.php
 - app/Services/ReceiptService.php
 - app/Exceptions/PaymentException.php

 Controllers (2):
 - app/Http/Controllers/PaymentController.php
 - app/Http/Controllers/WebhookController.php

 Jobs (2):
 - app/Jobs/ProcessPaidBatch.php
 - app/Jobs/ExpireUnpaidOrders.php

 Events (3):
 - app/Events/PaymentSuccessful.php
 - app/Events/PaymentFailed.php
 - app/Events/BatchProcessingComplete.php

 Listeners (3):
 - app/Listeners/SendPaymentSuccessNotification.php
 - app/Listeners/SendPaymentFailedNotification.php
 - app/Listeners/SendBatchCompleteNotification.php

 Notifications (3):
 - app/Notifications/PaymentSuccessful.php
 - app/Notifications/PaymentFailed.php
 - app/Notifications/BatchProcessingComplete.php

 Livewire (1):
 - app/Livewire/BatchDetails.php

 Views (5):
 - resources/views/livewire/batch-details.blade.php
 - resources/views/emails/payment-successful.blade.php
 - resources/views/emails/payment-failed.blade.php
 - resources/views/emails/batch-complete.blade.php
 - resources/views/receipts/payment.blade.php

 Translations (9):
 - lang/en/payment.php, lang/kz/payment.php, lang/ru/payment.php
 - lang/en/emails.php, lang/kz/emails.php, lang/ru/emails.php
 - lang/en/orders.php, lang/kz/orders.php, lang/ru/orders.php

 Tests (10):
 - tests/Unit/Services/AsiaPayServiceTest.php
 - tests/Unit/Services/PaymentServiceTest.php
 - tests/Unit/Models/OrderTest.php
 - tests/Unit/Models/PaymentTest.php
 - tests/Feature/Payment/PaymentInitiationTest.php
 - tests/Feature/Payment/WebhookTest.php
 - tests/Feature/Payment/OrderExpirationTest.php
 - tests/Feature/Jobs/ProcessPaidBatchTest.php
 - tests/Feature/Notifications/PaymentNotificationsTest.php
 - tests/Feature/Browser/PaymentFlowTest.php

 Factories (2):
 - database/factories/OrderFactory.php
 - database/factories/PaymentFactory.php

 Files to Modify (7)

 - app/Models/ImportBatch.php - Add order relationship and payment methods
 - app/Livewire/GtinImport.php - Add payment flow
 - app/Livewire/MyRequests.php - Add payment status display
 - routes/web.php - Add payment routes
 - routes/console.php - Add scheduled jobs
 - config/services.php - Add AsiaPay config
 - .env.example - Add environment variables

 ---
 Implementation Order

 1. Foundation (Day 1-2)
   - Create enums
   - Create Order & Payment models
   - Update ImportBatch model
   - Create factories
 2. Services (Day 3-5)
   - Create payment gateway interface
   - Implement AsiaPayService
   - Implement PaymentService
   - Implement OrderService
   - Create exceptions
 3. Controllers & Routes (Day 6)
   - Create PaymentController
   - Create WebhookController
   - Add routes
 4. Jobs & Events (Day 7-8)
   - Create jobs
   - Create events & listeners
   - Add console schedule
 5. UI Integration (Day 9-11)
   - Modify GtinImport component
   - Create BatchDetails component
   - Update MyRequests component
 6. Notifications (Day 12-13)
   - Configure email
   - Create notification classes
   - Create email templates
 7. Localization (Day 14)
   - Create translation files
   - Translate all strings
 8. Receipts (Day 15)
   - Install PDF library
   - Create ReceiptService
   - Create receipt template
 9. Testing (Day 16-19)
   - Write unit tests
   - Write feature tests
   - Write browser tests
   - Test with AsiaPay test mode
 10. QA & Polish (Day 20)
   - End-to-end testing
   - Multi-language verification
   - Run Pint formatter
   - Security review

 ---
 Key Design Decisions

 1. Service Architecture - Following existing patterns (GtinImportService, NationalCatalogService)
 2. Static Factory Methods - Use fromConfig() for services like AsiaPayService
 3. Queue-Based Processing - Dispatch ProcessPaidBatch job after payment success
 4. Event-Driven Notifications - Decouple emails from payment logic
 5. Idempotent Webhooks - Handle duplicate callbacks gracefully
 6. Security First - Webhook validation, HTTPS, proper authorization
 7. Multi-Language from Day 1 - All UI and emails localized
 8. Filament Ready - Proper model structure for future admin panel
 9. Comprehensive Testing - Full Pest coverage following existing patterns
 10. User Experience - Clear errors, retry functionality, automatic processing

 ---
 Security Checklist

 - HTTPS for all payment pages
 - Webhook signature validation
 - User authorization (can only pay own orders)
 - Never store card data
 - Comprehensive logging (all transactions)
 - Rate limiting on webhook endpoint
 - Validate payment amounts match
 - Environment variables for credentials
 - Idempotent webhook handling

 ---
 Post-Implementation

 After implementation complete:
 1. Configure real AsiaPay credentials
 2. Test with real payments in test mode
 3. Switch to production API URL
 4. Integrate Filament admin panel
 5. Monitor payment success rates
 6. Set up error alerting