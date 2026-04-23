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
 * Digistore24 thank-you / return page.
 *
 * The IPN is the authority for marking a payment delivered. This page only
 * surfaces whatever state the IPN has already produced. If the IPN hasn't
 * arrived yet, we auto-refresh briefly.
 */

require(__DIR__ . '/../../../config.php');
require_login();

$paymentid = required_param('paymentid', PARAM_INT);

$payment = $DB->get_record('payments', ['id' => $paymentid], '*', MUST_EXIST);
if ((int)$payment->userid !== (int)$USER->id) {
    require_capability('paygw/digistore24:viewreport', context_system::instance());
}

$pageurl = new moodle_url('/payment/gateway/digistore24/return.php', ['paymentid' => $paymentid]);
$PAGE->set_url($pageurl);
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('return_pagetitle', 'paygw_digistore24'));
$PAGE->set_heading(get_string('return_pagetitle', 'paygw_digistore24'));

$txn = $DB->get_record('paygw_digistore24_txn', ['paymentid' => $payment->id, 'status' => \paygw_digistore24\helper::STATUS_DELIVERED]);

// Resolve the success URL the component wants the user to land on.
$successurl = null;
try {
    $successurl = \core_payment\helper::get_success_url($payment->component, $payment->paymentarea, (int)$payment->itemid);
} catch (\Throwable $e) {
    $successurl = new moodle_url('/');
}

if ($txn) {
    // Done -- forward the user to the component's success URL after a brief notice.
    $PAGE->requires->js_init_code(
        sprintf("setTimeout(function(){window.location=%s;}, 1500);", json_encode($successurl->out(false))));

    echo $OUTPUT->header();
    echo $OUTPUT->notification(get_string('return_paid', 'paygw_digistore24'), 'success');
    echo html_writer::link($successurl, get_string('return_continue', 'paygw_digistore24'), ['class' => 'btn btn-primary']);
    echo $OUTPUT->footer();
    exit;
}

// Pending -- short auto-refresh.
$refreshseconds = 5;
$PAGE->requires->js_init_code(
    sprintf("setTimeout(function(){window.location.reload();}, %d);", $refreshseconds * 1000));

echo $OUTPUT->header();
echo $OUTPUT->notification(get_string('return_pending', 'paygw_digistore24'), 'info');
echo html_writer::link($successurl, get_string('return_continue', 'paygw_digistore24'), ['class' => 'btn btn-secondary']);
echo $OUTPUT->footer();
