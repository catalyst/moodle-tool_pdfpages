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

        set_config('wkhtmltopdfpath', '/usr/local/bin/wkhtmltopdf', 'tool_pdfpages');
        $this->assertEquals('/usr/local/bin/wkhtmltopdf', helper::get_config('wkhtmltopdfpath'));
        unset_config('wkhtmltopdfpath', 'tool_pdfpages');
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("No configured tool_pdfpages setting 'wkhtmltopdfpath'.");
        helper::get_config('wkhtmltopdfpath');
    }

    /**
     * Test getting the names of installed converters.
     */
    public function test_get_installed_converters() {
        $this->resetAfterTest();

        set_config('wkhtmltopdfpath', '/usr/local/bin/wkhtmltopdf', 'tool_pdfpages');
        $this->assertEquals(['wkhtmltopdf'], helper::get_installed_converters());

        unset_config('wkhtmltopdfpath', 'tool_pdfpages');
        $this->assertEmpty(helper::get_installed_converters());
    }

    /**
     * Test getting the filearea for a Moodle URL's converted PDF.
     */
    public function test_get_moodle_url_pdf_filearea() {
        $this->assertEquals('pdf', helper::get_moodle_url_pdf_filearea());
    }

    /**
     * Test getting the filename for a Moodle URL's converted PDF.
     */
    public function test_get_moodle_url_pdf_filename() {
        $testurl = 'https://www.nonesuch.com/some/path.index.html?id=55&test=value';

        // The filename for a Moodle URL PDF should be a SHA1 hash of the non-encoded URL string
        // proceeded by the '.pdf' file extension.
        $expected = sha1($testurl) . '.pdf';

        $url = new moodle_url($testurl);
        $this->assertEquals($expected, helper::get_moodle_url_pdf_filename($url));
    }

    /**
     * Test getting a file record for a converted URL PDF.
     */
    public function test_get_pdf_filerecord() {
        $this->resetAfterTest();

        $testurl = 'https://www.nonesuch.com/some/path.index.html?id=55&test=value';
        $url = new moodle_url($testurl);

        set_config('wkhtmltopdfpath', '/usr/local/bin/wkhtmltopdf', 'tool_pdfpages');

        $actual = helper::get_pdf_filerecord($url, 'wkhtmltopdf');
        $this->assertCount(6, $actual);
        $this->assertIsArray($actual);
        $this->assertEquals(\context_system::instance()->id, $actual['contextid']);
        $this->assertEquals('tool_pdfpages', $actual['component']);
        $this->assertEquals('pdf', $actual['filearea']);
        $this->assertEquals(0, $actual['itemid']);
        $this->assertEquals('/wkhtmltopdf/', $actual['filepath']);
        $this->assertEquals(sha1($url->out(false)) . '.pdf', $actual['filename']);

        unset_config('wkhtmltopdfpath', 'tool_pdfpages');
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Cannot get fileinfo for 'wkhtmltopdf' converter, not installed or invalid.");
        helper::get_pdf_filerecord($url, 'wkhtmltopdf');
    }
}
