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

use paygw_digistore24\api\ipn_signature;

/**
 * @covers \paygw_digistore24\api\ipn_signature
 */
final class ipn_signature_test extends \advanced_testcase {

    public function test_compute_is_deterministic_and_uppercase(): void {
        $post = ['custom' => '42', 'amount' => '19.90', 'currency' => 'EUR'];
        $sig = ipn_signature::compute($post, 'topsecret');
        $this->assertSame(strtoupper($sig), $sig);
        $this->assertSame($sig, ipn_signature::compute($post, 'topsecret'));
    }

    public function test_compute_excludes_sha_sign_field(): void {
        $a = ['custom' => '42', 'amount' => '19.90'];
        $b = $a + ['sha_sign' => 'WHATEVER'];
        $this->assertSame(
            ipn_signature::compute($a, 'pp'),
            ipn_signature::compute($b, 'pp')
        );
    }

    public function test_compute_is_case_insensitive_keys(): void {
        $a = ['Amount' => '5', 'Custom' => '7'];
        $b = ['amount' => '5', 'custom' => '7'];
        $this->assertSame(
            ipn_signature::compute($a, 'pp'),
            ipn_signature::compute($b, 'pp')
        );
    }

    public function test_validate_accepts_correct_signature(): void {
        $post = ['custom' => '42', 'amount' => '19.90', 'currency' => 'EUR'];
        $passphrase = 'topsecret';
        $post['sha_sign'] = ipn_signature::compute($post, $passphrase);
        $this->assertTrue(ipn_signature::validate($post, $passphrase));
    }

    public function test_validate_rejects_tampered_signature(): void {
        $post = ['custom' => '42', 'amount' => '19.90'];
        $passphrase = 'topsecret';
        $post['sha_sign'] = ipn_signature::compute($post, $passphrase);
        $post['amount'] = '0.01'; // Tamper after signing.
        $this->assertFalse(ipn_signature::validate($post, $passphrase));
    }

    public function test_validate_rejects_missing_signature(): void {
        $this->assertFalse(ipn_signature::validate(['custom' => '1'], 'pp'));
    }

    public function test_validate_rejects_empty_passphrase(): void {
        $post = ['custom' => '1', 'sha_sign' => 'X'];
        $this->assertFalse(ipn_signature::validate($post, ''));
    }
}
