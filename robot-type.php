<?php

/**
 * robot class
 */
class SNRobot_robot
{

    public function __construct()
    {
        add_action('init', array($this, 'robots_init'));
        add_action('save_post', array($this, 'snillrik_robot_save_meta'), 1, 2); // save the custom fields
        add_filter('the_content', array($this, 'robot_extra_content'));
        add_action('wp_ajax_nopriv_snillrik_call_robot', array($this, 'call_robot'));
        add_action('wp_ajax_snillrik_call_robot', array($this, 'call_robot'));
        add_action('wp_ajax_snillrik_get_robotip', array($this, 'get_robotip'));
        add_action('wp_ajax_nopriv_snillrik_get_robotip', array($this, 'get_robotip'));
        
        add_action('wp_ajax_nopriv_snillrik_fetchsessontoken', array($this, 'snillrik_fetchsessontoken'));
        add_action('wp_ajax_snillrik_fetchsessontoken', array($this, 'snillrik_fetchsessontoken'));
        
    }

/**
 * Add robot post type
 */
    public function robots_init()
    {
        $labels = [
            'name' => __('Robots'),
            'singular_name' => __('Robot'),
        ];

        $args = [
            'labels' => $labels,
            'public' => true,
            'has_archive' => true,
            'capability_type' => 'post',
            'map_meta_cap' => true,
            'register_meta_box_cb' => array($this, 'snillrik_robots_metaboxes'),
            'supports' => array('title', 'editor', 'author', 'thumbnail'),
            'taxonomies' => array('robots', 'category'),
        ];

        if (!post_type_exists(SNILLRIK_ROBOT_POST_TYPE_NAME)) {
            register_post_type(SNILLRIK_ROBOT_POST_TYPE_NAME, $args);
        }

    }

/**
 * Add custom fields to robot ie dates.
 */
    public function snillrik_robots_metaboxes()
    {
        add_meta_box(
            'snillrik_robots_tokenbox',
            'Token',
            array($this, 'snillrik_robot_metabox')
        );

    }

/**
 * The token
 */
    public function snillrik_robot_metabox()
    {
        global $post;
        $robot_token = get_post_meta($post->ID, 'snillrik_robot_token', true);

        //echo SNILLRIK_robot_POST_TYPE_NAME;
        echo '<input type="hidden" name="snillrik_robot_noncename" id="snillrik_robot_noncename" value="' . wp_create_nonce(plugin_basename(__FILE__)) . '" />';
        echo "<div><h3>Token</h3>
        <p>Token, that should be generated. Button to generate.</p>";

        echo '<table class="snillrik-robot-table">';
        echo '<tr><td>Copy this.' . $robot_token . '</td></tr></table>';
        echo '</div>';

        $robot_openornot = get_post_meta($post->ID, 'snillrik_robot_openornot', true);

        //echo SNILLRIK_robot_POST_TYPE_NAME;
        echo "<div><h3>Private robot</h3>
        <p>Whether the robot should be visible to other people or not (not very secure, but fun!)</p>";
        $selectstr = "";
        foreach (array("Yepp", "Nope") as $selects) {
            $selectstr .= $robot_openornot == $selects ? "<option selected>$selects</option>" : "<option>$selects</option>";
        }

        echo "<table class='snillrik-robot-table'>";
        echo "<tr><td><select id='snillrik_robot_openornot' name='snillrik_robot_openornot'>$selectstr</select></td></tr></table>";
        echo "</div>";

        $gampadinfo = get_post_meta($post->ID, 'snillrik_buttons_and_axis', true);

        echo "<div><div><h3>Gamepad</h3>
        <p>To set the gamepad, move any axis or push a button on it to select the gamepad you want to use.</p>
        <p>The idea is to name the different buttons and axis, that is saved as an array, and then send that name with a value to the robot.</p>";
        //echo '<input type="hidden" id="buttons_and_axis" />';
        
        echo '<div id="snillrik_robot_gamepad_info">';
        echo '</div>';
        echo '<input type="button" id="buttons_and_axis_save" value="Make JSON"/></div>';
        echo '</div><div><textarea id="snillrik_buttons_and_axis" name="snillrik_buttons_and_axis" style="width:300px;">'.$gampadinfo.'</textarea>';
    }
  

    public function snillrik_robot_save_meta($post_id, $post)
    {

        if ($post->post_type == SNILLRIK_ROBOT_POST_TYPE_NAME) {
            if (!isset($_POST['snillrik_robot_noncename']) || !wp_verify_nonce($_POST['snillrik_robot_noncename'], plugin_basename(__FILE__))) {
                return $post->ID;
            }

            if (!current_user_can('edit_post', $post->ID)) {
                return $post->ID;
            }

            $robot_token = get_post_meta($post->ID, 'snillrik_robot_token', true);

            if ($robot_token == "") {
                $robot_token = wp_generate_password(43, false, false);
            }

            $menuscode_meta['snillrik_robot_token'] = $robot_token;

            $menuscode_meta['snillrik_robot_openornot'] = $_POST['snillrik_robot_openornot'];
            $menuscode_meta['snillrik_buttons_and_axis'] = $_POST['snillrik_buttons_and_axis'];
            
            
            foreach ($menuscode_meta as $key => $value) {
                if ($post->post_type == 'revision') {
                    return;
                }

                $value = implode(',', (array) $value);
                if (get_post_meta($post->ID, $key, false)) {
                    update_post_meta($post->ID, $key, $value);
                } else {
                    add_post_meta($post->ID, $key, $value);
                }
                if (!$value) {
                    delete_post_meta($post->ID, $key);
                }
            }

        }
    }

