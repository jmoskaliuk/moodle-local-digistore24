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
- Plugin type: `paygw` → plugin name `paygw_digistore24`, path `payment/gateway/digistore24`.
- Must implement Moodle's standard gateway contract (`gateway`, AMD JS entry point, external service endpoints, lang strings, settings, `version.php`).
- Payment completion must call Moodle's payment API to mark the payable item as paid so `component` callbacks (e.g. `enrol_fee`) grant access.
- Concrete class/method details to be verified against the Moodle 5.x developer docs before implementation (fetch currently blocked by docs.moodle.org / moodledev.io from this environment).

### Digistore24 API
Facts collected from Digistore24 developer docs (via web search — see README/source links in commits):

- **HTTP base URL**: `https://www.digistore24.com/api/call/{APIKEY}/{FORMAT}/{FUNCTION}` — `FORMAT` ∈ {`json`, `xml`, `php`, `text`}.
- **Authentication**: send the API key in the HTTP header `X-DS-API-KEY` (preferred over URL-embedded key).
- **Response formats**: `Accept: application/json` by default; `text/plain`, `text/xml`, `application/php` also supported.
- **API key types**: `readonly` (reads only), `developer` (publishable, lets an app generate per-user keys), and full-access keys (never to be published).
- **Relevant API functions** (from API reference A–Z): `createBuyUrl`, `getPurchase`, `createBillingOnDemand`, refund-related calls, plus others TBD.
- **Hosted checkout URL**: `https://www.checkout-ds24.com/product/{product_id}/` — accepts GET parameters to pre-fill buyer data and carry a `custom` parameter (≤ 127 chars) that is forwarded to both the thank-you page and the IPN call. This is the primary mechanism to correlate a Digistore24 order with a Moodle `payment.id`.
- **Buy URL via API**: `createBuyUrl(product_id, buyer, payment_plan, tracking, valid_until, urls, placeholders, settings, addons)` — produces a signed, one-shot checkout URL when we need dynamic pricing, pre-filled buyer data or read-only fields.
- **IPN (Instant Payment Notification)**: Digistore24 POSTs to our configured IPN URL; payload is signed with our `SHA_PASSPHRASE`; we must validate the signature before trusting the data. One IPN per payment (a 3-product purchase → 3 IPNs).
- **Test mode**: Digistore24 supports test purchases; we need to verify the same IPN/signature path runs for test orders.

---

## Technical Constraints

- Server-to-server IPN is authoritative for marking a Moodle payment as delivered; the user's browser return is for UX only.
- The `custom` parameter in Digistore24 is limited to 127 characters — Moodle payment ids (bigint) fit comfortably, but any additional context must stay under that budget.
- Duplicate IPNs must be handled idempotently (network retries, refund reversals).
- Currency and amount from the IPN must be compared to the Moodle payable item; mismatch → reject and log.
- Moodle payment gateway class/method names and JS contract are version-sensitive (5.0/5.1/5.2) — pin a target version before coding.

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
