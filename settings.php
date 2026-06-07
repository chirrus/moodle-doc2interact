<?php
defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {

    $settings->add(new admin_setting_configtext(
        'mod_doc2interact/apiurl',
        get_string('apiurl', 'mod_doc2interact'),
        get_string('apiurl_desc', 'mod_doc2interact'),
        'https://doc2interact.com',
        PARAM_URL
    ));

    $settings->add(new admin_setting_configtext(
        'mod_doc2interact/apikey',
        get_string('apikey', 'mod_doc2interact'),
        get_string('apikey_desc', 'mod_doc2interact'),
        'Moodle2026#!', // Clave por defecto — configurar en MongoDB
        PARAM_RAW
    ));

}
