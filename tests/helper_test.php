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
     * Test that user keys are created correctly.
     */
    public function test_create_user_key() {
        $this->resetAfterTest();

        set_config('accesskeyttl', 60, 'tool_pdfpages');

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $actual = helper::create_user_key();

        $key = validate_user_key($actual, 'tool/pdfpages', null);
        $this->assertEquals('tool/pdfpages', $key->script);
        $this->assertEquals($user->id, $key->userid);

        $this->setUser();
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Cannot create a user key when not logged in as a user.');
        helper::create_user_key();
    }

    /**
     * Test that IP restrictions applied to access keys function correctly.
     */
    public function test_create_user_key_iprestriction() {
        $this->resetAfterTest();

        set_config('accesskeyttl', 60, 'tool_pdfpages');

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $actual = helper::create_user_key('123.121.234.0/30');

        // Spoof server remote address matching CIDR IP restriction.
        $_SERVER['REMOTE_ADDR'] = '123.121.234.1';

        // Should only validate if current remote address is within the specified IP restriction range.
        $key = validate_user_key($actual, 'tool/pdfpages', null);
        $this->assertEquals('tool/pdfpages', $key->script);
        $this->assertEquals($user->id, $key->userid);
        $this->assertEquals('123.121.234.0/30', $key->iprestriction);

        // Spoof server remote address not matching CIDR IP restriction.
        $_SERVER['REMOTE_ADDR'] = '123.121.234.4';

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('Client IP address mismatch');
        validate_user_key($actual, 'tool/pdfpages', null);
    }

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

        $filename = 'test.pdf';

        set_config('wkhtmltopdfpath', '/usr/local/bin/wkhtmltopdf', 'tool_pdfpages');

        $actual = helper::get_pdf_filerecord($filename, 'wkhtmltopdf');
        $this->assertCount(6, $actual);
        $this->assertIsArray($actual);
        $this->assertEquals(\context_system::instance()->id, $actual['contextid']);
        $this->assertEquals('tool_pdfpages', $actual['component']);
        $this->assertEquals('pdf', $actual['filearea']);
        $this->assertEquals(0, $actual['itemid']);
        $this->assertEquals('/wkhtmltopdf/', $actual['filepath']);
        $this->assertEquals('test.pdf', $actual['filename']);

        unset_config('wkhtmltopdfpath', 'tool_pdfpages');
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Cannot get fileinfo for 'wkhtmltopdf' converter, not installed and/or enabled.");
        helper::get_pdf_filerecord('test.pdf', 'wkhtmltopdf');
    }

    /**
     * Test checking if a converter is enabled.
     */
    public function test_is_converter_enabled() {
        $this->resetAfterTest();

        set_config('wkhtmltopdfpath', '/usr/local/bin/wkhtmltopdf', 'tool_pdfpages');
        $this->assertTrue(helper::is_converter_enabled('wkhtmltopdf'));

        unset_config('wkhtmltopdfpath', 'tool_pdfpages');
        $this->assertFalse(helper::is_converter_enabled('wkhtmltopdf'));
    }

    /**
     * Test that proxy URL is built correctly.
     */
    public function test_get_proxy_url() {
        $this->resetAfterTest();

        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();

        $url = new moodle_url("/course/view.php?id={$course->id}");
        $key = helper::create_user_key();

        $actual = helper::get_proxy_url($url, $key);
        $this->assertInstanceOf(moodle_url::class, $actual);
        $this->assertEquals($url->out(), $actual->get_param('url'));
        $this->assertEquals($key, $actual->get_param('key'));
    }

    /**
     * Test that user session is correctly created with a key login.
     */
    public function test_login_with_key() {
        global $DB, $USER;

        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $key = helper::create_user_key();

        // Check that the key record exists and is for the correct user.
        $record = $DB->get_record('user_private_key', ['script' => 'tool/pdfpages', 'value' => $key]);
        $this->assertEquals($user->id, $record->userid);

        // Emulate using new browser without an existing session or login.
        \core\session\manager::kill_all_sessions();
        $this->setUser();

        helper::login_with_key($key);

        // Login with key should correctly set up session and log in user.
        $this->assertEquals($user->id, $USER->id);
        $this->assertEquals($user->id, $_SESSION['USER']->id);

        // Create a fake key.
        $key = md5($user->id . '_' . time() . random_string(40));

        // Invalid key should not allow login.
        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('Incorrect key');
        helper::login_with_key($key);
    }
}
