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

namespace paygw_digistore24\task;

defined('MOODLE_INTERNAL') || die();

use paygw_digistore24\helper;
use paygw_digistore24\reversal;

/**
 * Subscription end-of-life enforcement.
 *
 * For each delivered Digistore24 transaction that:
 *   - is the most recent for its payment,
 *   - has cancelled = 1 (cancellation IPN received) AND no fresher delivery,
 *   - period_end is set,
 *   - period_end + grace_period_days < now,
 *   - has not yet been processed,
 * run the reversal bridge so the user loses access.
 */
class check_subscription_endings extends \core\task\scheduled_task {

    public function get_name(): string {
        return get_string('task_check_subscription_endings', 'paygw_digistore24');
    }

    public function execute() {
        global $DB;

        $graceseconds = ((int)get_config('paygw_digistore24', 'grace_period_days')) * DAYSECS;
        $cutoff = time() - $graceseconds;

        $sql = "SELECT t.*
                  FROM {paygw_digistore24_txn} t
                 WHERE t.status = :delivered
                   AND t.cancelled = 1
                   AND t.grace_processed = 0
                   AND t.period_end IS NOT NULL
                   AND t.period_end < :cutoff";
        $rows = $DB->get_records_sql($sql, [
            'delivered' => helper::STATUS_DELIVERED,
            'cutoff'    => $cutoff,
        ]);

        foreach ($rows as $row) {
            // Skip if a newer delivery for the same payment exists
            // (i.e. the user resubscribed before the grace period ran out).
            $newer = $DB->record_exists_select('paygw_digistore24_txn',
                'paymentid = :pid AND status = :delivered AND id > :id',
                ['pid' => $row->paymentid, 'delivered' => helper::STATUS_DELIVERED, 'id' => $row->id]);
            if ($newer) {
                $row->grace_processed = 1;
                $row->timemodified = time();
                $DB->update_record('paygw_digistore24_txn', $row);
                continue;
            }

            $payment = $DB->get_record('payments', ['id' => $row->paymentid]);
            if (!$payment) {
                continue;
            }

            try {
                reversal::run($payment, 'subscription_grace_expired');
            } catch (\Throwable $e) {
                helper::log('error', 'Grace expiry reversal failed: ' . $e->getMessage(), null, (int)$payment->id);
                continue;
            }

            $row->status = helper::STATUS_REVERSED;
            $row->grace_processed = 1;
            $row->timemodified = time();
            $DB->update_record('paygw_digistore24_txn', $row);
        }
    }
}
