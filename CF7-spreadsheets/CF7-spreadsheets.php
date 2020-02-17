<?php
/*
Plugin Name: CF7 Spreadsheets
Plugin URI: https://github.com/moshenskyDV/CF7-spreadsheets
Description: Send Contact form 7 mail to Google spreadsheets
Version: 2.2.2
Author: Moshenskyi Danylo
Author URI: https://github.com/moshenskyDV/
Text Domain: CF7-spreadsheets
Domain Path: /languages/
License: MIT
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

require_once ABSPATH.'wp-admin/includes/upgrade.php';

class CF7spreadsheets
{
    public $plugin_name = '';
    public $plugin_path = '';
    public $plugin_url = '';
    public $client;
    public $service;

    /**
     * Related to https://contactform7.com/special-mail-tags/.
     *
     * @var array
     */
    public $predefined_mail = ['_remote_ip', '_user_agent', '_url', '_date', '_time', '_invalid_fields', '_serial_number'];
    public $predefined_post = ['_post_id', '_post_name', '_post_title', '_post_url', '_post_author', '_post_author_email'];
    public $predefined_site = ['_site_title', '_site_description', '_site_url', '_site_admin_email'];
    public $predefined_user = ['_user_login', '_user_email', '_user_url', '_user_first_name', '_user_last_name', '_user_nickname', '_user_display_name'];

    /**
     * Backward compatibility with versions <2.0.4.
     *
     * @var array
     */
    public $obsolete_predefined_tags = [
        'def-date' => '_date',
        'def-datetime' => '_date',
        'def-user-ip' => '_remote_ip',
    ];
    private $error_list = [];

    /**
     * Custom tags by plugin.
     *
     * @var array
     */
    private $allowed_tags = ['text', 'email', 'url', 'tel', 'number', 'range', 'date', 'textarea', 'select', 'checkbox', 'radio', 'acceptance', 'quiz', 'hidden'];

    /**
     * Allowed by google sheets types.
     *
     * @var array
     */
    private $allowed_types = ['string', 'number', 'boolean', 'formula'];

    public function __construct($file)
    {
        $this->plugin_name = plugin_basename($file);
        $this->plugin_path = plugin_dir_path($file);
        $this->plugin_url = plugins_url('', $file);

        register_activation_hook($this->plugin_name, [&$this, 'activate']);
        register_deactivation_hook($this->plugin_name, [&$this, 'deactivate']);
    }

    public function activate()
    {
    }

    public function deactivate()
    {
        /*remove all plugin data*/
        $forms = get_posts([
            'numberposts' => -1,
            'post_type' => 'wpcf7_contact_form',
        ]);
        if (!empty($forms)) {
            foreach ($forms as $form) {
                delete_post_meta($form->ID, 'CF7spreadsheets_option_url');
                delete_post_meta($form->ID, 'CF7spreadsheets_option_id');
                delete_post_meta($form->ID, 'CF7spreadsheets_option_enabled');
                delete_post_meta($form->ID, 'CF7spreadsheets_option_mail');
                delete_post_meta($form->ID, 'CF7spreadsheets_output_tags');
                delete_post_meta($form->ID, 'CF7spreadsheets_output_types');
            }
        }
        delete_option('CF7spreadsheets_api_file');
    }

    public function admin_styles()
    {
        wp_enqueue_style('CF7spreadsheets_styles', $this->plugin_url.'/css/style.css');
    }

    public function scripts()
    {
        wp_enqueue_script('CF7spreadsheets_script', $this->plugin_url.'/js/script.js');
    }

    public function get_forms()
    {
        return get_posts([
            'numberposts' => -1,
            'post_type' => 'wpcf7_contact_form',
        ]);
    }

    public function get_form($id)
    {
        return get_post($id);
    }

    /**
     * Regex similar to contact-form-7/includes/form-tags-manager.php:262.
     *
     * @param $meta
     *
     * @return bool|mixed
     */
    public function get_fields($meta)
    {
        $allowed_tags_string = implode(
            '|',
            array_merge(
                $this->allowed_tags,
                array_map(function ($tag) { return $tag.'\*'; }, $this->allowed_tags),
                $this->predefined_mail,
                $this->predefined_post,
                $this->predefined_site,
                $this->predefined_user,
                array_keys($this->obsolete_predefined_tags)
            )
        );
        $regexp = '/(\[?)'
            .'\[('.$allowed_tags_string.')(?:[\r\n\t ](.*?))?(?:[\r\n\t ](\/))?\]'
            .'(?:([^[]*?)\[\/\2\])?'
            .'(\]?)/s';
        $arr = [];
        if (false == preg_match_all($regexp, $meta, $arr)) {
            return false;
        }

        return $arr[0];
    }

    public function get_field_assoc($content)
    {
        $regexp_type = '/(?<=\[)[^\s\*]*/';
        $regexp_name = '/(?<=\s)[^\s\]]*/';
        $arr_type = [];
        $arr_name = [];
        if (false == preg_match($regexp_type, $content, $arr_type)) {
            return false;
        }
        if (!in_array($arr_type[0], $this->allowed_tags)) {
            return false;
        }
        if (false == preg_match($regexp_name, $content, $arr_name)) {
            return false;
        }

        return [$arr_type[0] => $arr_name[0]];
    }

    public function get_form_data($form)
    {
        $assoc_arr = [];
        $meta = get_post_meta($form->ID, '_form', true);
        $fields = $this->get_fields($meta);
        foreach ($fields as $field) {
            $single = $this->get_field_assoc($field);
            if ($single) {
                $assoc_arr[] = $single;
            }
        }

        return $assoc_arr;
    }

    public function main($cf7)
    {
        if ('on' == get_post_meta($cf7->id(), 'CF7spreadsheets_option_enabled', true)) {
            require 'vendor/autoload.php';

            $this->client = new Google_Client();

            try {
                $this->client->setAuthConfig(json_decode(get_option('CF7spreadsheets_api_file'), true)); //<----path to JSON
            } catch (Exception $e) {
                // Something went wrong
                echo $e->getMessage();
            }

            $this->client->setApplicationName('cf7table');
            $this->client->addScope(Google_Service_Sheets::SPREADSHEETS);
            $this->client->setRedirectUri('http://'.$_SERVER['HTTP_HOST']);
            $this->service = new Google_Service_Sheets($this->client);

            try {
                // Set the sheet ID
                $fileId = esc_html(get_post_meta($cf7->id(), 'CF7spreadsheets_option_url', true)); // Copy & paste from a spreadsheet URL
                // Build the CellData array
                $params_names = json_decode(get_post_meta($cf7->id(), 'CF7spreadsheets_output_tags', true));
                $params_types = json_decode(get_post_meta($cf7->id(), 'CF7spreadsheets_output_types', true));
                $values = [];
                foreach ($params_names as $i => $param) {
                    $d = $this->replace_tags($this->exec_shortcodes($param), $cf7->scan_form_tags());
                    $cellData = new Google_Service_Sheets_CellData();
                    $value = new Google_Service_Sheets_ExtendedValue();
                    switch ($params_types[$i]) {
                        case 'formula':
                            $value->setFormulaValue($d);
                            break;
                        case 'boolean':
                            $value->setBoolValue((bool) $d);
                            break;
                        case 'number':
                            $value->setNumberValue((float) $d);
                            break;
                        case 'string':
                        default:
                            $value->setStringValue($d);
                            break;
                    }
                    $cellData->setUserEnteredValue($value);
                    $values[] = $cellData;
                }
                // Build the RowData
                $rowData = new Google_Service_Sheets_RowData();
                $rowData->setValues($values);
                // Prepare the request
                $append_request = new Google_Service_Sheets_AppendCellsRequest();
                $append_request->setSheetId(esc_html(get_post_meta($cf7->id(), 'CF7spreadsheets_option_id', true))); //<-----SHEET ID
                $append_request->setRows($rowData);
                $append_request->setFields('userEnteredValue');
                // Set the request
                $request = new Google_Service_Sheets_Request();
                $request->setAppendCells($append_request);
                // Add the request to the requests array
                $requests = [];
                $requests[] = $request;
                // Prepare the update
                $batchUpdateRequest = new Google_Service_Sheets_BatchUpdateSpreadsheetRequest([
                    'requests' => $requests,
                ]);

                try {
                    // Execute the request
                    $response = $this->service->spreadsheets->batchUpdate($fileId, $batchUpdateRequest);
                    if ($response->valid()) {
                        // Success, the row has been added
                    }
                } catch (Exception $e) {
                    // Something went wrong
                    echo 'Error.';
                    echo $e->getMessage();
                }
            } catch (Exception $e) {
                //not valid JSON
            }

            /*after google send option skip email needed*/
            if ('on' != get_post_meta($cf7->id(), 'CF7spreadsheets_option_mail', true)) {
                $cf7->skip_mail = true;
            }
        }
    }

    public function adminmenu()
    {
        global $admin_page_hooks;

        if (isset($admin_page_hooks['wpcf7'])) {
            add_submenu_page(
                'wpcf7',
                'Google Spreadsheets',
                'Google Spreadsheets',
                'activate_plugins',
                'wpcf7-cf7spreadsheet',
                [&$this, 'adminprint']
            );
        } else {
            /*WPCF not installed, notice*/
            function CF7spreadsheets_notice()
            { ?>
                <div class="error notice is-dismissible">
                    <p><?php echo __('"Contact form 7" plugin required.', 'CF7-spreadsheets'); ?></p>
                </div>
            <?php }

            add_action('admin_notices', 'CF7spreadsheets_notice');
        }
    }

    public function page_tabs($current = 'tab1')
    {
        $tabs = [
            'tab1' => __('Table options', 'CF7-spreadsheets'),
            'tab2' => __('Google API', 'CF7-spreadsheets'),
            'tab3' => __('Output', 'CF7-spreadsheets'),
        ];
        $html = '<h2 class="nav-tab-wrapper">';
        foreach ($tabs as $tab => $name) {
            $class = ($tab == $current) ? 'nav-tab-active' : '';
            $html .= '<a class="nav-tab '.$class.'" href="?page=wpcf7-cf7spreadsheet&tab='.$tab.'">'.$name.'</a>';
        }
        $html .= '</h2>';
        echo $html;
    }

    public function adminprint()
    { ?>
        <div class="wrap">
            <h1><?php echo __('Google Spreadsheets', 'CF7-spreadsheets'); ?></h1>
            <?php $tab = (!empty($_GET['tab'])) ? esc_attr($_GET['tab']) : 'tab1';
            $this->page_tabs($tab);
            if ('tab1' == $tab) { ?>
                <div class="col-container">
                    <div class="form-wrap">
                        <form action="#" id="CF7spreadsheets_search_form" method="post">
                            <?php $forms = $this->get_forms();
                            if (!empty($forms)) { ?>
                                <div class="CF7spreadsheets_col_wrapper">
                                    <div class="CF7spreadsheets_col_left">
                                        <h3><?php _e('Title', 'CF7-spreadsheets'); ?></h3>
                                    </div>
                                    <div class="CF7spreadsheets_col_right">
                                        <h3><?php _e('Options', 'CF7-spreadsheets'); ?></h3>
                                    </div>
                                </div>
                                <?php foreach ($forms as $form) {
                                $enabled = get_post_meta($form->ID, 'CF7spreadsheets_option_enabled', true); ?>
                                    <div class="CF7spreadsheets_col_wrapper">
                                        <div class="CF7spreadsheets_col_left">
                                            <h3><?php echo $form->post_title; ?></h3>
                                            <div class="form-field form-required">
                                                <p class="CF7spreadsheets_switcher_text"><?php _e('Enable sending to google tables', 'CF7-spreadsheets'); ?></p>
                                                <input type="checkbox"
                                                       name="CF7spreadsheets_option_enabled[<?php echo $form->ID; ?>]"
                                                       id="CF7spreadsheets_option_enabled_<?php echo $form->ID; ?>"
                                                       class="CF7spreadsheets_switcher_field"
                                                    <?php
                                                    if (!empty($enabled) && 'on' == $enabled) {
                                                        echo 'checked="checked"';
                                                    } ?>>
                                                <label for="CF7spreadsheets_option_enabled_<?php echo $form->ID; ?>"
                                                       class="CF7spreadsheets_switcher_label"></label>
                                            </div>
                                        </div>
                                        <div class="CF7spreadsheets_col_right">
                                            <div class="form-field form-required">
                                                <label
                                                        for="CF7spreadsheets_option_url"><?php echo __('Spreadsheet URL', 'CF7-spreadsheets'); ?></label>
                                                <input type="text"
                                                       name="CF7spreadsheets_option_url[<?php echo $form->ID; ?>]"
                                                       id="CF7spreadsheets_option_url_<?php echo $form->ID; ?>"
                                                       value="<?php echo get_post_meta($form->ID, 'CF7spreadsheets_option_url', true); ?>" <?php
                                                if (empty($enabled) || 'on' != $enabled) {
                                                    echo 'readonly';
                                                } ?>>
                                            </div>
                                            <div class="form-field form-required">
                                                <label
                                                        for="CF7spreadsheets_option_id"><?php echo __('Spreadsheet ID', 'CF7-spreadsheets'); ?></label>
                                                <input type="text"
                                                       name="CF7spreadsheets_option_id[<?php echo $form->ID; ?>]"
                                                       id="CF7spreadsheets_option_id_<?php echo $form->ID; ?>"
                                                       value="<?php echo get_post_meta($form->ID, 'CF7spreadsheets_option_id', true); ?>" <?php
                                                if (empty($enabled) || 'on' != $enabled) {
                                                    echo 'readonly';
                                                } ?>>
                                            </div>
                                            <div class="form-field form-required">
                                                <label
                                                        for="CF7spreadsheets_option_mail_<?php echo $form->ID; ?>"><?php echo __('Send on email too (continue default Contact form action)?', 'CF7-spreadsheets'); ?></label>
                                                <input type="checkbox"
                                                       name="CF7spreadsheets_option_mail[<?php echo $form->ID; ?>]"
                                                       id="CF7spreadsheets_option_mail_<?php echo $form->ID; ?>" <?php $mail = get_post_meta($form->ID, 'CF7spreadsheets_option_mail', true);
                                if (empty($enabled) || 'on' != $enabled) {
                                    echo 'readonly';
                                }
                                if (!empty($mail) || 'on' == $mail) {
                                    echo 'checked="checked"';
                                } ?>>
                                            </div>
                                        </div>
                                    </div>
                                <?php
                            } ?>
                            <?php } ?>
                            <input type="button" id="CF7spreadsheets_option_submit" class="button button-primary"
                                   value="<?php echo __('Save', 'CF7-spreadsheets'); ?>">
                            <p class="CF7spreadsheets_response"></p>
                        </form>
                        <div class="ipcontrol_response"></div>
                    </div>
                </div>
            <?php } elseif ('tab2' == $tab) { ?>
                <h3><?php _e('Google API', 'CF7-spreadsheets'); ?></h3>
                <div class="col-container">
                    <div class="form-wrap">
                        <form enctype="multipart/form-data" action="#" id="CF7spreadsheets_search_form" method="post">
                            <div class="form-field form-required">
                                <label
                                        for="CF7spreadsheets_api_file"><?php echo __('Upload JSON from API console', 'CF7-spreadsheets'); ?></label>
                                <input type="file" name="CF7spreadsheets_api_file" id="CF7spreadsheets_api_file"
                                       value="<?php echo esc_html(get_option('CF7spreadsheets_api_file')); ?>">
                                <span class="CF7spreadsheets_status"></span>
                                <?php if (get_option('CF7spreadsheets_api_file')) { ?>
                                    <p><?php
                                        echo __('Current service account email: ', 'CF7-spreadsheets');
                                        if ($json = json_decode(get_option('CF7spreadsheets_api_file'))) {
                                            echo $json->client_email;
                                        }
                                        ?></p>
                                <?php } ?>
                            </div>
                            <input type="hidden" name="MAX_FILE_SIZE" value="1000000"/>
                            <input type="button" id="CF7spreadsheets_api_submit" class="button button-primary"
                                   value="<?php echo __('Save', 'CF7-spreadsheets'); ?>">
                            <p class="CF7spreadsheets_response"></p>
                        </form>
                        <div class="ipcontrol_response"></div>
                    </div>
                </div>
            <?php } elseif ('tab3' == $tab) { ?>
                <div class="col-container">
                    <div class="form-wrap">
                        <form action="#" id="CF7spreadsheets_output_form" method="post">
                            <div class="CF7spreadsheets_col_wrapper">
                                <div class="CF7spreadsheets_col_left">
                                    <h3><?php _e('Select form', 'CF7-spreadsheets'); ?></h3>
                                </div>
                                <div class="CF7spreadsheets_col_right">
                                    <h3><?php _e('Output queue', 'CF7-spreadsheets'); ?></h3>
                                </div>
                            </div>
                            <?php $forms = $this->get_forms(); ?>
                            <div class="CF7spreadsheets_col_wrapper">
                                <div class="CF7spreadsheets_col_left">
                                    <div class="form-field form-required">
                                        <select name="CF7spreadsheets_output_select" id="CF7spreadsheets_output_select">
                                            <?php foreach ($forms as $form) { ?>
                                                <option
                                                        value="<?php echo $form->ID; ?>"
                                                    <?php
                                                    $enabled = get_post_meta($form->ID, 'CF7spreadsheets_option_enabled', true);
                                                    if (empty($enabled) || 'on' != $enabled) {
                                                        echo 'disabled="disabled"';
                                                    } ?>>
                                                    <?php echo $form->post_title; ?>
                                                </option>
                                            <?php } ?>
                                        </select>
                                        <p class="CF7spreadsheets_switcher_text"><?php _e('If your table disabled to choose - you need to enable it on "Table options" tab.', 'CF7-spreadsheets'); ?></p>
                                        <p class="CF7spreadsheets_switcher_text"><?php _e('Put allowed mail tags to following fields.', 'CF7-spreadsheets'); ?></p>
                                    </div>
                                </div>
                                <div class="CF7spreadsheets_col_right">
                                    <p class="CF7spreadsheets_allowed_tags"><?php _e('Allowed tags:', 'CF7-spreadsheets'); ?></p>
                                    <p id="CF7spreadsheets_allowed_tags" class="CF7spreadsheets_allowed_tags"></p>
                                    <p class="CF7spreadsheets_allowed_tags"><?php _e('Special mail tags for submissions:', 'CF7-spreadsheets'); ?></p>
                                    <p class="CF7spreadsheets_allowed_tags">
                                        <?php foreach ($this->predefined_mail as $item) { ?>
                                            <span>[<?php echo $item; ?>]</span>
                                        <?php } ?>
                                    </p>
                                    <p class="CF7spreadsheets_allowed_tags"><?php _e('Post-related special mail tags:', 'CF7-spreadsheets'); ?></p>
                                    <p class="CF7spreadsheets_allowed_tags">
                                        <?php foreach ($this->predefined_post as $item) { ?>
                                            <span>[<?php echo $item; ?>]</span>
                                        <?php } ?>
                                    </p>
                                    <p class="CF7spreadsheets_allowed_tags"><?php _e('Site-related special mail tags:', 'CF7-spreadsheets'); ?></p>
                                    <p class="CF7spreadsheets_allowed_tags">
                                        <?php foreach ($this->predefined_site as $item) { ?>
                                            <span>[<?php echo $item; ?>]</span>
                                        <?php } ?>
                                    </p>
                                    <p class="CF7spreadsheets_allowed_tags"><?php _e('User-related special mail tags:', 'CF7-spreadsheets'); ?></p>
                                    <p class="CF7spreadsheets_allowed_tags">
                                        <?php foreach ($this->predefined_user as $item) { ?>
                                            <span>[<?php echo $item; ?>]</span>
                                        <?php } ?>
                                    </p>
                                    <p class="CF7spreadsheets_allowed_tags"><?php _e('Spreadsheet row:', 'CF7-spreadsheets'); ?></p>
                                    <div id="CF7spreadsheets_table_wrapper" class="CF7spreadsheets_table_wrapper">
                                        <button title="Add cell" type="button" class="button CF7spreadsheets_table_add">
                                            + <?php echo __('Add cell', 'CF7-spreadsheets'); ?></button>
                                    </div>
                                </div>
                            </div>
                            <input type="button" id="CF7spreadsheets_output_submit" class="button button-primary" value="<?php echo __('Save', 'CF7-spreadsheets'); ?>">
                            <p class="CF7spreadsheets_response"></p>
                        </form>
                        <div class="ipcontrol_response"></div>
                    </div>
                </div>
            <?php } ?>
        </div>
    <?php }

    public function update_options()
    {
        foreach ($_POST['CF7spreadsheets_option_url'] as $post_title => $post_val) {
            if (empty($post_val) && !empty($_POST['CF7spreadsheets_option_enabled'][$post_title]) && 'on' == $_POST['CF7spreadsheets_option_enabled'][$post_title]) {
                $this->error_list[] = [
                    'title' => $post_title,
                    'root' => 'CF7spreadsheets_option_url',
                    'value' => __('It must be filled.', 'CF7-spreadsheets'),
                ];
            }
        }
        foreach ($_POST['CF7spreadsheets_option_id'] as $post_title => $post_val) {
            if (!empty($_POST['CF7spreadsheets_option_enabled'][$post_title]) && 'on' == $_POST['CF7spreadsheets_option_enabled'][$post_title]) {
                if (!empty($post_val) || '0' === $post_val) {
                    if (!is_numeric($post_val)) {
                        $this->error_list[] = [
                            'title' => $post_title,
                            'root' => 'CF7spreadsheets_option_id',
                            'value' => __('It must be numeric.', 'CF7-spreadsheets'),
                        ];
                    }
                } else {
                    $this->error_list[] = [
                        'title' => $post_title,
                        'root' => 'CF7spreadsheets_option_id',
                        'value' => __('It must be filled.', 'CF7-spreadsheets'),
                    ];
                }
            }
        }
        if (!empty($this->error_list)) {
            echo json_encode([
                'response' => 'error',
                'content' => $this->error_list,
            ]);
        } else {
            foreach ($_POST['CF7spreadsheets_option_url'] as $post_title => $post_val) {
                update_post_meta($post_title, 'CF7spreadsheets_option_url', sanitize_text_field($post_val));
            }
            foreach ($_POST['CF7spreadsheets_option_id'] as $post_title => $post_val) {
                update_post_meta($post_title, 'CF7spreadsheets_option_id', sanitize_text_field($post_val));
            }
            $forms = $this->get_forms();
            foreach ($forms as $form) {
                if (!empty($_POST['CF7spreadsheets_option_enabled'][$form->ID]) && 'on' == $_POST['CF7spreadsheets_option_enabled'][$form->ID]) {
                    update_post_meta($form->ID, 'CF7spreadsheets_option_enabled', 'on');
                } else {
                    update_post_meta($form->ID, 'CF7spreadsheets_option_enabled', 'off');
                }
                if (!empty($_POST['CF7spreadsheets_option_mail'][$form->ID]) && 'on' == $_POST['CF7spreadsheets_option_mail'][$form->ID]) {
                    update_post_meta($form->ID, 'CF7spreadsheets_option_mail', 'on');
                } else {
                    update_post_meta($form->ID, 'CF7spreadsheets_option_mail', 'off');
                }
            }
            echo json_encode([
                'response' => 'success',
                'content' => "<div class='CF7spreadsheets_success_ajax'>".__('Changes saved successfully.', 'CF7-spreadsheets').'</div>',
            ]);
        }
        wp_die();
    }

    public function isJson($string)
    {
        json_decode($string);

        return JSON_ERROR_NONE == json_last_error();
    }

    public function update_api()
    {
        if (!empty($_POST['CF7spreadsheets_api_file'])) {
            if ($this->isJson(stripcslashes($_POST['CF7spreadsheets_api_file']))) {
                update_option('CF7spreadsheets_api_file', stripslashes($_POST['CF7spreadsheets_api_file']));
                echo "<div class='CF7spreadsheets_success_ajax'>".__('File updated successfully.', 'CF7-spreadsheets').'</div>';
            } else {
                /*try to send broken file and hack front-end barier*/
                echo "<div class='CF7spreadsheets_error_ajax'>".__('Wrong value: JSON file not valid.', 'CF7-spreadsheets').'</div>';
            }
        } else {
            echo "<div class='CF7spreadsheets_error_ajax'>".__('No file choosen.', 'CF7-spreadsheets').'</div>';
        }
        wp_die();
    }

    public function update_output()
    {
        $form = get_post($_POST['CF7spreadsheets_post_id']);
        if ($form && 'wpcf7_contact_form' == $form->post_type) {
            if (!empty($_POST['CF7spreadsheets_output_tags']) && is_array($_POST['CF7spreadsheets_output_tags'])) {
                $sanitized_tags = [];
                $sanitized_types = [];
                foreach ($_POST['CF7spreadsheets_output_tags'] as $tag) {
                    array_push($sanitized_tags, sanitize_text_field($tag));
                }
                foreach ($_POST['CF7spreadsheets_output_types'] as $type) {
                    array_push($sanitized_types, in_array($type, $this->allowed_types) ? $type : 'string');
                }
                update_post_meta($form->ID, 'CF7spreadsheets_output_tags', json_encode($sanitized_tags));
                update_post_meta($form->ID, 'CF7spreadsheets_output_types', json_encode($sanitized_types));
                echo json_encode([
                    'response' => 'success',
                    'content' => __('Changes saved successfully.', 'CF7-spreadsheets'),
                ]);
            } else {
                echo json_encode([
                    'response' => 'error',
                    'content' => __('Tags is empty.', 'CF7-spreadsheets'),
                ]);
            }
        } else {
            echo json_encode([
                'response' => 'error',
                'content' => __('Form does not exists.', 'CF7-spreadsheets'),
            ]);
        }
        wp_die();
    }

    public function update_form_data()
    {
        $form = get_post($_POST['CF7spreadsheets_post_id']);
        if ($form && 'wpcf7_contact_form' == $form->post_type) {
            $post_data = $this->get_form_data($form);
            if (!empty($post_data)) {
                $filled_tags = get_post_meta($form->ID, 'CF7spreadsheets_output_tags', true);
                $filled_types = get_post_meta($form->ID, 'CF7spreadsheets_output_types', true);
                if (!empty($filled_tags)) {
                    echo json_encode([
                        'response' => 'success',
                        'content' => $post_data,
                        'filled_tags' => $filled_tags,
                        'filled_types' => $filled_types,
                    ]);
                } else {
                    echo json_encode([
                        'response' => 'success',
                        'content' => $post_data,
                        'filled' => false,
                    ]);
                }
            } else {
                echo json_encode([
                    'response' => 'error',
                    'content' => "<div class='CF7spreadsheets_error_ajax'>".__('Form is empty.', 'CF7-spreadsheets').'</div>',
                ]);
            }
        } else {
            echo json_encode([
                'response' => 'error',
                'content' => "<div class='CF7spreadsheets_error_ajax'>".__('Form does not exists.', 'CF7-spreadsheets').'</div>',
            ]);
        }
        wp_die();
    }

    private function replace_tags($string, $request_form_tags)
    {
        $assoc_request_form_tags = [];
        foreach ($request_form_tags as $tag) {
            $assoc_request_form_tags[$tag->name] = $tag;
        }
        $regexp = '/\[.*\]/U';
        preg_match_all($regexp, $string, $arr);
        $replace_from = [];
        $replace_to = [];
        if (!empty($arr[0])) {
            foreach ($arr[0] as $tag) {
                $clear_tag = substr($tag, 1, -1);
                //backward compatibility
                if (in_array($clear_tag, array_keys($this->obsolete_predefined_tags))) {
                    $clear_tag = $this->obsolete_predefined_tags[$clear_tag];
                }

                if (!empty($_POST[$clear_tag]) || '0' === $_POST[$clear_tag]) {
                    /*user tags*/
                    $replace_from[] = '/'.quotemeta($tag).'/';
                    if (is_array($_POST[$clear_tag])) {
                        /*multiselect or checkboxes*/
                        $replace_to[] = implode(', ', $_POST[$clear_tag]);
                    } else {
                        if (!empty($assoc_request_form_tags[$clear_tag]->pipes->collect_befores())) {
                            $index = array_search($_POST[$clear_tag], $assoc_request_form_tags[$clear_tag]->pipes->collect_befores());
                            $replace_to[] = $assoc_request_form_tags[$clear_tag]->pipes->collect_afters()[$index];
                        } else {
                            $replace_to[] = $_POST[$clear_tag];
                        }
                    }
                } elseif ($defined = wpcf7_special_mail_tag(false, $clear_tag, false)) {
                    $replace_from[] = '/'.quotemeta($tag).'/';
                    $replace_to[] = $defined;
                } elseif ($defined = wpcf7_post_related_smt(false, $clear_tag, false)) {
                    $replace_from[] = '/'.quotemeta($tag).'/';
                    $replace_to[] = $defined;
                } elseif ($defined = wpcf7_site_related_smt(false, $clear_tag, false)) {
                    $replace_from[] = '/'.quotemeta($tag).'/';
                    $replace_to[] = $defined;
                } elseif ($defined = wpcf7_user_related_smt(false, $clear_tag, false)) {
                    $replace_from[] = '/'.quotemeta($tag).'/';
                    $replace_to[] = $defined;
                } else {
                    /*empty tags*/
                    $replace_from[] = '/'.quotemeta($tag).'/';
                    $replace_to[] = '';
                }
            }
            $result = preg_replace($replace_from, $replace_to, $string);

            return $result;
        }

        return $string;
    }

    private function exec_shortcodes($string)
    {
        return do_shortcode($string);
    }
}

$CF7Inst = new CF7spreadsheets(__FILE__);

/*includes*/
add_action('admin_menu', [&$CF7Inst, 'admin_styles']);
add_action('admin_enqueue_scripts', [&$CF7Inst, 'scripts']);
add_action('plugins_loaded', 'CF7_spreadsheets_language');
function CF7_spreadsheets_language()
{
    /*translate*/
    load_plugin_textdomain('CF7-spreadsheets', false, dirname(plugin_basename(__FILE__)).'/languages/');
}

/*main function*/
add_action('wpcf7_before_send_mail', [&$CF7Inst, 'main']);

/*admin panel part*/
add_action('admin_menu', [&$CF7Inst, 'adminmenu']);

/*ajax*/
add_action('wp_ajax_CF7spreadsheets_update_ajax_options', [&$CF7Inst, 'update_options']);
add_action('wp_ajax_CF7spreadsheets_update_ajax_api', [&$CF7Inst, 'update_api']);
add_action('wp_ajax_CF7spreadsheets_update_ajax_form_data', [&$CF7Inst, 'update_form_data']);
add_action('wp_ajax_CF7spreadsheets_update_ajax_output', [&$CF7Inst, 'update_output']);
