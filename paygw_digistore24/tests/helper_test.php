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

/**
 * @covers \paygw_digistore24\helper
 */
final class helper_test extends \advanced_testcase {

    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_resolve_product_id_uses_override_when_present(): void {
        global $DB;
        $DB->insert_record('paygw_digistore24_itemmap', (object)[
            'component' => 'enrol_fee', 'paymentarea' => 'fee', 'itemid' => 99,
            'product_id' => 'P-OVERRIDE', 'timemodified' => time(),
        ]);
        set_config('default_product_id', 'P-DEFAULT', 'paygw_digistore24');

        $this->assertSame('P-OVERRIDE', helper::resolve_product_id('enrol_fee', 'fee', 99));
    }

    public function test_resolve_product_id_falls_back_to_default(): void {
        set_config('default_product_id', 'P-DEFAULT', 'paygw_digistore24');
        $this->assertSame('P-DEFAULT', helper::resolve_product_id('enrol_fee', 'fee', 1));
    }

    public function test_resolve_product_id_throws_when_nothing_configured(): void {
        set_config('default_product_id', '', 'paygw_digistore24');
        $this->expectException(\moodle_exception::class);
        helper::resolve_product_id('enrol_fee', 'fee', 1);
    }

    public function test_redact_masks_known_secret_keys(): void {
        $payload = [
            'apikey' => 'SECRET',
            'sha_sign' => 'ABCDEF',
            'nested' => ['ipn_passphrase' => 'p', 'safe' => 'ok'],
            'safe' => 'visible',
        ];
        $r = helper::redact($payload);
        $this->assertSame('[redacted]', $r['apikey']);
        $this->assertSame('[redacted]', $r['sha_sign']);
        $this->assertSame('[redacted]', $r['nested']['ipn_passphrase']);
        $this->assertSame('ok', $r['nested']['safe']);
        $this->assertSame('visible', $r['safe']);
    }
}
