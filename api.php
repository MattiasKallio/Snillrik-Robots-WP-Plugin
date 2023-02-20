<?php

/**
 *
 * API endpoints and stuff for Robot
 *
 * http://{domain}/wp-json/robot/token/blabla
 *
 *
 */
class SNRobot_api extends WP_REST_Controller
{

    public function __construct(){
        add_action('rest_api_init', function () {
            //Get specific market.
            register_rest_route('robots', '/getinfo/(?P<token>[a-zA-Z0-9-]+)', array(
                'methods' => 'GET',
                'callback' => array($this, 'get_robot_info'),
            ));
            register_rest_route('robots', '/addinfo/(?P<token>[a-zA-Z0-9-]+)', array(
                'methods' => 'GET',
                'callback' => array($this, 'add_robot_info'),
            ));            

            register_rest_route('robots', '/popular', array(
                'methods' => 'GET',
                'callback' => array($this,'robot_popular'),

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
    else
        return "Noo, something is wrong, robot does not exist!";
}    
/**
 * The market page ie, the page show to the world.
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

        return array("content" => $robot);
    }

}
