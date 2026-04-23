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
 * IPN signature validation per the Digistore24 IPN spec.
 *
 * Algorithm (per the public Digistore24 IPN PHP receiver script):
 *   1. Take the POST array.
 *   2. Drop the 'sha_sign' field.
 *   3. Sort remaining keys alphabetically (case-insensitive).
 *   4. Concatenate values separated by SHA passphrase.
 *   5. Append the SHA passphrase at the end.
 *   6. Hash the resulting string with SHA-512, uppercase the hex digest.
 *   7. Compare with $_POST['sha_sign'].
 *
 * NOTE: The exact concatenation rule is the standard Digistore24 IPN scheme;
 * verify against the latest ipn_receiver.php (see Open questions in
 * 03-dev-doc.md) on first sandbox run.
 */
class ipn_signature {

    public static function compute(array $post, string $passphrase): string {
        $data = $post;
        unset($data['sha_sign']);

        // Case-insensitive key sort.
        uksort($data, 'strcasecmp');

        $parts = [];
        foreach ($data as $value) {
            if (is_array($value)) {
                $value = json_encode($value, JSON_UNESCAPED_SLASHES);
            }
            $parts[] = (string)$value;
        }
        $parts[] = $passphrase;
        $blob = implode($passphrase, $parts);

        return strtoupper(hash('sha512', $blob));
    }

    public static function validate(array $post, string $passphrase): bool {
        if (empty($post['sha_sign']) || $passphrase === '') {
            return false;
        }
        $expected = self::compute($post, $passphrase);
        return hash_equals($expected, strtoupper((string)$post['sha_sign']));
    }
}
