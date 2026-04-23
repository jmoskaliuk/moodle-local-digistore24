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

declare(strict_types=1);

namespace paygw_digistore24\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use core_payment\helper as payment_helper;
use paygw_digistore24\api\create_buy_url;
use paygw_digistore24\helper;

class start_checkout extends external_api {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'component'   => new external_value(PARAM_COMPONENT, 'Component'),
            'paymentarea' => new external_value(PARAM_AREA, 'Payment area'),
            'itemid'      => new external_value(PARAM_INT, 'Item id'),
        ]);
    }

    public static function execute(string $component, string $paymentarea, int $itemid): array {
        global $USER;

        self::validate_parameters(self::execute_parameters(), [
            'component' => $component, 'paymentarea' => $paymentarea, 'itemid' => $itemid,
        ]);

        $payable = payment_helper::get_payable($component, $paymentarea, $itemid);
        $surcharge = payment_helper::get_gateway_surcharge('digistore24');
        $amount = payment_helper::get_rounded_cost($payable->get_amount(), $payable->get_currency(), $surcharge);
        $currency = $payable->get_currency();

        $productid = helper::resolve_product_id($component, $paymentarea, $itemid);

        // Persist the pending payment first so we have an id to embed in custom.
        $paymentid = payment_helper::save_payment(
            $payable->get_account_id(), $component, $paymentarea, $itemid,
            (int)$USER->id, (float)$amount, $currency, 'digistore24'
        );

        // Pre-fill buyer from Moodle user.
        $buyer = (object)[
            'email'     => (string)$USER->email,
            'firstname' => (string)$USER->firstname,
            'lastname'  => (string)$USER->lastname,
            'country'   => (string)($USER->country ?: ''),
        ];

        // payment_plan handling is intentionally not derived from the payable item itself
        // because core_payment has no subscription concept (see Open questions in 03-dev-doc.md).
        // The caller is responsible for setting it up via component-specific logic.
        $paymentplan = null;

        $thankyou = new \moodle_url('/payment/gateway/digistore24/return.php', ['paymentid' => $paymentid]);
        $callback = new \moodle_url('/payment/gateway/digistore24/callback.php');

        try {
            $url = create_buy_url::execute($productid, $buyer, $paymentplan, $paymentid, $thankyou, $callback);
        } catch (\moodle_exception $e) {
            helper::log('error', 'createBuyUrl failed: ' . $e->getMessage(), null, $paymentid);
            throw $e;
        }

        return ['redirecturl' => $url, 'paymentid' => $paymentid];
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'redirecturl' => new external_value(PARAM_URL, 'Digistore24 checkout URL to redirect to'),
            'paymentid'   => new external_value(PARAM_INT, 'Moodle payment id'),
        ]);
    }
}
