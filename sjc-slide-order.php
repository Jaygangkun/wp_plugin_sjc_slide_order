<?php
/**
* Plugin Name: SJC Slide Order
* Plugin URI: 
* Description: 
* Version: 1.0.0
* Author: Jay
* Author URI: 
* License: 
*/

define ('TB_NAME', 'sjc_slide_order');

function sjc_register_scripts($hook) {

    if( $hook != 'toplevel_page_sjc-slide-order' ) {
    
        return;
    
    }

    wp_register_style( 'bootstrap-css', plugins_url( 'sjc-slide-order/bootstrap.min.css' ) );
    wp_register_style( 'datatables-css', plugins_url( 'sjc-slide-order/datatables.min.css' ) );
    wp_register_style( 'sjc-css', plugins_url( 'sjc-slide-order/sjc.css' ) );
    
    wp_register_script( 'bootstrap-js', plugins_url( 'sjc-slide-order/bootstrap.min.js' ) );
    wp_register_script( 'datatables-js', plugins_url( 'sjc-slide-order/datatables.min.js' ) );
    wp_register_script( 'sjc-js', plugins_url( 'sjc-slide-order/sjc.js' ) );
    

    wp_enqueue_style( 'bootstrap-css' );
    wp_enqueue_style( 'datatables-css' );
    wp_enqueue_style( 'sjc-css' );
    
    wp_enqueue_script( 'bootstrap-js' );
    wp_enqueue_script( 'datatables-js' );
    wp_enqueue_script( 'sjc-js' );
}

add_action( 'admin_enqueue_scripts', 'sjc_register_scripts' );

// plugin deactive
function sjc_deactive() {
    delete_option('sjc_option_db_init');
}

register_deactivation_hook( __FILE__, 'sjc_deactive' );

// ajax calls
function sjc_order_change() {
    global $wpdb;

    $resp = array('success' => true);

    $results = $wpdb->get_results('SELECT * FROM '.TB_NAME.' WHERE slide_order='.$_POST['slide_order']);
    if(count($results) > 0) {
        $resp['success'] = false;
        $resp['message'] = 'Order already exist.';
        echo json_encode($resp);
        die();
    }

    $results = $wpdb->get_results('SELECT * FROM '.TB_NAME.' WHERE post_id='.$_POST['post_id']);
    if(count($results) > 0) {
     
        // Update
        $wpdb->query('UPDATE '.TB_NAME.' SET slide_order='.$_POST['slide_order'].' WHERE post_id='.$_POST['post_id']);
        echo json_encode($resp);    
        die();
    }

    $post_title = get_the_title($_POST['post_id']);

    if(!$wpdb->query('INSERT INTO '.TB_NAME.'(`post_id`, `post_title`, `slide_order`) VALUES("'.$_POST['post_id'].'", "'.$post_title.'", "'.$_POST['slide_order'].'")')) {
        $resp['success'] = false;
        $resp['message'] = 'Insert into table is failed!';
    }

    echo json_encode($resp);
    die();
}

add_action("wp_ajax_sjc_order_change", "sjc_order_change");
add_action("wp_ajax_nopriv_sjc_order_change", "sjc_order_change");

function sjc_order_clear() {
    global $wpdb;

    $resp = array('success' => true);

    $result = $wpdb->delete(TB_NAME, ['post_id' => $_POST['post_id']]);
    if(!$result) {
        $resp['success'] = false;
        $resp['message'] = 'Delete row is failed!';
    }

    echo json_encode($resp);
    die();
}

add_action("wp_ajax_sjc_order_clear", "sjc_order_clear");
add_action("wp_ajax_nopriv_sjc_order_clear", "sjc_order_clear");

function sjc_table_overwrite() {
    global $wpdb;

    $resp = array('success' => true);

    if(!$wpdb->query('TRUNCATE TABLE '.TB_NAME)) {
        $resp['success'] = false;
        $resp['message'] = 'Empty table failed!';
    }

    add_option('sjc_option_db_init', true);

    echo json_encode($resp);
    die();
}

add_action("wp_ajax_sjc_table_overwrite", "sjc_table_overwrite");
add_action("wp_ajax_nopriv_sjc_table_overwrite", "sjc_table_overwrite");

