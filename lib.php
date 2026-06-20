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

// Servir archivos HTML generados (contenido + autoevaluación)
function doc2interact_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    if ($context->contextlevel != CONTEXT_MODULE) return false;
    require_login($course, true, $cm);
    if ($filearea !== 'content') return false;

    $itemid = array_shift($args);
    $filename = array_pop($args);
    $filepath = $args ? '/' . implode('/', $args) . '/' : '/';

    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'mod_doc2interact', $filearea, $itemid, $filepath, $filename);
    if (!$file || $file->is_directory()) return false;

    // Servir como HTML sin forzar descarga
    send_stored_file($file, 0, 0, false, $options);
}
