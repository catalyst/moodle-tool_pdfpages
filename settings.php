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
 * Settings for tool_pdfpages.
 *
 * @package    tool_pdfpages
 * @copyright  2020 Tom Dickman <tomdickman@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

global $ADMIN;

if (!empty($hassiteconfig)) {
    $settings = new admin_settingpage('tool_pdfpages', get_string('pluginname', 'tool_pdfpages'));
    $ADMIN->add('tools', $settings);

    $settings->add(new admin_setting_heading('tool_pdfpages/converters',
        get_string('settings:convertersheading', 'tool_pdfpages'), ''));

    $settings->add(new admin_setting_configexecutable('tool_pdfpages/wkhtmltopdfpath',
        get_string('settings:wkhtmltopdfpath', 'tool_pdfpages'),
        get_string('settings:wkhtmltopdfpath_desc', 'tool_pdfpages'),
        ''));

    $settings->add(new admin_setting_configexecutable('tool_pdfpages/chromiumpath',
        get_string('settings:chromiumpath', 'tool_pdfpages'),
        get_string('settings:chromiumpath_desc', 'tool_pdfpages'),
        ''));

    $settings->add(new admin_setting_configtext('tool_pdfpages/chromiumresponsetimeout',
        get_string('settings:chromiumresponsetimeout', 'tool_pdfpages'),
        get_string('settings:chromiumresponsetimeout_desc', 'tool_pdfpages'),
        10, PARAM_INT));

    $settings->add(new admin_setting_heading('tool_pdfpages/accesskey',
        get_string('settings:accesskeyheading', 'tool_pdfpages'), ''));

    $settings->add(new admin_setting_configtext('tool_pdfpages/accesskeyttl',
        get_string('settings:accesskeyttl', 'tool_pdfpages'),
        get_string('settings:accesskeyttl_desc', 'tool_pdfpages'),
        MINSECS, PARAM_INT));
}
