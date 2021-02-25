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
     *
     * Note: If the currently logged in user does not have the correct capabilities to view the
     * target URL, the created PDF will most likely be an error page.
     *
     * @param \moodle_url $url the target URL to convert.
     * @param array $options any additional options to pass to converter, valid options vary with converter
     * instance, see relevant converter for further details.
     *
     * @return \stored_file the stored file created during conversion.
     */
    public function convert_moodle_url_to_pdf(moodle_url $url, array $options = []): \stored_file {
        // Implement converter specific logic for URL PDF extraction here.
    }

    /**
     * Get the converted PDF for a Moodle URL if it exists.
     *
     * @param \moodle_url $url the target URL to get converted PDF for.
     *
     * @return bool|\stored_file the stored file PDF, false if Moodle URL has not been converted to PDF.
     */
    public function get_converted_moodle_url_pdf(moodle_url $url) {
        $fs = get_file_storage();
        $filerecord = helper::get_pdf_filerecord($url, $this->get_name());

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
