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

class gateway extends \core_payment\gateway {

    public static function get_supported_currencies(): array {
        // Permissive set; Digistore24 is the merchant of record and rejects
        // anything it does not actually support when createBuyUrl runs.
        return [
            'AUD', 'BRL', 'CAD', 'CHF', 'CZK', 'DKK', 'EUR', 'GBP', 'HKD', 'HUF',
            'ILS', 'INR', 'JPY', 'MXN', 'MYR', 'NOK', 'NZD', 'PHP', 'PLN', 'RUB',
            'SEK', 'SGD', 'THB', 'TRY', 'TWD', 'USD',
        ];
    }

    public static function add_configuration_to_gateway_form(\core_payment\form\account_gateway $form): void {
        $mform = $form->get_mform();

        $mform->addElement('text', 'brandname', get_string('brandname', 'paygw_digistore24'));
        $mform->setType('brandname', PARAM_TEXT);
        $mform->addHelpButton('brandname', 'brandname', 'paygw_digistore24');
    }

    public static function validate_gateway_form(\core_payment\form\account_gateway $form,
            \stdClass $data, array $files, array &$errors): void {
        if ($data->enabled) {
            $apikey = get_config('paygw_digistore24', 'apikey');
            $passphrase = get_config('paygw_digistore24', 'ipn_passphrase');
            $defaultproduct = get_config('paygw_digistore24', 'default_product_id');
            if (empty($apikey) || empty($passphrase) || empty($defaultproduct)) {
                $errors['enabled'] = get_string('gatewaycannotbeenabled', 'paygw_digistore24');
            }
        }
    }
}
