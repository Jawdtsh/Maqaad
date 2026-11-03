## D1
I lock the session row using PostgreSQL `FOR NO KEY UPDATE` inside a database transaction, then count confirmed bookings and compare the count with capacity before confirming a seat.
All booking and cancellation paths must follow the same session-locking protocol; the student row is also locked to serialize overlapping booking checks.
I rejected locking the result of `COUNT(*)`: an aggregate is not a lockable seat or session row and cannot reliably serialize competing reservations.

## D2
The append-only `wallet_transactions` ledger is the accounting source of truth; `wallets.balance` is a cached projection for fast reads.
Charges and top-ups insert signed `NUMERIC(18,2)` ledger entries and update the cached balance within the same database transaction while holding the wallet row lock.
Database constraints, unique operation keys and the immutable-ledger trigger protect transaction integrity; reconciliation can verify `wallets.balance = SUM(wallet_transactions.amount)`.

## D3
`idempotency_requests` stores `(student_id, idempotency_key)` under a unique constraint, together with a SHA-256 request fingerprint, HTTP status and response body.
The fingerprint includes the HTTP method, session ID and raw request body; the key itself is the lookup identity, not part of the fingerprint.
An identical retry returns the recorded successful response without charging again; using the same key for a different fingerprint returns HTTP 409.
The booking, charge and stored response commit atomically; failed requests roll back and their responses are not cached.

## D4
Session timestamps are stored as PostgreSQL `TIMESTAMPTZ`, and each branch stores its IANA time zone (`Asia/Damascus` or `Asia/Muscat`).
I compare the current instant with the session start instant: **at least 48 hours** gives 100%, **12 to less than 48 hours** gives 50%, and **less than 12 hours** gives 0%.
Branch-local time is used to interpret local schedules and dates; converting both instants to UTC preserves the exact 48-hour and 12-hour boundaries, including across time-zone offset changes.

## D5

## D6
Process center cancellation in a background queue, not one synchronous HTTP request, because a session may have 800 bookings.
First mark the session cancelled; workers then process bookings in small, resumable batches with database transactions and a consistent locking order.
For each paid booking, lock the relevant rows, compute `remaining = charged_amount - SUM(previous refunds)`, and create a `center_refund` entry only if the remaining amount is positive.
Use the deterministic key `booking:{id}:center_refund` and a partial unique constraint on `(booking_id, type)` for refunds; insert the ledger entry, update wallet balance, and update booking status atomically as applicable.
Retries skip already completed refunds, so interruption cannot cause duplicate payouts; notify users only after successful commits.
