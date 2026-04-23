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

namespace paygw_digistore24\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\contextlist;

class provider implements
        \core_privacy\local\metadata\provider,
        \core_payment\privacy\paygw_provider {

    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('paygw_digistore24_txn', [
            'paymentid'      => 'privacy:metadata:paygw_digistore24_txn:paymentid',
            'transaction_id' => 'privacy:metadata:paygw_digistore24_txn:transaction_id',
            'order_id'       => 'privacy:metadata:paygw_digistore24_txn:order_id',
            'status'         => 'privacy:metadata:paygw_digistore24_txn:status',
            'timecreated'    => 'privacy:metadata:paygw_digistore24_txn:timecreated',
        ], 'privacy:metadata:paygw_digistore24_txn');

        $collection->add_external_location_link('digistore24', [
            'email'      => 'privacy:metadata:digistore24:email',
            'firstname'  => 'privacy:metadata:digistore24:firstname',
            'lastname'   => 'privacy:metadata:digistore24:lastname',
            'country'    => 'privacy:metadata:digistore24:country',
            'amount'     => 'privacy:metadata:digistore24:amount',
            'currency'   => 'privacy:metadata:digistore24:currency',
            'product_id' => 'privacy:metadata:digistore24:product_id',
        ], 'privacy:metadata:digistore24');

        return $collection;
    }

    public static function export_payment_data(\context $context, array $subcontext, \stdClass $payment) {
        global $DB;

        $record = $DB->get_record('paygw_digistore24_txn', ['paymentid' => $payment->id]);
        if (!$record) {
            return;
        }

        $data = (object)[
            'transaction_id' => $record->transaction_id,
            'order_id'       => $record->order_id,
            'status'         => $record->status,
            'timecreated'    => \core_privacy\local\request\transform::datetime($record->timecreated),
        ];

        \core_privacy\local\request\writer::with_context($context)
            ->export_data(array_merge($subcontext, [get_string('pluginname', 'paygw_digistore24')]), $data);
    }

    public static function delete_data_for_payment_sql(string $paymentidsubsql, array $paymentidsubparams) {
        global $DB;
        $DB->delete_records_select('paygw_digistore24_txn', "paymentid IN ($paymentidsubsql)", $paymentidsubparams);
    }
}
