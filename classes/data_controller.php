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

namespace customfield_apsolu_skill;

/**
 * Base class for custom fields data controllers
 *
 * This class is a wrapper around the persistent data class that allows to define
 * how the element behaves in the instance edit forms.
 *
 * Contrôle le formulaire d'édition des données d'une instance de champ personnalisé.
 * Enregistre un entier représentant des secondes. À l'affichage, la valeur est formatée en HH:MM.
 *
 * @package   customfield_apsolu_skill
 * @copyright 2026 Université Rennes 2
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class data_controller extends \core_customfield\data_controller {
    /**
     * Return the name of the field in the db table {customfield_data} where the data is stored
     *
     * Must be one of the following:
     *   intvalue - can store integer values, this field is indexed
     *   decvalue - can store decimal values
     *   shortcharvalue - can store character values up to 255 characters long, this field is indexed
     *   charvalue - can store character values up to 1333 characters long, this field is not indexed but
     *     full text search is faster than on field 'value'
     *   value - can store character values of unlimited length ("text" field in the db)
     *
     * @return string
     */
    public function datafield(): string {
        return 'intvalue';
    }

    /**
     * Add a field to the instance edit form.
     *
     * @param \MoodleQuickForm $mform
     */
    public function instance_form_definition(\MoodleQuickForm $mform) {
        $field = $this->get_field();
        $config = $field->get('configdata');

        $options = $this->get_field()->get_options();

        $elementname = $this->get_form_element_name();
        $mform->addElement('select', $elementname, $this->get_field()->get_formatted_name(), $options);

        $defaultvalue = $config['defaultvalue'];
        if (isset($options[$defaultvalue]) === true) {
            $mform->setDefault($elementname, $defaultvalue);
        }

        if ($field->get_configdata_property('required')) {
            $mform->addRule($elementname, null, 'required', null, 'client');
        }
    }

    /**
     * Validates data for this field.
     *
     * Called from instance edit form in validation()
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function instance_form_validation(array $data, array $files): array {
        $errors = parent::instance_form_validation($data, $files);

        if ($this->get_field()->get_configdata_property('required')) {
            // Standard required rule does not work on select element.
            $elementname = $this->get_form_element_name();
            if (empty($data[$elementname]) === true) {
                $errors[$elementname] = get_string('err_required', 'form');
            }
        }

        return $errors;
    }

    /**
     * Returns the default value as it would be stored in the database (not in human-readable format).
     *
     * @return mixed
     */
    public function get_default_value() {
        $defaultvalue = $this->get_field()->get_configdata_property('defaultvalue');

        $key = array_search($defaultvalue, $this->get_field()->get_options());
        if ($key !== false) {
            return $key;
        }

        return 0;
    }

    /**
     * Returns the value as it is stored in the database or default value if data record is not present
     *
     * @return mixed
     */
    public function get_value() {
        return $this->get($this->datafield());
    }

    /**
     * Returns value in a human-readable format
     *
     * @return mixed|null value or null if empty
     */
    public function export_value() {
        $value = $this->get_value();

        if ($this->is_empty($value)) {
            return null;
        }

        $options = $this->get_field()->get_options();
        if (array_key_exists($value, $options)) {
            return format_string(
                $options[$value],
                true,
                ['context' => $this->get_field()->get_handler()->get_configuration_context()]
            );
        }

        return null;
    }

    /**
     * Compare l'objet courant avec un autre objet.
     *
     * @param \core_customfield\data_controller $otherobject Un objet a comparé avec l'objet courant.
     *
     * @return int Retourne 0 si les 2 objets sont identiques,
     *             un entier négatif si l'objet courant est inférieur à l'autre objet ou
     *             un entier positif si l'objet courant est supérieur à l'autre objet.
     */
    public function compare_with(\core_customfield\data_controller $otherobject): int {
        $value1 = $this->export_value() ?? '';
        $value2 = $otherobject->export_value() ?? '';

        return strcoll($value1, $value2);
    }
}
