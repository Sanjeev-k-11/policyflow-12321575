# PolicyFlow API Curl Cheat Sheet & Response Formats

Base URL: `http://127.0.0.1:8000`

---

## 1. Policy Request Intake
**`POST /api/policy-requests`**

### New Request (HTTP 201 Created)
```bash
curl -X POST "http://127.0.0.1:8000/api/policy-requests" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "client_request_id": "AGT-2026-0001",
    "agent_id": "AGENT-17",
    "customer_name": "Sample Customer",
    "date_of_birth": "1998-04-10",
    "destination": "INTERNATIONAL",
    "trip_start": "2026-11-10",
    "trip_end": "2026-11-14",
    "plan_code": "PLUS",
    "sum_insured": 300000
  }'
```

**JSON Response (201 Created):**
```json
{
  "success": true,
  "is_duplicate": false,
  "message": "Policy request received and priced successfully.",
  "data": {
    "id": 1,
    "client_request_id": "AGT-2026-0001",
    "agent_id": "AGENT-17",
    "customer_name": "Sample Customer",
    "date_of_birth": "1998-04-10",
    "destination": "INTERNATIONAL",
    "trip_start": "2026-11-10",
    "trip_end": "2026-11-14",
    "plan_code": "PLUS",
    "sum_insured": 300000,
    "status": "RECEIVED",
    "policy_number": null,
    "provider_reference": null,
    "attempt_count": 0,
    "pricing_breakdown": {
      "travel_days": 5,
      "age_at_trip_start": 28,
      "base_daily_rate": "20.00",
      "age_multiplier": "1.00",
      "destination_multiplier": "1.80",
      "coverage_multiplier": "1.25",
      "calculated_premium": "225.00",
      "currency": "INR",
      "formula": "base_daily_rate × travel_days × age_multiplier × destination_multiplier × coverage_multiplier"
    },
    "created_at": "2026-10-23T14:40:00Z"
  }
}
```

### Repeated Submission (HTTP 200 OK)
Repeating the same `agent_id` + `client_request_id`:
```json
{
  "success": true,
  "is_duplicate": true,
  "message": "Policy request already exists for this agent and client_request_id. Safely returning existing record.",
  "data": { ... }
}
```

---

## 2. Start or Resume Issuance
**`POST /api/policy-requests/{id}/submit`**

```bash
curl -X POST "http://127.0.0.1:8000/api/policy-requests/1/submit" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"scenario": "SUCCESS"}'
```

**JSON Response (200 OK):**
```json
{
  "success": true,
  "message": "Policy issued successfully.",
  "status": "ISSUED",
  "policy_number": "POL-2026-IN001",
  "provider_reference": "REF-INS-A1B2C3D4E5",
  "data": { ... }
}
```

---

## 3. Reconcile Stale or Timed-Out Request
**`POST /api/policy-requests/{id}/reconcile`**

```bash
curl -X POST "http://127.0.0.1:8000/api/policy-requests/1/reconcile" \
  -H "Accept: application/json"
```

**JSON Response (200 OK):**
```json
{
  "success": true,
  "message": "Reconciliation successful: policy confirmed and marked ISSUED.",
  "data": {
    "status": "ISSUED",
    "policy_number": "POL-2026-IN001",
    "provider_reference": "REF-INS-A1B2C3D4E5"
  }
}
```

---

## 4. Webhook Callback Receiver
**`POST /api/mock-insurer/callback`**

```bash
curl -X POST "http://127.0.0.1:8000/api/mock-insurer/callback" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "event_id": "EVT-2026-9901",
    "provider_reference": "REF-INS-A1B2C3D4E5",
    "event_type": "POLICY_ISSUED",
    "policy_number": "POL-2026-CONFIRMED-99"
  }'
```

**Duplicate Delivery Response (200 OK):**
```json
{
  "success": true,
  "status": "IGNORED_DUPLICATE",
  "event_id": "EVT-2026-9901",
  "message": "Callback event already received and processed. Duplicate safely ignored."
}
```

---

## 5. Mock Insurer Status Query
**`GET /api/mock-insurer/status/{provider_reference}`**

```bash
curl -X GET "http://127.0.0.1:8000/api/mock-insurer/status/REF-INS-A1B2C3D4E5" \
  -H "Accept: application/json"
```

**JSON Response (200 OK):**
```json
{
  "found": true,
  "provider_reference": "REF-INS-A1B2C3D4E5",
  "status": "ISSUED",
  "policy_number": "POL-2026-IN001",
  "customer_name": "Sample Customer",
  "premium": "225.00"
}
```

---

## 6. List Requests with Filters & Pagination
**`GET /api/policy-requests?status=ISSUED&per_page=15`**

```bash
curl -X GET "http://127.0.0.1:8000/api/policy-requests?status=ISSUED&per_page=15" \
  -H "Accept: application/json"
```
