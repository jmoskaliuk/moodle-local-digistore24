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
 * Digistore24 gateway entry point for the core_payment modal.
 *
 * @module     paygw_digistore24/gateways_modal
 */

import * as Repository from './repository';
import Modal from 'core/modal';
import {getString} from 'core/str';

const showRedirectModal = async() => Modal.create({
    body: await getString('redirecting', 'paygw_digistore24'),
    show: true,
    removeOnClose: true,
});

/**
 * Start the Digistore24 checkout: open a "Redirecting..." modal, ask the
 * Moodle backend to create a Digistore24 buy URL, then redirect the
 * browser. Resolves with the success message on the way back.
 *
 * @param {string} component
 * @param {string} paymentArea
 * @param {number} itemId
 * @returns {Promise<string>}
 */
export const process = async(component, paymentArea, itemId) => {
    const modal = await showRedirectModal();

    try {
        const result = await Repository.startCheckout(component, paymentArea, itemId);
        if (!result || !result.redirecturl) {
            throw new Error('No redirect URL returned');
        }
        // Replace the current page with the Digistore24 checkout URL.
        window.location = result.redirecturl;
        // Keep the modal up; the page is unloading.
        return Promise.resolve('');
    } catch (error) {
        modal.hide();
        const msg = (error && error.message) ? error.message : String(error);
        const failed = await getString('redirect_failed', 'paygw_digistore24', msg);
        return Promise.reject(failed);
    }
};
