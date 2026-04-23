# User Documentation

## Meta

This document describes how users interact with the product and its features.

It serves two purposes:
1. Explain the product from a user perspective
2. Describe how individual features (featXX) are used

This document is the **source of truth for user experience**.

---

## How to use this document

### For humans

Use this document to:
- describe how users interact with the system
- ensure features are understandable and usable
- document flows, steps, and expected outcomes
- validate usability independently from implementation

Think:
→ *How does a user experience this product?*

---

### For AI

When working with this document:

- treat it as the **source of truth for user-facing behavior**
- do not introduce technical explanations
- ensure consistency with `01-features.md`
- ensure it matches actual behavior (`03-dev-doc.md`)
- if unclear → request clarification

---

## What belongs here

Include:
- user flows
- step-by-step interactions
- expected results
- constraints from a user perspective
- usage examples

---

## What does NOT belong here

Do NOT include:
- implementation details → `03-dev-doc.md`
- internal logic or architecture
- tasks or planning → `04-tasks.md`
- bugs or test logs → `05-quality.md`

---

# Product Usage Overview

## Target Users

- **Site administrator** — installs the plugin, enters Digistore24 credentials, configures the default product and grace period, decides which roles may manage product mappings.
- **Manager / mapping editor** — anyone holding `paygw/digistore24:managemapping`. Maintains the per-item Digistore24 product mapping (e.g. assigns a specific Digistore24 product to a specific course).
- **Course / activity editor** — attaches a fee to a course (or activity / custom payable item) and links it to a payment account that has Digistore24 enabled. Does NOT touch Digistore24 product ids.
- **Learner** — the buyer. Sees a "Pay" button, is redirected to Digistore24, comes back after paying, gets access automatically.

---

## Main Use Cases

- Sell a paid course enrolment via Digistore24.
- Sell access to other paid Moodle items (activities, custom payable items) via Digistore24.
- Sell a recurring (subscription) course enrolment with a configurable grace period.
- Refund / chargeback handling: Digistore24 sends a refund IPN → the user automatically loses access (for `enrol_fee`) or a documented event is fired for other components.

---

## Typical Workflow

1. Admin installs the plugin and saves credentials in *Site administration → Plugins → Payment gateways → Digistore24*.
2. Admin creates a payment account and enables the Digistore24 gateway on it.
3. Admin (or a Manager) optionally maps individual courses to specific Digistore24 product ids on the *Product mapping* page; courses without a mapping use the default product.
4. Course editor attaches `enrol_fee` to the course and links it to the payment account.
5. Learner opens the course, clicks **Pay**, picks **Digistore24**.
6. The browser is redirected to Digistore24's checkout page, with the learner's email / name / country pre-filled and locked.
7. Learner completes payment.
8. Digistore24 confirms the payment to Moodle in the background (IPN). The learner is enrolled automatically.
9. Learner sees a confirmation page and is forwarded into the course.

---

## Key Concepts

- **Default Digistore24 product** — one Digistore24 product the plugin uses for any payable item without an explicit mapping. Set in plugin settings.
- **Product mapping** — an optional override that assigns a specific Digistore24 product to a specific Moodle payable item (course, activity, custom). Lives on its own admin page; not on the course form.
- **IPN** — the server-to-server notification Digistore24 sends to Moodle. The IPN is what actually marks a payment paid; the browser return is only cosmetic.
- **Grace period** — for subscriptions: how many days access continues after a failed rebill or a user cancellation, counted from the end of the last paid period.

---

# Feature Usage

---

### feat01 Digistore24 payment gateway

#### For the site administrator

**Install + configure**
1. Drop the `paygw_digistore24` plugin into `public/payment/gateway/` of your Moodle 5.2 install (or install via the admin plugin uploader).
2. Visit *Site administration → Notifications* and let Moodle finish the install.
3. Go to *Site administration → Plugins → Payment gateways → Digistore24* and enter:
   - **API key** — your full-access Digistore24 API key.
   - **IPN passphrase** — the SHA passphrase you'll set on your IPN connection in Digistore24.
   - **Default Digistore24 product id** — the fallback product for any unmapped item.
   - **Subscription grace period (days)** — defaults to 3.
   - **Test mode** — enable while testing with Digistore24 test purchases.
4. Note the **IPN URL** shown on that settings page; configure it as the IPN target in your Digistore24 vendor account, with the same SHA passphrase.

**Enable the gateway on a payment account**
1. *Site administration → Payments → Payment accounts*.
2. Edit a payment account, switch to the **Digistore24** tab, fill the brand name (optional), enable.

**Refunds / reversals**
- A refund IPN from Digistore24 is processed automatically. For `enrol_fee` payments, the user is unenrolled. For all other components, the plugin fires a `paygw_digistore24\event\payment_reversed` event so other plugins can react.
- Every reversal is also visible in *Site administration → Plugins → Payment gateways → Digistore24 → Transactions*, so you can intervene manually if needed.

**Allow other roles to manage mappings**
- The capability `paygw/digistore24:managemapping` controls who can use the product mapping page. By default, Site administrators and Managers have it. To grant it to other roles (e.g. course creators), edit the role under *Site administration → Users → Permissions → Define roles*.

#### For Managers / mapping editors

**Map a Moodle item to a specific Digistore24 product**
1. *Site administration → Plugins → Payment gateways → Digistore24 → Product mapping*.
2. Click **Add mapping** and enter:
   - **Component** (e.g. `enrol_fee`)
   - **Payment area** (e.g. `fee`)
   - **Item id** (the enrolment instance id, activity instance id, etc.)
   - **Digistore24 product id**
3. Save. From now on, paying that specific item uses your mapped product instead of the site default.

#### For course editors

- Attach `enrol_fee` to the course as usual; pick a payment account where Digistore24 is enabled. You don't need to know any Digistore24 product ids — that's the admin's job.

#### For learners

- On the course / item, click **Pay**, pick **Digistore24**, complete the checkout. Your name, email, and country are pre-filled and locked so they match your Moodle profile.
- After paying, you're redirected back to Moodle. If the access doesn't appear immediately, the page refreshes itself; the IPN usually arrives within seconds.
- For subscriptions: your access is renewed automatically after each successful charge. If a charge fails or you cancel on Digistore24, you keep access until the end of the current paid period plus the grace period configured by the site.

**Limitations / Notes**
- v1 is officially tested for `enrol_fee` (paid course enrolment). Other `core_payment` components work, but only `enrol_fee` automatically unenrols on refund — other components need a custom subscriber for the `payment_reversed` event.
- Tax / VAT, invoicing and dunning are handled by Digistore24 (Merchant of Record). Moodle only displays the price.
- The plugin needs server-to-server access from Digistore24 to your Moodle site (HTTPS, no auth on the IPN URL).

---

# Rules

- Every feature must reference a `featXX`
- Keep language simple and user-focused
- Avoid technical terminology
- Keep instructions actionable
- Update when user-facing behavior changes

---

# Key Principle

> This document explains how the product feels and works for the user — not how it is built.
