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
 * Class for converting Moodle pages to PDFs using chromium/chrome.
 *
 * @package    tool_pdfpages
 * @author     Tom Dickman <tomdickman@catalyst-au.net>
 * @copyright  2021 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_pdfpages;

use HeadlessChromium\Browser;
use HeadlessChromium\BrowserFactory;
use HeadlessChromium\Cookies\Cookie;
use moodle_url;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/admin/tool/pdfpages/vendor/autoload.php');

/**
 * Class for converting Moodle pages to PDFs using chromium/chrome.
 *
 * @package    tool_pdfpages
 * @author     Tom Dickman <tomdickman@catalyst-au.net>
 * @copyright  2021 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class converter_chromium extends converter {

    /**
     * Converter name.
     */
    protected const NAME = 'chromium';

    /**
     * A list of valid options, keyed by option with value being a description.
     */
    protected const VALID_OPTIONS = [
        'landscape' => '(bool) print PDF in landscape',
        'printBackground' => '(bool) print background colors and images',
        'displayHeaderFooter' => '(bool) display header and footer',
        'headerTemplate' => '(string) HTML template to use as header',
        'footerTemplate' => '(string) HTML template to use as footer',
        'paperWidth' => '(float) paper width in inches',
        'paperHeight' => '(float) paper height in inches',
        'marginTop' => '(float) margin top in inches',
        'marginBottom' => '(float) margin bottom in inches',
        'marginLeft'  => '(float) margin left in inches',
        'marginRight' => '(float) margin right in inches',
        'preferCSSPageSize' => '(bool) read params directly from @page',
        'scale' => '(float) scale the page',
    ];

    /**
     * Convert a moodle URL to PDF and store in file system.
     * Note: If the currently logged in user does not have the correct capabilities to view the
     * target URL, the created PDF will most likely be an error page.
     *
     * @param \moodle_url $url the target URL to convert.
     * @param string $key access key to use for user validation, this is required to login user and allow access of target page
     * for conversion {@see \tool_pdfpages\helper::create_user_key}.
     * @param string $filename the name to give converted file.
     * @param array $options any options to be used {@see converter_chromium::VALID_OPTIONS}
     * @param string $cookiename cookie name to apply to conversion (optional).
     * @param string $cookievalue cookie value to apply to conversion (optional).
     *
     * @return \stored_file the stored file created during conversion.
     * @throws \moodle_exception if conversion fails.
     */
    public function convert_moodle_url_to_pdf(moodle_url $url, string $key, string $filename = '', array $options = [],
                                              string $cookiename = '', string $cookievalue = ''): \stored_file {
        $this->validate_options($options);

        try {
            $browserfactory = new BrowserFactory(helper::get_config($this->get_name() . 'path'));
            $browser = $browserfactory->createBrowser([
                'headless' => true,
                'noSandbox' => true
            ]);

            $page = $browser->createPage();
            if (!empty($cookiename) && !empty($cookievalue)) {
                $page->setCookies([
                    Cookie::create($cookiename, $cookievalue, [
                        'domain' => $url->get_host(),
                        'expires' => time() + DAYSECS
                    ])
                ])->await();
            }

            // Pass through proxy for user login validation.
            $proxyurl = helper::get_proxy_url($url, $key);
            $page->navigate($proxyurl->out(false))->waitForNavigation();
            $pdf = $page->pdf($options);
            $content = base64_decode($pdf->getBase64());

            if (empty($filename)) {
                $filename = helper::get_moodle_url_pdf_filename($url);
            }

            return $this->create_pdf_file($content, $filename);

        } catch (\Exception $exception) {
            throw new \moodle_exception('error:urltopdf', 'tool_pdfpages', '', null, $exception->getMessage());
        } finally {
            // Always close the browser instance to ensure that chromium process is stopped.
            if (!empty($browser) && $browser instanceof Browser) {
                $browser->close();
            }

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
            if (!array_key_exists($option, self::VALID_OPTIONS)) {
                throw new \moodle_exception('error:invalidpageoption', 'tool_pdfpages', '', $option);
            }
        }
    }
}
