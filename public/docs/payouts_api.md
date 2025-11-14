# Payout & Withdrawal API Documentation

## Auth
- Header: `Authorization: Bearer <token>`
- Content-Type: `application/json`

## Status lifecycle
- User requests withdrawal → `processing`
- RazorpayX webhook updates:
  - Success: `completed` (+ `processed_at`)
  - Failure/Reverse/Cancel: `failed` and wallet auto-refunded

## Endpoints

### POST /api/withdrawals (auth)
Creates a withdrawal and initiates RazorpayX payout.

Body
```json
{ "banking_detail_id": 12, "amount": 500 }
```

201 Response (example)
```json
{
  "success": true,
  "data": {
    "id": 93,
    "user_id": 7,
    "banking_detail_id": 12,
    "amount": 500,
    "fee": 10,
    "currency": "INR",
    "status": "processing",
    "gateway": "razorpay",
    "gateway_payout_id": "pout_123...",
    "gateway_status": "processing",
    "reference": "2f7f0f8a-...",
    "created_at": "2025-10-30T10:12:34Z",
    "updated_at": "2025-10-30T10:12:35Z"
  }
}
```

Errors
- 422: validation / insufficient funds
- 502: payout initiation failed (wallet auto-refunded)

---

### GET /api/withdrawals (auth)
Paginated list of withdrawals.

200 Response (example)
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 93,
        "amount": 500,
        "fee": 10,
        "currency": "INR",
        "status": "processing",
        "reference": "2f7f0f8a-...",
        "banking_detail": {
          "id": 12,
          "bank_name": "HDFC",
          "account_number_masked": "****1234"
        },
        "created_at": "2025-10-30T10:12:34Z"
      }
    ],
    "last_page": 1,
    "per_page": 20,
    "total": 1
  }
}
```

---

### GET /api/withdrawals/{id} (auth)
Details for a single withdrawal.

200 Response (example)
```json
{
  "success": true,
  "data": {
    "id": 93,
    "amount": 500,
    "fee": 10,
    "status": "processing",
    "banking_detail": {
      "id": 12,
      "bank_name": "HDFC",
      "account_number_masked": "****1234"
    },
    "reference": "2f7f0f8a-...",
    "created_at": "2025-10-30T10:12:34Z"
  }
}
```

---

### POST /api/withdrawals/{id}/cancel (auth)
Cancels only when status is `pending`. Refunds wallet (amount + fee).

200 Response
```json
{ "success": true }
```

422 Response
```json
{ "success": false, "message": "Cannot cancel" }
```

---

### POST /api/payouts/webhook (public)
RazorpayX webhook endpoint. Verifies `X-Razorpay-Signature` using `RAZORPAYX_WEBHOOK_SECRET`.

Status mapping
- `processed`/`credited` → `completed`, set `processed_at`
- `failed`/`reversed`/`cancelled` → `failed`, wallet refunded (idempotent)

Responses
- 200: `{ "success": true }`
- 401: `{ "success": false, "message": "Invalid signature" }`

## Configuration
Add to `.env`:
```
RAZORPAY_KEY_ID=...
RAZORPAY_KEY_SECRET=...
RAZORPAYX_ACCOUNT_NUMBER=...
RAZORPAYX_WEBHOOK_SECRET=...
```
Run migrations:
```
php artisan migrate
```

## Data contracts
- Withdrawal: id, user_id, banking_detail_id, amount, fee, currency, status, gateway, gateway_payout_id, gateway_status, failure_reason, reference, processed_at, timestamps
- BankingDetail: bank_name, account_holder_name, account_number, ifsc, currency, razorpay_contact_id, razorpay_fund_account_id

## Mobile app integration guide

1) Prerequisites
- User must be authenticated (get bearer token after login/register).
- Ensure user has a saved bank account (`banking_details`) and wallet balance >= amount + fee.

2) Create Withdrawal
- Endpoint: `POST /api/withdrawals`
- Body: `{ "banking_detail_id": <id>, "amount": <number> }`
- Handle responses:
  - 201: show status `processing` to user.
  - 422: show validation/insufficient funds message.
  - 502: show payout initiation failed; funds auto-refunded.

3) Poll or subscribe for status
- Option A (poll): Call `GET /api/withdrawals/{id}` every 10–20s until status is `completed` or `failed`.
- Option B (list): Refresh `GET /api/withdrawals` and display the latest status.
- Recommended UX states:
  - processing: "Transfer in progress"
  - completed: "Transfer completed"
  - failed: "Transfer failed" (wallet refunded)

4) Display masked bank data
- Use `banking_detail.account_number_masked` from responses; never display full account number.

5) Handle currency
- RazorpayX supports INR. Ensure `currency` shows INR and amounts are in rupees in UI.

6) Errors & Retries
- Do not retry POST `/api/withdrawals` on network failure blindly; show a retry button and if user retries, treat as a new request (server uses idempotency when creating payout).
- For polling, stop after a reasonable timeout (e.g., 2–3 minutes) and prompt user to check history.

7) Example cURL
```bash
curl -X POST https://your-domain/api/withdrawals \
 -H "Authorization: Bearer <TOKEN>" \
 -H "Content-Type: application/json" \
 -d '{ "banking_detail_id": 12, "amount": 500 }'
```

---

Last updated: 2025-10-30

