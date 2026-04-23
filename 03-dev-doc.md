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

**Refund / reversal — known gap (Moodle 5.2)**:
- `core_payment` has **no built-in reversal API** in Moodle 5.2. Search for `refund|reverse|chargeback|void` across `public/payment/` returns nothing payment-specific; `service_provider` has only `deliver_order`.
- Consequence: there is no generic "call component to undo a payment" entry point we can hit from our refund IPN. We must bridge it ourselves.
- Planned approach, to be implemented in task08:
  1. The plugin marks the original Moodle payment row as reversed (own bookkeeping column in the `paygw_digistore24` table, since `payments` has no status column for this).
  2. For `enrol_fee`: call `enrol_fee`'s enrolment plugin directly to unenrol the user (specifics to confirm in task08 against `public/enrol/fee`).
  3. For all other components: fire a Moodle event `paygw_digistore24\event\payment_reversed` carrying `component`, `paymentarea`, `itemid`, `paymentid`, `userid`. Site operators / component authors subscribe to act on it; documented as an extension point in `02-user-doc.md`.
  4. Admin report surfaces reversed payments so the admin can always intervene manually.

**Per-item `digistore24_product_id` override — known gap**:
- The `\core_payment\gateway` base class only exposes `add_configuration_to_gateway_form` (per-account), not per-payable-item. Moodle's payable item itself (`enrol_fee` instance, activity, custom) owns its price and account id but does not expose a standard "extra gateway-specific field" slot.
- Planned approach, to be implemented in task04: keep a dedicated plugin table `paygw_digistore24_itemmap` keyed by `(component, paymentarea, itemid)` with an optional `product_id` column. Admin UI lives on a plugin-admin page under "Site administration → Plugins → Payment gateways → Digistore24 → Product mapping" (not inline on the course/activity edit form). Course editors do *not* set product ids — it's an admin-level mapping.

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

## Feature Template

---

### [Feature Name] (featXX)

**Overview**  
Short description of the implementation.

---

**Architecture**  
How this feature fits into the system:

- components involved
- communication patterns

---

**Components**

List relevant components:

- component A → role
- component B → role

---

**Data Flow**

Describe how data moves:

- trigger → processing → result

---

**State Management (if relevant)**

- how state is stored
- how state changes

---

**Dependencies**

- internal dependencies (other features, modules)
- external dependencies (APIs, libraries)

---

**Constraints / Limitations**

- known issues
- technical limitations
- edge-case behavior

---

**Notes (optional)**

- implementation details worth knowing
- unusual decisions

---

# 📏 Rules

- Always describe the current implementation (not the intended one)
- Keep descriptions precise and technical
- Do not duplicate feature descriptions
- Update this document when implementation changes

---

# 🔑 Key Principle

> This document explains how the system is built — not how it should behave.
