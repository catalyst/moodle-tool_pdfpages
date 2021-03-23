# moodle-tool_pdfpages

## Changes

### Version 2021031701

- Breaking changes to `converter::convert_moodle_url_to_pdf`, removed the `$key` parameter and abstracted access key generation into the method itself.

### Version 2021032301

- Breaking changes to `converter::convert_moodle_url_to_pdf`, added new `$keepsession` parameter to allow for optional switching off of session termination following conversion.
