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

/**
 * Digistore24 IPN endpoint.
 *
 * Public, no Moodle session, accepts POST from Digistore24's IPN service.
 * Validates the SHA passphrase signature, matches the `custom` field to a
 * Moodle payment id, then delivers / reverses depending on event type.
 */

define('NO_MOODLE_COOKIES', true);
define('NO_DEBUG_DISPLAY', true);
define('AJAX_SCRIPT', true);

require(__DIR__ . '/../../../config.php');

use core_payment\helper as payment_helper;
use paygw_digistore24\api\ipn_signature;
use paygw_digistore24\helper;

$post = (array)$_POST;

$respond = function (int $http, string $body) {
    http_response_code($http);
    header('Content-Type: text/plain; charset=utf-8');
    echo $body;
    exit;
};

$passphrase = (string)get_config('paygw_digistore24', 'ipn_passphrase');

helper::log('ipn_received', 'IPN POST received', $post);

if (!ipn_signature::validate($post, $passphrase)) {
    helper::log('ipn_rejected', 'Invalid signature', $post);
    $respond(400, get_string_manager()->get_string('ipn_invalid_signature', 'paygw_digistore24'));
}

// custom = Moodle payments.id.
$paymentid = isset($post['custom']) ? (int)$post['custom'] : 0;
$payment = $paymentid > 0 ? helper::get_payment_record($paymentid) : null;
if (!$payment) {
    helper::log('ipn_rejected', 'Unknown payment id ' . $paymentid, ['custom' => $post['custom'] ?? null]);
    $respond(404, get_string_manager()->get_string('ipn_unknown_payment', 'paygw_digistore24'));
}

$event = (string)($post['event'] ?? '');
$transactionid = (string)($post['transaction_id'] ?? '');
$orderid = (string)($post['order_id'] ?? '');
$productid = (string)($post['product_id'] ?? '');
$amount = isset($post['amount']) ? (float)$post['amount'] : null;
$currency = strtoupper((string)($post['currency'] ?? ''));
$pay_sequence_no = isset($post['pay_sequence_no']) ? (int)$post['pay_sequence_no'] : 0;

// Idempotency: if we already recorded this transaction id with the same status
// implied by the event, return OK without acting again.
$existing = $DB->get_record('paygw_digistore24_txn', [
    'paymentid'      => $payment->id,
    'transaction_id' => $transactionid,
]);

$ispayment   = in_array($event, ['on_payment', 'on_rebill', 'on_payment_missed', '', 'connection_test'], true);
$isrefund    = in_array($event, ['on_refund', 'on_chargeback', 'on_revoked'], true);
$iscancel    = $event === 'on_payment_missed' || $event === 'on_affiliation_cancelled';

// Plain "is this a payment success event": treat empty event (some IPN configs
// don't send one explicitly) and pay_sequence_no>=1 as a payment unless event
// indicates otherwise.
$ispaymentsuccess = ($event === 'on_payment' || $event === 'on_rebill'
    || ($event === '' && $pay_sequence_no >= 1));

if ($event === 'connection_test') {
    helper::log('ipn_received', 'Connection test', null, $payment->id);
    $respond(200, get_string_manager()->get_string('ipn_ok', 'paygw_digistore24'));
}

