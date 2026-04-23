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

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_configpasswordunmask(
        'paygw_digistore24/apikey',
        get_string('apikey', 'paygw_digistore24'),
        get_string('apikey_desc', 'paygw_digistore24'),
        ''));

    $settings->add(new admin_setting_configpasswordunmask(
        'paygw_digistore24/ipn_passphrase',
        get_string('ipn_passphrase', 'paygw_digistore24'),
        get_string('ipn_passphrase_desc', 'paygw_digistore24'),
        ''));

    $settings->add(new admin_setting_configtext(
        'paygw_digistore24/default_product_id',
        get_string('default_product_id', 'paygw_digistore24'),
        get_string('default_product_id_desc', 'paygw_digistore24'),
        '',
        PARAM_ALPHANUMEXT));

    $settings->add(new admin_setting_configtext(
        'paygw_digistore24/grace_period_days',
        get_string('grace_period_days', 'paygw_digistore24'),
        get_string('grace_period_days_desc', 'paygw_digistore24'),
        3,
        PARAM_INT));

    $settings->add(new admin_setting_configcheckbox(
        'paygw_digistore24/test_mode',
        get_string('test_mode', 'paygw_digistore24'),
        get_string('test_mode_desc', 'paygw_digistore24'),
        0));

    $settings->add(new admin_setting_heading(
        'paygw_digistore24/links_heading',
        get_string('links_heading', 'paygw_digistore24'),
        get_string('links_desc', 'paygw_digistore24',
            (object)[
                'callback' => (new moodle_url('/payment/gateway/digistore24/callback.php'))->out(false),
                'mapping' => (new moodle_url('/payment/gateway/digistore24/mapping.php'))->out(false),
                'report' => (new moodle_url('/payment/gateway/digistore24/report.php'))->out(false),
            ])));

    \core_payment\helper::add_common_gateway_settings($settings, 'paygw_digistore24');
}

// External admin pages, gated by the plugin capability.
$ADMIN->add('paymentgateways', new admin_externalpage(
    'paygw_digistore24_mapping',
    get_string('mapping_pagetitle', 'paygw_digistore24'),
    new moodle_url('/payment/gateway/digistore24/mapping.php'),
    'paygw/digistore24:managemapping'));

$ADMIN->add('paymentgateways', new admin_externalpage(
    'paygw_digistore24_report',
    get_string('report_pagetitle', 'paygw_digistore24'),
    new moodle_url('/payment/gateway/digistore24/report.php'),
    'paygw/digistore24:viewreport'));
