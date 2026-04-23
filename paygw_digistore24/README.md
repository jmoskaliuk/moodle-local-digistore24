# paygw_digistore24

Moodle 5.2 payment gateway plugin that lets a Moodle site accept payments through
[Digistore24](https://www.digistore24.com/).

## Status

Alpha (`MATURITY_ALPHA`, `0.1.0`). Not yet exercised against a live Digistore24
account. See `../03-dev-doc.md` for the open questions to confirm on the first
sandbox run.

## Requirements

- Moodle 5.2 (`MOODLE_502_STABLE`, `2026041000` or later).
- A Digistore24 vendor account with a full-access API key and an IPN passphrase.
- HTTPS reachable from the public internet so Digistore24 can deliver IPN POSTs.

## Install

1. Copy this folder into your Moodle install at
   `public/payment/gateway/digistore24/` (the plugin directory must be named
   `digistore24`, not `paygw_digistore24`).
2. Visit *Site administration → Notifications* and let Moodle finish the install.

## Configure

1. *Site administration → Plugins → Payment gateways → Digistore24*:
   - **API key** — your full-access Digistore24 API key.
   - **IPN passphrase** — the SHA passphrase you'll set on your Digistore24 IPN
     connection.
   - **Default Digistore24 product id** — used for any payable item without an
     explicit mapping.
   - **Subscription grace period (days)** — defaults to 3.
   - **Test mode** — leave on while testing.
2. Note the **IPN URL** shown on the settings page. Configure it in your
   Digistore24 vendor account *Settings → IPN* with the same SHA passphrase.
3. *Site administration → Payments → Payment accounts* — enable Digistore24 on a
   payment account and give it a brand name (optional).

## Use

- Add a fee to a course via `enrol_fee` and link it to the payment account.
- Optionally map specific Moodle items to specific Digistore24 products under
  *Site administration → Plugins → Payment gateways → Digistore24 → Product
  mapping* (capability `paygw/digistore24:managemapping`, default-allowed for
  the `manager` archetype).
- Watch transactions under *... → Digistore24 → Transactions* (capability
  `paygw/digistore24:viewreport`).

## Refunds and subscriptions

- Refund / chargeback IPNs unenrol the user automatically when the payment is
  for `enrol_fee`. For other components, the plugin fires
  `paygw_digistore24\event\payment_reversed` so custom plugins can react.
- Subscriptions: each successful rebill IPN extends access. On a failed rebill
  or user cancellation, access ends `grace_period_days` after the end of the
  last paid period (enforced by the scheduled task
  `\paygw_digistore24\task\check_subscription_endings`).

## Security

- The full-access Digistore24 API key and the IPN passphrase are stored as
  Moodle admin secrets and never logged or exposed to the client.
- The IPN endpoint validates the SHA-512 passphrase signature before any side
  effect; tampered IPNs are rejected with HTTP 400.
- Amount and currency in every IPN are compared against the Moodle payment row.

## Tests

```
vendor/bin/phpunit --testsuite paygw_digistore24_testsuite
```

Manual / sandbox test plan: see `../05-quality.md` (test01–test19).

## Licence

GPL v3 or later, same as Moodle.
