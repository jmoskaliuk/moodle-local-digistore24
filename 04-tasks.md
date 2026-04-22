# Tasks

## Meta

This is the operational center of the system.

It contains:
- new observations
- tasks (taskXX)
- clarifications
- active work
- verification steps

Start every session here.

---

## 🆕 New

(Unstructured input allowed)

---

## ❓ Clarification Needed

None.

---

## 📋 Tasks

All tasks below implement **feat01 — Digistore24 payment gateway**.
Order is the recommended implementation sequence: each task builds on the previous one.

---

### task01 Verify Moodle 5.2 paygw contract
Status: open  
Feature: feat01  
Depends on: —

**Goal**  
Pin down the exact Moodle 5.2 payment-gateway plugin contract before writing code, so subsequent tasks build on confirmed signatures (not guesses).

**Steps**
- Read the Moodle 5.2 payment-gateway docs on moodledev.io (currently 403 from this environment — fetch locally or via a proxy).
- Identify, with file paths and method signatures, what `paygw_*` plugins must implement: `classes/gateway.php`, `amd/src/gateways_modal.js` (or equivalent), `classes/external/*` services, `lang/en/paygw_<name>.php`, `settings.php`, `version.php`.
- Identify the exact API call(s) used to mark a payment as `delivered` and to trigger reversal.
- Confirm how Moodle hands off payable-item context (`component`, `paymentarea`, `itemid`, amount, currency, user) to a gateway.
- Confirm how the per-item gateway settings form is provided (for the `digistore24_product_id` override UI).

**Expected result**
- Notes added to `03-dev-doc.md → External Dependencies → Moodle Payment subsystem` with concrete class/method names for Moodle 5.2.
- Any deviations from the assumptions in this document recorded as decisions (or back-ported into `01-features.md` if behavior changes).

---

### task02 Plugin skeleton
Status: open  
Feature: feat01  
Depends on: task01

**Goal**  
Create a minimal installable `paygw_digistore24` plugin so Moodle 5.2 recognises it.

**Steps**
- Create directory `payment/gateway/digistore24/` with: `version.php`, `lang/en/paygw_digistore24.php`, `classes/gateway.php` (extending the Moodle base gateway), empty `settings.php`, `db/install.xml` placeholder if any plugin-owned tables are needed.
- `version.php`: `$plugin->component = 'paygw_digistore24'`, `$plugin->requires` set for Moodle 5.2.
- Stub `gateway` class: declare supported currencies as a permissive set (delegated to Digistore24) and a no-op `validate_data` for now.
- Verify the plugin shows up in `Site administration → Plugins → Payment gateways` after install.

**Expected result**  
Plugin installs cleanly on a fresh Moodle 5.2; appears in the gateway list; can be enabled on a payment account; no behavior yet.

---

### task03 Admin settings (credentials & defaults)
Status: open  
Feature: feat01  
Depends on: task02

**Goal**  
Provide the admin form for Digistore24 credentials and site-wide defaults.

**Steps**
- In `settings.php`, add admin settings (all secrets via `admin_setting_configpasswordunmask` or equivalent):
  - `apikey` (full-access Digistore24 API key, secret)
  - `ipn_passphrase` (SHA passphrase, secret)
  - `default_product_id` (string)
  - `grace_period_days` (int, default 3) — used by subscription end-of-life logic.
  - `test_mode` (bool) — toggles use of Digistore24 test purchases.
- Add lang strings.
- Document where these are used in `03-dev-doc.md`.

**Expected result**  
Admin can enter all required credentials and defaults; values are persisted; secrets are never echoed back in plain text in the UI.

---

### task04 Per-item product-id override UI
Status: open  
Feature: feat01  
Depends on: task02

**Goal**  
Let any Moodle payable item (enrol_fee, activity, custom component) set its own `digistore24_product_id` instead of falling back to the site-wide default.

**Steps**
- Implement the per-payment-area settings form hook the Moodle 5.2 paygw contract exposes (confirmed in task01) — single field `digistore24_product_id` (string, optional).
- Persist the override in the plugin's per-item settings store provided by `core_payment` (no custom DB table if Moodle provides one).
- Resolution helper: `resolve_product_id($component, $paymentarea, $itemid)` → override if set, else `default_product_id` from admin settings.