if ($ispaymentsuccess) {
    // Verify amount and currency against the Moodle payment.
    if ($amount === null || $currency === ''
        || abs($amount - (float)$payment->amount) > 0.005
        || $currency !== strtoupper((string)$payment->currency)) {
        helper::log('ipn_rejected', 'Amount or currency mismatch', [
            'expected_amount' => $payment->amount, 'got_amount' => $amount,
            'expected_currency' => $payment->currency, 'got_currency' => $currency,
        ], $payment->id);
        $respond(400, get_string_manager()->get_string('ipn_amount_mismatch', 'paygw_digistore24'));
    }

    if ($existing && $existing->status === helper::STATUS_DELIVERED) {
        // Already delivered; nothing more to do.
        $respond(200, get_string_manager()->get_string('ipn_ok', 'paygw_digistore24'));
    }

    // Persist the transaction row.
    $now = time();
    $row = (object)[
        'paymentid'       => $payment->id,
        'transaction_id'  => $transactionid,
        'order_id'        => $orderid,
        'product_id'      => $productid,
        'status'          => helper::STATUS_DELIVERED,
        'event_type'      => $event,
        'amount'          => $amount,
        'currency'        => $currency,
        'period_end'      => isset($post['next_payment_at']) ? strtotime((string)$post['next_payment_at']) : null,
        'cancelled'       => 0,
        'grace_processed' => 0,
        'raw'             => json_encode(helper::redact($post), JSON_UNESCAPED_SLASHES),
        'timecreated'     => $now,
        'timemodified'    => $now,
    ];
    if ($existing) {
        $row->id = $existing->id;
        $DB->update_record('paygw_digistore24_txn', $row);
    } else {
        $DB->insert_record('paygw_digistore24_txn', $row);
    }

    // Deliver via core_payment so the component (e.g. enrol_fee) grants access.
    try {
        payment_helper::deliver_order(
            $payment->component, $payment->paymentarea, (int)$payment->itemid,
            (int)$payment->id, (int)$payment->userid
        );
    } catch (\Throwable $e) {
        helper::log('error', 'deliver_order failed: ' . $e->getMessage(), null, $payment->id);
        $respond(500, get_string_manager()->get_string('internalerror', 'paygw_digistore24'));
    }

    $respond(200, get_string_manager()->get_string('ipn_ok', 'paygw_digistore24'));
}

if ($isrefund) {
    // Idempotency check.
    if ($existing && $existing->status === helper::STATUS_REVERSED) {
        $respond(200, get_string_manager()->get_string('ipn_ok', 'paygw_digistore24'));
    }

    $now = time();
    if ($existing) {
        $existing->status = helper::STATUS_REVERSED;
        $existing->event_type = $event;
        $existing->raw = json_encode(helper::redact($post), JSON_UNESCAPED_SLASHES);
        $existing->timemodified = $now;
        $DB->update_record('paygw_digistore24_txn', $existing);
    } else {
        $DB->insert_record('paygw_digistore24_txn', (object)[
            'paymentid'       => $payment->id,
            'transaction_id'  => $transactionid,
            'order_id'        => $orderid,
            'product_id'      => $productid,
            'status'          => helper::STATUS_REVERSED,
            'event_type'      => $event,
            'amount'          => $amount,
            'currency'        => $currency,
            'cancelled'       => 1,
            'grace_processed' => 0,
            'raw'             => json_encode(helper::redact($post), JSON_UNESCAPED_SLASHES),
            'timecreated'     => $now,
            'timemodified'    => $now,
        ]);
    }

    // Run the reversal bridge (task08).
    try {
        \paygw_digistore24\reversal::run($payment);
    } catch (\Throwable $e) {
        helper::log('error', 'Reversal failed: ' . $e->getMessage(), null, $payment->id);
        // Still respond OK to avoid Digistore24 retries; admin sees this in the report.
    }

    $respond(200, get_string_manager()->get_string('ipn_ok', 'paygw_digistore24'));
}

if ($iscancel) {
    // Subscription cancellation: mark cancelled but keep access. The scheduled
    // task ends access after the grace period.
    if ($existing) {
        $existing->cancelled = 1;
        $existing->event_type = $event;
        $existing->raw = json_encode(helper::redact($post), JSON_UNESCAPED_SLASHES);
        $existing->timemodified = time();
        $DB->update_record('paygw_digistore24_txn', $existing);
    }
    helper::log('ipn_received', "Subscription cancellation event '$event' recorded", null, $payment->id);
    $respond(200, get_string_manager()->get_string('ipn_ok', 'paygw_digistore24'));
}

// Any other event is logged but not acted on.
helper::log('ipn_received', "Unhandled event '$event' acknowledged", null, $payment->id);
$respond(200, get_string_manager()->get_string('ipn_ok', 'paygw_digistore24'));
