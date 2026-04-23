# Developer Documentation

## Meta

This document describes how the system is actually implemented.

It serves two purposes:
1. Document the technical structure of the product
2. Describe how individual features (featXX) are implemented

This document represents the **current implementation (source of truth for reality)**.

---

## How to use this document

### For humans

Use this document to:
- understand how the system works internally
- navigate architecture and components
- onboard new developers
- support debugging and extension

Think:
→ *How is this system built and how does it actually work?*

---

### For AI

When working with this document:

- treat it as the **source of truth for implementation**
- do not invent behavior not implemented
- do not describe intended behavior → use `01-features.md` for that
- ensure consistency with:
  - `01-features.md` (intended behavior)
  - `02-user-doc.md` (user-facing behavior)
- if mismatch is detected → flag inconsistency

---

## What belongs here

Include:
- architecture
- components and modules
- data flow
- interactions between components
- technical constraints
- known limitations

---

## What does NOT belong here

Do NOT include:
- feature planning or ideas → `01-features.md`
- tasks or work tracking → `04-tasks.md`
- bugs or test logs → `05-quality.md`
- user explanations → `02-user-doc.md`

---

# 🧭 System Overview

## Architecture Overview

Describe the overall architecture:

- system type (e.g. client-side, server-client)
- major layers
- communication patterns

---

## Core Components

List and describe key components:

- component 1 → purpose
- component 2 → purpose

---

## Data Flow

Describe how data moves through the system:

- input → processing → output
- component interactions
- event flow

---

## External Dependencies

### Moodle Payment subsystem (`core_payment`)
- Target: **Moodle 5.2** (branch `MOODLE_502_STABLE`).
- Codebase location: `public/payment/` (Moodle 5.2 moved webroot code under `public/`).
- Plugin type: `paygw` → plugin name `paygw_digistore24`, path `public/payment/gateway/digistore24/`.

**Component side** (implemented by components that want to accept money — NOT us):
- Interface: `\core_payment\local\callback\service_provider` (`public/payment/classes/local/callback/service_provider.php`).
  - `get_payable(string $paymentarea, int $itemid): \core_payment\local\entities\payable`
  - `get_success_url(string $paymentarea, int $itemid): \moodle_url`
  - `deliver_order(string $paymentarea, int $itemid, int $paymentid, int $userid): bool`
- Entity: `\core_payment\local\entities\payable($amount, $currency, $accountid)` — immutable, amount (float), currency (ISO-4217 3-letter), accountid (int).
- `core_payment\helper::get_service_provider_classname($component)` resolves the implementation at `"$component\\payment\\service_provider"` and requires it to implement the interface (otherwise `coding_exception`). So `enrol_fee`'s class is `\enrol_fee\payment\service_provider`.
- Frontend trigger: element with `data-action="core_payment/triggerPayment"` + `data-component`, `data-paymentarea`, `data-itemid`, `data-cost`, `data-description`, `data-successurl`; AMD `core_payment/gateways_modal`.

**Gateway side** (what `paygw_digistore24` implements) — confirmed against `public/payment/classes/gateway.php` and the reference implementation `public/payment/gateway/paypal/`:

- `classes/gateway.php` extends abstract `\core_payment\gateway` with:
  - `public static function get_supported_currencies(): array` (abstract) — ISO-4217 codes. For Digistore24: permissive set (delegate to Digistore24), candidate list equal to PayPal's plus any Digistore24-specific codes; filtered again server-side in `createBuyUrl`.
  - `public static function add_configuration_to_gateway_form(\core_payment\form\account_gateway $form): void` (abstract) — builds the per-account gateway config form via `$form->get_mform()`. This is where the **per-payment-account** Digistore24 settings go (e.g. default product id, test mode override). There is no per-payable-item hook here; per-item override must live elsewhere (see Refund / per-item section below).
  - `public static function validate_gateway_form(...)` — default implementation in the base class blocks enabling; override to allow enable only when required fields are filled.
- External services registered in `db/services.php`, convention `paygw_<name>_<verb>`; each `ajax => true`, `loginrequired` as needed (PayPal sets `loginrequired => false` on `transaction_complete` to allow guest payments — we will need the same or stricter depending on flow). We implement at least:
  - `paygw_digistore24_get_config_for_js` — read-only config the modal needs (brand name, test-mode flag, …). Mirrors PayPal's `get_config_for_js`.
  - `paygw_digistore24_start_checkout` — `write`, `ajax => true`. Does: `helper::get_payable(...)` → `helper::save_payment(...)` → Digistore24 `createBuyUrl(...)` with `custom = payments.id` → returns the signed checkout URL. Replaces PayPal's in-modal JS-SDK flow.
