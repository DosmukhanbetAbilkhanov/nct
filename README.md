# GTIN Bulk Import & National Catalog Sync

A Laravel 12 application for bulk importing GTINs (Global Trade Item Numbers) from Excel/CSV files and synchronizing product data with Kazakhstan's National Catalog API.

## Features

- **Bulk GTIN Import**: Upload Excel or CSV files containing GTINs
- **Automatic API Sync**: Fetches product data from National Catalog API
- **Queue-Based Processing**: Asynchronous processing with rate limiting (30 req/min)
- **Real-Time Progress**: Live progress tracking with Livewire polling
- **Error Handling**: Comprehensive error handling and retry mechanisms
- **Download Template**: Provides a sample template for users

## Requirements

- PHP 8.4+
- MySQL/MariaDB
- Composer
- Node.js & NPM
- National Catalog API Key

## Installation

### 1. Clone and Install Dependencies

```bash
# Clone repository
git clone <repository-url>
cd nct

# Install PHP dependencies
composer install

# Install Node dependencies
npm install
```

### 2. Environment Configuration

```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

### 3. Configure Environment Variables

Edit `.env` and set the following:

```env
# Application
APP_NAME="GTIN Import"
APP_URL=https://nct.test  # Or your Herd domain

# Database
DB_CONNECTION=mysql
DB_DATABASE=nct
DB_USERNAME=root
DB_PASSWORD=

# Queue (already configured)
QUEUE_CONNECTION=database

# National Catalog API
NATIONAL_CATALOG_API_KEY=your_api_key_here
NATIONAL_CATALOG_BASE_URL=https://nationalcatalog.kz/gwp
```

### 4. Database Setup

```bash
# Run migrations (creates products, import_batches, import_batch_items, jobs, failed_jobs tables)
php artisan migrate
```

### 5. Build Assets

```bash
# Build frontend assets
npm run build

