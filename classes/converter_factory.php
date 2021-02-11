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
 * Factory for getting a converter.
 *
 * @package    tool_pdfpages
 * @author     Tom Dickman <tomdickman@catalyst-au.net>
 * @copyright  2021 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_pdfpages;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/admin/tool/pdfpages/classes/converter.interface');

/**
 * Factory for getting a converter.
 *
 * @package    tool_pdfpages
 * @author     Tom Dickman <tomdickman@catalyst-au.net>
 * @copyright  2021 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class converter_factory {

    /**
     * Instantiate a converter.
     *
     * @returns converter A converter instance.
     *
     * @throws \moodle_exception If no converters are correctly installed and set up.
     * @throws \coding_exception If required converter class is missing.
     */
    public static function get_converter() : converter {
        $installedconverters = helper::get_installed_converters();

        if (empty($installedconverters)) {
            throw new \moodle_exception('error:noinstalledconverters', 'tool_pdfpages');
        }

        $convertername = reset($installedconverters);
        $converterclass = "\\tool_pdfpages\\converter_$convertername";

        if (!class_exists($converterclass)) {
            throw new \coding_exception("The converter class '$converterclass' has not been implemented, cannot instantiate.");
        }

        return new $converterclass();
    }
}