**Expected result**  
On any payable item bound to a payment account that has Digistore24 enabled, an admin can enter a Digistore24 product id; empty field falls back to the site default.

---

### task05 Checkout: createBuyUrl + redirect
Status: open  
Feature: feat01  
Depends on: task03, task04

**Goal**  
When the learner picks Digistore24 in Moodle's payment dialog, call `createBuyUrl` and redirect them to the resulting signed URL.

**Steps**
- Build a Digistore24 API client (`classes/api/client.php`): `POST https://www.digistore24.com/api/call/{APIKEY}/json/{FUNCTION}` with `X-DS-API-KEY` header; JSON parsing; error mapping.
- Implement `createBuyUrl` wrapper with parameters per `03-dev-doc.md`:
  - `product_id` from `resolve_product_id(...)`.
  - `buyer` from the Moodle user (email, first/last name, country); pass via `settings` to make read-only.
  - Moodle-controlled `price` / `currency` for the payable item.
  - `tracking.custom = payment.id` (created here as a Moodle pending payment via the core API).
  - `urls.thankyou_url` = Moodle return URL with `payment.id`; `urls.notification_url` = the IPN endpoint from task06.
  - `valid_until = '24h'`.
- Wire this into the `paygw_digistore24` external service / AJAX entry point that Moodle's payment modal calls.
- On success, return the URL to the front-end so Moodle redirects the learner; on failure, surface a clear error and keep the Moodle payment in `pending` state (do not delete it; needed for diagnostics).

**Expected result**  
Clicking "Pay" on a course with `enrol_fee` + Digistore24 enabled lands the learner on a real Digistore24 checkout page with the right product, price, currency, pre-filled (read-only) buyer data, and a `custom` value matching the Moodle pending payment id.

---

### task06 IPN endpoint: signature, match, deliver
Status: open  
Feature: feat01  
Depends on: task05

**Goal**  
Receive Digistore24 IPNs, validate them, and tell Moodle the matching payment is paid so the component's delivery callback runs.

**Steps**
- Create a publicly reachable script (no Moodle login required) that accepts the Digistore24 POST.
- Validate the SHA-passphrase signature using `ipn_passphrase` from admin settings; reject (HTTP 400, log) on mismatch.
- Resolve the Moodle payment via `custom` → `payment.id`. Reject (log) if missing or already terminal.
- Verify `currency` and `amount` against the Moodle pending payment; mismatch → reject and log.
- Idempotency: if the payment is already `delivered` for this Digistore24 transaction id, return success without doing anything.
- Mark the payment delivered via the Moodle core payment API → `core_payment` triggers the component's delivery callback (e.g. `enrol_fee` enrols the user).
- Always respond `OK` on success so Digistore24 doesn't retry.

**Expected result**  
A real Digistore24 test purchase produces an IPN that enrols the buyer in the corresponding course and is visible in Moodle's payment log. Replaying the same IPN does not double-enrol.

---

### task07 Thank-you / return UX
Status: open  
Feature: feat01  
Depends on: task06

**Goal**  
After paying, the learner returns to a Moodle page that shows a consistent state regardless of whether the IPN has arrived yet.

**Steps**
- Implement a Moodle return page (the URL passed as `urls.thankyou_url` in task05) that takes `payment.id` and renders:
  - "Payment confirmed — you have been enrolled" if delivered.
  - "Payment received, finalising your enrolment" with a short auto-refresh if still pending.
  - Clear error if the payment is in an error state.
- Do NOT mark anything paid here — IPN remains the only authority.

**Expected result**  
Learner sees a consistent confirmation page on return, with no race condition between the IPN and the redirect.

---

### task08 Refund / chargeback handling
Status: open  
Feature: feat01  
Depends on: task06

**Goal**  
On a Digistore24 refund or chargeback IPN, reverse the matching Moodle payment so the component (e.g. `enrol_fee`) unenrols the user.

**Steps**
- Extend the IPN handler from task06 to recognise refund / chargeback event types from Digistore24.
- Locate the original Moodle payment via Digistore24 transaction id (stored on the original delivery).
- Call the Moodle core payment API to reverse the payment so `core_payment` invokes the component's reversal path.
- Log the action; do not silently swallow failures.

**Expected result**  
Refunding a Digistore24 test order unenrols the user from the related course (or runs the equivalent reversal for other components).

---

