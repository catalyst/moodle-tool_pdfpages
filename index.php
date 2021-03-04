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
 * Proxy for PDF page conversion.
 *
 * This uses an access key to avoid having to conduct a full headless browser login for page
 * conversion and then redirects to the page in question, if the current user doesn't have
 * access to the page, the converted PDF will likely be the login page.
 *
 * @package    tool_pdfpages
 * @author     Tom Dickman <tomdickman@catalyst-au.net>
 * @copyright  2021 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('NO_MOODLE_COOKIES', true); // No need for a session here.

require_once(__DIR__ . '/../../../config.php');

global $CFG;

$targeturl = required_param('url', PARAM_URL);
$key = required_param('key', PARAM_ALPHANUM);

$url = new moodle_url($targeturl);

require_user_key_login('tool/pdfpages');

foreach ($url->params() as $param => $value) {
    $_GET[$param] = $value;
}

require($CFG->dirroot . $url->get_path());
