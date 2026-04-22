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
Main flow (one-off payment):

1. Admin installs the plugin and enters Digistore24 credentials (full-access API key, IPN passphrase, default Digistore24 product id) in site settings.
2. Admin creates a Moodle payment account and enables the Digistore24 gateway on it.
3. A Moodle payable item (course `enrol_fee`, activity, or other `core_payment` component) is attached to the payment account. Optionally, a `digistore24_product_id` override is set at item level; otherwise the site-wide default product is used.
4. Learner clicks "Pay", chooses Digistore24.
5. Moodle creates a pending payment record. The plugin calls Digistore24 `createBuyUrl` with the resolved product id, Moodle-controlled price/currency, pre-filled read-only buyer data (from the Moodle user), `custom = payment.id`, and Moodle-side thank-you + IPN URLs, then redirects the learner to the returned signed URL.
6. Learner completes payment on Digistore24.
7. Digistore24 POSTs an IPN to the Moodle callback endpoint. The plugin validates the SHA-passphrase signature, matches `custom` to the pending Moodle `payment.id`, verifies amount + currency, and marks the payment `delivered`.
8. Moodle's payment subsystem triggers the component's delivery callback — e.g. `enrol_fee` enrols the user in the course.
9. Learner returns to Moodle via Digistore24's thank-you redirect and sees the paid / enrolled state.

Subscription flow (recurring):

- `createBuyUrl` is called with a `payment_plan` so Digistore24 sets up a subscription.
- Each recurring charge produces its own IPN; the plugin marks the corresponding Moodle payment for that period as delivered and extends access.
- A cancellation or failed rebill IPN ends access (timing per the open question on lifecycle).

Refund / chargeback flow:

- A refund / chargeback IPN triggers reversal: the plugin marks the payment reversed and invokes the component's reversal path (for `enrol_fee`: unenrol the user). The action is logged and visible to admins.

Edge cases:
- IPN arrives before the user returns (normal case) — learner must see a consistent "paid" state on return.
- User abandons checkout — payment stays pending; no enrolment; Moodle can time out / clean up pending rows eventually.
- Duplicate IPN for the same order — idempotent; no double enrolment, no double refund.
- Amount or currency in IPN does not match the Moodle payable item — reject, log, do not deliver.
- `createBuyUrl` call fails — show the learner a clear error and keep the Moodle payment pending; do not redirect to a broken checkout.
- IPN signature invalid — reject (HTTP 400), log, never mark delivered.

---

**Non-goals**  
- Replacing Digistore24's own checkout UI (Moodle always redirects out).
- Handling affiliate management inside Moodle (stays in Digistore24).
- Issuing invoices from Moodle — Digistore24 is the Merchant of Record and owner of the tax/VAT flow.

---

**Decisions**  
- Plugin type `paygw` → plugin name `paygw_digistore24`, path `payment/gateway/digistore24`; target **Moodle 5.2**.
- Server-to-server IPN is authoritative for marking payments complete; browser redirect only updates UX.
- Order reference = Moodle `payment.id`, passed through Digistore24's `custom` parameter (≤ 127 chars) so every IPN can be matched to a pending Moodle payment.
- **Checkout surface: `createBuyUrl` API (option 1b).** For each Moodle payment, the plugin calls `createBuyUrl` server-side to obtain a signed, short-lived Digistore24 checkout URL with Moodle-controlled price, currency, buyer data (read-only) and thank-you/IPN URLs. No static product-URL fallback.
- **Product mapping: hybrid (option 2c).** A site-wide default Digistore24 product is configured in the plugin settings. Any Moodle payable item (course/activity/etc.) can optionally override it with its own `digistore24_product_id`. Missing override → fall back to the default product; price/title are set dynamically via `createBuyUrl` in both cases.
- **Scope of payment areas: generic (option 3B).** The plugin works for any `core_payment` component (`enrol_fee`, activities, custom). v1 release notes document `enrol_fee` as the primary tested path; other areas are technically supported from day one.
- **Refund / chargeback: auto-unenrol (option 4).** On a refund / chargeback IPN from Digistore24, the plugin reverses the corresponding Moodle payment and calls the component's reversal path (for `enrol_fee`: unenrol the user from the course). Action is logged and visible to admins.
- **Currencies & tax (option 5): Digistore24 is authoritative.** Moodle displays the price set on the Moodle payable item; Digistore24 handles VAT calculation, country-based tax rules and invoicing as Merchant of Record. The plugin does not add or recalculate tax on Moodle's side.
- **Subscriptions (option 6): in scope for v1.** The plugin supports Digistore24 payment plans / recurring billing. Each recurring IPN marks the next period paid; Moodle grants / extends access accordingly.
- **API key scope: site-wide.** A single full-access Digistore24 API key is stored as an admin setting and used for all Moodle payable items. No per-vendor / per-course key. Treated as a secret: never logged, never sent to the client.
- **Subscription end on failed rebill / cancellation: end of paid period plus a configurable admin grace period.** The plugin keeps access until `period_end + grace_period_days`. Default grace period: 3 days (admin-configurable). Hard chargeback / refund still ends access immediately via the refund flow.
- **Product-id override UI: all payable items.** The optional `digistore24_product_id` override is exposed wherever a Moodle payable item exists — `enrol_fee` (course level), activity payment instances, and custom `core_payment` components — via the standard payment-area form extension Moodle provides for gateway plugins.

---

**Open Questions**

None. Feature is ready for `#plan` (task breakdown in `04-tasks.md`).

---

## Rules

- Every feature must have a unique ID (featXX)
- Keep descriptions precise and unambiguous
- Avoid technical implementation details
- Update this document if behavior changes

---

## Key Principle

> This document defines what should happen — not how it is implemented.
