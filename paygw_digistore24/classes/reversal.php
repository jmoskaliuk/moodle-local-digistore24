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

namespace paygw_digistore24;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/enrollib.php');

use paygw_digistore24\event\payment_reversed;

/**
 * Reversal bridge: core_payment in Moodle 5.2 has no generic refund API,
 * so the plugin owns the reversal logic.
 *
 * - For component = 'enrol_fee', the user is unenrolled from the course.
 * - For any other component, the payment_reversed event is fired so
 *   custom plugins / observers can apply their own reversal.
 *
 * The event is fired in all cases (even after the enrol_fee bridge ran)
 * so observers see every reversal.
 */
class reversal {

    public static function run(\stdClass $payment, string $reason = 'refund'): void {
        global $DB;

        if ($payment->component === 'enrol_fee') {
            $instance = $DB->get_record('enrol', ['enrol' => 'fee', 'id' => $payment->itemid]);
            if ($instance) {
                $plugin = enrol_get_plugin('fee');
                if ($plugin) {
                    $plugin->unenrol_user($instance, (int)$payment->userid);
                    helper::log('reversal', "enrol_fee unenrolled user {$payment->userid} from instance {$instance->id}",
                        null, (int)$payment->id);
                }
            } else {
                helper::log('reversal', 'enrol_fee instance not found; firing event only', null, (int)$payment->id);
            }
        }

        payment_reversed::create_from_payment($payment, $reason)->trigger();
    }
}
