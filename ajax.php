<?php

function CF7spreadsheets_update()
{
    if (isset($_POST['CF7spreadsheets_option_url']) && isset($_POST['CF7spreadsheets_option_id']) && isset($_POST['CF7spreadsheets_option_file']) && isset($_POST['CF7spreadsheets_option_file_name'])) {
        update_option('CF7spreadsheets_option_url', $_POST['CF7spreadsheets_option_url']);
        update_option('CF7spreadsheets_option_id', $_POST['CF7spreadsheets_option_id']);
        update_option('CF7spreadsheets_option_mail', $_POST['CF7spreadsheets_option_mail']);
        update_option('CF7spreadsheets_option_time', $_POST['CF7spreadsheets_option_time']);

        /*if file come - update file*/
        if (!get_option('CF7spreadsheets_option_filename') || !empty($_POST['CF7spreadsheets_option_file_name']) && $_POST['CF7spreadsheets_option_file_name'] != get_option('CF7spreadsheets_option_filename')) {
            update_option('CF7spreadsheets_option_filename', $_POST['CF7spreadsheets_option_file_name']);

            if (!file_put_contents(plugin_dir_path(__FILE__) . $_POST['CF7spreadsheets_option_file_name'], stripcslashes($_POST['CF7spreadsheets_option_file']))) {
                echo "<div class='CF7spreadsheets_error_ajax'>Error file write.</div>";
                wp_die();
            }
        }

        echo "<div class='CF7spreadsheets_success_ajax'>Changes saved successfully.</div>";
    } else {
        echo "<div class='CF7spreadsheets_error_ajax'>Error.</div>";
    }
    wp_die();
}

add_action('wp_ajax_CF7spreadsheets_update_ajax', 'CF7spreadsheets_update');