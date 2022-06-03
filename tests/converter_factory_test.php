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
 * Converter factory tests for tool_pdfpages.
 *
 * @package    tool_pdfpages
 * @author     Tom Dickman <tomdickman@catalyst-au.net>
 * @copyright  2021 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class converter_factory_test extends \advanced_testcase {

    /**
     * Test getting a converter instance.
     */
    public function test_get_converter() {
        $this->resetAfterTest();

        set_config('wkhtmltopdfpath', '/usr/local/bin/wkhtmltopdf', 'tool_pdfpages');
        set_config('chromiumpath', '/usr/bin/chromium-browser', 'tool_pdfpages');

        // Should get specific converter if name specified.
        $actual = converter_factory::get_converter('wkhtmltopdf');
        $this->assertInstanceOf(converter_wkhtmltopdf::class, $actual);
        $this->assertTrue($actual->is_enabled());

        // Should get first converter if multiple installed.
        $actual = converter_factory::get_converter();
        $this->assertInstanceOf(converter_chromium::class, $actual);
        $this->assertTrue($actual->is_enabled());
        unset_config('chromiumpath', 'tool_pdfpages');
        $actual = converter_factory::get_converter();
        $this->assertInstanceOf(converter_wkhtmltopdf::class, $actual);
        $this->assertTrue($actual->is_enabled());

        // Should throw an exception if no installed converters.
        unset_config('wkhtmltopdfpath', 'tool_pdfpages');
        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessage('Could not find enabled converter, please check tool_pages plugin settings.');
        converter_factory::get_converter();
    }

    /**
     * Test getting multiple converter instances.
     */
    public function test_get_converters() {
        $this->resetAfterTest();

        set_config('wkhtmltopdfpath', '/usr/local/bin/wkhtmltopdf', 'tool_pdfpages');
        set_config('chromiumpath', '/usr/bin/chromium-browser', 'tool_pdfpages');

        $actual = converter_factory::get_converters();
        $this->assertCount(2, $actual);
        $this->assertArrayHasKey('wkhtmltopdf', $actual);
        $this->assertArrayHasKey('chromium', $actual);
        $this->assertInstanceOf(converter_wkhtmltopdf::class, $actual['wkhtmltopdf']);
        $this->assertInstanceOf(converter_chromium::class, $actual['chromium']);
        $this->assertTrue($actual['wkhtmltopdf']->is_enabled());
        $this->assertTrue($actual['chromium']->is_enabled());

        unset_config('wkhtmltopdfpath', 'tool_pdfpages');
        unset_config('chromiumpath', 'tool_pdfpages');
        $this->assertEmpty(converter_factory::get_converters());
    }
}
