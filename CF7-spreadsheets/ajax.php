<?php

function CF7spreadsheets_update()
{
    if (!empty($_POST['CF7spreadsheets_option_url'])) {
        update_option('CF7spreadsheets_option_url', sanitize_text_field($_POST['CF7spreadsheets_option_url']));
    } else {
        echo "<div class='CF7spreadsheets_error_ajax'>" . __('Wrong value: Spreadsheet URL. It must be filled.', 'CF7-spreadsheets') . "</div>";
        wp_die();
    }
    if (is_numeric($_POST['CF7spreadsheets_option_id'])) {
        update_option('CF7spreadsheets_option_id', sanitize_text_field($_POST['CF7spreadsheets_option_id']));
    } else {
        echo "<div class='CF7spreadsheets_error_ajax'>" . __('Wrong value: Spreadsheet ID. It must be a number.', 'CF7-spreadsheets') . "</div>";
        wp_die();
    }
    if ($_POST['CF7spreadsheets_option_mail'] == 'true' || $_POST['CF7spreadsheets_option_mail'] == 'false') {
        update_option('CF7spreadsheets_option_mail', sanitize_text_field($_POST['CF7spreadsheets_option_mail']));
    } else {
        echo "<div class='CF7spreadsheets_error_ajax'>" . __('Wrong value: Send email.', 'CF7-spreadsheets') . "</div>";
        wp_die();
    }
    if ($_POST['CF7spreadsheets_option_time'] == 'true' || $_POST['CF7spreadsheets_option_time'] == 'false') {
        update_option('CF7spreadsheets_option_time', sanitize_text_field($_POST['CF7spreadsheets_option_time']));
    } else {
        echo "<div class='CF7spreadsheets_error_ajax'>" . __('Wrong value: Send date/time.', 'CF7-spreadsheets') . "</div>";
        wp_die();
    }


    /*check valid JSON*/
    function isJson($string)
    {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }

    /*if file come - update file*/
    if (!get_option('CF7spreadsheets_option_filename') || !empty($_POST['CF7spreadsheets_option_file_name']) && $_POST['CF7spreadsheets_option_file_name'] != get_option('CF7spreadsheets_option_filename')) {
        if (!empty($_POST['CF7spreadsheets_option_file'])) {
            if (isJson(stripcslashes($_POST['CF7spreadsheets_option_file']))) {
                if (!file_put_contents(plugin_dir_path(__FILE__) . sanitize_file_name($_POST['CF7spreadsheets_option_file_name']), stripcslashes($_POST['CF7spreadsheets_option_file']))) {
                    echo "<div class='CF7spreadsheets_error_ajax'>" . __('Error file write.', 'CF7-spreadsheets') . "</div>";
                    wp_die();
                } else {
                    /*success file write*/
                    update_option('CF7spreadsheets_option_filename', sanitize_file_name($_POST['CF7spreadsheets_option_file_name']));
                }
            } else {
                /*try to send broken file and hack front-end barier*/
                echo "<div class='CF7spreadsheets_error_ajax'>" . __('Wrong value: JSON file not valid.', 'CF7-spreadsheets') . "</div>";
                wp_die();
            }
        } else {
            /*try to send broken file*/
            echo "<div class='CF7spreadsheets_error_ajax'>" . __('Wrong value: JSON file not valid.', 'CF7-spreadsheets') . "</div>";
            wp_die();
        }
    }

    echo "<div class='CF7spreadsheets_success_ajax'>" . __('Changes saved successfully.', 'CF7-spreadsheets') . "</div>";

    wp_die();
}

add_action('wp_ajax_CF7spreadsheets_update_ajax', 'CF7spreadsheets_update');