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
 * Wkhtmltopdf converter tests for tool_pdfpages.
 *
 * @package    tool_pdfpages
 * @author     Tom Dickman <tomdickman@catalyst-au.net>
 * @copyright  2021 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_pdfpages\converter_wkhtmltopdf;
use tool_pdfpages\helper;

defined('MOODLE_INTERNAL') || die();

/**
 * Wkhtmltopdf converter tests for tool_pdfpages.
 *
 * @package    tool_pdfpages
 * @author     Tom Dickman <tomdickman@catalyst-au.net>
 * @copyright  2021 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class converter_wkhtmltopdf_test extends advanced_testcase {

    /**
     * Test getting converter name.
     */
    public function test_get_name() {
        $converter = new converter_wkhtmltopdf();
        $this->assertEquals('wkhtmltopdf', $converter->get_name());
    }

    /**
     * Test checking if converter is enabled.
     */
    public function test_is_enabled() {
        $this->resetAfterTest();

        set_config('wkhtmltopdfpath', '/usr/bin/wkhtmltopdf-browser', 'tool_pdfpages');
        $converter = new converter_wkhtmltopdf();
        $this->assertTrue($converter->is_enabled());
        unset_config('wkhtmltopdfpath', 'tool_pdfpages');
        $this->assertFalse($converter->is_enabled());
    }

    /**
     * Test converter creates PDF files correctly.
     */
    public function test_create_pdf_file() {
        $this->resetAfterTest();

        set_config('wkhtmltopdfpath', '/usr/bin/wkhtmltopdf-browser', 'tool_pdfpages');

        $filename = 'test.pdf';
        $content = 'Hello World!';

        $converter = new converter_wkhtmltopdf();
        $actual = $converter->create_pdf_file($content, $filename);
        $this->assertInstanceOf(stored_file::class, $actual);
        $this->assertEquals('test.pdf', $actual->get_filename());
        $this->assertEquals('Hello World!', $actual->get_content());
    }

    /**
     * Test getting a previously converted PDF file.
     */
    public function test_get_converted_moodle_url_pdf() {
        $this->resetAfterTest();

        set_config('wkhtmltopdfpath', '/usr/bin/wkhtmltopdf-browser', 'tool_pdfpages');

        $filename = 'test.pdf';

        $converter = new converter_wkhtmltopdf();
        $this->assertFalse($converter->get_converted_moodle_url_pdf($filename));

        $content = 'Hello World!';
        $converter->create_pdf_file($content, $filename);

        $actual = $converter->get_converted_moodle_url_pdf($filename);
        $this->assertEquals('test.pdf', $actual->get_filename());
        $this->assertEquals('Hello World!', $actual->get_content());
    }

    /**
     * Test validating converter options.
     */
    public function test_validate_options() {
        // Testing a protected method, so we need to setup reflector magic.
        $method = new ReflectionMethod('\tool_pdfpages\converter_wkhtmltopdf', 'validate_options');
        $method->setAccessible(true); // Allow accessing of protected method.

        $converter = new converter_wkhtmltopdf();
        $options = [
            'print-media-type' => true,
            'enable-javascript' => true,
            'javascript-delay' => 200,
            'background' => false,
            'header-html' => '<p>Header template</p>',
            'footer-html' => '<p>Footer template</p>',
            'page-size' => 'A4',
            'margin-top' => '0',
            'margin-bottom' => '10mm',
            'margin-left'  => '10mm',
            'margin-right' => '10mm',
            'afakeoption' => true // Not a valid option.
        ];

        $actual = $method->invoke($converter, $options);
        // Should return any valid options.
        $this->assertIsArray($actual);
        $this->assertTrue($actual['print-media-type']);
        $this->assertTrue($actual['enable-javascript']);
        $this->assertEquals(200, $actual['javascript-delay']);
        $this->assertFalse($actual['background']);
        $this->assertEquals('<p>Header template</p>', $actual['header-html']);
        $this->assertEquals('<p>Footer template</p>', $actual['footer-html']);
        $this->assertEquals('A4', $actual['page-size']);
        $this->assertEquals('0', $actual['margin-top']);
        $this->assertEquals('10mm', $actual['margin-bottom']);
        $this->assertEquals('10mm', $actual['margin-left']);
        $this->assertEquals('10mm', $actual['margin-right']);
        // Should remove any invalid options.
        $this->assertArrayNotHasKey('afakeoption', $actual);
    }
}
