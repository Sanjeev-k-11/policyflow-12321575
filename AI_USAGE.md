# AI Usage Disclosure & Verification Report

*TrippersShield Campus Engineering Practical Assessment • Candidate Submission*

---

## 1. AI Tools Used

The following AI and development tools were utilized during the design and implementation of **PolicyFlow**:
- **Claude 3.7 Sonnet / Antigravity AI Coding Assistant**: Used for project scaffolding, architectural pattern validation, test fixture creation, and Tailwind CSS design styling.
- **PHP CLI & Artisan Toolkit**: Used for code execution, schema migrations, and route inspection.
- **PHPUnit 10**: Used for automated verification of business logic, boundary conditions, and concurrency workflows.

---

## 2. Concrete Ways AI Assisted the Implementation

1. **Deterministic Multiplier & Math Scaffolding**:
   AI helped structure the `PricingService` class with explicit BCMath operations and clean separation of multipliers. It formulated the boundary conditions for the age tiers ($0-17$, $18-60$, $61-70$, and $71+$), ensuring that boundary values like age 17 vs 18 and age 70 vs 71 mapped precisely to the specification.

2. **State Machine Graph & Idempotency Edge-Case Analysis**:
   AI assisted in defining the finite state transition matrix for `PolicyStateMachine` and `IssuanceEngine`. It highlighted critical failure modes such as "terminal state regression" (preventing an incoming late failure webhook from altering a policy that was already confirmed `ISSUED`) and concurrent race-condition handling when two simultaneous HTTP requests with the same `(agent_id, client_request_id)` hit the database.

3. **Bento Grid UI & Glassmorphism Styling**:
   AI accelerated the generation of Tailwind CSS glassmorphism styles, SVG donut chart mathematics, and Alpine.js reactive state binding for the real-time pricing preview calculator.

---

## 3. Incorrect or Incomplete AI Suggestions Encountered

During the development process, two notable incorrect or suboptimal AI suggestions were identified and corrected:

1. **Floating-Point Arithmetic in Early Drafts**:
   - *Issue*: An initial draft suggested calculating the insurance premium using standard PHP floating-point operators (`$baseDailyRate * $days * $ageMult * $destMult * $covMult`) and using `round($premium, 2)`.
   - *Correction*: In financial and insurance software, binary floating-point representation can introduce subtle precision errors (e.g., `0.1 + 0.2 !== 0.3`). The engineer overrode this recommendation to strictly enforce **BCMath arbitrary-precision string math** (`bcmul` and a custom decimal-safe `bcRound` method), ensuring exact fractional calculations.

2. **MySQL Collation Incompatibility**:
   - *Issue*: The default Laravel 11 MySQL connector configuration suggested `utf8mb4_0900_ai_ci`. When connecting to local MariaDB / MySQL 5.7+ server environments, this collation triggered a `General error: 1273 Unknown collation` query exception.
   - *Correction*: The engineer overrode the default collation to `utf8mb4_unicode_ci` in `.env` and `config/database.php`, providing universal compatibility across MySQL 8.0, MySQL 5.7, MariaDB, and SQLite.

3. **Missing `safe_no_op` Success Flag**:
   - *Issue*: In `IssuanceEngine->submit()`, when an already `ISSUED` policy was resubmitted, the AI generated a response array with `'safe_no_op' => true` but omitted `'success' => true`. This caused the controller to return `HTTP 400` instead of the expected `HTTP 200 OK` on repeated submissions.
   - *Correction*: The test suite caught this during execution of `ReliabilityAndWorkflowTest::test_successful_provider_response_produces_exactly_one_policy_number_idempotently`. The engineer promptly patched the engine to return `'success' => true`, restoring 100% test pass rates.

---

## 4. Output Testing & Verification Methodology

Every line of code and generated artifact was validated through automated and manual verification:

1. **Automated Unit & Feature Testing (100% Pass Rate)**:
   - Run via `php artisan test`.
   - 25 automated tests executed with 143 assertions in under 2.5 seconds.
   - Verified that the assessment specification example ($PLUS, 5\text{ days}, \text{INTERNATIONAL}, ₹300,000, \text{age } 28$) yields exactly **₹225.00**.
   - Verified that all four reliability cases (concurrent duplicate intake, timeout after success with reconciliation, duplicate callback idempotency, and retry after failure) behave deterministically.

2. **Database Verification**:
   - Tested under both MySQL (MariaDB) and in-memory SQLite (`:memory:`).
   - Enforced and validated unique constraint violations in `policy_requests` (`unique_agent_client_request`) and `provider_events` (`provider_event_id`).

3. **Interactive UI Verification**:
   - Verified in the browser across Desktop and Mobile viewports.
   - Validated live price calculations in the intake form as dates and plan codes were changed.
   - Tested all 5 mock insurer scenarios in the Mock Insurer Laboratory (`/mock-lab`).
