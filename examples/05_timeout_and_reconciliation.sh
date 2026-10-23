#!/usr/bin/env bash
# ==============================================================================
# 05. Reliability Case B: Timeout After Issue & Safe Reconciliation
# 1. Submits with TIMEOUT_AFTER_ISSUE
# 2. Inspects request placed in NEEDS_RECONCILIATION
# 3. Executes POST /api/policy-requests/{id}/reconcile to query upstream status
# 4. Safely restores to confirmed ISSUED state without double-issuance
# ==============================================================================

BASE_URL="http://127.0.0.1:8000"
REQ_ID="DEMO-TIMEOUT-$RANDOM"

echo "Step 1: Create request..."
INTAKE_RES=$(curl -s -X POST "${BASE_URL}/api/policy-requests" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "client_request_id": "'"$REQ_ID"'",
    "agent_id": "AGENT-RECON",
    "customer_name": "Timeout Demo Customer",
    "date_of_birth": "1992-05-15",
    "destination": "INTERNATIONAL",
    "trip_start": "2026-11-20",
    "trip_end": "2026-11-25",
    "plan_code": "PLUS",
    "sum_insured": 300000,
    "scenario": "TIMEOUT_AFTER_ISSUE"
  }')

POLICY_ID=$(echo "$INTAKE_RES" | jq -r '.data.id')
echo "Created Policy ID: $POLICY_ID"

echo -e "\nStep 2: Submit with simulated timeout (HTTP 202 expected)..."
curl -s -X POST "${BASE_URL}/api/policy-requests/${POLICY_ID}/submit?scenario=TIMEOUT_AFTER_ISSUE" \
  -H "Accept: application/json" | jq .

echo -e "\nStep 3: Query mock insurer status endpoint for verification..."
REF=$(curl -s -X GET "${BASE_URL}/api/policy-requests/${POLICY_ID}" | jq -r '.data.provider_reference')
echo "Provider Reference: $REF"
curl -s -X GET "${BASE_URL}/api/mock-insurer/status/${REF}" | jq .

echo -e "\nStep 4: Execute PolicyFlow Reconciliation..."
curl -s -X POST "${BASE_URL}/api/policy-requests/${POLICY_ID}/reconcile" \
  -H "Accept: application/json" | jq .
