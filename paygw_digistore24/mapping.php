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

require(__DIR__ . '/../../../config.php');

require_login();
admin_externalpage_setup('paygw_digistore24_mapping');
require_capability('paygw/digistore24:managemapping', context_system::instance());

$action = optional_param('action', 'list', PARAM_ALPHA);
$id     = optional_param('id', 0, PARAM_INT);

$baseurl = new moodle_url('/payment/gateway/digistore24/mapping.php');
$PAGE->set_url($baseurl);
$PAGE->set_title(get_string('mapping_pagetitle', 'paygw_digistore24'));
$PAGE->set_heading(get_string('mapping_pagetitle', 'paygw_digistore24'));

if (!\paygw_digistore24\helper::digistore24_enabled_anywhere()) {
    echo $OUTPUT->header();
    echo $OUTPUT->notification(get_string('mapping_no_payment_account', 'paygw_digistore24'), 'warning');
    echo $OUTPUT->footer();
    exit;
}

if ($action === 'delete') {
    require_sesskey();
    $DB->delete_records('paygw_digistore24_itemmap', ['id' => $id]);
    redirect($baseurl, get_string('mapping_deleted', 'paygw_digistore24'));
}

if ($action === 'edit' || $action === 'add') {
    $record = $id
        ? $DB->get_record('paygw_digistore24_itemmap', ['id' => $id], '*', MUST_EXIST)
        : (object)['id' => 0, 'component' => '', 'paymentarea' => '', 'itemid' => 0, 'product_id' => ''];

    $form = new \paygw_digistore24\form\mapping_form($baseurl->out(false) . '?action=' . $action . '&id=' . $id);
    $form->set_data($record);

    if ($form->is_cancelled()) {
        redirect($baseurl);
    }

    if ($data = $form->get_data()) {
        $data->timemodified = time();
        if (!empty($data->id)) {
            $DB->update_record('paygw_digistore24_itemmap', $data);
        } else {
            unset($data->id);
            $DB->insert_record('paygw_digistore24_itemmap', $data);
        }
        redirect($baseurl, get_string('mapping_saved', 'paygw_digistore24'));
    }

    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('mapping_pagetitle', 'paygw_digistore24'));
    $form->display();
    echo $OUTPUT->footer();
    exit;
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('mapping_pagetitle', 'paygw_digistore24'));
echo html_writer::tag('p', get_string('mapping_intro', 'paygw_digistore24'));

echo html_writer::link(new moodle_url($baseurl, ['action' => 'add']),
    get_string('mapping_addnew', 'paygw_digistore24'),
    ['class' => 'btn btn-primary mb-3']);

$rows = $DB->get_records('paygw_digistore24_itemmap', null, 'component, paymentarea, itemid');

$table = new html_table();
$table->head = [
    get_string('mapping_component', 'paygw_digistore24'),
    get_string('mapping_paymentarea', 'paygw_digistore24'),
    get_string('mapping_itemid', 'paygw_digistore24'),
    get_string('mapping_productid', 'paygw_digistore24'),
    get_string('mapping_actions', 'paygw_digistore24'),
];
foreach ($rows as $row) {
    $editurl = new moodle_url($baseurl, ['action' => 'edit', 'id' => $row->id]);
    $delurl  = new moodle_url($baseurl, ['action' => 'delete', 'id' => $row->id, 'sesskey' => sesskey()]);
    $actions = html_writer::link($editurl, get_string('edit'))
        . ' &middot; '
        . html_writer::link($delurl, get_string('delete'),
            ['data-confirmation' => 'modal',
             'data-confirmation-title-str' => json_encode(['delete', 'core']),
             'data-confirmation-content-str' => json_encode(['mapping_delete_confirm', 'paygw_digistore24'])]);
    $table->data[] = [s($row->component), s($row->paymentarea), s($row->itemid), s($row->product_id), $actions];
}

echo html_writer::table($table);
echo $OUTPUT->footer();
