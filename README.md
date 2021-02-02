# PDF pages
Download webpages as PDFs in Moodle using wkhtmltopdf.

1. [Installation](#installation)
2. [License](#license)

## Installation

- Download and install [wkhtmltopdf](https://wkhtmltopdf.org/) onto your Moodle server.
- Clone or copy this plugin into your Moodle code base:
```bash
git clone git@github.com:catalyst/moodle-tool_pdfpages.git <moodledir>/admin/tool/pdfpages
```
- Upgrade Moodle instance to install plugin.
- Log into Moodle instance as admin and change the setting `tool_pdfpages|pathtowkhtmltopdf` to the path to your installed wkhtmltopdf binary (on a Unix like system you can find this by using `which wkhtmltopdf`).

## License ##

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
