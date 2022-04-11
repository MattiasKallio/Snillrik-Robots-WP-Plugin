<?php

/*
Plugin Name: Snillrik Robot
Plugin URI: http://www.snillrik.se/
Description: Snillrik robot is a plugin to integrate some robot controlling and viewing stuff on your page.
Version: 0.1
Author: Mattias Kallio
Author URI: http://www.snillrik.se
License: GPL2
 */

DEFINE("SNILLRIK_ROBOT_PLUGIN_URL", plugin_dir_url(__FILE__));
DEFINE("SNILLRIK_ROBOT_DIR", plugin_dir_path(__FILE__));
DEFINE("SNILLRIK_ROBOT_POST_TYPE_NAME", "snillrik_robot");


require_once SNILLRIK_ROBOT_DIR . 'robot-type.php';
require_once SNILLRIK_ROBOT_DIR . 'api.php';
require_once SNILLRIK_ROBOT_DIR . 'settings.php';
require_once SNILLRIK_ROBOT_DIR . 'includes/shortcodes.php';

new SNRobot_robot();
new SNRobot_shortcodes();
new SNRobot_api();

function snillrik_robot_admin_scripts(){
    
    wp_enqueue_script('snillrik-robot-admin-script', SNILLRIK_ROBOT_PLUGIN_URL . 'js/admin.js', array('jquery'));
    
}
add_action('admin_enqueue_scripts', 'snillrik_robot_admin_scripts');

function snillrik_robot_add_scripts(){
    wp_enqueue_style('snillrik-robot-main', SNILLRIK_ROBOT_PLUGIN_URL . 'css/front.css');
    //wp_register_script('snillrik-robot-socket', SNILLRIK_ROBOT_PLUGIN_URL . 'node_modules/socket.io/client-dist/socket.io.js', array('jquery'));
    wp_register_script('snillrik-robot-main-script', SNILLRIK_ROBOT_PLUGIN_URL . 'js/main.js', array('jquery'));
    wp_localize_script('snillrik-robot-main-script', 'page_info', array(
        'ajax_url' => admin_url('admin-ajax.php')
    ));
}
add_action('wp_enqueue_scripts', 'snillrik_robot_add_scripts');

?>