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

class get_config_for_js extends external_api {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'component'   => new external_value(PARAM_COMPONENT, 'Component'),
            'paymentarea' => new external_value(PARAM_AREA, 'Payment area'),
            'itemid'      => new external_value(PARAM_INT, 'Item id'),
        ]);
    }

    public static function execute(string $component, string $paymentarea, int $itemid): array {
        self::validate_parameters(self::execute_parameters(), [
            'component' => $component, 'paymentarea' => $paymentarea, 'itemid' => $itemid,
        ]);

        $config = payment_helper::get_gateway_configuration($component, $paymentarea, $itemid, 'digistore24');
        $payable = payment_helper::get_payable($component, $paymentarea, $itemid);
        $surcharge = payment_helper::get_gateway_surcharge('digistore24');

        return [
            'brandname' => $config['brandname'] ?? '',
            'cost'      => payment_helper::get_rounded_cost($payable->get_amount(), $payable->get_currency(), $surcharge),
            'currency'  => $payable->get_currency(),
        ];
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'brandname' => new external_value(PARAM_TEXT, 'Brand name'),
            'cost'      => new external_value(PARAM_FLOAT, 'Cost with surcharge'),
            'currency'  => new external_value(PARAM_TEXT, 'Currency'),
        ]);
    }
}
