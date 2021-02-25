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
 * Chromium converter tests for tool_pdfpages.
 *
 * @package    tool_pdfpages
 * @author     Tom Dickman <tomdickman@catalyst-au.net>
 * @copyright  2021 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_pdfpages\converter_chromium;
use tool_pdfpages\helper;

defined('MOODLE_INTERNAL') || die();

/**
 * Chromium converter tests for tool_pdfpages.
 *
 * @package    tool_pdfpages
 * @author     Tom Dickman <tomdickman@catalyst-au.net>
 * @copyright  2021 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class converter_chromium_test extends advanced_testcase {

    /**
     * Test getting previously converted URL file.
     */
    public function test_get_converted_moodle_url_pdf() {
        $this->resetAfterTest();

        set_config('chromiumpath', '/usr/bin/chromium-browser', 'tool_pdfpages');
        $converter = new converter_chromium();

        // Create existing file for URL.
        $url = new \moodle_url('course/view.php', ['id' => 1337]);
        $filerecord = helper::get_pdf_filerecord($url, $converter->get_name());
        $fs = get_file_storage();
        $existing = $fs->create_file_from_string($filerecord, 'Hello World!');

        // Should match existing file record for URL.
        $actual = $converter->get_converted_moodle_url_pdf($url);
        $this->assertInstanceOf(stored_file::class, $actual);
        $this->assertEquals($existing->get_id(), $actual->get_id());
        $this->assertEquals($existing->get_filearea(), $actual->get_filearea());
        $this->assertEquals($existing->get_filepath(), $actual->get_filepath());
        $this->assertEquals($existing->get_filename(), $actual->get_filename());
        $this->assertEquals($existing->get_itemid(), $actual->get_itemid());
        $this->assertEquals('Hello World!', $actual->get_content());

        // Should not return a file if conversion has not been conducted.
        $url = new \moodle_url('https://example.org');
        $this->assertFalse($converter->get_converted_moodle_url_pdf($url));
    }

    /**
     * Test getting converter name.
     */
    public function test_get_name() {
        $converter = new converter_chromium();
        $this->assertEquals('chromium', $converter->get_name());
    }

    /**
     * Test checking if converter is enabled.
     */
    public function test_is_enabled() {
        $this->resetAfterTest();

        set_config('chromiumpath', '/usr/bin/chromium-browser', 'tool_pdfpages');
        $converter = new converter_chromium();
        $this->assertTrue($converter->is_enabled());
        unset_config('chromiumpath', 'tool_pdfpages');
        $this->assertFalse($converter->is_enabled());
    }

    /**
     * Test validating converter options.
     */
    public function test_validate_options() {
        // Testing a protected method, so we need to setup reflector magic.
        $method = new ReflectionMethod('\tool_pdfpages\converter_chromium', 'validate_options');
        $method->setAccessible(true); // Allow accessing of protected method.

        $converter = new converter_chromium();
        $options = [
            'landscape' => true,
            'printBackground' => true,
            'displayHeaderFooter' => true,
            'headerTemplate' => '<p>Header template</p>',
            'footerTemplate' => '<p>Footer template</p>',
            'paperWidth' => 6.0,
            'paperHeight' => 6.0,
            'marginTop' => 0.0,
            'marginBottom' => 1.4,
            'marginLeft'  => 0.4,
            'marginRight' => 0.4,
            'preferCSSPageSize' => true,
            'scale' => 1.0,
            'afakeoption' => true // Not a valid option.
        ];

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('The PDF page option you selected is not supported: afakeoption');
        $method->invoke($converter, $options);
    }
}