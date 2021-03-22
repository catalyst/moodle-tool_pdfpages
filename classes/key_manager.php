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
     * @param int $instance the instance to create key for.
     * @param string $iprestriction optional IP range to restrict access to.
     *
     * @return string the created user key value.
     * @throws \moodle_exception if user doesn't have permission to create key.
     */
    public static function create_user_key(int $instance, string $iprestriction = ''): string {
        global $USER;

        require_capability('tool/pdfpages:generatepdf', \context_system::instance());

        $iprestriction = !empty($iprestriction) ? $iprestriction : null;

        self::delete_user_key($instance);

        $ttl = get_config('tool_pdfpages', 'accesskeyttl');
        $expirationtime = !empty($ttl) ? (time() + $ttl) : (time() + MINSECS);

        return create_user_key(self::SCRIPT, $USER->id, $instance, $iprestriction, $expirationtime);
    }

    /**
     * Delete a user key.
     *
     * @param int $instance the instance to delete user key for.
     *
     * @return bool true on success.
     */
    public static function delete_user_key(int $instance): bool {
        global $DB, $USER;

        $record = [
            'script' => self::SCRIPT,
            'userid' => $USER->id,
            'instance' => $instance
        ];

        return $DB->delete_records('user_private_key', $record);
    }

    /**
     * Generate a unique access key instance for a seed value.
     *
     * @param string $seed the seed string to generate instance for.
     *
     * @return int the instance.
     */
    public static function generate_instance(string $seed): int {
        return (int) substr(base_convert(sha1($seed), 16, 10), 0, 18);
    }

    /**
     * Validate a key and return record if valid.
     *
     * @param string $key access key to validate.
     * @param int $instance the instance of key to validate.
     *
     * @return object the validated key record.
     */
    public static function validate_user_key(string $key, int $instance): object {
        return validate_user_key($key, self::SCRIPT, $instance);
    }
}
