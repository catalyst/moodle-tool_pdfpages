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
 * Class for converting Moodle pages to PDFs using wkhtmltopdf.
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
 * Class for converting Moodle pages to PDFs using wkhtmltopdf.
 *
 * @package    tool_pdfpages
 * @author     Tom Dickman <tomdickman@catalyst-au.net>
 * @copyright  2021 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class converter_wkhtmltopdf extends converter {

    /**
     * Converter name.
     */
    protected const NAME = 'wkhtmltopdf';

    /**
     * A list of valid options.
     * For more information see {@link https://wkhtmltopdf.org/usage/wkhtmltopdf.txt}
     */
    protected const VALID_OPTIONS = [
        'collate',
        'no-collate',
        'cookie-jar',
        'copies',
        'dpi',
        'extended-help',
        'grayscale',
        'help',
        'htmldoc',
        'ignore-load-errors',
        'image-dpi',
        'image-quality',
        'license',
        'log-level',
        'lowquality',
        'manpage',
        'margin-bottom',
        'margin-left',
        'margin-right',
        'margin-top',
        'orientation',
        'page-height',
        'page-size',
        'page-width',
        'no-pdf-compression',
        'quiet',
        'read-args-from-stdin',
        'readme',
        'title',
        'use-xserver',
        'version',
        'dump-default-toc-xsl',
        'dump-outline',
        'outline',
        'no-outline',
        'outline-depth',
        'output-format',
        'allow',
        'background',
        'no-background',
        'bypass-proxy-for',
        'cache-dir',
        'checkbox-checked-svg',
        'checkbox-svg',
        'cookie',
        'custom-header',
        'custom-header-propagation',
        'no-custom-header-propagation',
        'debug-javascript',
        'no-debug-javascript',
        'default-header',
        'encoding',
        'disable-external-links',
        'enable-external-links',
        'disable-forms',
        'enable-forms',
        'images',
        'no-images',
        'disable-internal-links',
        'enable-internal-links',
        'disable-javascript',
        'enable-javascript',
        'javascript-delay',
        'keep-relative-links',
        'load-error-handling',
        'load-media-error-handling',
        'disable-local-file-access',
        'enable-local-file-access',
        'minimum-font-size',
        'exclude-from-outline',
        'include-in-outline',
        'page-offset',
        'password',
        'disable-plugins',
        'enable-plugins',
        'post',
        'post-file',
        'print-media-type',
        'no-print-media-type',
        'proxy',
        'proxy-hostname-lookup',
        'radiobutton-checked-svg',
        'radiobutton-svg',
        'redirect-delay',
        'resolve-relative-links',
        'run-script',
        'disable-smart-shrinking',
        'enable-smart-shrinking',
        'ssl-crt-path',
        'ssl-key-password',
        'ssl-key-path',
        'stop-slow-scripts',
        'no-stop-slow-scripts',
        'disable-toc-back-links',
        'enable-toc-back-links',
        'user-style-sheet',
        'username',
        'viewport-size',
        'window-status',
        'zoom',
        'footer-center',
        'footer-font-name',
        'footer-font-size',
        'footer-html',
        'footer-left',
        'footer-line',
        'no-footer-line',
        'footer-right',
        'footer-spacing',
        'header-center',
        'header-font-name',
        'header-font-size',
        'header-html',
        'header-left',
        'header-line',
        'no-header-line',
        'header-right',
        'header-spacing',
        'replace',
        'cover',
        'toc',
        'disable-dotted-lines',
        'toc-depth',
        'toc-font-name',
        'toc-l1-font-size',
        'toc-header-text',
        'toc-header-font-name',
        'toc-header-font-size',
        'toc-level-indentation',
        'disable-toc-links',
        'toc-text-size-shrink',
        'xsl-style-sheet',
    ];

    /**
     * Convert a moodle URL to PDF and store in file system.
     *
     * Note: If the currently logged in user does not have the correct capabilities to view the
     * target URL, the created PDF will most likely be an error page.
     *
     * @param \moodle_url $url the target URL to convert.
     * @param string $key access key to use for user validation, this is required to login user and allow access of target page
     * for conversion {@see \tool_pdfpages\helper::create_user_key}.
     * @param string $filename the name to give converted file.
     * @param array $options any options to be used {@see converter_wkhtmltopdf::VALID_OPTIONS}
     * @param string $cookiename cookie name to apply to conversion (optional).
     * @param string $cookievalue cookie value to apply to conversion (optional).
     *
     * @return \stored_file the stored file created during conversion.
     * @throws \moodle_exception if conversion fails.
     */
    public function convert_moodle_url_to_pdf(moodle_url $url, string $key, string $filename = '', array $options = [],
                                              string $cookiename = '', string $cookievalue = ''): \stored_file {
        try {
            // Close the session to prevent current session from blocking wkthmltopdf headless browser
            // session which, causes a timeout and failed conversion.
            \core\session\manager::write_close();

            $pdf = new Pdf(helper::get_config($this->get_name() . 'path'));
            $pdf->setOptions($options);

            if (!empty($cookiename) && !empty($cookievalue)) {
                $pdf->setOption('cookie', [$cookiename => $cookievalue]);
            }

            // Pass through proxy for user login validation.
            $proxyurl = helper::get_proxy_url($url, $key);
            $content = $pdf->getOutput($proxyurl->out(false));

            if (empty($filename)) {
                $filename = helper::get_moodle_url_pdf_filename($url);
            }

            return $this->create_pdf_file($content, $filename);

        } catch (\Exception $exception) {
            throw new \moodle_exception('error:urltopdf', 'tool_pdfpages', '', null, $exception->getMessage());
        } finally {
            // Destroy the session to prevent token login session hijacking.
            $this->destroy_session();
        }
    }

    /**
     * Validate a list of options.
     *
     * @param array $options
     *
     * @throws \moodle_exception if an option is invalid.
     */
    protected function validate_options(array $options) {
        foreach (array_keys($options) as $option) {
            if (!in_array($option, self::VALID_OPTIONS)) {
                throw new \moodle_exception('error:invalidpageoption', 'tool_pdfpages', '', $option);
            }
        }
    }
}
