# GTIN Bulk Import & National Catalog Sync
Laravel 12 · Livewire · Alpine.js

---

## 1. Project Purpose

The purpose of this project is to build a **one-page web application** that allows users to upload an Excel file containing multiple **GTIN numbers**.  
The application checks whether each GTIN already exists in the local database.  
If a GTIN does not exist, the system fetches product data from the **National Catalog of Kazakhstan** via an external API and saves it locally.

The system must respect **API request limits** and therefore must process external requests **asynchronously using queues**.

### Key Goals
- Bulk GTIN import via Excel
- Prevent duplicate product records
- Automatic synchronization with National Catalog API
- Safe handling of API rate limits
- Simple, non-technical user experience

---

## 2. Project Scope

### In Scope (v1)
- One-page web interface
- Excel file upload (.xlsx, .xls)
- GTIN extraction and validation
- Database lookup by GTIN
- External API integration
- Queued processing of API requests
- User feedback on processing status

### Out of Scope (v1)
- User authentication
- Manual product editing
- Multi-language UI
- Exporting results to Excel
- Admin dashboards

---

## 3. Technical Stack

### Backend
- Laravel 12
- Livewire (stateful UI, file upload)
- Laravel Queue (rate limiting & async processing)
- MySQL 

### Frontend
- Livewire Components
- Alpine.js (progress, UI interactivity)
- Tailwind CSS (optional but recommended)

### External Services
- National Catalog API  
  Documentation: https://nationalcatalog.kz/gwp/docs  
- API authentication via API Key (stored securely in `.env`)

---

## 4. Database Structure

###  `products`
id
gtin
ntin
nameKk
nameRu
nameEn
shortNameKk
shortNameRu
shortNameEn
createdDate
updatedDate

## 5. Architectural Principles

The application must follow these rules:

- Single-page application using Livewire
- No frontend framework other than Alpine.js
- No API calls from frontend
- All external API communication must happen in backend services or jobs
- Heavy processing must never block the request cycle
- Queue-based architecture is mandatory for external API calls
- Code must follow Laravel best practices and conventions

## 6. Data Flow & Processing Rules

1. User uploads Excel file
2. File is validated (type, size)
3. GTINs are extracted from the file
4. GTINs are normalized (trimmed, numeric only)
5. Duplicate GTINs within the file are removed
6. Each GTIN is checked against the `products` table
7. Existing GTINs are skipped
8. Missing GTINs are queued for external API processing
9. External API responses are validated
10. Valid responses are saved to the database
11. Failures are logged and marked

## 7. GTIN Rules

- GTIN must be numeric
- Allowed lengths:  13
- Invalid GTINs must be skipped
- Invalid GTINs must not be sent to external API
- GTIN must be unique in the `products` table
- Database column `gtin` must be indexed

## 8. External API Usage Rules

- API key must be stored in `.env`
- API calls must be wrapped in a dedicated service class
- API must be accessed only from queue jobs
- API rate limits must be respected using delayed jobs
- API responses must be validated before saving
- On API failure, retry must be attempted
- On permanent failure, error must be logged

## 9. Queue Strategy

- Each GTIN is processed by a separate job
- Jobs must be delayed to avoid rate limit violations
- Delay strategy: incremental delay per GTIN
- Retry attempts: 3
- Backoff strategy: 10s, 30s, 60s
- Failed jobs must not block other GTINs

## 10. UI Behavior Rules

- The page must not reload during processing
- User must see processing status
- UI must show:
  - Total GTINs
  - Processed count
  - Success count
  - Failed count
- Errors must be user-friendly
- Technical errors must not be exposed to users

## 11. Naming Conventions

- Livewire component: `GtinImport`
- Job: `FetchProductFromNationalCatalog`
- Service: `NationalCatalogService`
- Queue name: `national-catalog`
- Environment variables:
  - NATIONAL_CATALOG_API_KEY
  - NATIONAL_CATALOG_API_URL

## 12. Error Handling Rules

- Validation errors must stop processing early
- API errors must not crash the application
- Failed GTINs must be tracked
- Errors must be logged using Laravel logging
- Queue failures must be retryable


## 13. Acceptance Criteria

The implementation is considered complete when:

- Excel file upload works
- GTINs are validated and deduplicated
- Existing products are not duplicated
- Missing products are fetched via API
- API calls are queued and rate-limited
- Products are saved correctly
- User receives processing feedback
- No API call is made synchronously


## 13. Acceptance Criteria

The implementation is considered complete when:

- Excel file upload works
- GTINs are validated and deduplicated
- Existing products are not duplicated
- Missing products are fetched via API
- API calls are queued and rate-limited
- Products are saved correctly
- User receives processing feedback
- No API call is made synchronously