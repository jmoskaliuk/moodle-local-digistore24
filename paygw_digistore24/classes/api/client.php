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

use paygw_digistore24\helper;

defined('MOODLE_INTERNAL') || die();

/**
 * Thin HTTP client for the Digistore24 JSON API.
 *
 * Endpoint: POST https://www.digistore24.com/api/call/{APIKEY}/{FORMAT}/{FUNCTION}
 * Auth: header X-DS-API-KEY (preferred over the URL-embedded key).
 */
class client {

    public const BASE_URL = 'https://www.digistore24.com/api/call';

    /** @var string */
    private $apikey;

    public function __construct(?string $apikey = null) {
        $this->apikey = $apikey ?? (string)get_config('paygw_digistore24', 'apikey');
        if ($this->apikey === '') {
            throw new \moodle_exception('apikey', 'paygw_digistore24');
        }
    }

    /**
     * Call a Digistore24 API function.
     *
     * @param string $function API function name (e.g. createBuyUrl)
     * @param array  $params   Function parameters as associative array
     * @return array decoded JSON response
     * @throws \moodle_exception
     */
    public function call(string $function, array $params): array {
        $url = self::BASE_URL . '/' . urlencode($this->apikey) . '/json/' . urlencode($function);

        $body = json_encode($params, JSON_UNESCAPED_SLASHES);

        helper::log('api_request', 'POST ' . $function, ['function' => $function, 'params' => $params]);

        $curl = new \curl();
        $curl->setHeader([
            'Content-Type: application/json',
            'Accept: application/json',
            'X-DS-API-KEY: ' . $this->apikey,
        ]);
        $curl->setopt([
            'CURLOPT_TIMEOUT'        => 20,
            'CURLOPT_CONNECTTIMEOUT' => 10,
            'CURLOPT_RETURNTRANSFER' => true,
        ]);

        $raw = $curl->post($url, $body);
        $httpcode = (int)$curl->get_info()['http_code'];
        $errno = $curl->get_errno();

        if ($errno) {
            helper::log('error', "Digistore24 transport error #$errno", ['function' => $function]);
            throw new \moodle_exception('internalerror', 'paygw_digistore24');
        }

        $decoded = json_decode((string)$raw, true);
        if (!is_array($decoded)) {
            helper::log('error', 'Digistore24 returned invalid JSON', ['function' => $function, 'http' => $httpcode]);
            throw new \moodle_exception('internalerror', 'paygw_digistore24');
        }

        helper::log('api_response', $function . ' -> ' . $httpcode, ['response' => $decoded]);

        if ($httpcode < 200 || $httpcode >= 300 || (isset($decoded['result']) && $decoded['result'] === 'error')) {
            $msg = $decoded['message'] ?? ('HTTP ' . $httpcode);
            throw new \moodle_exception('redirect_failed', 'paygw_digistore24', '', $msg);
        }

        return $decoded;
    }
}
