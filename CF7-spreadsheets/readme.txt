=== Plugin Name ===
Contributors: Moshenskyi Danylo
Tags: contact form 7, google, spreadsheets, table, data, merge, save mail
Requires at least: 4.7
Tested up to: 5.3.2
Stable tag: 2.2.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Wordpress plugin that merge Contact form 7 functional with google spreadsheets (also works with private spreadsheet).

== Description ==

Wordpress plugin that merge Contact form 7 functional with google spreadsheets (also works with private spreadsheet).

How to use:

Video instruction: [https://www.youtube.com/watch?v=ZgZRBByY4fc](https://www.youtube.com/watch?v=ZgZRBByY4fc)
Video instruction (OLD versions): [https://www.youtube.com/watch?v=5ICWr5MMo7E](https://www.youtube.com/watch?v=5ICWr5MMo7E)

1. Prepare API
    * Go to the [Console Developers API](https://console.developers.google.com/)
    * Create new project (or choose existing one)
    * Click "enable API and services", or click on "library" tab
    * Find in list "Google sheets API" and click "enable"
    * Go to `Credentials` tab and click `create credentials`. In dropdown list choose `Service account key`. In next window select you service account, and `Key type` to `JSON`.
    * Save the JSON document
    * Upload JSON document on plugin page (second tab).
2. Create a table
    * Go to the [Google Spreadsheets](https://docs.google.com/spreadsheets/) and create new table (or open exists table)
    * Copy table URL and ID. For example: `//docs.google.com/spreadsheets/d/1yhzO1Q6ikYysfg8LCHqegPM/edit#gid=0` in this table URL is: `1yhzO1Q6ikYysfg8LCHqegPM`, and ID is: `0`(parameter `gid`).
    * If you have private spreadsheet - you should grant edit rights to your service account (that we create earlier) directly in spreadsheet. You could find service account email in your website admin panel after uploading JSON file.
3. Paste the spreadsheet URL and ID in plugin options page, upload JSON file, and choose other options.
4. Create usual form in `Contact form 7` and use it.

== Installation ==

1. Upload plugin folder `CF7-spreadsheets` to `wp-content/plugins` directory
2. Open wordpress admin tool and activate the plugin at `plugins` menu

== Frequently Asked Questions ==

= Infinite wheel on mail send =

Probably, you not enable “Sheets API” on Google developers console. [Follow this link](https://console.developers.google.com/apis/api/sheets.googleapis.com/), and click “Enable”. Or you didn't grant writable access to your service account.

= Mail successfully send, but I don't see result on google table... =

Please check, that service account have access to write to your table.

= Where I can give spreadsheet ID and URL? =

Open table in browser, this parameters will in address string of your browser.

== Screenshots ==

1. Table options page
2. Output page

== Changelog ==

= 2.2.2 =

* Fixed regexp, responsible for parsing text message (multiline tags and couple of tags in one line works well now)
* Added [pipes feature](https://contactform7.com/selectable-recipient-with-pipes/)

= 2.2.0 =

* Added composer config with package list
* Updated dependencies
* Added travis.ci to project
* Added linter (php-cs-fixer) too project files
* Fixed hidden field

= 2.1.2 =

* Added data types (Number, Boolean, Formula, String)

= 2.1.1 =

* Added shortcodes support
* Fixed duplicate require upgrade.php file

= 2.1.0 =

* Added CF7 special tags

= 2.0.3 =

* Posts limit changed to unlimited

= 2.0.2 =

* Fixed checkbox and multiselect results
* Removed placeholders at empty fields

= 2.0.1 =

* Fixed removing plugin data from database on deactivation

= 2.0.0 =

* Rewrite in OOP style
* Added configuring output row
* Added forms choose to use
* Fixed bug with checkbox (default action CF7)
* New user interface

= 1.0.4 =

* Compatibility with other plugins, that use google account fixed
* Notice added
* RU Translation updated

= 1.0.3 =

* Security fixes

= 1.0.2 =

* Added readme.txt and assets

= 1.0.1 =
* Added translate to russian language
* Added readme

= 1.0.0 =
* Alpha version

== Upgrade Notice ==

= 2.1.2 =

Column data types (can send formulas to google sheets).

= 2.0.1 =

Small fix.

= 2.0.0 =

Major update. New interface, few new important features.

= 1.0.4 =

Important fix for compatibility with other plugins (required moved inside mani function). Notices.

= 1.0.3 =

Security fixes.

= 1.0.2 =

Added readme.txt and assets. Not important update.

= 1.0.1 =
Added translate to russian language. Added readme.

= 1.0.0 =
Alpha version.
