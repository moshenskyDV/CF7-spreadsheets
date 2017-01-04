# CF7 Spreadsheets

Wordpress plugin that merge Contact form 7 functional with google spreadsheets (also works with private spreadsheet).

### Installation

1. Upload plugin folder `CF7 -sreadsheets` to `wp-content/plugins` directory
2. Open wordpress admin tool and activate the plugin at `plugins` menu

### How to

1. Prepare API
    * Go to the [Console Developers API](https://console.developers.google.com/)
    * Enable Sheets API (in `library` tab)
    * Create new service account
    * Go to `Credentials` tab and click `create credentials`. In dropdown list choose `Service account key`. In next window select you service account, and `Key type` to `JSON`.
    * Save the json document
2. Create a table
    * Go to the [Google Spreadsheets](https://docs.google.com/spreadsheets/) and create new table (or open exists table)
    * Copy table URL and ID. For example: `https://docs.google.com/spreadsheets/d/1yhzO1Q6ikYysfg8LCHqegPM0CI_NtZwZMI7PZTo33z8/edit#gid=0` in this table URL is: `1yhzO1Q6ikYysfg8LCHqegPM0CI_NtZwZMI7PZTo33z8`, and ID is: `0`(parameter `gid`).
3. Paste the spreadsheet URL and ID in plugin options page, upload JSON file, and choose other options. ![CF7-spreadshhets](http://i.piccy.info/i9/6c4ae328f23084143e0d749af2238245/1483544368/52269/1095319/Screenshot_from_2017_01_04_17_39_07.png)
4. Create usual form in `Contact form 7` and use it.

### Changelog

1. v 1.0.0. - beta
    * default functional
2. v 1.0.1. - release
    * Added translate to russian language
    * Added readme