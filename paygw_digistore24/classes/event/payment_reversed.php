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

namespace paygw_digistore24\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Fired when a Digistore24 payment has been reversed (refund / chargeback /
 * end of subscription past grace period). Carries the Moodle payable
 * (component / paymentarea / itemid / paymentid / userid) so observers can
 * apply component-specific reversal logic.
 *
 * Stable extension point: third-party / custom components subscribe to this
 * event to undo whatever they delivered for the original payment.
 */
class payment_reversed extends \core\event\base {

    protected function init() {
        $this->data['crud'] = 'd';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        // No fixed objecttable: the reversed thing depends on the component
        // that owned the original payment.
        $this->data['objecttable'] = 'payments';
    }

    public static function get_name() {
        return get_string('event_payment_reversed', 'paygw_digistore24');
    }

    public function get_description() {
        return "Digistore24 payment reversed: payment id {$this->objectid}, "
            . "component {$this->other['component']}, paymentarea {$this->other['paymentarea']}, "
            . "itemid {$this->other['itemid']}.";
    }

    public static function create_from_payment(\stdClass $payment, string $reason): self {
        return self::create([
            'objectid' => (int)$payment->id,
            'userid'   => (int)$payment->userid,
            'context'  => \context_system::instance(),
            'other'    => [
                'component'   => (string)$payment->component,
                'paymentarea' => (string)$payment->paymentarea,
                'itemid'      => (int)$payment->itemid,
                'reason'      => $reason,
            ],
        ]);
    }
}
