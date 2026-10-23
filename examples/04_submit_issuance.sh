#!/usr/bin/env bash
# ==============================================================================
# 04. Submit Policy Request to Mock Insurer
# POST /api/policy-requests/{id}/submit
# Pessimistic locking prevents duplicate provider dispatch even when retried
# ==============================================================================

BASE_URL="http://127.0.0.1:8000"
ID="1"

curl -s -X POST "${BASE_URL}/api/policy-requests/${ID}/submit" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "scenario": "SUCCESS"
  }' | jq .