### task09 Subscription support
Status: open  
Feature: feat01  
Depends on: task05, task06

**Goal**  
Support Digistore24 payment plans (recurring) end-to-end: initial purchase, every recurring charge, end of access at `period_end + grace_period_days`.

**Steps**
- Detect when a Moodle payable item represents a subscription and pass an appropriate `payment_plan` to `createBuyUrl` in task05.
- In task06's IPN handler, treat each recurring `payment` event as a separate payment for the same subscription: extend access through the new period.
- Track `current_period_end` per subscription so we know when the grace period ends.
- Implement a scheduled task that, on each run, ends access for subscriptions where `period_end + grace_period_days < now` AND no fresh successful payment IPN has arrived.
- Cancellation / failed-rebill IPNs from Digistore24 set the cancellation flag; the scheduled task above does the actual cut-off.
- Refund/chargeback IPN (task08) overrides this and ends access immediately.

**Expected result**  
A Digistore24 test subscription creates a Moodle enrolment that extends each successful billing cycle and ends `grace_period_days` after the last paid period when rebill fails or the user cancels.

---

### task10 Logging, error handling, observability
Status: open  
Feature: feat01  
Depends on: task05, task06

**Goal**  
Make the plugin diagnosable in production without leaking secrets.

**Steps**
- Centralised log helper that records: API requests/responses (with API key redacted), IPN bodies (with signature redacted), match failures, signature failures, amount mismatches, delivery / reversal outcomes.
- Use Moodle's logging / events API; surface in a plugin admin report page (paginated, filterable by status).
- Never log the API key or IPN passphrase.

**Expected result**  
An admin can investigate any payment end-to-end (Moodle payment id ↔ Digistore24 transaction id ↔ outcome) from a single report page.

---

### task11 Tests
Status: open  
Feature: feat01  
Depends on: task05, task06, task08, task09

**Steps**
- PHPUnit unit tests for: `resolve_product_id`, IPN signature validation, idempotency check, amount/currency mismatch rejection, refund reversal.
- Behat acceptance test for the `enrol_fee` happy path (mocking the Digistore24 API client and IPN POST).
- Manual test plan in `05-quality.md` for: subscription, refund, expired/invalid signature, duplicate IPN, abandoned checkout.

**Expected result**  
CI green; manual test plan runnable against a Digistore24 sandbox account.

---

### task12 Documentation sync (DoD)
Status: open  
Feature: feat01  
Depends on: task11

**Steps**
- Update `02-user-doc.md` for the two user-facing personas:
  - **Site admin**: install, enter credentials, create payment account, set default product id, configure grace period.
  - **Course editor**: attach `enrol_fee` with Digistore24 enabled, optionally set a per-course `digistore24_product_id`.
  - **Learner**: how the pay → checkout → return → enrolment flow looks.
- Update `03-dev-doc.md` Feature Implementation section for feat01 with the as-built component map (gateway class, API client, IPN endpoint, return page, scheduled task, logger).
- Run `#consistency` across `01/02/03` and resolve any drift.

**Expected result**  
feat01 is "done" per the eLeDia.OS Definition of Done — feature, user doc, and dev doc are consistent.

---

### task13 Release packaging for Moodle 5.2
Status: open  
Feature: feat01  
Depends on: task12

**Steps**
- Verify directory layout matches what the Moodle plugin directory expects.
- Add `README.md` for the plugin (install, configure, Digistore24 setup checklist).
- Tag a release; note version compatibility (Moodle 5.2).
- (Out of scope here: actual submission to moodle.org/plugins — handled by the `Skills/moodle-plugin-submit.md` playbook when ready.)

**Expected result**  
A clean ZIP that installs into a fresh Moodle 5.2 and runs end-to-end against a Digistore24 sandbox.

---

## 🔧 In Progress

(none yet)

---

## 🔎 Verify After Deploy

- Real Digistore24 sandbox: one-off purchase enrols the user.
- Real Digistore24 sandbox: subscription extends access on each rebill.
- Real Digistore24 sandbox: refund unenrols the user.
- Replay an old IPN → no double effect.
- Tampered IPN signature → rejected and logged, no side effect.

---

## ✅ Done

(none yet)

---

## Rules

- convert relevant items into tasks
- keep tasks small
- move completed tasks to Done
- do not delete, only move
