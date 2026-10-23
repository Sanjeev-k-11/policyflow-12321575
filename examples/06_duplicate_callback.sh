#!/usr/bin/env bash
# ==============================================================================
# 06. Reliability Case C: Duplicate Callback Idempotency
# Dispatches identical provider webhook event 3 times
# Expected: Delivery #1 processes; Deliveries #2 and #3 safely ignored
# ==============================================================================

BASE_URL="http://127.0.0.1:8000"
EVENT_ID="EVT-DUP-$RANDOM"
PROVIDER_REF="REF-INS-CALLBACK-DEMO"

echo "=== Delivery 1 ==="
curl -s -X POST "${BASE_URL}/api/mock-insurer/callback" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "event_id": "'"$EVENT_ID"'",
    "provider_reference": "'"$PROVIDER_REF"'",
    "event_type": "POLICY_ISSUED",
    "policy_number": "POL-2026-CONFIRMED-'"$RANDOM"'"
  }' | jq .

echo -e "\n=== Delivery 2 (Identical Payload) ==="
curl -s -X POST "${BASE_URL}/api/mock-insurer/callback" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "event_id": "'"$EVENT_ID"'",
    "provider_reference": "'"$PROVIDER_REF"'",
    "event_type": "POLICY_ISSUED",
    "policy_number": "POL-2026-CONFIRMED-'"$RANDOM"'"
  }' | jq .

echo -e "\n=== Delivery 3 (Identical Payload) ==="
curl -s -X POST "${BASE_URL}/api/mock-insurer/callback" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "event_id": "'"$EVENT_ID"'",
    "provider_reference": "'"$PROVIDER_REF"'",
    "event_type": "POLICY_ISSUED",
    "policy_number": "POL-2026-CONFIRMED-'"$RANDOM"'"
  }' | jq .
