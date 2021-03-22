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

/**
 * Access key manager.
 *
 * @package    tool_pdfpages
 * @author     Tom Dickman <tomdickman@catalyst-au.net>
 * @copyright  2021 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_pdfpages;

defined('MOODLE_INTERNAL') || die();

/**
 * Access key manager.
 *
 * @package    tool_pdfpages
 * @author     Tom Dickman <tomdickman@catalyst-au.net>
 * @copyright  2021 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class key_manager {

    /**
     * Create a user key.
     *
     * @param int $instanceid the instance ID to create key for.
     * @param string $iprestriction optional IP range to restrict access to.
     *
     * @return string the created user key value.
     * @throws \moodle_exception if user doesn't have permission to create key.
     */
    public static function create_user_key(int $instanceid, string $iprestriction = ''): string {
        global $USER;

        require_capability('tool/pdfpages:generatepdf', \context_system::instance());

        $iprestriction = !empty($iprestriction) ? $iprestriction : null;

        self::delete_user_key($instanceid);

        $ttl = get_config('tool_pdfpages', 'accesskeyttl');
        $expirationtime = !empty($ttl) ? (time() + $ttl) : (time() + MINSECS);

        return create_user_key('tool/pdfpages', $USER->id, $instanceid, $iprestriction, $expirationtime);
    }

    /**
     * Delete a user key.
     *
     * @param int $instanceid the instance ID to delete user key for.
     *
     * @return bool true on success.
     */
    public static function delete_user_key(int $instanceid): bool {
        global $DB, $USER;

        $record = [
            'script' => 'tool/pdfpages',
            'userid' => $USER->id,
            'instance' => $instanceid
        ];

        return $DB->delete_records('user_private_key', $record);
    }

    /**
     * Get a unique access key instance ID for a filename.
     *
     * @param string $filename the filename to get instance ID for.
     *
     * @return int the instance ID.
     */
    public static function get_instance_id(string $filename): int {
        return (int) substr(base_convert(sha1($filename), 16, 10), 0, 18);
    }

    /**
     * Login user with access key.
     *
     * @param string $key access key to use for user validation, this is required to login user and allow access of target page.
     * @param int $instanceid the instance ID of key to login with.
     */
    final public static function login_with_key(string $key, int $instanceid) {
        $key = self::validate_user_key($key, $instanceid);

        // Destroy the single use key immediately following validation.
        self::delete_user_key($instanceid);

        self::setup_user_session($key->userid);
    }

    /**
     * Setup a user session for headless browser use.
     *
     * @param int $userid the Moodle user ID.
     *
     * @throws \moodle_exception if the user ID was invalid.
     */
    protected static function setup_user_session(int $userid) {
        global $DB;

        if (!$user = $DB->get_record('user', ['id' => $userid])) {
            throw new \moodle_exception('invaliduserid');
        }

        \core_user::require_active_user($user, true, true);

        enrol_check_plugins($user);
        \core\session\manager::set_user($user);

        if (!defined('USER_KEY_LOGIN')) {
            define('USER_KEY_LOGIN', true);
        }
    }

    /**
     * Validate a key and return record if valid.
     *
     * @param string $key access key to validate.
     * @param int $instanceid the instance ID of key to validate.
     *
     * @return object the validated key record.
     */
    public static function validate_user_key(string $key, int $instanceid): object {
        return validate_user_key($key, 'tool/pdfpages', $instanceid);
    }
}
