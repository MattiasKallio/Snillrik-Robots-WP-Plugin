<?php
/**
 *
 * The settings page for the plugin.
 */
new SNRobot_settings();
class SNRobot_settings
{

    public function __construct()
    {
        add_action('admin_menu', array($this, 'snillrik_settings_create_menu'));
    }

    public function snillrik_settings_create_menu()
    {
        add_menu_page(
            'Snillrik robot',
            'Snillrik robots',
            'administrator',
            __FILE__,
            array($this,'snillrik_settings_page'),
            plugins_url('/images/snillrik_bulb.svg', __FILE__)
        );
    }

    public function snillrik_settings_page()
    {
        echo "<div class=wrap snillrik-robot-settings'>
            <h1>How2</h1>
            <p>Add robot items under Robots, works like an ordinary post in WP with a table of dates and times added to the bottom.</p>
            <h3>Shortcodes</h3>
            <p>Display the robot items with the shortcodes:</p>
            [snillrik_robot] with the parameters category, numposts, skip and type.<br />
            <strong>Example: </strong>[snillrik_robot category=\"Catpix\" numposts=\"4\" skip=\"2\" type=big1|big2|big3|big4]<br />
            [snillrik_robot_swipe] that is displayed with the <a href='https://swiperjs.com/'>Swiper</a> jvascript plugin.<br />
            <strong>Example: </strong>[snillrik_robot_swipe category=\"Catpix\" numposts=\"4\" skip=\"2\" slidesperview=\"1\" effect=\"coverflow\"[cube|flip|fade|coverflow]]<br />
        </div>";
    }
}
