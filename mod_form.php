<?php
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/course/moodleform_mod.php');

class mod_doc2interact_mod_form extends moodleform_mod {
    public function definition() {
        $mform = $this->_form;
        $mform->addElement('text', 'name', get_string('activityname', 'core'), ['size' => 64]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $this->standard_intro_elements();
        $this->standard_coursemodule_elements();
        // Oculto por defecto — no es necesario que los estudiantes lo vean
        $mform->setDefault('visible', 0);
        $this->add_action_buttons();
    }
}
