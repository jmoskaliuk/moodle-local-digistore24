<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Digistore24';
$string['pluginname_desc'] = 'The Digistore24 plugin allows you to receive payments via Digistore24.';
$string['gatewayname'] = 'Digistore24';
$string['gatewaydescription'] = 'Digistore24 is an authorised payment provider.';
$string['gatewaycannotbeenabled'] = 'The Digistore24 gateway cannot be enabled. Set the API key, IPN passphrase and default product id in the plugin settings first.';

// Per-account form.
$string['brandname'] = 'Brand name';
$string['brandname_help'] = 'Optional brand name shown to the buyer on the Digistore24 checkout page.';

// Site settings.
$string['apikey'] = 'API key';
$string['apikey_desc'] = 'Full-access Digistore24 API key. Stored as a secret; required for the createBuyUrl call.';
$string['ipn_passphrase'] = 'IPN passphrase';
$string['ipn_passphrase_desc'] = 'Shared SHA passphrase used to validate incoming IPN requests. Must match the passphrase configured for the IPN connection in your Digistore24 vendor account.';
$string['default_product_id'] = 'Default Digistore24 product id';
$string['default_product_id_desc'] = 'Used for any payable item that does not have its own override on the product mapping page.';
$string['grace_period_days'] = 'Subscription grace period (days)';
$string['grace_period_days_desc'] = 'After a failed rebill or user cancellation, access ends this many days after the end of the currently paid period.';
$string['test_mode'] = 'Test mode';
$string['test_mode_desc'] = 'When enabled, expects Digistore24 test purchases. Test IPNs are still validated and recorded normally.';

$string['links_heading'] = 'Endpoints';
$string['links_desc'] = '
<dl>
  <dt>IPN URL (configure on Digistore24)</dt><dd><code>{$a->callback}</code></dd>
  <dt>Product mapping page</dt><dd><a href="{$a->mapping}">{$a->mapping}</a></dd>
  <dt>Transactions report</dt><dd><a href="{$a->report}">{$a->report}</a></dd>
</dl>';

// Mapping page.
$string['mapping_pagetitle'] = 'Digistore24 product mapping';
$string['mapping_intro'] = 'Map a specific Moodle payable item (course enrolment, activity, custom component) to a Digistore24 product id. Items without a mapping use the site-wide default product.';
$string['mapping_addnew'] = 'Add mapping';
$string['mapping_component'] = 'Component';
$string['mapping_paymentarea'] = 'Payment area';
$string['mapping_itemid'] = 'Item id';
$string['mapping_productid'] = 'Digistore24 product id';
$string['mapping_actions'] = 'Actions';
$string['mapping_edit'] = 'Edit mapping';
$string['mapping_delete_confirm'] = 'Delete this mapping? Items will fall back to the site-wide default product.';
$string['mapping_saved'] = 'Mapping saved';
$string['mapping_deleted'] = 'Mapping deleted';
$string['mapping_no_payment_account'] = 'No payment account uses the Digistore24 gateway yet. Enable Digistore24 on a payment account first.';

// Report page.
$string['report_pagetitle'] = 'Digistore24 transactions';
$string['report_paymentid'] = 'Payment';
$string['report_user'] = 'User';
$string['report_component'] = 'Component';
$string['report_itemid'] = 'Item';
$string['report_amount'] = 'Amount';
$string['report_status'] = 'Status';
$string['report_event'] = 'Event';
$string['report_transaction_id'] = 'Digistore24 transaction';
$string['report_period_end'] = 'Period end';
$string['report_timecreated'] = 'Created';
$string['report_filter_status'] = 'Status filter';
$string['report_status_all'] = 'All';
$string['report_status_delivered'] = 'Delivered';
$string['report_status_reversed'] = 'Reversed';
$string['report_status_failed'] = 'Failed';

// Frontend / modal.
$string['redirecting'] = 'Redirecting to Digistore24…';
$string['redirect_failed'] = 'Could not start the Digistore24 checkout: {$a}';
$string['internalerror'] = 'An internal error occurred. The payment was not started.';
$string['no_product_mapping'] = 'No Digistore24 product is configured for this item and no site-wide default is set. Please contact the site administrator.';

// Return / thank-you page.
$string['return_pagetitle'] = 'Payment confirmation';
$string['return_pending'] = 'Your payment was received and is being finalised. This page will refresh automatically.';
$string['return_paid'] = 'Thank you. Your payment was confirmed and access has been granted.';
$string['return_failed'] = 'We could not confirm this payment. If you have been charged, please contact support.';
$string['return_continue'] = 'Continue';

// IPN responses.
$string['ipn_ok'] = 'OK';
$string['ipn_invalid_signature'] = 'Invalid IPN signature';
$string['ipn_unknown_payment'] = 'Unknown payment id in custom field';
$string['ipn_amount_mismatch'] = 'IPN amount or currency does not match the Moodle payment';

// Capabilities.
$string['digistore24:managemapping'] = 'Manage Digistore24 product mappings';
$string['digistore24:viewreport'] = 'View Digistore24 transactions report';

// Scheduled task.
$string['task_check_subscription_endings'] = 'End access for cancelled or failed Digistore24 subscriptions past the grace period';

// Events.
$string['event_payment_reversed'] = 'Digistore24 payment reversed';

// Privacy.
$string['privacy:metadata:paygw_digistore24_txn'] = 'Stores Digistore24 transaction references tied to a Moodle payment.';
$string['privacy:metadata:paygw_digistore24_txn:paymentid'] = 'Moodle payment id this transaction belongs to.';
$string['privacy:metadata:paygw_digistore24_txn:transaction_id'] = 'Digistore24 transaction id.';
$string['privacy:metadata:paygw_digistore24_txn:order_id'] = 'Digistore24 order id.';
$string['privacy:metadata:paygw_digistore24_txn:status'] = 'Status of the transaction (delivered, reversed, failed).';
$string['privacy:metadata:paygw_digistore24_txn:timecreated'] = 'Time the transaction record was created.';

$string['privacy:metadata:digistore24'] = 'In order to process payments, user data is forwarded to Digistore24.';
$string['privacy:metadata:digistore24:email'] = 'The user\'s email address is sent to Digistore24 to identify the buyer.';
$string['privacy:metadata:digistore24:firstname'] = 'The user\'s first name is sent to Digistore24.';
$string['privacy:metadata:digistore24:lastname'] = 'The user\'s last name is sent to Digistore24.';
$string['privacy:metadata:digistore24:country'] = 'The user\'s country is sent to Digistore24 (used for tax calculation).';
$string['privacy:metadata:digistore24:amount'] = 'The amount being paid is sent to Digistore24.';
$string['privacy:metadata:digistore24:currency'] = 'The currency of the payment is sent to Digistore24.';
$string['privacy:metadata:digistore24:product_id'] = 'The Digistore24 product id is included in the checkout URL.';
