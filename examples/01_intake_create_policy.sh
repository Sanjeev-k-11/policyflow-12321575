#!/usr/bin/env bash
# ==============================================================================
# 01. Intake - Create New Policy Request
# Specification Example: PLUS, 5 days, INTERNATIONAL, 300000, age 28 -> ₹225.00
# Expected Response: HTTP 201 Created
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
