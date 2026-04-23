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
 * Digistore24 transactions admin report.
 */

require(__DIR__ . '/../../../config.php');

require_login();
admin_externalpage_setup('paygw_digistore24_report');
require_capability('paygw/digistore24:viewreport', context_system::instance());

$status = optional_param('status', '', PARAM_ALPHANUM);
$page   = optional_param('page', 0, PARAM_INT);
$perpage = 50;

$baseurl = new moodle_url('/payment/gateway/digistore24/report.php', ['status' => $status]);
$PAGE->set_url($baseurl);
$PAGE->set_title(get_string('report_pagetitle', 'paygw_digistore24'));
$PAGE->set_heading(get_string('report_pagetitle', 'paygw_digistore24'));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('report_pagetitle', 'paygw_digistore24'));

// Status filter.
$options = [
    ''                              => get_string('report_status_all', 'paygw_digistore24'),
    \paygw_digistore24\helper::STATUS_DELIVERED => get_string('report_status_delivered', 'paygw_digistore24'),
    \paygw_digistore24\helper::STATUS_REVERSED  => get_string('report_status_reversed', 'paygw_digistore24'),
    \paygw_digistore24\helper::STATUS_FAILED    => get_string('report_status_failed', 'paygw_digistore24'),
];
echo html_writer::start_tag('form', ['method' => 'get', 'class' => 'mb-3']);
echo html_writer::label(get_string('report_filter_status', 'paygw_digistore24'), 'status', false, ['class' => 'mr-2']);
echo html_writer::select($options, 'status', $status, false, ['onchange' => 'this.form.submit()']);
echo html_writer::end_tag('form');

$where = '';
$params = [];
if ($status !== '') {
    $where = 'WHERE t.status = :status';
    $params['status'] = $status;
}

$sql = "SELECT t.*, p.userid, p.component, p.paymentarea, p.itemid, p.amount AS payamount, p.currency AS paycurrency
          FROM {paygw_digistore24_txn} t
          JOIN {payments} p ON p.id = t.paymentid
          $where
         ORDER BY t.timecreated DESC";

$total = $DB->count_records_sql(
    "SELECT COUNT(*) FROM {paygw_digistore24_txn} t " . ($where ? "JOIN {payments} p ON p.id = t.paymentid $where" : ''),
    $params);

$rows = $DB->get_records_sql($sql, $params, $page * $perpage, $perpage);

$table = new html_table();
$table->head = [
    get_string('report_paymentid', 'paygw_digistore24'),
    get_string('report_user', 'paygw_digistore24'),
    get_string('report_component', 'paygw_digistore24'),
    get_string('report_itemid', 'paygw_digistore24'),
    get_string('report_amount', 'paygw_digistore24'),
    get_string('report_status', 'paygw_digistore24'),
    get_string('report_event', 'paygw_digistore24'),
    get_string('report_transaction_id', 'paygw_digistore24'),
    get_string('report_period_end', 'paygw_digistore24'),
    get_string('report_timecreated', 'paygw_digistore24'),
];
foreach ($rows as $row) {
    $user = core_user::get_user($row->userid);
    $username = $user ? fullname($user) : (string)$row->userid;
    $amount = format_float((float)$row->payamount, 2) . ' ' . $row->paycurrency;
    $period = $row->period_end ? userdate((int)$row->period_end) : '-';
    $created = userdate((int)$row->timecreated);
    $statuslabel = get_string('report_status_' . $row->status, 'paygw_digistore24');

    $table->data[] = [
        $row->paymentid,
        s($username),
        s($row->component . '/' . $row->paymentarea),
        $row->itemid,
        $amount,
        $statuslabel,
        s($row->event_type ?? ''),
        s($row->transaction_id),
        $period,
        $created,
    ];
}

echo html_writer::table($table);
echo $OUTPUT->paging_bar($total, $page, $perpage, $baseurl);
echo $OUTPUT->footer();
