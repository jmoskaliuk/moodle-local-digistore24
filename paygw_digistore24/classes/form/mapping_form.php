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

namespace paygw_digistore24\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

class mapping_form extends \moodleform {

    protected function definition() {
        $mform = $this->_form;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('text', 'component', get_string('mapping_component', 'paygw_digistore24'),
            ['size' => 40]);
        $mform->setType('component', PARAM_COMPONENT);
        $mform->addRule('component', null, 'required', null, 'client');

        $mform->addElement('text', 'paymentarea', get_string('mapping_paymentarea', 'paygw_digistore24'),
            ['size' => 40]);
        $mform->setType('paymentarea', PARAM_AREA);
        $mform->addRule('paymentarea', null, 'required', null, 'client');

        $mform->addElement('text', 'itemid', get_string('mapping_itemid', 'paygw_digistore24'),
            ['size' => 12]);
        $mform->setType('itemid', PARAM_INT);
        $mform->addRule('itemid', null, 'required', null, 'client');

        $mform->addElement('text', 'product_id', get_string('mapping_productid', 'paygw_digistore24'),
            ['size' => 40]);
        $mform->setType('product_id', PARAM_ALPHANUMEXT);
        $mform->addRule('product_id', null, 'required', null, 'client');

        $this->add_action_buttons();
    }

    public function validation($data, $files) {
        global $DB;
        $errors = parent::validation($data, $files);

        $existing = $DB->get_record('paygw_digistore24_itemmap', [
            'component'   => $data['component'],
            'paymentarea' => $data['paymentarea'],
            'itemid'      => $data['itemid'],
        ]);
        if ($existing && (int)$existing->id !== (int)$data['id']) {
            $errors['itemid'] = get_string('error');
        }

        return $errors;
    }
}