- AMD module: `paygw_digistore24/gateways_modal` exporting `process(component, paymentArea, itemId, description)`; the core payment modal auto-discovers modules at that path. Responsibility: show a "redirecting…" modal, call `paygw_digistore24_start_checkout`, then `window.location = result.redirecturl`.
- Helpers we call from the gateway (all in `\core_payment\helper`, `public/payment/classes/helper.php`):
  - `get_gateway_configuration($component, $paymentarea, $itemid, 'digistore24'): array` — our per-account config.
  - `get_payable($component, $paymentarea, $itemid): payable`.
  - `get_rounded_cost`, `get_gateway_surcharge`, `get_cost_as_string` — for price display / surcharge rounding.
  - `save_payment($accountid, $component, $paymentarea, $itemid, $userid, $amount, $currency, 'digistore24'): int` — creates the pending row in the `payments` table; the returned id is what we pass to Digistore24 as `custom`.
  - `deliver_order($component, $paymentarea, $itemid, $paymentid, $userid): bool` — called from the IPN handler on successful payment; internally invokes the component's `deliver_order`.
- Required plugin files:
  - `version.php` — `$plugin->component = 'paygw_digistore24'`, `$plugin->requires = 2026041000` (matches PayPal in MOODLE_502_STABLE; pin to Moodle 5.2 release version).
  - `settings.php` — admin settings (API key, IPN passphrase, default product id, grace period, test mode). Plus `helper::add_common_gateway_settings($settings, 'digistore24')` which adds the common `surcharge` setting.
  - `lang/en/paygw_digistore24.php`, `lang/de/paygw_digistore24.php`.
  - `db/services.php` (external functions), optional `db/install.xml` (if we keep our own `paygw_digistore24` table like PayPal does for order-id mapping).
  - `classes/privacy/provider.php` (GDPR — follow PayPal's structure).

**IPN endpoint** (no Moodle abstraction — our own):
- Moodle provides no gateway-callback framework; PayPal is synchronous via the modal. For Digistore24 we add a standalone PHP entry point `public/payment/gateway/digistore24/callback.php`: `define('NO_MOODLE_COOKIES', true);` + `define('NO_DEBUG_DISPLAY', true);`, load Moodle config, read raw `$_POST`, validate SHA signature with admin setting `ipn_passphrase`, then the steps in task06.

**Refund / reversal — gap in Moodle 5.2 bridged by the plugin**:
- `core_payment` has **no built-in reversal API** in Moodle 5.2. Search for `refund|reverse|chargeback|void` across `public/payment/` returns nothing payment-specific; `service_provider` has only `deliver_order`.
- Approach (decided), implemented in task08:
  1. The plugin marks the original Moodle payment row as reversed in its own bookkeeping table (`paygw_digistore24_txn`, added in task06; `payments` has no status column).
  2. For `component = 'enrol_fee'`: call `enrol_fee`'s enrolment plugin directly to unenrol the user (confirm exact API against `public/enrol/fee/` at task-start).
  3. For all other components: fire a Moodle event `paygw_digistore24\event\payment_reversed` carrying `component`, `paymentarea`, `itemid`, `paymentid`, `userid`. Component authors / custom plugins subscribe to act on it. Documented as an extension point in `02-user-doc.md`.
  4. The event is fired in *all* cases (even when the enrol_fee bridge runs) so observers see every reversal.
  5. Admin report surfaces reversed payments so an admin can always intervene manually.

**Per-item `digistore24_product_id` override — gap in `\core_payment\gateway` worked around with a dedicated admin page**:
- The `\core_payment\gateway` base class only exposes `add_configuration_to_gateway_form` (per-account), not per-payable-item. Moodle's payable item itself (`enrol_fee` instance, activity, custom) owns its price and account id but does not expose a standard "extra gateway-specific field" slot.
- Approach (decided), implemented in task04:
  - Dedicated plugin table `paygw_digistore24_itemmap` keyed by `(component, paymentarea, itemid)` with an optional `product_id` column.
  - Admin UI on a plugin-admin page under "Site administration → Plugins → Payment gateways → Digistore24 → Product mapping" (not inline on course/activity edit forms).
  - Access gated by Moodle capability `paygw/digistore24:managemapping` (defined in `db/access.php`, `captype => write`, `contextlevel => CONTEXT_SYSTEM`, default-allowed for the `manager` archetype; site administrators always have it). Admins can grant it to additional roles via the standard role permissions UI.

**Currency handling**
- Because `helper::get_available_gateways` filters by currency support, our `get_supported_currencies()` list must be a superset of every currency we expect to actually use. Keep it broad; Digistore24 will reject anything it doesn't support when we call `createBuyUrl`.

**v1 QA** focuses on `enrol_fee`; other `core_payment` components are technically supported because `core_payment` dispatches to whichever `service_provider` owns the payable item — our gateway never touches the component directly (except in the refund bridge above, which is `enrol_fee`-specific).

### Digistore24 API
Facts collected from Digistore24 developer docs (via web search — see README/source links in commits):

- **HTTP base URL**: `https://www.digistore24.com/api/call/{APIKEY}/{FORMAT}/{FUNCTION}` — `FORMAT` ∈ {`json`, `xml`, `php`, `text`}.
- **Authentication**: send the API key in the HTTP header `X-DS-API-KEY` (preferred over URL-embedded key).
- **Response formats**: `Accept: application/json` by default; `text/plain`, `text/xml`, `application/php` also supported.
- **API key types**: `readonly` (reads only), `developer` (publishable, lets an app generate per-user keys), and full-access keys (never to be published).
- **Relevant API functions** (from API reference A–Z): `createBuyUrl`, `getPurchase`, `createBillingOnDemand`, refund-related calls, plus others TBD.
- **Chosen integration path**: `createBuyUrl` (called server-side per Moodle payment). The static `https://www.checkout-ds24.com/product/{id}/` URL is *not* used.
- **`createBuyUrl(product_id, buyer, payment_plan, tracking, valid_until, urls, placeholders, settings, addons)`** — produces a signed, one-shot checkout URL. Used by the plugin with:
  - `product_id` = per-item override `digistore24_product_id` if set, else the site-wide default from plugin settings (hybrid mapping).
  - `buyer` = Moodle user data (email, first/last name, country); passed read-only via `settings` so the buyer cannot change it at checkout.
  - Moodle-controlled price / currency for the payable item.
  - `payment_plan` = recurring terms when the Moodle payable item is a subscription.
  - `tracking.custom` = Moodle `payment.id` (≤ 127 chars) — this is the correlation key returned in every IPN.
  - `urls.thankyou_url` and `urls.notification_url` overridden to Moodle endpoints per order.
  - `valid_until` = short (e.g. 24 h) so stale links can't be reused.
- **IPN**: Digistore24 POSTs to the Moodle notification URL; payload is signed with the plugin's `SHA_PASSPHRASE`; signature must be validated before any side effect. One IPN per payment (a 3-product purchase → 3 IPNs; each recurring charge → its own IPN).
- **Refund / chargeback IPNs** are a distinct event type on the same endpoint → reverse the matching Moodle payment and run the component's reversal path.
- **Test mode**: Digistore24 supports test purchases; verify the IPN signature path runs identically for test orders.

---

## Technical Constraints

- Server-to-server IPN is authoritative for marking a Moodle payment as delivered; the user's browser return is for UX only.
- The `custom` parameter in Digistore24 is limited to 127 characters — Moodle payment ids (bigint) fit comfortably, but any additional context must stay under that budget.
- Duplicate IPNs must be handled idempotently (network retries, refund reversals, recurring charges).
- Currency and amount from the IPN must be compared to the Moodle payable item; mismatch → reject and log, do not deliver.
- A full-access Digistore24 API key is required server-side for `createBuyUrl`. Storage: Moodle admin setting, treated as a secret (no logging, no client exposure).
- Moodle payment gateway class/method names and JS contract are version-specific — pinned to **Moodle 5.2**; verify against moodledev.io during `#plan`.
- Tax / VAT is handled entirely by Digistore24 (Merchant of Record). The plugin does not add, calculate or display tax on Moodle's side.

---

# 🧩 Feature Implementation

Each feature describes how it is implemented in the system.

All entries must:
- reference a feature (featXX)
- describe actual implementation
- avoid speculation

---

### Digistore24 payment gateway (feat01)

**Overview**  
Implemented as the Moodle 5.2 payment gateway plugin `paygw_digistore24`. The plugin lives in the repository's `paygw_digistore24/` subdirectory and is deployed to `public/payment/gateway/digistore24/` of a Moodle install. Code is structured to mirror the in-tree `paygw_paypal` reference plugin.

---

**Architecture**

```
   Browser              Moodle (paygw_digistore24)             Digistore24
   ───────              ──────────────────────────             ───────────
   Pay button  ─────►   gateways_modal.js
                          → start_checkout (WS)  ──────────►   POST createBuyUrl
                          ◄───── signed URL  ◄──────────────
   redirect  ◄────────  window.location = signed URL
                                                       ─────►   Checkout UX
                                                                Payment

                                                         ◄─── IPN (signed)
                          callback.php
                            verify SHA passphrase
                            match payments.id via custom
                            verify amount + currency
                            insert/update paygw_digistore24_txn
                            core_payment\helper::deliver_order
                                → enrol_fee.deliver_order → enrol user
                          ◄── 200 OK ──────────────────────────►
   return.php  ◄────────  Browser comes back via thankyou_url
                            shows "paid" once txn row exists
                            otherwise auto-refreshes
```

---

**Components**

| Path | Role |
|------|------|
| `paygw_digistore24/version.php` | Component metadata (`paygw_digistore24`, requires Moodle 5.2). |
| `paygw_digistore24/classes/gateway.php` | Implements `\core_payment\gateway`: supported currencies, per-account form (brand name), validation that admin secrets are set before enable. |
| `paygw_digistore24/settings.php` | Admin form: API key, IPN passphrase, default product id, grace period, test mode. Registers two admin external pages (mapping, report). Calls `\core_payment\helper::add_common_gateway_settings`. |
| `paygw_digistore24/db/access.php` | Capabilities `paygw/digistore24:managemapping` (write, system) and `paygw/digistore24:viewreport` (read, system); both default-allowed for `manager` archetype. |
| `paygw_digistore24/db/install.xml` | Tables `paygw_digistore24_itemmap`, `paygw_digistore24_txn`, `paygw_digistore24_log`. |
| `paygw_digistore24/db/services.php` | External functions `paygw_digistore24_get_config_for_js` (read) and `paygw_digistore24_start_checkout` (write). |
| `paygw_digistore24/db/tasks.php` | Schedules `check_subscription_endings` every hour at minute 15. |
| `paygw_digistore24/classes/external/get_config_for_js.php` | Returns brand name, cost (with surcharge), currency for the modal. |
| `paygw_digistore24/classes/external/start_checkout.php` | Reads payable, persists pending Moodle payment via `\core_payment\helper::save_payment`, calls `create_buy_url::execute`, returns `{redirecturl, paymentid}`. |
| `paygw_digistore24/amd/src/gateways_modal.js` | Exports `process(component, paymentArea, itemId)`: shows a "Redirecting…" modal, calls `start_checkout`, sets `window.location`. |
| `paygw_digistore24/amd/src/repository.js` | AJAX wrappers around the two webservices. |
| `paygw_digistore24/classes/api/client.php` | Thin Digistore24 HTTP client (`POST https://www.digistore24.com/api/call/{APIKEY}/json/{FUNCTION}`, header `X-DS-API-KEY`). Logs each call (with secrets redacted). |
| `paygw_digistore24/classes/api/create_buy_url.php` | Wrapper for `createBuyUrl` with `product_id`, buyer (read-only), payment plan (optional), `tracking.custom = paymentid`, thankyou + notification URLs, 24 h validity. |
| `paygw_digistore24/classes/api/ipn_signature.php` | SHA-512 IPN signature compute + validate (key sort case-insensitive, exclude `sha_sign`, append passphrase). |
| `paygw_digistore24/callback.php` | Public IPN endpoint. `NO_MOODLE_COOKIES` + `NO_DEBUG_DISPLAY` + `AJAX_SCRIPT`. Validates signature, matches payment, verifies amount + currency, idempotent insert/update of `paygw_digistore24_txn`, calls `\core_payment\helper::deliver_order` for payment events, runs `reversal::run` for refund/chargeback events, marks `cancelled=1` for cancellation events. |
| `paygw_digistore24/return.php` | Thank-you page. Shows "paid" if a delivered txn row exists, otherwise auto-refreshes every 5 s. Forwards the user to `service_provider::get_success_url` after a brief notice. |
| `paygw_digistore24/mapping.php` + `classes/form/mapping_form.php` | Admin mapping CRUD; capability-gated. |
| `paygw_digistore24/report.php` | Admin transactions report. Joins `paygw_digistore24_txn` with `payments`. Status filter, paginated. |
| `paygw_digistore24/classes/helper.php` | `resolve_product_id`, `digistore24_enabled_anywhere`, `log` (writes to `paygw_digistore24_log` with redacted payload), `redact` (recursive). Status constants `STATUS_DELIVERED`, `STATUS_REVERSED`, `STATUS_FAILED`. |
| `paygw_digistore24/classes/event/payment_reversed.php` | `\core\event\base` subclass. Stable extension point: any reversal fires this event with `component`, `paymentarea`, `itemid`, `reason` in `other`. |
| `paygw_digistore24/classes/reversal.php` | Plugin-side bridge over the missing `core_payment` reversal API. For `enrol_fee` calls `enrol_get_plugin('fee')->unenrol_user`; always fires `payment_reversed`. |
| `paygw_digistore24/classes/task/check_subscription_endings.php` | Scheduled task. Selects delivered + cancelled rows whose `period_end + grace_period_days < now`, skips ones with a newer delivery (resubscribed), runs `reversal::run`, marks `grace_processed = 1`. |
| `paygw_digistore24/classes/privacy/provider.php` | `\core_payment\privacy\paygw_provider`: declares the txn table + the external Digistore24 location; exports per-payment data; deletes via subquery. |

---

**Data Flow**

Checkout: `Pay button → gateways_modal.process → start_checkout WS → save_payment + createBuyUrl → redirecturl → window.location`.

Payment success: `Digistore24 IPN → callback.php → ipn_signature::validate → payments + paygw_digistore24_txn match → amount/currency check → upsert txn row delivered → core_payment\helper::deliver_order → component grants access`.

Refund / chargeback: `Digistore24 IPN → callback.php → mark txn row reversed → reversal::run → (enrol_fee unenrol if applicable) + payment_reversed event`.

Subscription cancellation: `cancellation IPN → mark txn row cancelled = 1`. Later: `check_subscription_endings cron → if period_end + grace_period < now and no fresher delivery → reversal::run + grace_processed = 1`.

---

**State Management**

- `payments` (core) — owned by `core_payment`, holds the Moodle payment row.
- `paygw_digistore24_itemmap` — `(component, paymentarea, itemid) → product_id` overrides.
- `paygw_digistore24_txn` — one row per delivered or reversed Digistore24 transaction tied to a `payments.id`. Holds `transaction_id`, `order_id`, `product_id`, `status`, `event_type`, `amount`, `currency`, `period_end`, `cancelled`, `grace_processed`, redacted `raw` payload. Indexed on `transaction_id` and `status`.
- `paygw_digistore24_log` — diagnostic trail (`api_request`, `api_response`, `ipn_received`, `ipn_rejected`, `reversal`, `error`). Indexed on `kind`, `paymentid`, `timecreated`. Surfaced (in part) by the report; also queryable via DB.

---

**Dependencies**

Internal:
- `\core_payment\gateway`, `\core_payment\helper`, `\core_payment\local\callback\service_provider` (Moodle 5.2 — see top of this section).
- `\enrol_fee` enrolment plugin: `enrol_get_plugin('fee')->unenrol_user(...)` (used by the reversal bridge for `enrol_fee` payments).
- `\core\event\base`, `\core\task\scheduled_task`.

External:
- Digistore24 JSON API (`createBuyUrl`).
- Digistore24 IPN POST (one webhook with multiple event types).

---

**Constraints / Limitations**

- The reversal bridge only auto-acts for `component = 'enrol_fee'`. For any other component the `payment_reversed` event is fired and the row is marked reversed, but no built-in unenrol / undeliver runs (Moodle 5.2 has no generic reversal API). Admin report surfaces every reversal so manual intervention is always possible.
- The mapping page admits any `(component, paymentarea, itemid)` triple a Manager types — there is no picker that browses Moodle items yet (would need component-specific resolvers; deferred).
- The plugin's own `paygw_digistore24_log` table has no automatic pruning; admin should plan log retention if traffic is high.

---

**Notes**

- The `start_checkout` webservice is the only place a Moodle pending payment is created. The IPN handler never creates payment rows on its own — an IPN with an unknown `custom` is rejected.
- All API requests, IPN bodies and rejections are logged with secrets (API key, SHA passphrase, `sha_sign`) replaced by `[redacted]`.
- The Digistore24 `custom` field carries the Moodle `payments.id` only — no extra context. Stays well within the 127-char limit.

---

# Open Questions for Product Owner

Captured while implementing tasks 02–13; none of them block deployment in test mode but each may need a real-world adjustment before going live.

1. **Digistore24 `createBuyUrl` parameter shape** — the wrapper builds `{product_id, buyer, payment_plan, tracking, valid_until, urls, settings}`. The exact JSON keys (e.g. `urls.thankyou_url` vs `urls.thank_you_url`, the `buyer_readonly` array key) are inferred from the public docs and need verification on the first sandbox call. If Digistore24 returns an error, the API client logs the exact response which will tell us.

2. **Digistore24 IPN signature algorithm** — implementation follows the standard Digistore24 IPN PHP receiver scheme (uksort case-insensitive, drop `sha_sign`, join values with the passphrase, append the passphrase, SHA-512, uppercase hex). If your Digistore24 vendor account uses a different SHA scheme (e.g. SHA-256, different separator), `paygw_digistore24/classes/api/ipn_signature.php` is the only file to adjust.

3. **Subscription IPN field names** — `callback.php` reads `next_payment_at` for `period_end` and uses `pay_sequence_no` to detect rebills, plus event names (`on_payment`, `on_rebill`, `on_refund`, `on_chargeback`, `on_payment_missed`, `on_affiliation_cancelled`, `connection_test`, `on_revoked`). Confirm against the IPN field reference for your account; rename if needed.

4. **`createBuyUrl` payment_plan** — `start_checkout` currently passes `null`. To actually create a Digistore24 subscription from Moodle we need to know:
   - Which Moodle attribute on the payable item indicates "this is a subscription"? (`enrol_fee` only has `enrolperiod`, which is one-shot.)
   - What `payment_plan` shape does Digistore24 expect (`first_amount`, `other_amounts_count`, `interval`, `interval_unit` …)?
   Until confirmed, sales go through as one-off; the subscription bridge in `callback.php` and the scheduled task work but there's no Moodle-side trigger to ask Digistore24 for a payment plan.

5. **Buyer fields and `buyer_readonly`** — we send email, first name, last name, country and request that they be read-only at checkout. If Digistore24 expects different field names (`firstname` vs `first_name`, etc.) or a different way to lock fields, the createBuyUrl wrapper needs adjusting. Same place to fix.

6. **Repo name vs plugin component** — the repo is `moodle-local-digistore24`, the plugin is `paygw_digistore24`. Two plausible long-term shapes:
   - **(A)** Rename the repo to `moodle-paygw_digistore24` and move the plugin to repo root (standard moodle.org plugin layout); the eLeDia.OS docs move into a `docs/` subdir.
   - **(B)** Keep two plugins: `local_digistore24` (admin tooling, libraries) at repo root and `paygw_digistore24` in a subdir, repo name unchanged.
   For now the plugin code lives in `paygw_digistore24/` and the docs at the repo root.

7. **enrol_fee unenrol semantics** — `enrol_get_plugin('fee')->unenrol_user($instance, $userid)` follows the standard Moodle enrol plugin API. Should refunds also delete progress / grades / certificates issued during the paid period, or only revoke enrolment? Current behaviour: unenrol only.

8. **Log retention** — `paygw_digistore24_log` grows unbounded. Need an admin setting (e.g. "keep N days") and a small purge step inside the existing scheduled task? Not done in v1.

9. **Currency support list** — currently mirrored from PayPal's gateway plus EUR. If Digistore24 supports more (or fewer) currencies, edit `gateway::get_supported_currencies`.

---

# 📏 Rules

- Always describe the current implementation (not the intended one)
- Keep descriptions precise and technical
- Do not duplicate feature descriptions
- Update this document when implementation changes

---

# 🔑 Key Principle

> This document explains how the system is built — not how it should behave.
