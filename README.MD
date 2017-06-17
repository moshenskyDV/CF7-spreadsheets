# CF7 Spreadsheets

Wordpress plugin that merge Contact form 7 functional with google spreadsheets (also works with private spreadsheet).

### Installation

1. Upload plugin folder `CF7 -sreadsheets` to `wp-content/plugins` directory
2. Open wordpress admin tool and activate the plugin at `plugins` menu

### How to

Video instruction: [https://www.youtube.com/watch?v=5ICWr5MMo7E](https://www.youtube.com/watch?v=5ICWr5MMo7E)

1. Prepare API
    * Go to the [Console Developers API](https://console.developers.google.com/)
    * Enable Sheets API (in `library` tab)
    * Create new service account
    * Go to `Credentials` tab and click `create credentials`. In dropdown list choose `Service account key`. In next window select you service account, and `Key type` to `JSON`.
    * Save the json document
2. Create a table
    * Go to the [Google Spreadsheets](https://docs.google.com/spreadsheets/) and create new table (or open exists table)
    * Copy table URL and ID. For example: `//docs.google.com/spreadsheets/d/1yhzO1Q6ikYysfg8LCHqegPM/edit#gid=0` in this table URL is: `1yhzO1Q6ikYysfg8LCHqegPM`, and ID is: `0`(parameter `gid`).
3. Paste the spreadsheet URL and ID in plugin options page, upload JSON file, and choose other options.
4. Create usual form in `Contact form 7` and use it.

### Changelog

1. v 1.0.0 - beta
    * default functional
2. v 1.0.1 - release
    * Added translate to russian language
    * Added readme
3. v 1.0.2
    * Added readme.txt and assets
4. v 1.0.3
    * Security fixes
5. v 1.0.4
    * Compatibility with other plugins, that use google account fixed
    * Notice added
    * RU Translation updated
6. v 2.0.0
    * Rewrite in OOP style
    * Added configuring output row
    * Added forms choose to use
    * Fixed bug with checkbox (default action CF7)
    * New user interface
7. v 2.0.1
    * Fixed removing plugin data from database on deactivation
8. v 2.0.2
    * Fixed checkbox and multiselect results
    * Removed placeholders at empty fields