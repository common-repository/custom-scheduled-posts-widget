<?php

/**
 * @package CuSchost
 * @version 1.0
 */
/*
  Plugin Name: Custom scheduled Posts Widget
  Plugin URI: http://wordpress.org/extend/plugins/CuSchost/
  Description: Custom skeduled Posts. This widget gives you the ability to show  skeduled posts on the widget content.
  Version: 1.0
  Author URI: http://chipree.com
 */

class CuSchost extends WP_Widget {

    function CuSchost() {
        parent::WP_Widget(true, $name = 'Custom Skeduled Posts');
    }

    /** @see WP_Widget::form */
    function form($instance) {

        $title = esc_attr($instance['title']);
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label> 
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
        </p>
        <?php
    }

    /** @see WP_Widget::update */
    function update($new_instance, $old_instance) {
        $instance = $old_instance;
        $instance['title'] = strip_tags($new_instance['title']);
        return $instance;
    }

    /** @see WP_Widget::widget */
    function widget($args, $instance) {
        global $wpdb;
        extract($args);
        $title = apply_filters('widget_title', $instance['title']);
        ?>
        <?php echo $before_widget; ?>
        <?php
        if ($title)
            echo $before_title . $title . $after_title;

        $fivesdrafts = $wpdb->get_results("SELECT * FROM `" . $wpdb->prefix . "cuschost` WHERE `xstart_date` <= '" . (date("Y-m-d")) . "' AND xend_date >= '" . (date("Y-m-d")) . "';");
        $postIDlist = "";
        foreach ($fivesdrafts as $fivesdraft) {
            $postIDlist .= $fivesdraft->xpost_ID . ",";
        }
        ?>
        <ul>
            <?php
            global $post;
            $tmp_post = $post;
            $args = array('include' => $postIDlist);
            $myposts = get_posts($args);
            foreach ($myposts as $post) : setup_postdata($post);
                ?>
                <li><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></li>
            <?php endforeach; ?>
            <?php $post = $tmp_post; ?>
        </ul>
        <?php
        echo $after_widget;
    }

}

function cuschost_Activate() {
    global $wpdb;
    $wpdb->query('CREATE TABLE IF NOT EXISTS `' . $wpdb->prefix . 'cuschost` 
        (`xID` INT(9) NOT NULL AUTO_INCREMENT PRIMARY KEY, 
        `xpost_ID` INT(9) NOT NULL, 
        `xstart_date` DATE NOT NULL, 
        `xend_date` DATE NOT NULL) ENGINE = MyISAM;');
}

add_action('widgets_init', create_function('', 'return register_widget("CuSchost");'));
//////////////////////////////////////

/* Define the custom box */

// WP 3.0+
// add_action( 'add_meta_boxes', 'myplugin_add_custom_box' );
// backwards compatible
add_action('admin_init', 'cuschost_add_custom_box', 1);

/* Do something with the data entered */
add_action('save_post', 'cuschost_save_postdata');

/* Adds a box to the main column on the Post and Page edit screens */

function cuschost_add_custom_box() {
    add_meta_box(
            'cuschost_sectionid', __('Custom Skeduled Post', 'cuschost_textdomain'), 'cuschost_inner_custom_box', 'post'
    );
    add_meta_box(
            'cuschost_sectionid', __('Custom Skeduled Post', 'cuschost_textdomain'), 'cuschost_inner_custom_box', 'page'
    );
}

/* Prints the box content */

function cuschost_AddStyles() {
    wp_register_style('StyleA', WP_PLUGIN_URL . '/CuSchost/jquery-ui.css');
    wp_enqueue_style('StyleA');
}

function cuschost_AddScripts() {

    wp_register_script('scriptA', WP_PLUGIN_URL . '/CuSchost/jquery-ui.js');
    wp_register_script('scriptB', WP_PLUGIN_URL . '/CuSchost/script.js');
    wp_enqueue_script('jquery');

    wp_enqueue_script('scriptA');
    wp_enqueue_script('scriptB');
}

function cuschost_inner_custom_box() {

    // Use nonce for verification
    wp_nonce_field(plugin_basename(__FILE__), 'cuschost_noncename');
    global $wpdb;
    global $post;

    $xdata = $wpdb->get_row("SELECT * FROM `" . $wpdb->prefix . "cuschost` WHERE `xpost_ID` = " . $post->ID . ";");

    // The actual fields for data entry
    echo '<label for="cuschost_start_date">';
    _e("Start Date", 'cuschost_textdomain');
    echo '</label> ';
    $xstart = $xdata->xstart_date;
    $xend = $xdata->xend_date;
    echo '<input type="text" id="cuschost_start_date" class="datepickA" name="cuschost_start_date" value="' . $xstart . '" size="25" />';
    echo '<label for="cuschost_start_date">';
    _e("End Date", 'cuschost_textdomain');
    echo '</label> ';
    echo '<input type="text" id="cuschost_end_date" class="datepickB" name="cuschost_end_date" value="' . $xend . '" size="25" />';
}

/* When the post is saved, saves our custom data */

function cuschost_save_postdata($post_id) {
    global $wpdb;
    global $post;
    $xpost_ID = $post->ID;
    // verify if this is an auto save routine. 
    // If it is our form has not been submitted, so we dont want to do anything
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
        return;

    // verify this came from the our screen and with proper authorization,
    // because save_post can be triggered at other times
    if (!wp_verify_nonce($_POST['cuschost_noncename'], plugin_basename(__FILE__)))
        return;
    // Check permissions
    if ('page' == $_POST['post_type']) {
        if (!current_user_can('edit_page', $post_id))
            return;
    }
    else {
        if (!current_user_can('edit_post', $post_id))
            return;
    }

    //saving the data to the database :

    $cuschost_start_date = $_POST['cuschost_start_date'];
    $cuschost_end_date = $_POST['cuschost_end_date'];

    $xIDexists = 0;

    $xIDexists = $wpdb->get_row('SELECT * FROM `' . $wpdb->prefix . 'cuschost` WHERE xpost_ID =' . $xpost_ID . ';', 0, 0);
    $xID = $xIDexists->xID;

    if ($xID > 0) {
        $wpdb->update($wpdb->prefix . 'cuschost', array('xstart_date' => $cuschost_start_date, 'xend_date' => $cuschost_end_date), array('xpost_ID' => $xpost_ID), array("%s", "%s"), array("%d"));
    } else {
        $rows_affected = $wpdb->insert($wpdb->prefix . 'cuschost', array('xpost_ID' => $xpost_ID, 'xstart_date' => $cuschost_start_date, 'xend_date' => $cuschost_end_date));
    }


    return $mydata;
}

add_action('admin_print_styles', 'cuschost_AddStyles');
add_action('admin_print_scripts', 'cuschost_AddScripts');
register_activation_hook(__FILE__, 'cuschost_Activate');
?>
