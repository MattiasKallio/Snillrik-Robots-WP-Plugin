<?php

class SNRobot_shortcodes
{

    public function __construct()
    {
        add_shortcode('snillrik_robot', array($this, 'snillrik_robot_shortcode'));
    }

/**
 * Ads, as a robots from bizzes
 */
    public function snillrik_robot_shortcode($atts)
    {
        $attributes = shortcode_atts(array(
            'token' => 'AYIf7XBXb9zwVISA6OBwu2bft0wzWRbfZJi8CGZXWl6'
        ), $atts);

        $return_str = "Error";

        $robot = SNRobot_robot::get_from_token($attributes["token"]);
        if($robot)
            $return_str = "Robotname".$robot->post_title;

        wp_enqueue_script('snillrik-robot-socket');
        wp_enqueue_script('snillrik-robot-main-script');

        return $return_str;

    }

}
