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

defined('MOODLE_INTERNAL') || die();

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
}
