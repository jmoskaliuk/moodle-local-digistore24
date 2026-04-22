# Features

## Meta

This document defines what the product should do.

It describes:
- features (featXX)
- intended behavior
- product-level decisions

This document represents the **intended behavior of the system**.

---

## How to use this document

### For humans

Use this document to:
- define new features before implementation
- clarify behavior during development
- document decisions and constraints
- ensure a shared understanding of the product

This is the place to think about:
→ *What should the product do and why?*

---

### For AI

When working with this document:

- treat it as the **source of truth for expected behavior**
- do not assume behavior that is not defined here
- if something is unclear → raise a clarification instead of guessing
- ensure implementation and documentation align with this document

---

## What belongs here

Include:
- feature definitions (featXX)
- goals and purpose
- expected behavior (including edge cases)
- non-goals (explicit exclusions)
- design decisions

---

## What does NOT belong here

Do NOT include:
- tasks (→ 04-tasks.md)
- implementation details (→ 03-dev-doc.md)
- test results or bugs (→ 05-quality.md)
- user instructions (→ 02-user-doc.md)

---

## Product Overview

### Purpose
Enable Moodle sites to accept payments through Digistore24 — primarily for paid enrolments — by plugging Digistore24 into Moodle's core payment subsystem.

### Core Concepts
- **Moodle Payment API (`core_payment`)** — the generic payment area/account abstraction that collects money for enrolment fees and other payable items.
- **Payment gateway plugin (`paygw_digistore24`)** — the adapter that registers Digistore24 as an available gateway.
- **Digistore24** — external payment/affiliate platform handling checkout, payment methods, invoicing, tax, refunds; notifies Moodle via IPN (Instant Payment Notification).
- **Payable item** — a course enrolment (or other `component`/`paymentarea`) with a cost and currency.
- **Callback flow** — user pays on Digistore24 → Digistore24 notifies Moodle → Moodle marks payment complete → enrolment (or equivalent) is granted.

### Key Features (planned)
- feat01 — Digistore24 as a Moodle payment gateway

### Constraints
- Must conform to Moodle's `paygw_*` plugin contract (Moodle 5.x).
- Must work with at least the enrolment on payment flow (`enrol_fee`).
- Payment validation must not rely on the user's browser returning from Digistore24 — server-to-server IPN is authoritative.
- All secrets (API keys, IPN passphrase) must be stored in plugin settings, not in code.

---

## Features

---

### feat01 Digistore24 payment gateway

**Goal**  
Allow Moodle administrators to offer Digistore24 as a payment method for any Moodle payable item (starting with paid course enrolment), so learners can pay via Digistore24 and are automatically enrolled once the payment is confirmed.

---

**Behavior**  
Main flow:

1. Admin installs the plugin and enters Digistore24 credentials (API key, IPN passphrase, product mapping strategy) in site settings.
2. Admin creates a Moodle payment account and enables the Digistore24 gateway on it.
3. Course editor attaches a fee (via `enrol_fee` or similar) to the course and links the payment account.
4. Learner opens the course, clicks "Pay", chooses Digistore24.
5. Moodle creates a pending payment record and redirects the learner to the matching Digistore24 product/checkout URL (with order reference = Moodle payment id).
6. Learner completes payment on Digistore24.
7. Digistore24 sends an IPN to a Moodle callback endpoint. The plugin verifies the IPN signature/passphrase, matches the order reference to the pending payment, and marks it `delivered`.
8. Moodle's payment subsystem triggers the enrolment (or other `component` callback) for that payable item.
9. Learner returns to Moodle via Digistore24's thank-you redirect and sees the paid/enrolled state.

Edge cases to define:
- IPN arrives before the user returns (normal) — user must see consistent state.
- User abandons checkout — payment stays pending; no enrolment.
- Refund / chargeback IPN — reverse the enrolment (policy TBD).
- Duplicate IPN for the same order — idempotent; no double enrolment.
- Currency/amount mismatch between Moodle and Digistore24 product — reject and log.
- Multiple currencies supported by Digistore24 product mapping.

---

**Non-goals**  
- Replacing Digistore24's own checkout UI (Moodle redirects out).
- Implementing subscriptions/recurring billing in the first iteration (unless confirmed in scope).
- Handling affiliate management inside Moodle.
- Issuing invoices from Moodle (Digistore24 is the merchant of record).

---

**Decisions**  
- Implemented as a Moodle payment gateway plugin of type `paygw` → plugin name `paygw_digistore24`, path `payment/gateway/digistore24`. (Confirms to Moodle 5.x payment plugin contract.)
- Server-to-server IPN is authoritative for marking payments complete; browser redirect only updates UX.
- Order reference = Moodle `payment.id` (or a hashed form) so IPNs can be matched reliably.

---

**Open Questions**  
Awaiting more info from the product owner. Where Digistore24 gives us a choice, the options are listed explicitly:

1. **Checkout surface** — two realistic options, pick one as v1:
   - (a) **Static hosted checkout** `https://www.checkout-ds24.com/product/{product_id}/?custom={moodle_payment_id}` — simplest, requires one Digistore24 product per Moodle priceable item.
   - (b) **Dynamic `createBuyUrl` API** — lets us pre-fill buyer data, override price, set thank-you/IPN URLs per order; requires a full-access API key on the server.
2. **Product mapping** — one Digistore24 product per Moodle course (manual mapping in course settings), or one generic Digistore24 product with price set dynamically via `createBuyUrl`?
3. **Scope of payment areas** — only `enrol_fee` in v1, or also other `core_payment` components (e.g. enrolment plugins, activities, custom)?
4. **Refund / chargeback behaviour** — on Digistore24 refund IPN, should Moodle auto-unenrol (and for how far back), or only flag for admin review?
5. **Currencies / tax** — which currencies do we need to support? Does Digistore24 handle VAT on our behalf (Merchant of Record)? What does Moodle need to display?
6. **Subscriptions / recurring** — required in v1 (payment plans, billing-on-demand) or out of scope until later?
7. **Target Moodle version** — 5.0 / 5.1 / 5.2? Pin one; the `paygw` contract shifts between versions.
8. **API key type** — do we store a single site-wide API key (admin setting), or use a `developer` key and let each vendor connect their own account?

---

## Rules

- Every feature must have a unique ID (featXX)
- Keep descriptions precise and unambiguous
- Avoid technical implementation details
- Update this document if behavior changes

---

## Key Principle

> This document defines what should happen — not how it is implemented.
