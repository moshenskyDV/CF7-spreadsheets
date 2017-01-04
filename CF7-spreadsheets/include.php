<?php

function CF7spreadsheets_styles()
{
    wp_enqueue_style('CF7spreadsheets_styles', plugins_url('css/style.css', __FILE__));
}

add_action('admin_menu', 'CF7spreadsheets_styles');

function CF7spreadsheets_scripts()
{
    wp_enqueue_script('CF7spreadsheets_script', plugins_url('js/script.js', __FILE__));
}

add_action('admin_enqueue_scripts', 'CF7spreadsheets_scripts');

/*translate*/
function CF7spreadsheets_init()
{
    load_plugin_textdomain('CF7-spreadsheets', false, dirname(plugin_basename(__FILE__)) . '/languages/');
}

add_action('plugins_loaded', 'CF7spreadsheets_init');