    public static function get_from_token($the_token)
    {
        $args = array(
            'meta_key' => 'snillrik_robot_token',
            'meta_value' => $the_token,
            'post_type' => 'snillrik_robot',
        );

        $robot = get_posts($args);
        return isset($robot[0]) ? $robot[0] : false;
    }

    public static function robot_extra_content($content)
    {
        global $post;
        
        $current_user = wp_get_current_user();
        $post_user_id = isset($post->post_author) ? $post->post_author : "";

        if (isset($post->post_type) && $post->post_type == 'snillrik_robot'){// && $current_user->ID == $post_user_id) {

            //$datetext = $dateend == "" ? $datestart : "$datestart till $dateend";
            $ipnumber = get_post_meta($post->ID, 'snillrik_robot_ip', true);

            $content .= do_shortcode("[snillrik_robot]");
            $content .= "<input type='hidden' id='snillrik_robot_ip' value='$ipnumber' />";
            $content .= "<br /><strong>IP: </strong> $ipnumber<br />";
            $content .= "<strong>Status: </strong> <span id='snillrik_connecting_status'>Connecting...</span><br />";

            $content .= "<div class='snillrik_robot_buttons'>";
            $content .= "<a id='snillrik_robot_right'>Right</a><br />";
            $content .= "<a id='snillrik_robot_left'>Left</a><br />";
            $content .= "<a id='snillrik_robot_straight'>Straight</a><br />";
            $content .= "<a id='snillrik_robot_forward'>Speed +</a><br />";
            $content .= "<a id='snillrik_robot_back'>Speed -</a><br />";
            $content .= "<a id='snillrik_robot_stop'>Stop</a><br /></div>";

            $gampadinfo = get_post_meta($post->ID, 'snillrik_buttons_and_axis', true);
            $content .= "<input type='hidden' id='snillrik_controller_axis_and_buttons' value='$gampadinfo'/>";
            $content .= "<div id='snillrik_controller_text'>Wat</div>";

        }
        $beforecontent = '';
        //$beforecontent = '<video autoplay="true" id="videoElement" style="width:100%;min-height: 420px;"></video>';
        //$beforecontent = '<video autoplay="true" src="http://192.168.0.53:8099/webcam.ogg" id="videoElement2" style="width:100%;min-height: 420px;"></video>';
		//$beforecontent = '<img src="http://192.168.0.53:8099" id="videoElement2" style="width:100%;min-height: 420px;"/>';
        
        return $beforecontent . $content;
    }

    /**
     * Get the first image of the attachments
     */
    public static function first_image($parid, $path = false)
    {
        $attachment = get_children(
            array(
                'post_parent' => $parid,
                'post_type' => 'attachment',
                'post_mime_type' => 'image',
                'order' => 'DESC',
                'numberposts' => 1,
            )
        );
        if (!is_array($attachment) || empty($attachment)) {
            return false;
        }
        $attachment = current($attachment);

        return $path ? get_attached_file($attachment->ID) : wp_get_attachment_url($attachment->ID, 'thumbnail');
    }

    public function call_robot()
    {
        $url = wp_get_referer();
        $post_id = url_to_postid($url);

        $action = isset($_POST["robot_action"]) ? sanitize_text_field($_POST["robot_action"]) : false;
        $ipnumber = get_post_meta($post_id, 'snillrik_robot_ip', true);
        
        $urlen = "http://$ipnumber/?wat=$action";
        //wp_remote_post( $urlen, array $args = array() )
        wp_remote_get($urlen);
        echo wp_send_json($urlen);
        wp_die();
    }
    
    public static function get_robotip()
    {
        $url = wp_get_referer();
        $post_id = url_to_postid($url);
        $ipnumber = get_post_meta($post_id, 'snillrik_robot_ip', true);
        echo wp_send_json($ipnumber);
        wp_die();
    }

    /**
     * Fuskar lite så länge.
     */
    public static function snillrik_fetchsessontoken()
    {
        $url = wp_get_referer();
        $post_id = url_to_postid($url);
        //$sessiontoken = get_post_meta($post_id, 'snillrik_robot_sessiontoken', true);
        $sessiontoken = get_post_meta($post_id, 'snillrik_robot_token', true);
        
        echo wp_send_json($sessiontoken);
        wp_die();
    }    
    

}
