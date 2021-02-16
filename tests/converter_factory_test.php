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
 * Converter factory tests for tool_pdfpages.
 *
 * @package    tool_pdfpages
 * @author     Tom Dickman <tomdickman@catalyst-au.net>
 * @copyright  2021 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_pdfpages\converter_factory;

defined('MOODLE_INTERNAL') || die();

/**
 * Converter factory tests for tool_pdfpages.
 *
 * @package    tool_pdfpages
 * @author     Tom Dickman <tomdickman@catalyst-au.net>
 * @copyright  2021 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class converter_factory_test extends advanced_testcase {

    /**
     * Test getting a converter instance.
     */
    public function test_get_converter() {
        $this->resetAfterTest();

        set_config('wkhtmltopdfpath', '/usr/local/bin/wkhtmltopdf', 'tool_pdfpages');

        $actual = converter_factory::get_converter();
        $this->assertInstanceOf(\tool_pdfpages\converter_wkhtmltopdf::class, $actual);
        $this->assertTrue($actual->is_enabled());

        unset_config('wkhtmltopdfpath', 'tool_pdfpages');
        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('No converters are currently enabled, please check tool_pages plugin settings.');
        converter_factory::get_converter();
    }

    /**
     * Test getting multiple converter instances.
     */
    public function test_get_converters() {
        $this->resetAfterTest();

        set_config('wkhtmltopdfpath', '/usr/local/bin/wkhtmltopdf', 'tool_pdfpages');

        $actual = converter_factory::get_converters();
        $this->assertCount(1, $actual);
        $this->assertArrayHasKey('wkhtmltopdf', $actual);
        $this->assertInstanceOf(\tool_pdfpages\converter_wkhtmltopdf::class, $actual['wkhtmltopdf']);
        $this->assertTrue($actual['wkhtmltopdf']->is_enabled());

        unset_config('wkhtmltopdfpath', 'tool_pdfpages');
        $this->assertEmpty(converter_factory::get_converters());
    }
}
