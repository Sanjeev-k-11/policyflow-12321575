# PolicyFlow
### Fault-Tolerant Insurance Issuance & Reconciliation Engine
*Campus Placement Software Engineering Practical Assessment • TrippersShield*

---

## 1. Executive Summary & Core Engineering Objectives

**PolicyFlow** is a fault-tolerant travel-insurance distributor engine built with **PHP 8.2+** and **Laravel 11+**. It interfaces with an unreliable simulated external insurer to ingest, validate, price, and issue policies safely.

The primary engineering objective is **absolute idempotency and zero duplicate issuance** under:
- Repeated or near-simultaneous duplicate intake requests
- Network timeouts where the external insurer may have already created a policy upstream
- Duplicate or delayed webhook callback events
- Retries after temporary upstream failures

---

## 2. Implemented Reliability Cases

PolicyFlow implements all four key reliability scenarios:

| Case | Scenario | Reliability Defense Strategy | Automated Test |
| :--- | :--- | :--- | :--- |
| **Case A** | **Duplicate Intake** | Enforces `UNIQUE(agent_id, client_request_id)` at the database level. Catches duplicate collisions and concurrent race conditions within a transaction, returning the existing request with `HTTP 200 OK` (new requests receive `HTTP 201 Created`). | `PolicyIntakeApiTest::test_repeated_submission_returns_200_and_does_not_duplicate` |
| **Case B** | **Timeout After Success** | When a timeout occurs, PolicyFlow does **NOT** blindly reissue. Instead, it marks the attempt as `TIMEOUT`, transitions state to `NEEDS_RECONCILIATION`, and records the `provider_reference`. An automated/manual reconciliation endpoint queries `GET /api/mock-insurer/status/{ref}` to inspect upstream state and safely restores the policy to `ISSUED` with the exact upstream policy number. | `ReliabilityAndWorkflowTest::test_timeout_after_success_is_flagged_for_reconciliation_and_resolved_without_double_issuance` |
| **Case C** | **Duplicate Callback Idempotency** | Webhook events are tracked in `provider_events` with `UNIQUE(provider_event_id)`. The first callback updates state and marks `PROCESSED`. Subsequent identical callbacks are detected via unique index and safely acknowledged as `IGNORED_DUPLICATE` without state mutation. Furthermore, **terminal state defense** ensures an incoming delayed failure callback cannot overwrite a confirmed `ISSUED` policy. | `ReliabilityAndWorkflowTest::test_duplicate_callback_is_applied_once_and_subsequent_events_are_safely_ignored`<br>`ReliabilityAndWorkflowTest::test_repeated_or_delayed_failure_callback_cannot_overwrite_confirmed_issued_state` |
| **Case D** | **Retry After Temporary Failure** | Upstream 503/timeout responses mark the attempt as `FAILED`. A subsequent retry uses pessimistic row locking (`lockForUpdate`), reuses the immutable pricing snapshot accepted at intake, and executes without duplicating records. | `ReliabilityAndWorkflowTest::test_retry_after_temporary_failure_preserves_premium_and_issues_successfully` |

---

## 3. Architecture & Concurrency Strategy

```
  +------------------+         +-----------------------+         +---------------------+
  |   Agent Client   |         |      PolicyFlow       |         |     Mock Insurer    |
  +------------------+         +-----------------------+         +---------------------+
           |                               |                                |
   POST /api/policy-requests               |                                |
  ---------------------------------------->|                                |
           |     1. Intake & Pricing       |                                |
           |     - DB UNIQUE(agent,req)    |                                |
           |     - BCMath Math Snapshot    |                                |
           |<------------------------------|                                |
           |   HTTP 201 (or 200 if dup)    |                                |
           |                               |                                |
   POST /api/policy-requests/{id}/submit   |                                |
  ---------------------------------------->|                                |
           |     2. Issuance Engine        |                                |
           |     - lockForUpdate()         |  POST /api/mock-insurer/issue  |
           |     - Check if already ISSUED |------------------------------->|
           |     - Dispatch attempt        |                                |
           |                               |<-------------------------------|
           |                               |  Response (or simulated 504)   |
           |                               |                                |
           |     3. Reconciliation (if 504)|                                |
           |                               |  GET /mock-insurer/status/{ref}|
           |                               |------------------------------->|
           |                               |<-------------------------------|
           |                               |  Status: ISSUED (restored)     |
           |                               |                                |
           |                               |<-------------------------------|
           |                               |  POST /mock-insurer/callback   |
           |                               |  - DB UNIQUE(event_id)         |
           |                               |  - Terminal state defense      |
```

### Concurrency & Locking Implementation:
1. **Database Constraints Over Code Logic**: Idempotency is enforced by primary DB uniqueness (`unique_agent_client_request`, `policy_number`, `provider_event_id`). Application code wraps inserts in transactions and catches constraint exceptions (`QueryException 1062`).
2. **Pessimistic Row Locking (`lockForUpdate`)**: When `/submit`, `/reconcile`, or `/callback` executes, the engine locks the target `PolicyRequest` row. Concurrent requests wait on the lock and inspect the updated state upon acquisition, preventing race conditions.
3. **Explicit State Machine**: Only explicitly permitted state transitions are allowed. Any regression (e.g. `ISSUED → SUBMITTED`) throws `InvalidStateTransitionException`.

---

## 4. Deterministic Pricing Logic

Formula:
$$\text{Premium} = \text{base\_daily\_rate} \times \text{travel\_days} \times \text{age\_multiplier} \times \text{destination\_multiplier} \times \text{coverage\_multiplier}$$

