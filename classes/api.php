<?php

/**
 *
 * API endpoints and stuff for Robot
 *
 * http://{domain}/wp-json/robot/token/blabla
 * http://{domain}/wp-json/snillrik_robot/getinfo/Seer3745Fu77/
 *
 */
class SNRobot_api extends WP_REST_Controller
{

    public function __construct(){
        add_action('rest_api_init', function () {
            //Get specific market.
            register_rest_route(SNILLRIK_ROBOT_POST_TYPE_NAME, '/getinfo/(?P<token>[a-zA-Z0-9-]+)', array(
                'methods' => 'GET',
                'callback' => array($this, 'get_robot_info'),
                'permission_callback' => '__return_true'
            ));
            register_rest_route(SNILLRIK_ROBOT_POST_TYPE_NAME, '/addinfo/(?P<token>[a-zA-Z0-9-]+)', array(
                'methods' => 'GET',
                'callback' => array($this, 'add_robot_info'),
                'permission_callback' => '__return_true'
            ));            

            register_rest_route(SNILLRIK_ROBOT_POST_TYPE_NAME, '/popular', array(
                'methods' => 'GET',
                'callback' => array($this,'robot_popular'),
                'permission_callback' => '__return_true'

            ));
        });
    }

    public function robot_popular()
    {
        return "wat";
    }

/**
 * Add info, like ip-number, position etc.
 */
public function add_robot_info($request){
    $parameters = $request->get_params();
    
    if(!isset($parameters["ipnumber"])){
        return wp_send_json(array(
            "response" => "error missing ip"
        ));
    }
    $the_token = sanitize_text_field(urldecode($parameters["token"]));
    $ip_number = sanitize_text_field(urldecode($parameters["ipnumber"]));

    $args = array(
        'meta_key' => 'snillrik_robot_token',
        'meta_value' => $the_token,
        'post_type' => 'snillrik_robot'
    );
    $robot = get_posts($args);
    $robot = isset($robot[0]) ? $robot[0] : false;
    if($robot){
        $tempsessionid = wp_generate_password(43, false, false);
        update_post_meta($robot->ID, "snillrik_robot_ip", $ip_number);
        update_post_meta($robot->ID, "snillrik_robot_sessiontoken", $tempsessionid);
        return wp_send_json(array(
            "response" => "ok",
            "sessiontoken"=>$tempsessionid
        ));
    }
    else{
        return "Noo, something is wrong, robot does not exist!";
    }
}    
/**
 * The robot page ie, the page show to the world.
 */
    public function get_robot_info($request){
        $parameters = $request->get_params();
        $the_token = sanitize_text_field(urldecode($parameters["token"]));

        $args = array(
            'meta_key' => 'snillrik_robot_token',
            'meta_value' => $the_token,
            'post_type' => 'snillrik_robot'
        );
        $robot = get_posts($args);
        $robot = reset($robot);
        
        $metadata = get_post_meta($robot->ID);
        $metadata = array_map(function($value) {
            return is_array($value) ? $value[0] : $value;
        }, $metadata);

        if(!$robot){
            return array("response" => "error", "message" => "No robot found with that token.");
        }

        $content_out = [
            "id" => $robot->ID,
            "title" => $robot->post_title,
            "url" => get_permalink($robot->ID),
            "metadata" => [
                "snillrik_robot_openornot" => $metadata["snillrik_robot_openornot"],
                "snillrik_robot_ip" => $metadata["snillrik_robot_ip"],
                "snillrik_robot_sessiontoken" => $metadata["snillrik_robot_sessiontoken"],
                "snillrik_buttons_and_axis" => json_decode($metadata["snillrik_buttons_and_axis"]),
            ]
        ];

        return array(
            "response" => "ok",
            "message" => "Robot found",
            "content" => $content_out
        );
    }

}
