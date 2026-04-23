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

class helper {

    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_REVERSED  = 'reversed';
    public const STATUS_FAILED    = 'failed';

    public static function resolve_product_id(string $component, string $paymentarea, int $itemid): string {
        global $DB;

        $override = $DB->get_field('paygw_digistore24_itemmap', 'product_id',
            ['component' => $component, 'paymentarea' => $paymentarea, 'itemid' => $itemid]);
        if ($override !== false && $override !== '') {
            return $override;
        }

        $default = trim((string)get_config('paygw_digistore24', 'default_product_id'));
        if ($default === '') {
            throw new \moodle_exception('no_product_mapping', 'paygw_digistore24');
        }
        return $default;
    }

    public static function digistore24_enabled_anywhere(): bool {
        global $DB;
        return $DB->record_exists('payment_gateways', ['gateway' => 'digistore24', 'enabled' => 1]);
    }

    public static function log(string $kind, string $message, ?array $payload = null, ?int $paymentid = null): void {
        global $DB;
        $DB->insert_record('paygw_digistore24_log', (object)[
            'kind'        => $kind,
            'paymentid'   => $paymentid,
            'message'     => \core_text::substr($message, 0, 255),
            'payload'     => $payload === null ? null : json_encode(self::redact($payload), JSON_UNESCAPED_SLASHES),
            'timecreated' => time(),
        ]);
    }

    public static function redact(array $payload): array {
        $secrets = ['apikey', 'api_key', 'x-ds-api-key', 'sha_sign', 'signature', 'ipn_passphrase', 'passphrase'];
        $out = [];
        foreach ($payload as $k => $v) {
            if (in_array(strtolower((string)$k), $secrets, true)) {
                $out[$k] = '[redacted]';
            } else if (is_array($v)) {
                $out[$k] = self::redact($v);
            } else {
                $out[$k] = $v;
            }
        }
        return $out;
    }

    public static function get_payment_record(int $paymentid): ?\stdClass {
        global $DB;
        $rec = $DB->get_record('payments', ['id' => $paymentid]);
        return $rec ?: null;
    }
}
