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
     * The script name to associate with keys.
     */
    const SCRIPT = 'tool/pdfpages';

    /**
     * Create a user key.
     *
     * @param int $userid the user ID to create key for.
     * @param int $instance the instance to create key for.
     * @param string $iprestriction optional IP range to restrict access to.
     *
     * @return string the created user key value.
     * @throws \moodle_exception if user doesn't have permission to create key.
     */
    protected static function create_user_key(int $userid, int $instance, string $iprestriction = ''): string {
        require_capability('tool/pdfpages:generatepdf', \context_system::instance());

        $iprestriction = !empty($iprestriction) ? $iprestriction : null;

        self::delete_user_keys($userid, $instance);

        $ttl = get_config('tool_pdfpages', 'accesskeyttl');
        $expirationtime = !empty($ttl) ? (time() + $ttl) : (time() + MINSECS);

        return create_user_key(self::SCRIPT, $userid, $instance, $iprestriction, $expirationtime);
    }

    /**
     * Create a user key for a specific URL.
     *
     * @param int $userid the user ID to create key for.
     * @param \moodle_url $url the URL to create user key for.
     * @param string $iprestriction optional IP range to restrict access to.
     *
     * @return string the created user key value.
     */
    public static function create_user_key_for_url(int $userid, \moodle_url $url, string $iprestriction = ''): string {
        $instance = self::generate_instance_for_url($url);

        return self::create_user_key($userid, $instance, $iprestriction);
    }

    /**
     * Delete a user keys.
     *
     * @param int $userid the user ID to delete user key for.
     * @param int $instance the instance to delete user key for.
     *
     * @return bool true on success.
     */
    protected static function delete_user_keys(int $userid, int $instance): bool {
        global $DB;

        $record = [
            'script' => self::SCRIPT,
            'userid' => $userid,
            'instance' => $instance
        ];

        return $DB->delete_records('user_private_key', $record);
    }

    /**
     * Delete user keys for a URL.
     *
     * @param int $userid the user ID to delete user keys for.
     * @param \moodle_url $url the URL to delete user keys for.
     *
     * @return bool true on success.
     */
    public static function delete_user_keys_for_url(int $userid, \moodle_url $url): bool {
        $instance = self::generate_instance_for_url($url);

        return self::delete_user_keys($userid, $instance);
    }

    /**
     * Generate a unique access key instance for a seed value.
     *
     * @param string $seed the seed string to generate instance for.
     *
     * @return int the instance.
     */
    protected static function generate_instance(string $seed): int {
        return (int) substr(base_convert(sha1($seed), 16, 10), 0, 18);
    }

    /**
     * Generate a unique access key instance for a URL.
     *
     * @param \moodle_url $url the URL to generate instance for.
     *
     * @return int the instance.
     */
    public static function generate_instance_for_url(\moodle_url $url): int {
        return self::generate_instance($url->out(false));
    }

    /**
     * Validate a key and return record if valid.
     *
     * @param string $key access key to validate.
     * @param int $instance the instance of key to validate.
     *
     * @return object the validated key record.
     */
    protected static function validate_user_key(string $key, int $instance): object {
        return validate_user_key($key, self::SCRIPT, $instance);
    }

    /**
     * Validate a key for a specific URL and return record if valid.
     *
     * @param string $key access key to validate.
     * @param \moodle_url $url the URL to check key against.
     *
     * @return object the validated key record.
     */
    public static function validate_user_key_for_url(string $key, \moodle_url $url): object {
        $instance = self::generate_instance_for_url($url);

        return self::validate_user_key($key, $instance);
    }
}
