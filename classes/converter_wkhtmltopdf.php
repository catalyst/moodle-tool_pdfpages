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
 * Class for converting Moodle pages to PDFs.
 *
 * @package    tool_pdfpages
 * @author     Tom Dickman <tomdickman@catalyst-au.net>
 * @copyright  2021 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_pdfpages;

use moodle_url;
use Knp\Snappy\Pdf;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/admin/tool/pdfpages/vendor/autoload.php');

/**
 * Class for converting Moodle pages to PDFs.
 *
 * @package    tool_pdfpages
 * @author     Tom Dickman <tomdickman@catalyst-au.net>
 * @copyright  2021 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class converter_wkhtmltopdf implements converter {

    /**
     * Convert a moodle URL to PDF and store in file system.
     *
     * Note: If the currently logged in user does not have the correct capabilities to view the
     * target URL, the created PDF will most likely be an error page.
     *
     * @param \moodle_url $url the target URL to convert.
     *
     * @return \stored_file the stored file created during conversion.
     * @throws \moodle_exception if conversion fails.
     */
    public function convert_moodle_url_to_pdf(moodle_url $url, array $options = []) : \stored_file {
        try {
            // Close the session to prevent current session from blocking wkthmltopdf headless browser
            // session which, causes a timeout and failed conversion.
            \core\session\manager::write_close();

            $cookiename = session_name();
            $cookie = $_COOKIE[$cookiename];
            $path = helper::get_config('wkhtmltopdfpath');

            $pdf = new Pdf($path);
            $pdf->setOptions($options);
            $pdf->setOption('cookie', [$cookiename => $cookie]);

            $fileinfo = [
                'contextid' => \context_system::instance()->id,
                'component' => 'tool_pdfpages',
                'filearea' => helper::get_moodle_url_pdf_filearea(),
                'itemid' => 0,
                'filepath' => '/',
                'filename' => helper::get_moodle_url_pdf_filename($url),
            ];

            $fs = get_file_storage();
            $existingfile = $fs->get_file(...array_values($fileinfo));

            // If the file already exists, it needs to be deleted, as otherwise the new filename will collide
            // with existing filename and the new file will not be able to be created.
            if (!empty($existingfile)) {
                $existingfile->delete();
            }

            $fs->create_file_from_string($fileinfo, $pdf->getOutput($url->out(false)));
            $file = $fs->get_file(...array_values($fileinfo));

            return $file;

        } catch (\Exception $exception) {
            throw new \moodle_exception('error:urltopdf', 'tool_pdfpages', '', null, $exception->getMessage());
        }
    }

    /**
     * Get the converted PDF for a Moodle URL if it exists.
     *
     * @param \moodle_url $url the target URL to get converted PDF for.
     *
     * @return bool|\stored_file the stored file PDF, false if Moodle URL has not been converted to PDF.
     */
    public function get_converted_moodle_url_pdf(moodle_url $url) {
        $fs = get_file_storage();

        $fileinfo = [
            'contextid' => \context_system::instance()->id,
            'component' => 'tool_pdfpages',
            'filearea' => helper::get_moodle_url_pdf_filearea(),
            'itemid' => 0,
            'filepath' => '/',
            'filename' => helper::get_moodle_url_pdf_filename($url),
        ];

        return $fs->get_file(...array_values($fileinfo));
    }
}
