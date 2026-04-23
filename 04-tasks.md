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

Two architectural findings from task01 (Moodle 5.2 source analysis) need product-owner confirmation before they're locked in:

**clarif01 — Refund / reversal bridge**  
Moodle 5.2 `core_payment` has no generic reversal API (`service_provider` only has `deliver_order`; `helper.php` has no `refund_order` / `reverse_order`). Proposed approach: our plugin ships an `enrol_fee`-specific unenrol bridge for auto-unenrol on refund, and for any other component fires a `paygw_digistore24\event\payment_reversed` event that component authors / custom plugins can subscribe to. Admin report always surfaces reversed payments for manual intervention.
→ Confirm this approach (yes / no / different idea).

**clarif02 — Per-item product-id override UI location**  
The `paygw` base class has no per-payable-item form hook. Proposed approach: a dedicated admin page "Site administration → Plugins → Payment gateways → Digistore24 → Product mapping" where an admin maps `(component, paymentarea, itemid)` → `digistore24_product_id`. Course editors do *not* set product ids on course edit forms.
→ Confirm, or request that we inline it on specific forms (e.g. for `enrol_fee` via enrol-plugin fork / patch, which we don't recommend).

---

## 📋 Tasks

All tasks below implement **feat01 — Digistore24 payment gateway**.
Order is the recommended implementation sequence: each task builds on the previous one.

---

### task01 Verify Moodle 5.2 paygw gateway contract
Status: done  
Feature: feat01  
Depends on: —

**Outcome**  
Resolved by reading the Moodle 5.2 source (`MOODLE_502_STABLE`, `public/payment/`): base class `\core_payment\gateway`, `service_provider` interface, `helper::save_payment` + `helper::deliver_order`, external-service conventions, AMD module path `paygw_<name>/gateways_modal` with `process(component, paymentArea, itemId, description)`. Full concrete contract recorded in `03-dev-doc.md → External Dependencies → Moodle Payment subsystem`.

Two architectural findings now tracked under "Clarification Needed":
- `core_payment` has **no reversal API** → refund path needs a plugin-level bridge (see clarif01).
- `\core_payment\gateway` has **no per-item form hook** → product-id override must live on a plugin-admin mapping page (see clarif02).

PayPal (`public/payment/gateway/paypal/`) is the reference implementation; all subsequent tasks reference it rather than guessing.

---

### task02 Plugin skeleton
Status: open  
Feature: feat01  
Depends on: task01

**Goal**  
Create a minimal installable `paygw_digistore24` plugin so Moodle 5.2 recognises it.

**Steps**  
Mirror `public/payment/gateway/paypal/` structure. Create `public/payment/gateway/digistore24/` with:
- `version.php` — `$plugin->component = 'paygw_digistore24'`, `$plugin->requires = 2026041000` (Moodle 5.2).
- `lang/en/paygw_digistore24.php`, `lang/de/paygw_digistore24.php` — at minimum `pluginname` and `gatewaydescription`.
- `classes/gateway.php` extending `\core_payment\gateway`:
  - `get_supported_currencies()` — permissive set (start with PayPal's list).
  - `add_configuration_to_gateway_form($form)` — per-account gateway config (leave empty for now; populated in task03).
  - override `validate_gateway_form(...)` to allow enable once basic config is entered.
- `settings.php` — empty scaffold; populated in task03. Call `\core_payment\helper::add_common_gateway_settings($settings, 'digistore24')` at the bottom for the common `surcharge` setting.
- `classes/privacy/provider.php` — minimum implementation (null-provider or local metadata per PayPal).
- No `db/install.xml` yet — table added in task04 (`paygw_digistore24_itemmap`) and/or task06 (order-id bookkeeping).

**Expected result**  
Plugin installs cleanly on a fresh Moodle 5.2; appears under Site administration → Plugins → Payment gateways; can be enabled on a payment account; no behavior yet.

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

### task04 Per-item product-id override (admin mapping page)
Status: blocked by clarif02  
Feature: feat01  
Depends on: task02

**Goal**  
Let an admin map any Moodle payable item to a specific Digistore24 product, with fallback to the site-wide default.

**Why it's different from the original plan**  
Moodle 5.2's `\core_payment\gateway` base class has no per-payable-item form hook, so we can't inject a field into the course / activity edit form in a clean way. The override therefore lives on a dedicated plugin-admin mapping page (see clarif02).

**Steps** (run once clarif02 is confirmed)
- Create table `paygw_digistore24_itemmap` in `db/install.xml`: `id`, `component`, `paymentarea`, `itemid`, `product_id` (varchar), `timemodified`. Unique key on `(component, paymentarea, itemid)`.
- Add admin page under "Site administration → Plugins → Payment gateways → Digistore24 → Product mapping" with list + add/edit/delete mapping rows.
- Filter the picker to items that actually use the Digistore24 gateway (joined against `payments` / `payment_accounts` metadata).
- Resolution helper: `paygw_digistore24\helper::resolve_product_id($component, $paymentarea, $itemid): string` → mapping if set, else `default_product_id` admin setting.

**Expected result**  
An admin can map any payable item to a Digistore24 product id; items without a mapping fall back to the site default.

---

### task05 Checkout: createBuyUrl + redirect
Status: open  
Feature: feat01  
Depends on: task03, task04

**Goal**  
When the learner picks Digistore24 in Moodle's payment modal, call `createBuyUrl` and redirect the browser to the resulting signed URL.

**Steps**
- `classes/api/client.php` — Digistore24 API client using Moodle's `\core\http_client` / `curl`: `POST https://www.digistore24.com/api/call/{APIKEY}/json/{FUNCTION}` with `X-DS-API-KEY`; JSON parsing; error mapping.
- `classes/api/create_buy_url.php` — wrapper for `createBuyUrl` with the parameter shape from `03-dev-doc.md`:
  - `product_id` from `\paygw_digistore24\helper::resolve_product_id(...)` (task04).
  - `buyer` from Moodle user (email, firstname, lastname, country); pass via `settings` to make read-only.
  - Moodle-controlled `price` / `currency` from `\core_payment\helper::get_payable(...)` (+ surcharge).
  - `tracking.custom = payments.id` (from `\core_payment\helper::save_payment(...)` here).
  - `urls.thankyou_url` = Moodle return page (task07) with `payment.id`; `urls.notification_url` = `callback.php` from task06.
  - `valid_until = '24h'`.
- `classes/external/start_checkout.php` (webservice `paygw_digistore24_start_checkout`, `write`, `ajax => true`, `loginrequired => true`):
  - validates params (`component`, `paymentarea`, `itemid`),
  - calls `helper::save_payment` to create the pending Moodle payment,
  - calls the `createBuyUrl` wrapper,
  - returns `{ redirecturl: string }`.
- `amd/src/gateways_modal.js` exposing `process(component, paymentArea, itemId, description)`: show a "Redirecting to Digistore24…" modal, call the webservice, then `window.location = result.redirecturl`.
- On failure: keep the Moodle payment row (status stays pending), surface the error string in the modal, do not redirect.

**Expected result**  
Clicking "Pay" on a course with `enrol_fee` + Digistore24 enabled lands the learner on a real Digistore24 checkout page with the right product, price, currency, pre-filled (read-only) buyer data, and a `custom` value matching the Moodle pending `payments.id`.

---

### task06 IPN endpoint: signature, match, deliver
Status: open  
Feature: feat01  
Depends on: task05

**Goal**  
Receive Digistore24 IPNs, validate them, and tell Moodle the matching payment is paid so the component's delivery callback runs.

**Steps**
- `public/payment/gateway/digistore24/callback.php` — standalone entry, NOT a webservice:
  - `define('NO_MOODLE_COOKIES', true);` `define('NO_DEBUG_DISPLAY', true);`
  - `require(__DIR__ . '/../../../config.php');`
- Validate the SHA-passphrase signature using admin setting `ipn_passphrase`; reject (HTTP 400 + log) on mismatch. Must run BEFORE any side effect.
- Resolve the Moodle payment via `custom` → `payments.id`. Reject (log) if missing.
- Verify `currency` and `amount` against the `payments` row; mismatch → reject and log, do not deliver.
- Persist the Digistore24 `transaction_id` (and `order_id`) in our own bookkeeping table (`paygw_digistore24_txn`, added here via `db/install.xml`): one row per delivered / reversed payment.
- Idempotency: if an identical `transaction_id` is already recorded as delivered, return `OK` without side effect.
- Call `\core_payment\helper::deliver_order($component, $paymentarea, $itemid, $paymentid, $userid)` → `core_payment` runs the component's `deliver_order`.
- Always respond `OK` on success so Digistore24 does not retry.

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

### task08 Refund / chargeback reversal bridge
Status: blocked by clarif01  
Feature: feat01  
Depends on: task06

**Goal**  
On a Digistore24 refund / chargeback IPN, reverse the matching Moodle payment — without a generic core_payment reversal API, so the plugin bridges this itself.

**Steps** (run once clarif01 is confirmed)
- Extend the IPN handler to recognise refund / chargeback event types.
- Look up the original Moodle payment via the stored Digistore24 `transaction_id`; if not found, log and respond `OK` (idempotency).
- Mark the row in our `paygw_digistore24_txn` table as `reversed` (separate from `delivered`; original row not deleted).
- Component-specific reversal:
  - `component = 'enrol_fee'` → call `enrol_fee`'s enrolment plugin to unenrol the user (confirm exact API by reading `public/enrol/fee/` at task-start).
  - any other component → fire `paygw_digistore24\event\payment_reversed` with `component`, `paymentarea`, `itemid`, `paymentid`, `userid`; no further action.
- Fire `\paygw_digistore24\event\payment_reversed` in all cases (so observers see every reversal, not only non-`enrol_fee` ones).
- Admin report from task10 surfaces reversed payments.

**Expected result**  
Refunding a Digistore24 test order for an `enrol_fee` payment unenrols the user from the related course. A refund on any other component produces the `payment_reversed` event and a reversed-status row, ready for custom handling.

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
