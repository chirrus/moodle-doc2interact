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

namespace mod_doc2interact\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;

/**
 * Privacy provider for mod_doc2interact.
 *
 * This plugin does not store any personal data.
 * Content is generated externally via the Doc2Interact API.
 *
 * @package    mod_doc2interact
 * @copyright  2026 Sergio Alejandro Pilar
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\core_userlist_provider {

    /**
     * Returns metadata about this plugin's use of personal data.
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_external_location_link(
            'doc2interact_api',
            [
                'textoCompleto' => 'privacy:metadata:doc2interact_api:textoCompleto',
                'titulo'        => 'privacy:metadata:doc2interact_api:titulo',
            ],
            'privacy:metadata:doc2interact_api'
        );
        return $collection;
    }

    /**
     * Get the list of contexts that contain user information.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        return new contextlist();
    }

    /**
     * Get the list of users who have data within a context.
     */
    public static function get_users_in_context(userlist $userlist): void {
    }

    /**
     * Export all user data for the specified user.
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
    }

    /**
     * Delete all user data for the specified context.
     */
    public static function delete_data_for_all_users_in_context(\context $context): void {
    }

    /**
     * Delete all user data for the specified user.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
    }

    /**
     * Delete multiple users within a single context.
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
    }
}
