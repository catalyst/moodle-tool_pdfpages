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
 * Abstract converter class tests for tool_pdfpages.
 *
 * @package    tool_pdfpages
 * @author     Tom Dickman <tomdickman@catalyst-au.net>
 * @copyright  2021 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use PHPUnit\Framework\MockObject\MockObject;
use tool_pdfpages\converter;

defined('MOODLE_INTERNAL') || die();

/**
 * Abstract converter class tests for tool_pdfpages.
 *
 * @package    tool_pdfpages
 * @author     Tom Dickman <tomdickman@catalyst-au.net>
 * @copyright  2021 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class converter_test extends advanced_testcase {

    /**
     * Create a mock converter.
     *
     * @param string $pdfcontent content to get returned by `generate_pdf_content` method in mock.
     *
     * @return MockObject the mock converter.
     */
    protected function create_mock_converter(string $pdfcontent) {
        $mock = $this->createMock(converter::class);

        // Mock the abstract PDF generation method.
        $mock->method('generate_pdf_content')
            ->willReturn($pdfcontent);

        // Mock the PDF file creation.
        $filerecord = [
            'contextid' => \context_system::instance()->id,
            'component' => 'tool_pdfpages',
            'filearea' => 'pdf',
            'itemid' => 0,
            'filepath' => "/base/",
            'filename' => 'test.pdf'
        ];
        $fs = get_file_storage();
        $file = $fs->create_file_from_string($filerecord, $pdfcontent);
        $mock->method('create_pdf_file')
            ->willReturn($file);

        return $mock;
    }

    /**
     * Test that converter always destroys access key session after conversion.
     */
    public function test_convert_moodle_url_to_pdf_session_termination() {
        global $SESSION, $USER;

        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        // Assign the user a role with the capability to generate PDFs.
        $roleid = $this->getDataGenerator()->create_role();
        assign_capability('tool/pdfpages:generatepdf', CAP_ALLOW, $roleid, context_system::instance());
        $this->getDataGenerator()->role_assign($roleid, $user->id);

        // Check that session is created for logged in user before conversion.
        $this->assertEquals($user->id, $USER->id);
        $this->assertEquals($user->id, $_SESSION['USER']->id);

        $mock = $this->create_mock_converter('Test PDF content');

        $url = new moodle_url('/');
        $mock->convert_moodle_url_to_pdf($url);

        // User session should be destroyed following conversion.
        $this->assertEquals(0, $USER->id);
        $this->assertEmpty((array) $SESSION);
        $this->assertEquals(0, $_SESSION['USER']->id);
    }

    /**
     * Test that converter does not destroy access key session after conversion when `keepsession`
     * parameter is set.
     */
    public function test_convert_moodle_url_to_pdf_keep_session() {
        global $USER;

        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        // Assign the user a role with the capability to generate PDFs.
        $roleid = $this->getDataGenerator()->create_role();
        assign_capability('tool/pdfpages:generatepdf', CAP_ALLOW, $roleid, context_system::instance());
        $this->getDataGenerator()->role_assign($roleid, $user->id);

        // Check that session is created for logged in user before conversion.
        $this->assertEquals($user->id, $USER->id);
        $this->assertEquals($user->id, $_SESSION['USER']->id);

        $mock = $this->create_mock_converter('Test PDF content');

        $url = new moodle_url('/');
        $mock->convert_moodle_url_to_pdf($url, '', [], true);

        // User session should not be destroyed following conversion with `keepsession` flag set.
        $this->assertEquals($user->id, $USER->id);
        $this->assertEquals($user->id, $_SESSION['USER']->id);
    }
}
