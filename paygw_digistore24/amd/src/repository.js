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

/**
 * Webservice helper for paygw_digistore24.
 *
 * @module     paygw_digistore24/repository
 */

import Ajax from 'core/ajax';

export const getConfig = (component, paymentArea, itemId) => Ajax.call([{
    methodname: 'paygw_digistore24_get_config_for_js',
    args: {component, paymentarea: paymentArea, itemid: itemId},
}])[0];

export const startCheckout = (component, paymentArea, itemId) => Ajax.call([{
    methodname: 'paygw_digistore24_start_checkout',
    args: {component, paymentarea: paymentArea, itemid: itemId},
}])[0];
