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

namespace tool_pdfpages;

/**
 * Manager for user logins to conduct conversions.
 *
 * @package    tool_pdfpages
 * @author     Tom Dickman <tomdickman@catalyst-au.net>
 * @copyright  2021 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class login_manager {

    /**
     * Login user with access key.
     *
     * @param string $key access key to use for user validation, this is required to login user and allow access of target page.
     * @param \moodle_url $url the Moodle URL to login for with key.
     */
    final public static function login_with_key(string $key, \moodle_url $url) {
        $key = key_manager::validate_user_key_for_url($key, $url);

        // Destroy the single use key immediately following validation.
        key_manager::delete_user_keys_for_url($key->userid, $url);

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

        $user = $DB->get_record('user', ['id' => $userid]);

        if ($user === false) {
            throw new \moodle_exception('invaliduserid');
        }

        \core_user::require_active_user($user, true, true);

        enrol_check_plugins($user);
        \core\session\manager::set_user($user);

        if (!defined('USER_KEY_LOGIN')) {
            define('USER_KEY_LOGIN', true);
        }
    }
}
