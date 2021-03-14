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
 * Strings for component 'tool_pdfpages', language 'en'.
 *
 * @package    tool_pdfpages
 * @author     Tom Dickman <tomdickman@catalyst-au.net>
 * @copyright  2021 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'PDF Pages';
$string['error:converternotfound'] = 'Could not find enabled converter, please check tool_pages plugin settings.';
$string['error:invalidpageoption'] = 'The PDF page option you selected is not supported: {$a}';
$string['error:permissions:createkey'] = "User doesn't have required capability to create access keys.";
$string['error:urltopdf'] = 'URL to PDF conversion could not be completed.';
$string['settings:accesskeyheading'] = 'Access key settings';
$string['settings:accesskeyttl'] = 'TTL (Time To Live)';
$string['settings:accesskeyttl_desc'] = 'The time in seconds for access keys to live before expiring';
$string['settings:convertersheading'] = 'Converter settings';
$string['settings:chromiumpath'] = 'chromium binary';
$string['settings:chromiumpath_desc'] = 'The path to the chrome/chromium binary';
$string['settings:wkhtmltopdfpath'] = 'wkhtmltopdf binary';
$string['settings:wkhtmltopdfpath_desc'] = 'The path to wkhtmltopdf binary';
