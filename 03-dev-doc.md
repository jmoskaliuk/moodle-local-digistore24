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
- Target: **Moodle 5.2**.
- Plugin type: `paygw` → plugin name `paygw_digistore24`, path `payment/gateway/digistore24`.
- Two sides of the Payment API:

  **Component side** (what `enrol_fee`, activities, or custom components implement — NOT us) — confirmed from Moodle docs provided by product owner (doc dates back to Moodle 3.10; interface is stable through 5.x):
  - Interface: `\core_payment\local\callback\service_provider`.
  - `get_payable(string $paymentarea, int $itemid): \core_payment\local\entities\payable` — returns amount, currency, and target `accountid`.
  - `deliver_order(string $paymentarea, int $itemid, int $paymentid, int $userid): bool` — called by `core_payment` after a gateway marks a payment successful; the component grants access here (e.g. `enrol_fee` enrols the user in the course).
  - Frontend trigger: any page that wants to offer a payment places an element with `data-action="core_payment/triggerPayment"` + `data-component`, `data-paymentarea`, `data-itemid`, `data-cost`, `data-description`, then initialises `core_payment/gateways_modal`.
  - Consequence for us: we never enrol / deliver anything ourselves — we only tell `core_payment` the payment succeeded, and `core_payment` calls the component's `deliver_order` automatically.

  **Gateway side** (what we implement as `paygw_digistore24`) — confirmed pieces from Moodle 5.2 paygw contract still to be verified during task01:
  - `classes/gateway.php` — extends `\core_payment\gateway`; declares supported currencies (permissive set, delegated to Digistore24).
  - AMD module — hooks into the modal that `core_payment/triggerPayment` opens, presents our gateway, on confirm calls our external service to start checkout and then redirects the browser to the returned Digistore24 URL.
  - External services (`classes/external/*.php` + `db/services.php`) — at least one function callable from the AMD module that: reads the payable (`get_payable`), creates a Moodle pending payment, calls Digistore24 `createBuyUrl`, returns the checkout URL.
  - `version.php`, `lang/en/paygw_digistore24.php`, `settings.php`, optional `db/install.xml`.
  - API to mark the payment successful (triggers `deliver_order`) and to reverse a payment (triggers the component's refund / unenrol path in 5.2): exact function names and signatures to be nailed down in task01.

- v1 QA focuses on `enrol_fee`; other `core_payment` components are technically supported from day one because `core_payment` dispatches to whichever `service_provider` owns the payable item — our gateway does not care.

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
