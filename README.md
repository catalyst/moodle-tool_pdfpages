![GitHub Workflow Status (branch)](https://img.shields.io/github/actions/workflow/status/catalyst/moodle-tool_pdfpages/ci.yml?branch=VERSION1)

# PDF pages
An API to assist in downloading Moodle internal webpages as PDFs into the Moodle file system using a headless browser.

This plugin will not work by itself without further development work and is instead intended to be leveraged as an API by other Moodle plugins, to download PDFs of Moodle web pages as they appear in browser print engines.

1. [Installation](#installation)
2. [Requirements](#requirements)
3. [Usage](#usage)
4. [Testing Conversion](#testing-conversion)
5. [License](#license)

## Branches

| Moodle version    | Branch            | PHP  | Chrome-PHP |
|-------------------|-------------------|------|------------|
| Moodle 4.1+       | MOODLE_401_STABLE | 8.1+ | 1.11       |
| Moodle 3.9 - 4.1  | VERSION1          | 7.2+ | 0.9.0      |

## Requirements

This plugin requires the following:
- PHP 7.2+

## Installation

There are multiple drivers to use, each requires its own binary in order to access the headless browser for downloading of PDFs. Follow the instructions for whichever driver you would prefer to use. Note that the Qt browser under `wkhtmltopdf` is much older than the `chromium` browser, so the results of downloaded PDFs will vary according to the supported features for each.

### Chromium driver

- Download and install Chromium or Chrome onto your Moodle server.
  - On a debian based linux system this can be achieved by `sudo apt install chromium-browser`.
  - For a debian based linux system running in docker follow the steps below:
    - `wget -q -O - https://dl-ssl.google.com/linux/linux_signing_key.pub | apt-key add - `
    - `sh -c 'echo deb [arch=amd64] http://dl.google.com/linux/chrome/deb/ stable main >> /etc/apt/sources.list.d/google.list'`
    - `apt-get update`
    - `apt-get install -y google-chrome-stable fonts-ipafont-gothic fonts-wqy-zenhei fonts-thai-tlwg fonts-kacst fonts-freefont-ttf libxss1`
    - `php admin/cli/cfg.php --component='tool_pdfpages' --name='chromiumpath' --set='/usr/bin/google-chrome-stable'`
  - For other systems, you'll have to head to https://www.chromium.org/ to find out how to install it.
- Clone or copy this plugin into your Moodle code base:
```bash
git clone git@github.com:catalyst/moodle-tool_pdfpages.git <moodledir>/admin/tool/pdfpages
```
- Log into Moodle instance as admin and change the setting `tool_pdfpages|chromiumpath` to the path to your installed chromium/chrome binary (on a linux system where you installed via `apt` you can find this by using `which chromium-browser`).

### WKHTMLTOPDF driver

- Download and install [wkhtmltopdf](https://wkhtmltopdf.org/) onto your Moodle server.
- Clone or copy this plugin into your Moodle code base:
```bash
git clone git@github.com:catalyst/moodle-tool_pdfpages.git <moodledir>/admin/tool/pdfpages
```
- Upgrade Moodle instance to install plugin.
- Log into Moodle instance as admin and change the setting `tool_pdfpages|wkhtmltopdfpath` to the path to your installed wkhtmltopdf binary (on a Unix like system you can find this by using `which wkhtmltopdf`).

## Usage

Use of the converter requires programmatic access, there in no frontend associated with this plugin, so you need to develop another module, or add this plugin to the dependencies of an existing Moodle plugin.

> Only users with the system level capability `tool/pdfpages:generatepdf` can conducted conversions, as this is required to create the single use access key for the headless browser session.

- Create a converter instance using the factory passing in a converter name (`chromium` or `wkhtmltopdf`) or you can leave it empty to grab the first enabled converter found (if no converters are configured correctly, an exception will be thrown):
```php
$converter = converter_factory::get_converter('chromium');
```
- Pass a Moodle URL instance into the converter along with the desired filename to create the PDF file and return a `\stored_file` instance for that file:
```php
$url = new \moodle_url('course/view.php', ['id' => 1337]);
$file = $converter->convert_moodle_url_to_pdf($url, 'course1337.pdf');
```
__Note__: You can omit the `$filename` param and instead, a SHA1 hash of the URL will be used as the filename, with the `.pdf` extension concatenated to the end.
- If you want to see the PDF rendered in the browser, send it to the browser:
```php
send_file($file, $file->get_filename());
```
- To fetch a previously created PDF for a URL by a converter (if no conversion record exists for the URL in question, `false` will be returned):
```php
$file = $converter->get_converted_moodle_url_pdf('course1337.pdf');
```
__Note__: if you didn't specify a filename when converting, you can obtain the filename using the helper function `helper::get_moodle_url_pdf_filename($url)` passing in the Moodle URL.

## Testing Conversion

In order to test how a URL will be converted and see the outcome, you can utilise the `/admin/tool/pdfpages/test.php` page in your browser.
This will utilise the configured converter on the server side to carry out the conversion, creating the converted file in the Moodle file system and then serve up the PDF to the browser.

In order to access this page, the logged in Moodle user needs to be an Admin or have a role with the capability `tool/pdfpages:generatepdf` at the system level.

This page takes the following query parameters:
- url: (required) the ASCII encoded target URL (may be absolute URL or relative Moodle URL)
- converter: (optional) the converter name to use
- filename: (optional) the filename to give the converted PDF
- options: (optional) a JSON encoded string of converter options (see the relevant converter's docs for more information)

For example:
https://mymoodle.com/admin/tool/pdfpages/test.php?url=%2Fcourse%2Fview.php%3Fid%3D2&converter=chromium&filename=test.pdf&options={"landscape":true}

This allows you to test any conversion candidates and see how well they translate into a PDF or whether alterations might need to be made, such as CSS changes to the page.

## License

2021 Catalyst IT Australia

This program is free software: you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation, either version 3 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with
this program.  If not, see <http://www.gnu.org/licenses/>.


This plugin was developed by Catalyst IT Australia:

https://www.catalyst-au.net/

<img alt="Catalyst IT" src="https://raw.githubusercontent.com/catalyst/moodle-local_smartmedia/master/pix/catalyst-logo.svg?sanitize=true" width="400">
