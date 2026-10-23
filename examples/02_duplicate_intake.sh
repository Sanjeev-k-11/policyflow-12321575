#!/usr/bin/env bash
# ==============================================================================
# 02. Duplicate Intake Prevention
# Submitting the exact same agent_id + client_request_id repeatedly
# Expected Response: HTTP 200 OK (safely returning existing record without duplicating)
# ==============================================================================

BASE_URL="http://127.0.0.1:8000"

curl -s -X POST "${BASE_URL}/api/policy-requests" \
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
  }' | jq .
