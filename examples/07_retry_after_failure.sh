#!/usr/bin/env bash
# ==============================================================================
# 07. Reliability Case D: Retry After Temporary Failure
# 1. Simulates TEMPORARY_FAILURE
# 2. Inspects FAILED status
# 3. Retries safely without losing original accepted premium
# ==============================================================================

BASE_URL="http://127.0.0.1:8000"
REQ_ID="DEMO-RETRY-$RANDOM"

echo "Step 1: Intake policy..."
INTAKE_RES=$(curl -s -X POST "${BASE_URL}/api/policy-requests" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "client_request_id": "'"$REQ_ID"'",
    "agent_id": "AGENT-RETRY",
    "customer_name": "Retry Demo Customer",
    "date_of_birth": "1994-08-20",
    "destination": "DOMESTIC",
    "trip_start": "2026-12-01",
    "trip_end": "2026-12-05",
    "plan_code": "BASIC",
    "sum_insured": 100000,
    "scenario": "TEMPORARY_FAILURE"
  }')

POLICY_ID=$(echo "$INTAKE_RES" | jq -r '.data.id')
echo "Created Policy ID: $POLICY_ID"

echo -e "\nStep 2: Submit with TEMPORARY_FAILURE (HTTP 400 expected)..."
curl -s -X POST "${BASE_URL}/api/policy-requests/${POLICY_ID}/submit?scenario=TEMPORARY_FAILURE" \
  -H "Accept: application/json" | jq .

echo -e "\nStep 3: Safe Retry with SUCCESS override (HTTP 200 expected)..."
curl -s -X POST "${BASE_URL}/api/policy-requests/${POLICY_ID}/submit?scenario=SUCCESS" \
  -H "Accept: application/json" | jq .
