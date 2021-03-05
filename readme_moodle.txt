======================
Install third party dependencies for PDF Pages
======================

This plugin includes all third party libs required to run, if you need to install
them manually:

1. Install composer https://getcomposer.org/download/
2. Run `composer install` from the plugin root directory

======================
Updating third party libraries
======================

For developers/contributers, if you need to update third party libs:

1. Install composer https://getcomposer.org/download/ (if not already installed)
2. Run `composer update` from the plugin root directory
3. `git add` all changes in the `vendor` directory, `composer.json` and `composer.lock` files and commit