Implemented with **BCMath** arbitrary-precision arithmetic (`bcmul`, decimal-safe half-up rounding):

- **Base daily rate**: `BASIC = ₹12.00`, `PLUS = ₹20.00`, `PREMIUM = ₹30.00`
- **Age multiplier** (at trip start date):
  - $0 - 17$: `1.10x`
  - $18 - 60$: `1.00x`
  - $61 - 70$: `1.50x`
  - $71+$: `2.00x`
- **Destination multiplier**: `DOMESTIC = 1.00x`, `INTERNATIONAL = 1.80x`
- **Coverage multiplier**: `₹100,000 = 1.00x`, `₹300,000 = 1.25x`, `₹500,000 = 1.50x`
- **Days**: Inclusive `trip_end - trip_start + 1`, minimum 1 day.

*Specification Verification Check:*
`PLUS` (₹20), `5 days`, `INTERNATIONAL` (1.80x), `₹300,000` (1.25x), `age 28` (1.00x):
$$20.00 \times 5 \times 1.00 \times 1.80 \times 1.25 = \mathbf{₹225.00}$$

---

## 5. Local Setup & Execution Guide

### Prerequisites
- PHP 8.2+ with `bcmath`, `pdo_mysql` (or `pdo_sqlite`)
- Composer 2.x
- Node.js 18+ and NPM
- MySQL 8+ / MariaDB (or SQLite for instant zero-dependency execution)

### Setup Commands

```bash
# 1. Clone repository and enter directory
cd shield

# 2. Install PHP backend dependencies
composer install

# 3. Configure environment file
cp .env.example .env

# Generate application encryption key
php artisan key:generate

# 4. Database configuration (MySQL or SQLite)
# For MySQL (default in .env):
# Ensure MySQL is running on 127.0.0.1:3306 with database 'shield'
# Or switch DB_CONNECTION=sqlite in .env for zero-config SQLite

# Run database migrations
php artisan migrate

# Seed database with ~25 realistic fabricated policy records
php artisan db:seed

# 5. Install frontend dependencies and build assets
npm install
npm run build

# 6. Run the local development server
php artisan serve
```

Access the UI at: **`http://127.0.0.1:8000`**

---

## 6. Running Automated Tests

Run the full automated test suite (Feature and Unit tests) with a single command:

```bash
php artisan test
```

### Test Coverage Highlights:
- **`PricingServiceTest`**: Tests exact specification example (`₹225.00`), boundary checks for every age bracket (17/18, 60/61, 70/71), minimum 1-day logic, and invalid parameter rejections.
- **`PolicyStateMachineTest`**: Tests happy path, legal retries from `FAILED`, and verifies that `ISSUED` policies cannot be mutated or regressed.
- **`PolicyIntakeApiTest`**: Tests validation rules, 422 errors, 201 creation, and 200 duplicate intake resolution.
- **`ReliabilityAndWorkflowTest`**: Tests all four reliability cases (concurrent intake, timeout after success & reconciliation, duplicate callback idempotency, safe retries).

---

## 7. API Contract & Response Format

| Method | Endpoint | Purpose |
| :--- | :--- | :--- |
| `POST` | `/api/policy-requests` | Create new request (201) or safely return existing request (200) |
| `GET` | `/api/policy-requests/{id}` | Retrieve request details, status, pricing breakdown, and full audit trail |
| `POST` | `/api/policy-requests/{id}/submit` | Start or safely resume issuance (pessimistic lock) |
| `POST` | `/api/policy-requests/{id}/reconcile` | Query upstream insurer status and resolve timeout |
| `GET` | `/api/policy-requests` | List policy requests with status filter & pagination |
| `POST` | `/api/mock-insurer/callback` | Webhook receiver for simulated provider events (idempotent) |
| `GET` | `/api/mock-insurer/status/{provider_reference}` | Status query endpoint exposed by mock insurer for reconciliation |

*Detailed curl commands and JSON examples are available in [`examples/curl_cheat_sheet.md`](file:///c:/Users/ASUS/Desktop/shield/examples/curl_cheat_sheet.md).*

---

## 8. User Interface

PolicyFlow includes a responsive **Fintech Glassmorphism Dashboard**:
1. **Operations Dashboard (`/`)**: Bento grid with count-up statistics, SVG status distribution ring, pipeline architecture cards, and live audit activity feed.
2. **Requests Ledger (`/requests`)**: Filter chips, search bar, status pills, and responsive ledger.
3. **New Request Form (`/requests/create`)**: Live dynamic pricing preview updating multipliers in real-time as inputs change, inline validation error mapping, and a 1-click button to load the assessment example.
4. **Request Detail View (`/requests/{id}`)**: Immutable snapshot breakdown, provider references, vertical audit timeline with glowing status dots, and contextual action buttons (Submit, Retry, Reconcile).
5. **Mock Insurer Laboratory (`/mock-lab`)**: Interactive test bench allowing reviewers to fire any of the 5 simulated scenarios and test duplicate webhook deliveries live.
6. **Dark Mode**: Persisted dark-theme toggle with backdrop saturation.

---

## 9. Assumptions & Known Limitations

1. **Simulated Insurer**: The external insurer is simulated within dedicated mock routes and datastores (`mock_insurer_policies`). No real-world third-party credentials or paid APIs are required.
2. **Reconciliation Trigger**: In addition to manual operator reconciliation via UI and REST API (`POST /api/policy-requests/{id}/reconcile`), a scheduled background command (`php artisan schedule:run`) could be attached in high-volume production deployments.
3. **Single Currency**: All financial figures are denominated in Indian Rupees (INR, ₹).
