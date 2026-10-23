#!/usr/bin/env bash
# ==============================================================================
# 03. Retrieve Policy Request Detail & Audit Trail
# GET /api/policy-requests/{id_or_client_request_id}
# Returns: State, immutable pricing snapshot breakdown, and full audit timeline
# ==============================================================================

BASE_URL="http://127.0.0.1:8000"
ID="1"

curl -s -X GET "${BASE_URL}/api/policy-requests/${ID}" \
  -H "Accept: application/json" | jq .
