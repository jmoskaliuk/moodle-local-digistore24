# Quality

## Meta

This document tracks bugs and test results.

It only contains:
- bugs (bugXX)
- tests (testXX)
- reproducible issues
- verification of fixes

It does NOT contain:
- ideas → `01-features.md`
- tasks → `04-tasks.md`

---

## 🧪 Test Plan

### Automated (PHPUnit)

| Test | File | Verifies |
|------|------|----------|
| `test_compute_is_deterministic_and_uppercase` | `paygw_digistore24/tests/ipn_signature_test.php` | IPN SHA-512 hash is stable and uppercase |
| `test_compute_excludes_sha_sign_field` | same | The `sha_sign` field is not part of the signed input |
| `test_compute_is_case_insensitive_keys` | same | Key sort is case-insensitive |
| `test_validate_accepts_correct_signature` | same | Valid IPN passes |
| `test_validate_rejects_tampered_signature` | same | Tampered field fails |
| `test_validate_rejects_missing_signature` | same | Missing `sha_sign` fails |
| `test_validate_rejects_empty_passphrase` | same | Empty passphrase always fails |
| `test_resolve_product_id_uses_override_when_present` | `paygw_digistore24/tests/helper_test.php` | Per-item mapping wins over default |
| `test_resolve_product_id_falls_back_to_default` | same | Default used when no mapping |
| `test_resolve_product_id_throws_when_nothing_configured` | same | Hard-fail when neither set |
| `test_redact_masks_known_secret_keys` | same | Secrets are not logged |

Run: `vendor/bin/phpunit --testsuite paygw_digistore24_testsuite` (after Moodle's `phpunit_util` initialisation).

### Manual / Sandbox (against Digistore24 test mode)

| ID | Scenario | Expected | Result |
|----|----------|----------|--------|
| test01 | Install + admin settings | Plugin installs cleanly on Moodle 5.2; gateway appears under *Site administration → Plugins → Payment gateways*; admin can save API key, IPN passphrase, default product id, grace period, test mode | — |
| test02 | Enable on payment account | Admin can enable Digistore24 on a payment account once admin settings are filled; cannot enable while any required field is empty | — |
| test03 | Course paid enrolment, happy path | Learner clicks "Pay", picks Digistore24, lands on Digistore24 checkout, completes test purchase; IPN arrives; learner is enrolled in the course; return page shows confirmed state | — |
| test04 | Custom value in Digistore24 | The Digistore24 transaction record shows `custom = <Moodle payment.id>` | — |
| test05 | Buyer fields read-only | At Digistore24 checkout, email / first name / last name / country are pre-filled and not editable | — |
| test06 | IPN before user returns | Even if the IPN arrives first, the return page shows "paid" on first load (no race) | — |
| test07 | Replay IPN | Replaying the same IPN (same `transaction_id`) does not double-enrol; admin report shows one delivered row | — |
| test08 | Tampered IPN | Modifying any field of a captured IPN body and re-POSTing returns HTTP 400; nothing is delivered; rejection is logged | — |
| test09 | Amount mismatch | An IPN with the right `custom` but the wrong `amount` returns HTTP 400; no delivery | — |
| test10 | Currency mismatch | An IPN with the right `custom` but a different `currency` returns HTTP 400; no delivery | — |
| test11 | Refund (`enrol_fee`) | Refund the test order on Digistore24; refund IPN arrives; `paygw_digistore24_txn` row is `reversed`; user is unenrolled; `paygw_digistore24\event\payment_reversed` is fired | — |
| test12 | Refund on non-`enrol_fee` component | Refund a payment for a non-enrol_fee component; row is `reversed`, event is fired, no enrol_fee unenrol attempted | — |
| test13 | Subscription happy path | Set up a Digistore24 product with a payment plan; first IPN delivers; `period_end` is recorded | — |
| test14 | Subscription rebill | Each rebill IPN updates the row's `period_end` and stays delivered; user keeps access | — |
| test15 | Subscription cancellation + grace | Cancellation IPN sets `cancelled=1`; the scheduled task `check_subscription_endings` runs; before `period_end + grace_period_days`, no change; after, the user is unenrolled and the row becomes `reversed` | — |
| test16 | Per-item product mapping | Add a mapping for a course; verify the next checkout uses the mapped product, not the default | — |
| test17 | Mapping page capability | A user with the `manager` role can open and edit the mapping page; a user without `paygw/digistore24:managemapping` gets a permission error | — |
| test18 | Site default missing + no mapping | If neither default nor mapping is configured, `start_checkout` surfaces the "no_product_mapping" message; no payment row is left orphaned | — |
| test19 | Privacy export | `tool_dataprivacy` export for a user that paid via Digistore24 includes the transaction row | — |

---

## 🐞 Bugs

(none yet)

---

## ✅ Verified

(none yet)

---

## Rules

- Only reproducible issues belong here
- Link bugs to features (featXX) and the task that fixes them
- Move resolved bugs to "Verified", do not delete
