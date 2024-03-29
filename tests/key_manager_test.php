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
 * Tests for key manager.
 *
 * @package    tool_pdfpages
 * @author     Tom Dickman <tomdickman@catalyst-au.net>
 * @copyright  2021 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class key_manager_test extends \advanced_testcase {

    /**
     * Test generating an the instance for a URL.
     */
    public function test_generate_instance_for_url() {
        $url = new \moodle_url('/my/index.php');
        $actual = key_manager::generate_instance_for_url($url);

        // Should have no more that 19 digits, due to field length constraints in DB.
        $this->assertLessThanOrEqual(19, strlen($actual));

        // Should match a SHA1 hash of the unescaped URL converted to base 10 and shortened to fit field constraints.
        $sha1 = sha1($url->out(false));
        $base10 = base_convert($sha1, 16, 10);
        $expected = (int) substr($base10, 0, 18);
        $this->assertIsInt($actual);
        $this->assertEquals($expected, $actual);
    }

    /**
     * Test that user keys are created correctly for URLs.
     */
    public function test_create_user_key_for_url() {
        $this->resetAfterTest();

        set_config('accesskeyttl', 60, 'tool_pdfpages');

        $user = $this->getDataGenerator()->create_user();

        // Assign the user a role with the capability to generate PDFs.
        $roleid = $this->getDataGenerator()->create_role();
        assign_capability('tool/pdfpages:generatepdf', CAP_ALLOW, $roleid, \context_system::instance());
        $this->getDataGenerator()->role_assign($roleid, $user->id);

        $this->setUser($user);

        $url = new \moodle_url('/my/index.php');

        $actual = key_manager::create_user_key_for_url($user->id, $url);

        $instance = key_manager::generate_instance_for_url($url);
        $key = validate_user_key($actual, 'tool/pdfpages', $instance);
        $this->assertEquals('tool/pdfpages', $key->script);
        $this->assertEquals($user->id, $key->userid);
        $this->assertEquals($instance, $key->instance);
    }

    /**
     * Test that URL key cannot be created if user doesn't have capability to create keys.
     */
    public function test_create_key_for_url_no_permission() {
        $this->resetAfterTest();

        set_config('accesskeyttl', 60, 'tool_pdfpages');

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $url = new \moodle_url('/my/index.php');

        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessage('Sorry, but you do not currently have permissions to do that ' .
            '(Generate a PDF from a Moodle URL).');
        key_manager::create_user_key_for_url($user->id, $url);
    }

    /**
     * Test that IP restrictions applied to access keys function correctly.
     */
    public function test_create_user_key_for_url_iprestriction() {
        $this->resetAfterTest();

        set_config('accesskeyttl', 60, 'tool_pdfpages');

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        // Assign the user a role with the capability to generate PDFs.
        $roleid = $this->getDataGenerator()->create_role();
        assign_capability('tool/pdfpages:generatepdf', CAP_ALLOW, $roleid, \context_system::instance());
        $this->getDataGenerator()->role_assign($roleid, $user->id);

        $url = new \moodle_url('/my/index.php');

        $actual = key_manager::create_user_key_for_url($user->id, $url, '123.121.234.0/30');

        // Spoof server remote address matching CIDR IP restriction.
        $_SERVER['REMOTE_ADDR'] = '123.121.234.1';

        // Should only validate if current remote address is within the specified IP restriction range.
        $instance = key_manager::generate_instance_for_url($url);
        $key = validate_user_key($actual, 'tool/pdfpages', $instance);
        $this->assertEquals('tool/pdfpages', $key->script);
        $this->assertEquals($user->id, $key->userid);
        $this->assertEquals($instance, $key->instance);
        $this->assertEquals('123.121.234.0/30', $key->iprestriction);

        // Spoof server remote address not matching CIDR IP restriction.
        $_SERVER['REMOTE_ADDR'] = '123.121.234.4';

        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessage('Client IP address mismatch');
        validate_user_key($actual, 'tool/pdfpages', $instance);
    }

    /**
     * Test that user keys are correctly deleted.
     */
    public function test_delete_user_keys_for_url() {
        global $DB;

        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        // Assign the user a role with the capability to generate PDFs.
        $roleid = $this->getDataGenerator()->create_role();
        assign_capability('tool/pdfpages:generatepdf', CAP_ALLOW, $roleid, \context_system::instance());
        $this->getDataGenerator()->role_assign($roleid, $user->id);

        $url = new \moodle_url('/my/index.php');
        $key = key_manager::create_user_key_for_url($user->id, $url);

        $conditions = [
            'value' => $key,
            'script' => 'tool/pdfpages',
            'instance' => key_manager::generate_instance_for_url($url),
            'userid' => $user->id
        ];

        // Check that key is created in DB.
        $this->assertNotFalse($DB->get_record('user_private_key', $conditions));

        // Key record should be deleted.
        key_manager::delete_user_keys_for_url($user->id, $url);
        $this->assertFalse($DB->get_record('user_private_key', $conditions));
    }
}
