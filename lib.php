<?php
defined('MOODLE_INTERNAL') || die();

function doc2interact_add_instance($data) {
    global $DB;
    $data->timemodified = time();
    $data->timecreated  = time();
    return $DB->insert_record('doc2interact', $data);
}

function doc2interact_update_instance($data) {
    global $DB;
    $data->timemodified = time();
    $data->id = $data->instance;
    return $DB->update_record('doc2interact', $data);
}

function doc2interact_delete_instance($id) {
    global $DB;
    $DB->delete_records('doc2interact', ['id' => $id]);
    return true;
}

function doc2interact_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_INTRO:         return true;
        case FEATURE_BACKUP_MOODLE2:    return false;
        default:                        return null;
    }
}
