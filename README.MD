# CF7 Spreadsheets

Wordpress plugin that merge Contact form 7 functional with google spreadsheets (also works with private spreadsheet).
 
 [![WordPress plugin](https://img.shields.io/wordpress/plugin/dt/cf7-spreadsheets.svg)](https://wordpress.org/plugins/cf7-spreadsheets/advanced) 
[![WordPress plugin](https://img.shields.io/wordpress/plugin/installs/cf7-spreadsheets.svg)](https://wordpress.org/plugins/cf7-spreadsheets/advanced)
 [![Bitcoin wallet statistic](https://img.shields.io/badge/dynamic/json.svg?label=donations&url=https%3A%2F%2Fblockexplorer.com%2Fapi%2Faddr%2F126PYVmUc6X4BSqFckkwicJ8dJtCC9fCyL&query=%24.totalReceived&colorB=orange&suffix=%20BTC)](https://www.blockchain.com/btc/address/126PYVmUc6X4BSqFckkwicJ8dJtCC9fCyL)

![Travis (.org)](https://img.shields.io/travis/moshenskyDV/CF7-spreadsheets.svg?style=popout)
[![WordPress plugin](https://img.shields.io/wordpress/plugin/v/cf7-spreadsheets.svg?label=version)](https://wordpress.org/plugins/cf7-spreadsheets/#developers)

Donation address (BTC): `126PYVmUc6X4BSqFckkwicJ8dJtCC9fCyL`

### Installation

1. Upload plugin folder `CF7 -sreadsheets` to `wp-content/plugins` directory
2. Open wordpress admin tool and activate the plugin at `plugins` menu

### How to

Video instruction: [https://www.youtube.com/watch?v=ZgZRBByY4fc](https://www.youtube.com/watch?v=ZgZRBByY4fc)

Video instruction (OLD versions): [https://www.youtube.com/watch?v=5ICWr5MMo7E](https://www.youtube.com/watch?v=5ICWr5MMo7E)

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
