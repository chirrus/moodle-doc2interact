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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

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

// Serve generated HTML files (content + self-assessment)
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

/**
 * Mark the activity viewed and trigger the course_module_viewed event.
 *
 * @param stdClass $doc2interact doc2interact instance
 * @param stdClass $course Course object
 * @param stdClass $cm Course module object
 * @param context_module $context Context module object
 */
function doc2interact_view(stdClass $doc2interact, stdClass $course, stdClass $cm, context_module $context): void {
    // Trigger module viewed event.
    $event = \mod_doc2interact\event\course_module_viewed::create([
        'objectid' => $doc2interact->id,
        'context'  => $context,
    ]);
    $event->add_record_snapshot('course', $course);
    $event->add_record_snapshot('doc2interact', $doc2interact);
    $event->trigger();

    // Completion.
    $completion = new \completion_info($course);
    $completion->set_module_viewed($cm);
}
