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
 * Class containing helper functions for tool_pdfpages.
 *
 * @package    tool_pdfpages
 * @author     Tom Dickman <tomdickman@catalyst-au.net>
 * @copyright  2021 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_pdfpages;

use core_user;
use file_storage;
use moodle_url;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/filestorage/file_storage.php');

/**
 * Class containing helper functions for tool_pdfpages.
 *
 * @package    tool_pdfpages
 * @author     Tom Dickman <tomdickman@catalyst-au.net>
 * @copyright  2021 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class helper {

    /**
     * The filearea to store Moodle URL PDFs in.
     */
    const MOODLE_URL_PDF_FILEAREA = 'pdf';

    /**
     * List of available converters.
     *
     * To add a new converter, the following steps need to be conducted:
     * - Add converter name in this constant;
     * - Add a setting `<converter name>path` to plugin settings.php for setting path to converter binary;
     * - Create a class extending `converter` abstract class and named `converter_<converter name>`
     */
    const CONVERTERS = ['chromium', 'wkhtmltopdf'];

    /**
     * Create a user key for PDF pages.
     *
     * @param string $iprestriction optional IP range to restrict access to.
     *
     * @return string the created user key value.
     * @throws \moodle_exception if user doesn't have permission to create key.
     */
    public static function create_user_key(string $iprestriction = '') : string {
        global $USER;

        require_capability('tool/pdfpages:generatepdf', \context_system::instance());

        $iprestriction = !empty($iprestriction) ? $iprestriction : null;

        // Tidy up old keys.
        delete_user_key('tool/pdfpages', $USER->id);

        $ttl = get_config('tool_pdfpages', 'accesskeyttl');
        $expirationtime = !empty($ttl) ? (time() + $ttl) : (time() + MINSECS);

        return create_user_key('tool/pdfpages', $USER->id, null, $iprestriction, $expirationtime);
    }

    /**
     * Get a tool_pdfpages plugin setting.
     *
     * @param string $pluginsetting the plugin setting to get value for.
     *
     * @return mixed the set config value.
     * @throws \coding_exception if the plugin setting does not exist.
     */
    public static function get_config(string $pluginsetting) {
        $config = get_config('tool_pdfpages', $pluginsetting);

        if (empty($config)) {
            throw new \coding_exception("No configured tool_pdfpages setting '$pluginsetting'.");
        } else {
            return $config;
        }
    }

    /**
     * Get a list of valid converter names.
     *
     * @return string[] converter names.
     */
    public static function get_converter_names() {
        return self::CONVERTERS;
    }

    /**
     * Get the filearea where PDFs of Moodle URLs are stored in plugin files.
     */
    public static function get_moodle_url_pdf_filearea() {
        return static::MOODLE_URL_PDF_FILEAREA;
    }

    /**
     * Get the filename used for the PDF of a Moodle URL in plugin files.
     *
     * @param \moodle_url $url the Moodle URL to get PDF filename for.
     */
    public static function get_moodle_url_pdf_filename(moodle_url $url) {
        return file_storage::hash_from_string($url->out(false)) . '.pdf';
    }

    /**
     * Get file record for a converter created PDF.
     *
     * @param string $filename the name of the PDF file.
     * @param string $converter the converter which conducted the conversion.
     *
     * @return array array describing a file (file_info params)
     * @throws \coding_exception if converter is not installed or invalid.
     */
    public static function get_pdf_filerecord(string $filename, string $converter) : array {
        if (!self::is_converter_enabled($converter)) {
            throw new \coding_exception("Cannot get fileinfo for '$converter' converter, not installed and/or enabled.");
        }

        return [
            'contextid' => \context_system::instance()->id,
            'component' => 'tool_pdfpages',
            'filearea' => self::get_moodle_url_pdf_filearea(),
            'itemid' => 0,
            'filepath' => "/$converter/",
            'filename' => $filename,
        ];
    }

    /**
     * Get the proxy URL for converting a target URL.
     *
     * @param \moodle_url $targeturl the target URL to reach after passing through proxy.
     * @param string $key the access key to use for Moodle user login validation.
     *
     * @return \moodle_url
     */
    public static function get_proxy_url(moodle_url $targeturl, string $key) {
        return new moodle_url('/admin/tool/pdfpages/index.php', ['url' => $targeturl->out(), 'key' => $key]);
    }

    /**
     * Check if a converter is enabled.
     *
     * @param string $convertername the name of the converter to check.
     *
     * @return bool true if enabled, false otherwise.
     */
    public static function is_converter_enabled(string $convertername) {
        return array_key_exists($convertername, converter_factory::get_converters());
    }

    /**
     * Login user with access key.
     *
     * @param string $key access key to use for user validation, this is required to login user and allow access of target page
     * {@see \tool_pdfpages\helper::create_user_key}.
     */
    public static function login_with_key(string $key) {
        $key = validate_user_key($key, 'tool/pdfpages', null);
        // Destroy the single use key immediately following validation.
        delete_user_key('tool/pdfpages', $key->userid);

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

        core_user::require_active_user($user, true, true);

        enrol_check_plugins($user);
        \core\session\manager::set_user($user);

        if (!defined('USER_KEY_LOGIN')) {
            define('USER_KEY_LOGIN', true);
        }
    }
}