# Or for development with hot reload
npm run dev
```

## Usage

### Development Mode

The easiest way to run the application in development:

```bash
composer run dev
```

This starts:
- Laravel development server (http://localhost:8000)
- Queue worker (processes import jobs)
- Log viewer (Pail)
- Vite dev server (hot module reload)

### Production Queue Worker

For production, run a dedicated queue worker:

```bash
php artisan queue:work --queue=national-catalog --tries=3 --timeout=90
```

**Queue Worker Options:**
- `--queue=national-catalog`: Process jobs from the national-catalog queue
- `--tries=3`: Retry failed jobs up to 3 times
- `--timeout=90`: Maximum execution time per job (90 seconds)
- `--sleep=3`: Seconds to wait when no jobs available
- `--max-jobs=1000`: Restart worker after processing 1000 jobs
- `--max-time=3600`: Restart worker after 1 hour

### Supervisor Configuration (Production)

For production deployments, use Supervisor to keep the queue worker running:

```ini
[program:nct-queue-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/nct/artisan queue:work --queue=national-catalog --tries=3 --timeout=90 --sleep=3
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/path/to/nct/storage/logs/queue-worker.log
stopwaitsecs=3600
```

## Application Workflow

### 1. Upload File

- Navigate to the homepage
- Download the template (optional) to see the expected format
- Upload an Excel (.xlsx, .xls) or CSV file
- File must contain GTINs in column A (13-digit numeric codes)

### 2. Processing

- System validates and extracts GTINs from the file
- Creates an import batch with all items
- Dispatches queue jobs for each GTIN
- Jobs are rate-limited to 30 requests/minute

### 3. Progress Tracking

- Real-time progress updates every 5 seconds
- View statistics: Total, Processed, Successful, Failed
- Expandable failed items table with error messages

### 4. Results

- Successfully imported products are saved to the database
- Failed items show specific error messages
- Option to upload a new file when complete

## Queue Management

### Monitor Queue

```bash
# View pending jobs
php artisan queue:monitor

# View failed jobs
php artisan queue:failed

# Retry all failed jobs
php artisan queue:retry all

# Retry specific job
php artisan queue:retry <job-id>

# Clear failed jobs table
php artisan queue:flush
```

### Retry Failed Batch Items

If items in a batch failed, you can retry them:

```bash
# Retry all failed items in a specific batch
php artisan import:retry-failed <batch_id>
```

This command will:
- Find all failed items in the batch
- Reset their status to pending
- Re-dispatch jobs to the queue
- Update batch counters

### Clear Queue

```bash
# Clear all jobs from a queue
php artisan queue:clear --queue=national-catalog
```

## File Format

### Excel/CSV Template

Download the template from the application homepage or create a file with this format:

```csv
GTIN
4607001774419
4607001774426
4607001774433
4607001774440
4607001774457
```

**Requirements:**
- GTINs must be in column A
- Each GTIN must be 13 digits
- Only numeric characters
- Duplicates are automatically removed
- Empty rows are skipped

## API Rate Limiting

The National Catalog API is rate-limited to **30 requests per minute**. The application handles this automatically:

- Jobs use `RateLimited` middleware
- Rate limit exceptions release jobs back to queue
- Retry happens after delay specified in `Retry-After` header
- No manual intervention needed

## Error Handling

### Error Types

1. **Product Not Found** - GTIN not in National Catalog (404)
2. **Rate Limit Exceeded** - Too many requests (429) - auto-retried
3. **API Errors** - Server errors (500+) - retried with backoff
4. **Invalid GTIN** - Wrong format or length - marked as failed
5. **File Errors** - Invalid file format or no valid GTINs

### Failed Job Handling

Jobs are automatically retried using exponential backoff:
- 1st retry: after 60 seconds
- 2nd retry: after 300 seconds (5 minutes)
- 3rd retry: after 900 seconds (15 minutes)

After 3 failures, jobs are marked as permanently failed and logged.

## Database Schema

### Products Table

Stores imported product data from National Catalog:

- `id` - Primary key
- `gtin` - 13-digit GTIN (unique, indexed)
- `ntin` - National Trade Item Number
- `nameKk` - Kazakh name
- `nameRu` - Russian name
- `nameEn` - English name
- `shortNameKk` - Kazakh short name
- `shortNameRu` - Russian short name
- `shortNameEn` - English short name
- `createdDate` - Creation date from API
- `updatedDate` - Update date from API
- `timestamps` - Laravel timestamps

### Import Batches Table

Tracks batch-level import metadata:

- `id` - Primary key
- `filename` - Original file name
- `total_gtins` - Total GTINs in batch
- `processed_count` - Number processed
- `success_count` - Number successful
- `failed_count` - Number failed
- `status` - Batch status (pending/processing/completed/failed)
- `started_at` - Processing start time
- `completed_at` - Processing completion time
- `timestamps` - Laravel timestamps

### Import Batch Items Table

Tracks individual GTIN processing:

- `id` - Primary key
- `import_batch_id` - Foreign key to import_batches
- `gtin` - GTIN being processed
- `status` - Item status (pending/processing/success/failed)
- `error_message` - Error message if failed
- `product_id` - Foreign key to products (if successful)
- `timestamps` - Laravel timestamps

## Testing

### Run All Tests

```bash
php artisan test
```

### Run Specific Test Suite

```bash
# Livewire component tests
php artisan test --filter=GtinImportTest

# Job tests
php artisan test --filter=FetchProductFromNationalCatalogTest

# Service tests
php artisan test --filter=NationalCatalogServiceTest
```

### Test Coverage

- 54 tests with 148 assertions
- Unit tests for models and services
- Feature tests for jobs and imports
- Livewire component tests
- Error scenario tests

## Troubleshooting

### Queue Not Processing

```bash
# Check if queue worker is running
ps aux | grep "queue:work"

# Check for failed jobs
php artisan queue:failed

# Restart queue worker
composer run dev  # Development
# Or
supervisorctl restart nct-queue-worker  # Production
```

### Database Connection Issues

```bash
# Test database connection
php artisan tinker
>>> DB::connection()->getPdo();

# Clear config cache
php artisan config:clear
```

### Import Stuck in Processing

If a batch is stuck in "processing" status:

1. Check queue worker is running
2. Check for failed jobs: `php artisan queue:failed`
3. Retry failed jobs: `php artisan queue:retry all`
4. Retry specific batch: `php artisan import:retry-failed <batch_id>`

### Rate Limit Errors

If you see many rate limit errors:

- This is normal - jobs will auto-retry
- Ensure only 1-2 queue workers are running
- Check `bootstrap/app.php` rate limiter configuration
- Default is 30 requests/minute per API requirements

## Architecture

### Key Components

1. **Livewire Component** (`app/Livewire/GtinImport.php`)
   - Handles file upload
   - Manages UI state
   - Polls for progress updates

2. **Import Service** (`app/Services/GtinImportService.php`)
   - Processes uploaded files
   - Creates batch records
   - Dispatches jobs

3. **National Catalog Service** (`app/Services/NationalCatalogService.php`)
   - Handles API communication
   - Retry logic with exponential backoff
   - Error handling

4. **Queue Job** (`app/Jobs/FetchProductFromNationalCatalog.php`)
   - Fetches product data from API
   - Saves to database
   - Updates batch counters
   - Rate-limited processing

5. **Import Classes** (`app/Imports/GtinsImport.php`)
   - Parses Excel/CSV files
   - Validates GTIN format
   - Removes duplicates

### Design Decisions

- **Queue-First**: All API calls are asynchronous to prevent timeouts
- **Rate Limiting**: Implemented at job level using Laravel's RateLimited middleware
- **Polling**: Livewire polls every 5 seconds for progress (simple, works everywhere)
- **Transactions**: All database counter updates use transactions for atomicity
- **Exception Handling**: Custom exceptions for different error scenarios
- **Duplicate Detection**: Checks database before calling API to save requests

## Tech Stack

- **Laravel 12** - Backend framework
- **Livewire 3** - Frontend reactivity
- **Alpine.js** - UI interactions (drag-and-drop, expandable sections)
- **Tailwind CSS 4** - Styling
- **Laravel Excel** - Excel/CSV parsing
- **Pest 4** - Testing framework
- **MySQL** - Database

## License

MIT License
