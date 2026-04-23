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

$functions = [
    'paygw_digistore24_get_config_for_js' => [
        'classname'   => 'paygw_digistore24\external\get_config_for_js',
        'classpath'   => '',
        'description' => 'Returns gateway display configuration needed by the payment modal.',
        'type'        => 'read',
        'ajax'        => true,
    ],
    'paygw_digistore24_start_checkout' => [
        'classname'   => 'paygw_digistore24\external\start_checkout',
        'classpath'   => '',
        'description' => 'Creates a pending Moodle payment and a Digistore24 buy URL; returns the URL to redirect to.',
        'type'        => 'write',
        'ajax'        => true,
    ],
];