function sjc_table_keep() {
    global $wpdb;

    $resp = array('success' => true);

    add_option('sjc_option_db_init', true);
    
    echo json_encode($resp);
    die();
}

add_action("wp_ajax_sjc_table_keep", "sjc_table_keep");
add_action("wp_ajax_nopriv_sjc_table_keep", "sjc_table_keep");


// admin page
add_action( 'admin_menu', 'sjc_register_menu_page');
function sjc_register_menu_page() {
  add_menu_page( 'SJC Slide Order', 'SJC Slide Order', 'manage_options', 'sjc-slide-order', 'sjc_page', 'dashicons-welcome-widgets-menus', 90 );
}

function sjc_page() {
    global $wpdb;
    ?>
    <div class="container-fluid py-3">
        <h2>SJC Slide Order</h2>    
        <?php
        // $table_name = $wpdb->base_prefix.'custom_prices';
        $table_name = TB_NAME;
        
        $query = $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name);
        
        if ( ! $wpdb->get_var( $query ) == $table_name ) {
            $wpdb->query( 'CREATE TABLE '.$table_name.' (id INT NOT NULL AUTO_INCREMENT, post_id INT, post_title VARCHAR(255), slide_order INT, PRIMARY KEY (id))');
        }
        else {
            if(!get_option('sjc_option_db_init')) {
                ?>
                <div class="alert alert-primary" role="alert" id="alert_message">
                    <h6>Table already exist.</h6>
                    <h6>Do you want to keep or overwrite?</h6>
                    <div>
                        <button type="button" class="btn btn-primary" id="btn_table_keep">Keep</button>
                        <button type="button" class="btn btn-primary" id="btn_table_overwrite">Overwrite</button>
                    </div>
                </div>
                <?php

            }
            ?>
            <?php
        }
        ?>
        <table class="table table-striped table-hover" id="sjc_slide_order_table">
            <thead>
                <tr>
                    <td class="w-10" scope="col">Post ID</td>
                    <td class="w-50" scope="col">Post Title</td>
                    <td class="w-40" scope="col">Slide Order</td>
                </tr>
            </thead>
            <tbody>
                <?php
                
                // get records from TB_NAME
                $slides = $wpdb->get_results('SELECT * FROM '.TB_NAME.' ORDER BY slide_order ASC');
                $slide_post_ids = array();
                foreach($slides as $slide) {
                    $slide_post_ids[] = $slide->post_id;
                    ?>
                    <tr>
                        <td><?php echo $slide->post_id?></td>
                        <td><a target="_blank" href="<?php echo get_permalink($slide->post_id)?>"><?php echo get_the_title($slide->post_id)?></a></td>
                        <td>
                            <div class="d-flex order-input-wrap" id="<?php echo $slide->post_id ?>">
                                <input type="text" class="border form-control w-25" value="<?php echo $slide->slide_order?>"><span class="btn btn-primary ms-3 d-inline btn-order-change">Change</span><span class="btn btn-danger ms-3 d-inline btn-order-clear">Remove</span>
                            </div>
                        </td>
                    </tr>
                    <?php
                }

                // get all posts
                $args = array(
                    'post_type' => array('post'),
                    'post_status' => 'publish',
                    'posts_per_page' => -1,
                    'ignore_sticky_posts' => true,
                    'orderby' => 'post_date',
                    'order' => 'DESC'
                );
                $qry = new WP_Query($args);
                // Show post titles
                foreach ($qry->posts as $post) { 
                    if(!in_array($post->ID, $slide_post_ids)) {
                        ?>
                        <tr>
                            <td><?php echo $post->ID?></td>
                            <td><a target="_blank" href="<?php echo get_permalink($post->ID)?>"><?php echo $post->post_title?></a></td>
                            <td>
                                <div class="d-flex order-input-wrap" id="<?php echo $post->ID ?>">
                                    <input type="text" class="border form-control w-25"><span class="btn btn-primary ms-3 d-inline btn-order-change">Change</span>
                                </div>
                            </td>
                        </tr>
                        <?php
                    }
                }
                ?>
            </tbody>
        </table>
    </div>
    <script>
        var ajax_url = '<?php echo admin_url('admin-ajax.php')?>';
    </script>
    <?php
    
}