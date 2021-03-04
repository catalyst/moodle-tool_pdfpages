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
 * Interface for converting Moodle pages to PDFs.
 *
 * @package    tool_pdfpages
 * @author     Tom Dickman <tomdickman@catalyst-au.net>
 * @copyright  2021 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_pdfpages;

use moodle_url;

defined('MOODLE_INTERNAL') || die();

/**
 * Interface for converting Moodle pages to PDFs.
 *
 * @package    tool_pdfpages
 * @author     Tom Dickman <tomdickman@catalyst-au.net>
 * @copyright  2021 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class converter {

    /**
     * Converter name, override in extending classes.
     */
    protected const NAME = 'base';

    /**
     * Convert a moodle URL to PDF and store in file system.
     * Note: If the currently logged in user does not have the correct capabilities to view the
     * target URL, the created PDF will most likely be an error page.
     *
     * @param \moodle_url $url the target URL to convert.
     * @param string $key access key to use for user validation, this is required to login user and allow access of target page
     * for conversion {@see \tool_pdfpages\helper::create_user_key}.
     * @param string $filename the name to give converted file.
     * (if none is specified, use {@see \tool_pdfpages\helper::get_moodle_url_pdf_filename})
     * @param array $options any additional options to pass to converter, valid options vary with converter
     * instance, see relevant converter for further details.
     * @param string $cookiename cookie name to apply to conversion (optional).
     * @param string $cookievalue cookie value to apply to conversion (optional).
     *
     * @return \stored_file the stored file created during conversion.
     */
    public function convert_moodle_url_to_pdf(moodle_url $url, string $key, string $filename = '', array $options = [],
                                              string $cookiename = '', string $cookievalue = ''): \stored_file {
        // Implement converter specific logic for URL PDF extraction here.
        // When implemented, the target URL must be passed through the proxy page (index.php) as a parameter
        // along with the access key, in order to validate user login.
        // {@see \tool_pdfpages\helper::get_proxy_url}.
    }

    /**
     * Create a PDF file from content.
     *
     * @param string $content the PDF content to write to file.
     * @param string $filename the filename to give file.
     *
     * @return bool|\stored_file the file or false if file could not be created.
     */
    public function create_pdf_file(string $content, string $filename) {

        $filerecord = helper::get_pdf_filerecord($filename, $this->get_name());
        $fs = get_file_storage();
        $existingfile = $fs->get_file(...array_values($filerecord));

        // If the file already exists, it needs to be deleted, as otherwise the new filename will collide
        // with existing filename and the new file will not be able to be created.
        if (!empty($existingfile)) {
            $existingfile->delete();
        }

        return $fs->create_file_from_string($filerecord, $content);
    }

    /**
     * Get a previously converted URL PDF.
     *
     * @param string $filename the filename of conversion file to get.
     *
     * @return bool|\stored_file the file or false if file could not be found.
     */
    public function get_converted_moodle_url_pdf(string $filename) {
        $filerecord = helper::get_pdf_filerecord($filename, $this->get_name());
        $fs = get_file_storage();

        return $fs->get_file(...array_values($filerecord));
    }

    /**
     * Get the converter name.
     *
     * @return string the converter name.
     */
    public function get_name(): string {
        return static::NAME;
    }

    /**
     * Check if this converter is enabled.
     *
     * @return bool true if converter enabled, false otherwise.
     */
    public function is_enabled(): bool {
        try {
            helper::get_config($this->get_name() . 'path');
            return true;
        } catch (\moodle_exception $exception) {
            return false;
        }
    }
}
