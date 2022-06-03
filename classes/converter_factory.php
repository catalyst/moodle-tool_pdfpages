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
     * @param string $name specific converter name to get instance of.
     *
     * @return converter A converter instance.
     *
     * @throws \moodle_exception If converter could not be found.
     */
    public static function get_converter(string $name = ''): converter {
        $converternames = empty($name) ? helper::get_converter_names() : [$name];
        $converters = self::get_converters($converternames);

        if (empty($converters)) {
            throw new \moodle_exception('error:converternotfound', 'tool_pdfpages');
        }

        return reset($converters);
    }

    /**
     * Get multiple converters.
     *
     * @param string[] $converternames converter names to get (if empty, get all enabled converters).
     *
     * @return \tool_pdfpages\converter[] converter instances.
     */
    public static function get_converters(array $converternames = []): array {
        $availableconverters = helper::get_converter_names();
        $converterstoget = !empty($converternames) ? array_intersect($availableconverters, $converternames) : $availableconverters;
        $converters = [];

        foreach ($converterstoget as $convertername) {
            $converterclass = "\\tool_pdfpages\\converter_$convertername";

            if (class_exists($converterclass)) {
                $converter = new $converterclass();

                if ($converter->is_enabled()) {
                    $converters[$convertername] = $converter;
                }
            }
        }

        return $converters;
    }
}
