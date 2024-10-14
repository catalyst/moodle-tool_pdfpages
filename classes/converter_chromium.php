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

use HeadlessChromium\Browser;
use HeadlessChromium\BrowserFactory;
use HeadlessChromium\Cookies\Cookie;
use HeadlessChromium\Page;
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
        'windowSize' => '(array) The size of the browser window, e.g. [1920, 1080].',
        'userAgent' => '(string) The custom user agent to use when navigating the page.',
        'jsCondition' => '(string) A JavaScript condition to be evaluated, specified as a string.
            It should return a boolean value indicating whether the condition has been met',
        'jsConditionParams' => '(array) An array of parameters to pass to the Javascript function.',
        'customFlags' => '(array) An array of flags to pass to the BrowserFactory class.'
    ];

    /**
     * Generate the PDF content of a target URL passed through proxy URL.
     *
     * @param \moodle_url $proxyurl the plugin proxy url for access key login and redirection to target URL.
     * @param string $filename the name to give converted file.
     * @param array $options any additional options to pass to converter, valid options vary with converter
     * instance, see relevant converter for further details.
     * @param string $cookiename cookie name to apply to conversion (optional).
     * @param string $cookievalue cookie value to apply to conversion (optional).
     *
     * @return string raw PDF content of URL.
     */
    protected function generate_pdf_content(moodle_url $proxyurl, string $filename = '', array $options = [],
                                            string $cookiename = '', string $cookievalue = ''): string {
        try {
            $browseroptions = [
                'headless' => true,
                'noSandbox' => true,
            ];

            if (isset($options['windowSize'])) {
                $browseroptions['windowSize'] = $options['windowSize'];
            }

            if (isset($options['customFlags'])) {
                $browseroptions['customFlags'] = $options['customFlags'];
            }

            $browserfactory = new BrowserFactory(helper::get_config($this->get_name() . 'path'));
            $browser = $browserfactory->createBrowser($browseroptions);

            $page = $browser->createPage();
            if (!empty($cookiename) && !empty($cookievalue)) {
                $page->setCookies([
                    Cookie::create($cookiename, $cookievalue, [
                        'domain' => urldecode($proxyurl->get_param('url')),
                        'expires' => time() + DAYSECS
                    ])
                ])->await();
            }

            if (isset($options['userAgent'])) {
                $page->setUserAgent($options['userAgent']);
            }

            // Wait until the page reaches a "network idle" state, which means that no more active
            // network requests such as images or scripts are pending. A 60-second timeout is set to
            // prevent it from hanging indefinitely if the network takes too long to become idle.
            $page->navigate($proxyurl->out(false))->waitForNavigation(PAGE::NETWORK_IDLE, 60000);

            $timeout = 1000 * helper::get_config($this->get_name() . 'responsetimeout');

            $jscondition = isset($options['jsCondition']) ? $options['jsCondition'] : null;
            $jsconditionparams = isset($options['jsConditionParams']) ? $options['jsConditionParams'] : [];
            $this->wait_for_js_condition($page, $jscondition, $jsconditionparams, $timeout);

            $pdfoptions = array_filter($options, function($option) {
                $renderoptions = ['windowSize', 'userAgent', 'jsCondition', 'jsconditionparams', 'customFlags'];
                return !in_array($option, $renderoptions);
            }, ARRAY_FILTER_USE_KEY);

            $pdf = $page->pdf($pdfoptions);

            return base64_decode($pdf->getBase64($timeout));
        } finally {
            // Always close the browser instance to ensure that chromium process is stopped.
            if (!empty($browser) && $browser instanceof Browser) {
                $browser->close();
            }
        }
    }

    /**
     * Wait for a JavaScript condition on the page to be true, within a specified timeout.
     *
     * This function will repeatedly evaluate the provided JavaScript condition with a 0.5 second
     * pause between checks, until it returns true or the timeout is exceeded.
     *
     * @param Page $page The current browser page.
     * @param string|null $jscondition The JavaScript condition to be evaluated. This should be a function as a string,
     * and should return a boolean value indicating whether the condition has been met.
     * @param array $jsconditionparams An array of parameters to pass to the Javascript function.
     * @param int $timeout The maximum time to wait for the MathJax to finish processing, in milliseconds.
     * Defaults to 30000ms (30 seconds).
     * @throws \moodle_exception If the JavaScript condition does not finish within the specified timeout.
     */
    protected function wait_for_js_condition(Page $page, ?string $jscondition = null, array $jsconditionparams = [],
            int $timeout = 30000): void {

        if (empty($jscondition)) {
            return;
        }

        $isconditionmet = false;
        $starttime = microtime(true);

        while (!$isconditionmet) {
            $evaluation = $page->callFunction($jscondition, $jsconditionparams);

            if ($evaluation->getReturnValue() === true) {
                $isconditionmet = true;
            } else {
                // Wait 0.5 seconds before rechecking.
                usleep(500000);

                // Calculate elapsed time and check if timeout is exceeded.
                $elapsedtime = (microtime(true) - $starttime) * 1000;
                if ($elapsedtime >= $timeout) {
                    throw new \moodle_exception('error:mathjaxtimeout', 'tool_pdfpages');
                }
            }
        }
    }

    /**
     * Validate a list of options.
     *
     * @param array $options any additional options to pass to conversion.
     * {@see \tool_pdfpages\converter_chromium::VALID_OPTIONS}
     *
     * @return array validated options.
     */
    protected function validate_options(array $options): array {
        $validoptions = [];

        foreach ($options as $option => $value) {
            if (array_key_exists($option, self::VALID_OPTIONS)) {
                $validoptions[$option] = $value;
            }
        }

        return $validoptions;
    }
}
