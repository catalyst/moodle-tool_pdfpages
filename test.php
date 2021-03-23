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
 * Page for testing PDF conversion.
 *
 * @package    tool_pdfpages
 * @author     Tom Dickman <tomdickman@catalyst-au.net>
 * @copyright  2021 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');

require_admin();

$rawurl = required_param('url', PARAM_URL);
$converter = optional_param('converter', '', PARAM_ALPHA);
$filename = optional_param('filename', '', PARAM_FILE);
// Pass in any options as a JSON encoded string.
$options = optional_param('options', '{}', PARAM_RAW);

$options = json_decode($options, true);

$url = new moodle_url($rawurl);
$converter = \tool_pdfpages\converter_factory::get_converter($converter);

$pdffile = $converter->convert_moodle_url_to_pdf($url, $filename, $options, true);
send_file($pdffile, $pdffile->get_filename());
