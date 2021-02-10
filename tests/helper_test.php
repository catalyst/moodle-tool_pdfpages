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
 * Helper functions tests for tool_pdfpages.
 *
 * @package    tool_pdfpages
 * @author     Tom Dickman <tomdickman@catalyst-au.net>
 * @copyright  2021 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_pdfpages\helper;

defined('MOODLE_INTERNAL') || die();

/**
 * Helper functions tests for tool_pdfpages.
 *
 * @package    tool_pdfpages
 * @author     Tom Dickman <tomdickman@catalyst-au.net>
 * @copyright  2021 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_pdfpages_helper_test extends advanced_testcase {

    /**
     * Test getting a plugin setting value.
     */
    public function test_get_config() {
        $this->resetAfterTest();

        set_config('wkhtmltopdfpath', 'tool_pdfpages', '/usr/local/bin/wkhtmltopdf');
        $this->assertEquals('/usr/local/bin/wkhtmltopdf', helper::get_config('wkhtmltopdfpath'));
        unset_config('wkhtmltopdfpath', 'tool_pdfpages');
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("No configured tool_pdfpages setting 'wkhtmltopdfpath'.");
        helper::get_config('wkhtmltopdfpath');
    }
}
