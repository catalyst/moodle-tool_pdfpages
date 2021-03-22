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
 * Tests for login manager.
 *
 * @package    tool_pdfpages
 * @author     Tom Dickman <tomdickman@catalyst-au.net>
 * @copyright  2021 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_pdfpages\key_manager;
use tool_pdfpages\login_manager;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests for login manager.
 *
 * @package    tool_pdfpages
 * @author     Tom Dickman <tomdickman@catalyst-au.net>
 * @copyright  2021 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class login_manager_test extends advanced_testcase {

    /**
     * Test that user session is correctly created with a key login.
     */
    public function test_login_with_key() {
        global $DB, $USER;

        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        // Assign the user a role with the capability to generate PDFs.
        $roleid = $this->getDataGenerator()->create_role();
        assign_capability('tool/pdfpages:generatepdf', CAP_ALLOW, $roleid, context_system::instance());
        $this->getDataGenerator()->role_assign($roleid, $user->id);

        $instance = 123456789123456789;
        $key = key_manager::create_user_key($instance);

        // Check that the key record exists and is for the correct user.
        $record = $DB->get_record('user_private_key', ['script' => 'tool/pdfpages', 'value' => $key]);
        $this->assertEquals($user->id, $record->userid);

        // Emulate using new browser without an existing session or login.
        \core\session\manager::kill_all_sessions();
        $this->setUser();

        login_manager::login_with_key($key, $instance);

        // Login with key should correctly set up session and log in user.
        $this->assertEquals($user->id, $USER->id);
        $this->assertEquals($user->id, $_SESSION['USER']->id);

        // Create a fake key.
        $key = md5($user->id . '_' . time() . random_string(40));

        // Invalid key should not allow login.
        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('Incorrect key');
        login_manager::login_with_key($key, $instance);
    }

}
