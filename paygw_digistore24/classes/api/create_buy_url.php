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

namespace paygw_digistore24\api;

defined('MOODLE_INTERNAL') || die();

/**
 * Builds and dispatches a Digistore24 createBuyUrl call.
 *
 * Digistore24 createBuyUrl signature (from API reference):
 *   createBuyUrl(product_id, buyer, payment_plan, tracking, valid_until,
 *                urls, placeholders, settings, addons)
 *
 * Per-parameter shape is inferred from the public docs; verify on first
 * sandbox run (see Open questions in 03-dev-doc.md).
 */
class create_buy_url {

    public static function execute(
        string $productid,
        \stdClass $buyer,
        ?array $paymentplan,
        int $paymentid,
        \moodle_url $thankyouurl,
        \moodle_url $notificationurl
    ): string {

        $params = [
            'product_id' => $productid,
            'buyer' => [
                'email'      => $buyer->email,
                'first_name' => $buyer->firstname,
                'last_name'  => $buyer->lastname,
                'country'    => $buyer->country,
            ],
            'payment_plan' => $paymentplan,
            'tracking' => [
                'custom' => (string)$paymentid,
            ],
            'valid_until' => '24h',
            'urls' => [
                'thankyou_url'     => $thankyouurl->out(false),
                'notification_url' => $notificationurl->out(false),
            ],
            'settings' => [
                // Make pre-filled buyer fields read-only at checkout.
                'buyer_readonly' => ['email', 'first_name', 'last_name', 'country'],
            ],
        ];

        if ($paymentplan === null) {
            unset($params['payment_plan']);
        }

        $client = new client();
        $response = $client->call('createBuyUrl', $params);

        $url = $response['url'] ?? ($response['data']['url'] ?? null);
        if (!is_string($url) || $url === '') {
            throw new \moodle_exception('internalerror', 'paygw_digistore24');
        }
        return $url;
    }
}
