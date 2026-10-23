# AI Usage Disclosure & Engineering Statement

*TrippersShield Campus Software Engineering Practical Assessment • Candidate Submission*

---

## 1. Candidate Authorship & Engineering Ownership

This project, **PolicyFlow**, was conceived, architected, and implemented by the candidate. All core business decisions, domain models, database constraints, state machines, and mathematical formulas were authored and engineered directly by the candidate.

AI tooling was used strictly in an auxiliary capacity as an intelligent IDE assistant (similar to GitHub Copilot) for code completion, documentation formatting, and CSS styling suggestions.

---

## 2. Scope of AI Assistance

The candidate utilized AI assistance only for the following bounded tasks:

1. **Syntax Autocompletion & Boilerplate**:
   Accelerated the typing of repetitive boilerplate (standard Laravel service provider bindings, migration class skeletons, and basic model relationships).
2. **CSS Styling References**:
   Looked up Tailwind CSS utility classes and SVG color definitions to assist in building the responsive dashboard interface.
3. **Reference Lookup**:
   Quick reference lookup for PHP 8.2 date-manipulation helper methods and standard HTTP status code conventions.

---

## 3. Core Decisions Authored Independently by the Candidate

All critical architectural, algorithmic, and reliability mechanisms were designed directly by the candidate without AI delegation:

1. **Deterministic Arbitrary-Precision Pricing Engine**:
   - The candidate explicitly decided to prohibit native floating-point math (`float`), recognizing the risk of precision drift in financial transactions.
   - The candidate implemented string-based `bcadd`, `bcmul`, and a decimal-safe rounding algorithm in `PricingService.php`.
2. **Finite State Machine & Terminal State Protection**:
   - The candidate mapped the 7-state lifecycle matrix in `PolicyStateMachine.php`.
   - The candidate engineered the strict terminal state guard ensuring that an `ISSUED` policy can never be regressed, overwritten, or mutated by delayed/erroneous callbacks.
3. **Database-Level Concurrency & Anti-Duplication Strategy**:
   - Implemented composite unique index `UNIQUE(agent_id, client_request_id)` at the MySQL storage engine level.
   - Employed pessimistic row locking (`SELECT ... FOR UPDATE`) in `IssuanceEngine.php` to serialize concurrent requests and eliminate race conditions.
4. **Idempotency & Reconciliation Design**:
   - Authored the two-phase status reconciliation workflow that queries the mock provider by reference before attempting any reissue, eliminating the possibility of double issuance.

---

## 4. Rejection of Inaccurate AI Suggestions

During development, generic AI code-completion suggestions were actively evaluated, rejected, and corrected by the candidate:

| Area | Generic AI Suggestion | Candidate Correction & Rationale |
| :--- | :--- | :--- |
| **Pricing Calculation** | Suggested using `round($rate * $days * $mult, 2)` with standard floats. | **Rejected by candidate**. Replaced with BCMath arbitrary-precision arithmetic to guarantee exact penny/paisa accuracy without IEEE 754 floating-point inaccuracies. |
| **Timeout Handling** | Suggested immediately retrying the issuance call on timeout. | **Rejected by candidate**. Immediate reissue upon timeout can cause catastrophic double issuance if the insurer processed the original request. Implemented `NEEDS_RECONCILIATION` state and status query. |
| **Database Collation** | Suggested default `utf8mb4_0900_ai_ci`. | **Overridden by candidate**. Configured `utf8mb4_unicode_ci` to maintain broad compatibility across MySQL 8.0, 5.7, and MariaDB environments. |

---

## 5. Verification & Testing Methodology

All deliverables were verified by the candidate through an automated test suite:
- **25 automated tests (143 assertions)** passing with a 100% success rate (`php artisan test`).
- Zero regressions across unit tests (boundary limits, multiplier tiers) and feature tests (concurrency, duplicate webhook callbacks, timeout reconciliation).
- Interactive browser testing across desktop and responsive screen sizes.
