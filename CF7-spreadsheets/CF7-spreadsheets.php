<?php
/*
Plugin Name: CF7 Spreadsheets
Plugin URI: https://github.com/moshenskyDV/CF7-spreadsheets
Description: Send Contact form 7 mail to Google spreadsheets
Version: 1.0.1
Author: Moshenskyi Danylo
Author URI: http://itgo-solutions.com
*/

/*  Copyright 2016  Moshenskyi Danylo  (email: moshensky.c1371@yandex.ru)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

require(ABSPATH . 'wp-admin/includes/upgrade.php');
require('include.php');
require('ajax.php');

require 'vendor/autoload.php';
use Google\Spreadsheet\DefaultServiceRequest;
use Google\Spreadsheet\ServiceRequestFactory;


/*main function*/
function CF7spreadsheets_main($cf7)
{

    putenv('GOOGLE_APPLICATION_CREDENTIALS=./' . get_option('CF7spreadsheets_option_filename')); //<----path to JSON

    $client = new Google_Client();
//$client->useApplicationDefaultCredentials();

    try {
        $client->setAuthConfig(plugin_dir_path(__FILE__) . '/' . get_option('CF7spreadsheets_option_filename')); //<----path to JSON
    } catch (Exception $e) {
        // Something went wrong
        echo $e->getMessage();
    }

    $client->setApplicationName("cf7table");
    $client->addScope(Google_Service_Sheets::SPREADSHEETS);
    $client->setRedirectUri('http://' . $_SERVER['HTTP_HOST']);

    $service = new Google_Service_Sheets($client);

    $params_names_arr = $cf7->scan_form_tags();

    $params_values_arr = []; //will get from POST
    foreach ($params_names_arr as $param_names_arr) {
        array_push($params_values_arr, $_POST[$param_names_arr['name']]);
    }

    if (!empty($params_values_arr)) {

// Set the sheet ID
        $fileId = get_option('CF7spreadsheets_option_url'); // Copy & paste from a spreadsheet URL   <-----URL
// Build the CellData array

        $ary_values = [];

        /*datetime in table*/
        if(get_option('CF7spreadsheets_option_time') == 'true'){
            $_date = date('Y-m-d H:i:s', time());
            array_push($ary_values, $_date);
        }

        foreach ($params_values_arr as $param_values_arr) {
            array_push($ary_values, $param_values_arr);
        }

        $values = array();
        foreach ($ary_values AS $d) {
            $cellData = new Google_Service_Sheets_CellData();
            $value = new Google_Service_Sheets_ExtendedValue();
            $value->setStringValue($d);
            $cellData->setUserEnteredValue($value);
            $values[] = $cellData;
        }
// Build the RowData
        $rowData = new Google_Service_Sheets_RowData();
        $rowData->setValues($values);
// Prepare the request
        $append_request = new Google_Service_Sheets_AppendCellsRequest();
        $append_request->setSheetId(get_option('CF7spreadsheets_option_id')); //<-----SHEET ID
        $append_request->setRows($rowData);
        $append_request->setFields('userEnteredValue');
// Set the request
        $request = new Google_Service_Sheets_Request();
        $request->setAppendCells($append_request);
// Add the request to the requests array
        $requests = array();
        $requests[] = $request;
// Prepare the update
        $batchUpdateRequest = new Google_Service_Sheets_BatchUpdateSpreadsheetRequest(array(
            'requests' => $requests
        ));

        try {
            // Execute the request
            $response = $service->spreadsheets->batchUpdate($fileId, $batchUpdateRequest);
            if ($response->valid()) {
                // Success, the row has been added
                $success = true;
            }
        } catch (Exception $e) {
            // Something went wrong
            echo $e->getMessage();
        }
    }

    /*after google send option skip email needed*/
    if (get_option('CF7spreadsheets_option_mail') == 'true') {
        // If you want to skip mailing the data, you can do it...
        $cf7->skip_mail = true;
    }


}

add_action("wpcf7_before_send_mail", "CF7spreadsheets_main");


function CF7spreadsheets_adminmenu()
{
    add_submenu_page(
        'wpcf7',
        'Google Spreadsheets',
        'Google Spreadsheets',
        'activate_plugins',
        'wpcf7-cf7spreadsheet',
        'CF7spreadsheets_print'
    );
}

add_action('admin_menu', 'CF7spreadsheets_adminmenu');

function CF7spreadsheets_print()
{
    /*echo options in admin menu*/ ?>
    <div class="wrap">
        <h1><?php echo __('Google Spreadsheets', 'CF7-spreadsheets'); ?></h1>
        <div class="col-container">
            <div class="form-wrap">
                <form enctype="multipart/form-data" action="#" id="CF7spreadsheets_search_form" method="post">
                    <h3><?php echo __('Google spreadsheet options', 'CF7-spreadsheets'); ?></h3>
                    <div class="form-field form-required">
                        <label for="CF7spreadsheets_option_url"><?php echo __('Spreadsheet URL', 'CF7-spreadsheets'); ?></label>
                        <input type="text" name="CF7spreadsheets_option_url" id="CF7spreadsheets_option_url" value="<?php echo get_option('CF7spreadsheets_option_url'); ?>">
                    </div>
                    <div class="form-field form-required">
                        <label for="CF7spreadsheets_option_id"><?php echo __('Spreadsheet ID', 'CF7-spreadsheets'); ?></label>
                        <input type="text" name="CF7spreadsheets_option_id" id="CF7spreadsheets_option_id" value="<?php echo get_option('CF7spreadsheets_option_id'); ?>">
                    </div>
                    <div class="form-field form-required">
                        <label for="CF7spreadsheets_option_mail"><?php echo __('Send on email too (continue default Contact form action)?', 'CF7-spreadsheets'); ?></label>
                        <input type="checkbox" name="CF7spreadsheets_option_mail" id="CF7spreadsheets_option_mail"
                            <?php if (get_option('CF7spreadsheets_option_mail') == 'true'){ echo 'checked="checked"';} ?>>
                    </div>
                    <div class="form-field form-required">
                        <label for="CF7spreadsheets_option_time"><?php echo __('Send the datetime?', 'CF7-spreadsheets'); ?></label>
                        <input type="checkbox" name="CF7spreadsheets_option_time" id="CF7spreadsheets_option_time"
                            <?php if (get_option('CF7spreadsheets_option_time') == 'true'){ echo 'checked="checked"';} ?>>
                    </div>
                    <div class="form-field form-required">
                        <label for="CF7spreadsheets_option_file"><?php echo __('Upload JSON from API console', 'CF7-spreadsheets'); ?></label>
                        <input type="file" name="CF7spreadsheets_option_file" id="CF7spreadsheets_option_file" value="<?php echo get_option('CF7spreadsheets_option_file'); ?>">
                        <span class="CF7spreadsheets_status"></span>
                        <?php if(get_option('CF7spreadsheets_option_filename')){ ?>
                            <p><?php echo __('Current file: ', 'CF7-spreadsheets'), get_option('CF7spreadsheets_option_filename') ?></p>
                        <?php } ?>
                    </div>
                    <input type="hidden" name="MAX_FILE_SIZE" value="1000000" />
                    <input type="button" id="CF7spreadsheets_option_submit" class="button button-primary" value="<?php echo __('Save', 'CF7-spreadsheets'); ?>">
                    <p class="CF7spreadsheets_response"></p>
                </form>
                <div class="ipcontrol_response"></div>
            </div>
        </div>
    </div>
<?php } ?>