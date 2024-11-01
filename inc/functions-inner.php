<?php
//
//    error_reporting(E_ALL);
//    ini_set("display_errors", 1);

    global $package_prefix, $woo_inst_pro, $woo_inst_dir;

    $package_prefix = '_wi_packages';
	
	 if(!function_exists('woo_inst_pro_enabled_check')){
		function woo_inst_pro_enabled_check($post_id){
			global $woo_inst_pro_settings;
			$woo_inst_pro_settings['package'] = (isset($woo_inst_pro_settings['package']) && is_array($woo_inst_pro_settings['package']))?$woo_inst_pro_settings['package']:array();
			$woo_inst_pro_settings['full'] = (isset($woo_inst_pro_settings['full']) && is_array($woo_inst_pro_settings['full']))?$woo_inst_pro_settings['full']:array();
			
			$ret = (array('package'=>in_array($post_id, $woo_inst_pro_settings['package']), 'full'=>in_array($post_id, $woo_inst_pro_settings['full'])));		
			
			return $ret; 
		}
	}
	
    if(!function_exists('woo_inst_get_packages_name')){
        function woo_inst_get_packages_name($product_id){


            $product_meta = get_post_meta($product_id);
            $meta_names = array_keys($product_meta);


            $package_meta_names = array_map(function ($meta_name){
                global $package_prefix;


                $str_pos = strpos($meta_name, $package_prefix);

                if($str_pos !== false){
                    return $meta_name;
                }

            }, $meta_names);

            $package_meta_names = array_filter($package_meta_names);


            return $package_meta_names;

        }
    }

//    add_action('init', 'woo_inst_get_package_id');

    if(!function_exists('woo_inst_get_package_id')){

        function woo_inst_get_package_id($product_id){

//            $product_id = 2257;


            $default_package = 0;


            $package_meta_names = woo_inst_get_packages_name($product_id);
            $default_package = get_post_meta($product_id, '_woo_inst_package_id', true);

            if($default_package === false){

                $default_package = '1';

            }else{

                $default_package++;
            }

            update_post_meta($product_id, '_woo_inst_package_id', $default_package);

            return trim($default_package);


        }

    }

    add_action('admin_init', 'woo_inst_copy_old_packages');

    if(!function_exists('woo_inst_copy_old_packages')) {

            function woo_inst_copy_old_packages()
        {


            $old_data_update_status = get_option('woo_inst_old_update', false);

            if (!$old_data_update_status) {


                $args = array(
                    'post_type' => 'product',
                    'numberposts' => -1,
                    'post_status' => 'publish',
                    'fields' => 'ids',
                    'meta_query' => array(
                        array(

                            'key' => 'woo_inst_tiers_list',
                            'compare' => "EXIST",
                        )
                    )
                );

                $lock_post_args = array(
                    'post_type' => 'any',
                    'numberposts' => -1,
                    'post_status' => 'publish',
                    'fields' => 'ids',
                    'meta_query' => array(
                        array(

                            'key' => 'woo_inst_products',
                            'compare' => "EXIST",
                        )
                    )
                );

                $order_args = array(

                    'post_type' => 'shop_order',
                    'numberposts' => -1,
                    'post_status' => 'any',
                    'fields' => 'ids',
                    'meta_query' => array(
                        array(

                            'key' => 'woo_inst_cart_tiers',
                            'compare' => "EXIST",
                        )
                    )
                );

                $lock_post_ids = get_posts($lock_post_args);
                $order_ids = get_posts($order_args);
                $product_ids = get_posts($args);

    //            pree($product_ids);exit;



                if (!empty($product_ids)) {


                    $result_array = array();


                    foreach ($product_ids as $product_id) {


                        $woo_inst_tiers_list = get_post_meta($product_id, 'woo_inst_tiers_list', true);


                        if ($woo_inst_tiers_list && !empty($woo_inst_tiers_list)) {

                            global $package_prefix;

                            $arr = explode("\n", $woo_inst_tiers_list);
                            array_walk($arr,
                                function (&$val) {


                                    list($price, $desc) = explode('|', $val);
                                    $val = array('price' => $price, 'title' => $desc);

                                });


                            if(!empty($arr)){

                                foreach ($arr as $single_data){

                                        $new_package_id = woo_inst_get_package_id($product_id);
                                        $old_package_id = $single_data['price'];

                                        $new_package_key = $product_id."|".$new_package_id;
                                        $old_package_key = $product_id."|".$old_package_id;



                                        if(!empty($order_ids)){

                                            foreach ($order_ids as $order_id){

                                                $order_cart_tiers = get_post_meta($order_id, 'woo_inst_cart_tiers', true);

                                                if(!empty($order_cart_tiers)){

                                                    if(array_key_exists($product_id, $order_cart_tiers)){

                                                        $key_index = array_search($old_package_id, $order_cart_tiers[$product_id]);
                                                        if($key_index !== false){
                                                            $order_cart_tiers[$product_id][$key_index] = $new_package_id;
                                                            update_post_meta($order_id, 'woo_inst_cart_tiers', $order_cart_tiers);
                                                        }

                                                    }

                                                }

                                            }

                                        }

                                        if(!empty($lock_post_ids)){

                                            foreach ($lock_post_ids as $post_id){

                                                $lock_post_products = get_post_meta($post_id, 'woo_inst_products', true);


                                                if($lock_post_products !== false && !empty($lock_post_products)){

                                                    $lock_index = array_search($old_package_key, $lock_post_products);

                                                    if($lock_index !== false){

                                                        $lock_post_products[$lock_index] = $new_package_key;
                                                        update_post_meta($post_id, 'woo_inst_products', $lock_post_products);

                                                    }


                                                }


                                            }
                                        }


                                    $meta_id = update_post_meta($product_id, $package_prefix . "_" . $new_package_id, $single_data);

                                }

                            }

                        }


                    }

                }

                update_option('woo_inst_old_update', true);


            }

        }
    }



    if(!function_exists('woo_inst_get_packages_data_array')){

        function woo_inst_get_packages_data_array($product_id){

            $product_meta = get_post_meta($product_id);
            $packages_name = woo_inst_get_packages_name($product_id);
            global $package_prefix;

            $package_result = array();

            if(!empty($packages_name)){
                foreach ($packages_name as $package_name){


                    $package_array = explode('_', $package_name);
                    $package_array = array_reverse($package_array);

                    $package_id = current($package_array);

                    $package_data = get_post_meta($product_id, $package_prefix.'_'.$package_id, true);
					
					//pree($package_data);exit;



                    if(!isset($package_data['price']) || empty($package_data['price'])){

                        //echo "delete";

                        delete_post_meta($product_id, $package_prefix.'_'.$package_id);

                    }else{


                        $des = array_key_exists('e_description', $package_data) ? $package_data['e_description'] : "";

                        $package_data['description'] = '<span class="title"><strong>'.$package_data['title'].'</strong></span><div class="description">'.$des.'</div>';



                        $package_result[$package_id] = $package_data;


                    }
                }
            }

            return $package_result;

        }

    }


    add_action('wp_ajax_woo_inst_save_package_meta', 'woo_inst_save_package_meta');

    if(!function_exists('woo_inst_save_package_meta')){

        function woo_inst_save_package_meta(){


            if(isset($_POST['woo_inst_save_package_meta'])){

                global $package_prefix;


                $product_id = sanitize_winst_data($_POST['woo_inst_product_id']);

                $default_package = woo_inst_get_package_id($product_id);
                $package_id = trim($package_prefix)."_".$default_package;

                $meta_id = update_post_meta($product_id, $package_id, array());


                echo trim($default_package);

                exit;

            }




            wp_die();
        }
    }


    add_action('save_post', 'woo_inst_save_packages_data');

    if(!function_exists('woo_inst_save_packages_data')){

        function woo_inst_save_packages_data($product_id){


//            pree($_POST);exit;

            global  $package_prefix;

            if(isset($_POST['woo_inst_package_data']) && isset($_POST['woo_inst_save_package'])){

//                pree($_POST['woo_inst_package_data']);

//                pree($_POST);exit;


                $package_data = sanitize_winst_data($_POST['woo_inst_package_data']);

//                pree($package_data);exit;

                if(!empty($package_data)){

                    foreach ($package_data as $package_id => $data){
                        $package_id = trim($package_id);
                      $price_check = strlen($data['price']);

                      if($price_check === 0){

                          delete_post_meta($product_id, $package_prefix.'_'.$package_id);

                      }else{

                          update_post_meta($product_id, $package_prefix.'_'.$package_id, $data);

                      }

                    }

                }

                if(isset($_POST['woo_inst_post_lock'])){

                    $woo_inst_post_lock = sanitize_winst_data($_POST['woo_inst_post_lock']);

//                    pree($_POST);exit;


                    if(!empty($woo_inst_post_lock)){

                        foreach ($woo_inst_post_lock as $package_id => $woo_inst_products_current){

                            $package_id = trim($package_id);

                            $woo_inst_products_prev = woo_inst_get_post_ids_lock_by_package($package_id);

                            $woo_inst_remove_packages = array_diff($woo_inst_products_prev, $woo_inst_products_current);
                            $woo_inst_no_change_packages = array_intersect($woo_inst_products_prev, $woo_inst_products_current);
                            $woo_inst_need_change_packages = array_diff($woo_inst_products_current, $woo_inst_no_change_packages);

                            if(!empty($woo_inst_remove_packages)){

                                foreach ($woo_inst_remove_packages as $remove_post_id){

                                    $woo_inst_get_products = get_post_meta($remove_post_id, 'woo_inst_products', true);
                                    $remove_package_index = array_search($package_id, $woo_inst_get_products);

                                    if($remove_package_index !== false){

                                        array_splice($woo_inst_get_products, $remove_package_index, 1);
                                        update_post_meta($remove_post_id, 'woo_inst_products', $woo_inst_get_products);

                                    }
                                }


                            }


                            if(!empty($woo_inst_need_change_packages)){

                                foreach ($woo_inst_need_change_packages as $post_id){

                                    $woo_inst_products = get_post_meta($post_id, 'woo_inst_products', true);
                                    $woo_inst_products = is_array($woo_inst_products) ? $woo_inst_products : array();

                                    $value_exist = in_array($package_id, $woo_inst_products);

                                    if($value_exist === false){

                                        array_push($woo_inst_products, $package_id);
                                        update_post_meta($post_id, 'woo_inst_products', $woo_inst_products);

                                    }
                                }
                            }
                        }
                    }
                }
            }

//            exit;

        }

    }


    add_action('wp_ajax_woo_inst_del_package_meta', 'woo_inst_del_package_meta');

    if(!function_exists('woo_inst_del_package_meta')){

        function woo_inst_del_package_meta(){

            if(isset($_POST['woo_inst_del_package_meta'])){

                global $package_prefix;


                $package_id = sanitize_winst_data($_POST['woo_inst_package_id']);
                $product_id = sanitize_winst_data($_POST['woo_inst_product_id']);

                $del_package = delete_post_meta($product_id, $package_prefix.'_'.$package_id);

                echo $del_package;

            }

            wp_die();
        }
    }


    add_action('woocommerce_before_add_to_cart_form', 'woo_inst_add_to_cart_form_wrapper', 1000);
    add_action('woocommerce_after_add_to_cart_form', 'woo_inst_add_to_cart_form_wrapper_close', 1);

    if(!function_exists('woo_inst_add_to_cart_form_wrapper')){
        function woo_inst_add_to_cart_form_wrapper(){

            global  $post, $woo_inst_pro, $woo_inst_selected_template;

            $woo_inst_enabled = woo_inst_enabled_check($post->ID);


            $woo_inst_pro_enabled = woo_inst_pro_enabled_check($post->ID);
            $package = $woo_inst_pro_enabled['package'];
            $full = $woo_inst_pro_enabled['full'];
            $woo_inst_tiers_list = woo_inst_get_tiers_list($post->ID);


            if( $woo_inst_selected_template != 'package' && !empty($woo_inst_tiers_list) && $woo_inst_enabled){


                echo '<div class="woo_inst_add_to_cart_wrapper" style="display: none">';


            }


        }
    }

    if(!function_exists('woo_inst_add_to_cart_form_wrapper_close')){
        function woo_inst_add_to_cart_form_wrapper_close(){


            global  $post, $woo_inst_selected_template;

            $woo_inst_enabled = woo_inst_enabled_check($post->ID);

            $woo_inst_pro_enabled = woo_inst_pro_enabled_check($post->ID);
            $package = $woo_inst_pro_enabled['package'];
            $full = $woo_inst_pro_enabled['full'];
            $woo_inst_tiers_list = woo_inst_get_tiers_list($post->ID);


            if($woo_inst_selected_template != 'package' && !empty($woo_inst_tiers_list) && $woo_inst_enabled) {

                echo '</div>';

            }

        }
    }

    add_action('admin_init', 'test_function_2');
    function test_function_2(){

        $already = array(1, 2, 3);
        $now = array(2, 3 ,4);

//        pree(array_intersect($already, $now));exit;

//        pree(get_post_meta(1, 'woo_inst_products', true));exit;

//        echo "hello test"; exit;

//        woo_inst_get_enabled_products();exit;
//
//       $ret =  woo_inst_get_tiers_list(2257);
//
//       pree($ret);exit;

    }

    if(!function_exists('woo_inst_get_pkg_enabled_select')) {

        function woo_inst_get_pkg_enabled_select($post_id)
        {

            $products = woo_inst_get_enabled_products();
            global $woo_inst_currency;
//            pree($products);

            if (!empty($products)) {

                $applied_products = get_post_meta($post_id, 'woo_inst_products', true);
                $applied_products = is_array($applied_products) ? $applied_products : array();
                //pree($applied_products);
                ?>
                <select name="woo_inst_products[<?php echo $post_id ?>][]" style="width:100%; display: none; min-height: 200px;" multiple="multiple" size="5">
                    <option value="0"> - </option>
                    <?php

                    foreach ($products as $prod) {
                        $product = wc_get_product($prod->ID);
                        $woo_inst_tiers_list = woo_inst_get_tiers_list($prod->ID);

                        if (!empty($woo_inst_tiers_list)) {
                            ?>
                            <optgroup
                                label="<?php echo $prod->post_title . ' ' . $woo_inst_currency . $product->get_price(); ?>">
                                <?php foreach ($woo_inst_tiers_list as $package_key => $list) {
                                    $key = $prod->ID . '|' . $package_key; ?>
                                    <option
                                        value="<?php echo $key; ?>" <?php selected(in_array($key, $applied_products)); ?>><?php echo $woo_inst_currency . $list['price'] . ' - ' . $list['description']; ?></option>
                                <?php } ?>
                            </optgroup>


                            <?php
                        }
                    }
                    ?>
                </select>

                <?php

            }
        }

    }

    add_action('init', 'woo_inst_get_post_ids_lock_by_package');

    if(!function_exists('woo_inst_get_post_ids_lock_by_package')){

        function woo_inst_get_post_ids_lock_by_package($package_key){

//            $package_key = '2257|1s';

            $args = array(

                    'numberposts' => -1,
                    'post_type' => 'any',
                    'post_status' => 'publish',
                    'fields' => 'ids',
                    'meta_query' => array(

                            array(

                            'key' => 'woo_inst_products',
                            'value' => $package_key,
                            'compare' => 'LIKE',

                            )

                    )
            );

            $post_ids = get_posts($args);

            return $post_ids;

//            pree($post_ids);exit;

        }

    }

    if(!function_exists('woo_inst_get_ptypes_selection')){
        function woo_inst_get_ptypes_selection($product_id = "-1", $package_id = "-1"){

//            global $post;

            $package_key = $product_id.'|'.$package_id;
            $woo_inst_ptypes = get_option('woo_inst_ptypes', array( 'post', 'page' ));
            $post_types = get_post_types();
//            pree($package_key);
            $current_package_posts = woo_inst_get_post_ids_lock_by_package($package_key);

//            pree($package_key);

            if(!empty($post_types)){



                ?>
                <select class="woo_inst_post_lock" name="woo_inst_post_lock[<?php echo $package_key ?>][]" style="width:100%; min-height: 200px;" multiple="multiple" size="5" title="<?php _e('Select posts to lock against this package', 'woo-installments')?>" >
                    <option value="" disabled> - </option>
                    <?php

                        foreach($post_types as $key=>$val){

                            $args = array(
                                    'numberposts' => -1,
                                    'post_type' => $val,
                                    'post_status' => 'publish',
                            );


                            $posts = get_posts($args);

                            if(!in_array($val, $woo_inst_ptypes)) continue;


                    ?>
                            <optgroup label="<?php echo woo_inst_humanize($val); ?>">

                                <?php

                                    if(!empty($posts) && in_array($key, $woo_inst_ptypes)){

                                        foreach ($posts as $post){

                                            ?>

                                                    <option value="<?php echo $post->ID ?>" <?php selected(in_array($post->ID, $current_package_posts)) ?>>
                                                        <?php echo $post->post_title ?>
                                                    </option>

                                            <?php
                                        }

                                    }

                                ?>

                            </optgroup>
                    <?php

                        }

                    ?>
                </select>
                <?php

            }
        }
    }




    if($woo_inst_pro){

        $extended_template = $woo_inst_dir.'pro/wi_extended_2.php';

        if(file_exists($extended_template)){

            include_once($woo_inst_dir.'pro/wi_extended_2.php');

        }

    }else{

        add_action('woo_inst_add_new_tab_form', 'woo_inst_add_new_tab_form_callback');

        if(!function_exists('woo_inst_add_new_tab_form_callback')){

            function woo_inst_add_new_tab_form_callback(){

                global $woo_inst_premium_link, $woo_inst_url;

                ?>

                <div class="container-fluid">

                    <form class="nav-tab-content hide woo_inst_templates_wrapper" action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="post">

                        <input type="hidden" name="woo_inst_tn" value="<?php echo isset($_GET['t']) ? esc_attr($_GET['t']) : '0'; ?>" />

                        <div class="row">
                            <div class="col-md-12">

                                <div class="woo_inst_notes" style="width: 100%; line-height: 1.5;">
                                    <?php _e('This is a premium feature, please', 'woo-installments');?>
                                    <a href="<?php echo $woo_inst_premium_link ?>" target="_blank">
                                        <?php _e('click here', 'woo-installments');?></a>
                                    <?php _e('to get premium version.', 'woo-installments');?>

                                </div>

                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <a href="<?php echo $woo_inst_premium_link ?>" target="_blank" title="<?php _e('This is premium a feature.', 'woo-installments').' '.__('Click to get premium version', 'woo-installments');?>">
                                    <img src="<?php echo $woo_inst_url ?>/images/premium.png" style="width:98%" />
                                </a>
                            </div>
                        </div>



                    </form>

                </div>

                <?php

            }
        }

    }

    add_filter('woocommerce_account_menu_items', function($items){

        $new_items = array();

        if(!empty($items)){



            foreach ($items as $item_key => $item_value){


                $new_items[$item_key] = $item_value;

                if($item_key == 'orders'){


                    $new_items['woo-online-courses'] = __("Online Courses", "woo-installments");


                }

            }

        }




        return $new_items;

    });



    // register new endpoint to use for My Account page
    add_action( 'init', 'woo_inst_custom_endpoints' );
    function woo_inst_custom_endpoints() {

        add_rewrite_endpoint( 'woo-online-courses', EP_ROOT | EP_PAGES );

        if(!get_option('woo_inst_endpoint_flush')){

            flush_rewrite_rules();

            update_option('woo_inst_endpoint_flush', true);

        }

    } // end function

    function woo_inst_limit_text($string, $limit = 50){

        $string = strip_tags($string);
        if (strlen($string) > $limit) {

            // truncate string
            $stringCut = substr($string, 0, $limit);
            $endPoint = strrpos($stringCut, ' ');

            //if the string doesn't contain any space then it will cut without word basis.
            $string = $endPoint? substr($stringCut, 0, $endPoint) : substr($stringCut, 0);
            $string .= '...';
        }
        return $string;

    }




    function woo_inst_my_courses_html(){

        global $woo_inst_msgs_updated, $woo_inst_settings, $current_user, $woo_inst_msgs;

        $heading = array_key_exists('line-5', $woo_inst_msgs_updated) && $woo_inst_msgs_updated['line-5'] ? $woo_inst_msgs_updated['line-5'] : $woo_inst_msgs['line-5'];

        $woo_inst_products = isset($woo_inst_settings['woo_inst_products']) ? $woo_inst_settings['woo_inst_products'] : array();




        $args = array(

                'limit' => -1,
                'status' => 'publish',
                'author' => $current_user->ID,
                'include' => $woo_inst_products,

        );

        $courses = wc_get_products($args);


        ?>


        <div class="row mb-3">
            <div class="col-md-12">

                <div class="h3">
                    <?php echo $heading;?>
                </div>

            </div>
        </div>

        <div class="row">


            <?php

                if(!empty($courses)):

                    foreach ($courses as $course):


                    $packages_name = woo_inst_get_packages_name($course->get_id());
                    $count_package = count($packages_name);

                    $btn_string = empty($packages_name) ? __('Full course', 'woo-installments') : _n("$count_package Package", "$count_package Packages", $count_package, 'woo-installments');




            ?>


            <div class="col-md-6 mb-3 woo_inst_single_course">

                <div class="card">

                    <div class="card-body">

                    <div class="image_wrapper" style="width: 100%; height: 200px;">



                        <?php


                                echo $course->get_image();


                        ?>

                    </div>

                    <div class="text-center pt-4">

                        <h5 style="min-height: 60px;" title="<?php echo $course->get_name() ?>">
                            <?php echo woo_inst_limit_text($course->get_name());?>
                        </h5>


                        <hr>
                        <h6 class="mb-3"><?php echo $course->get_price_html(); ?></h6>

                        <a target="_blank" href="<?php echo get_edit_post_link($course->get_id()) ?>" type="button" class="btn btn-success btn-sm mr-1 mb-2" style="text-decoration: none;"><i
                                    class="fas fa-layer-group pr-2"></i><?php echo $btn_string ?></a>
                        <a target="_blank" href="<?php echo get_permalink($course->get_id()) ?>" type="button" class="btn btn-primary btn-sm mr-1 mb-2" style="text-decoration: none;"><i
                                    class="fas fa-eye pr-2"></i><?php _e('View', 'woo-installments') ?></a>



                    </div>

                    </div>

                </div>



            </div>


            <?php

                    endforeach;

                    else:

            ?>


            <div class="col-md-12">
                <div class="alert alert-warning">
                    <?php _e('No course found.', 'woo-installments') ?>
                </div>
            </div>


        <?php


        endif;


        ?>






        </div>


        <?php


    }


    function woo_inst_get_enrolled_courses_order_by_customer($customer_id){

        global $woo_inst_settings, $wpdb;

        $ghost_product = sanitize_winst_data(woo_inst_ghost_product_injection());
        $woo_inst_products = isset($woo_inst_settings['woo_inst_products']) ? sanitize_winst_data($woo_inst_settings['woo_inst_products']) : array();
        $woo_inst_products_str = implode(', ', $woo_inst_products);
        $order_items_table = $wpdb->prefix.'woocommerce_order_items';
        $order_items_meta_table = $wpdb->prefix.'woocommerce_order_itemmeta';
        $posts_table = $wpdb->posts;
        $post_meta_table = $wpdb->postmeta;


//        $query = "SELECT distinct order_id FROM  $table_name WHERE (product_id = $ghost_product OR product_id IN($woo_inst_products_str)) AND customer_id = $customer_id";


            $query = "SELECT DISTINCT order_items.order_id FROM $order_items_table as order_items
                        LEFT JOIN $order_items_meta_table items_meta on order_items.order_item_id = items_meta.order_item_id
                        LEFT JOIN $posts_table as posts on order_items.order_id = posts.ID
                        LEFT JOIN $post_meta_table as posts_meta on posts.ID = posts_meta.post_id
                        WHERE posts.post_type = 'shop_order' AND
                        items_meta.meta_key = '_product_id' AND (items_meta.meta_value = $ghost_product OR items_meta.meta_value IN($woo_inst_products_str))
                        AND posts_meta.meta_key = '_customer_user' AND posts_meta.meta_value = $customer_id";

//        echo $query;

        $results = $wpdb->get_results($query);
        $order_ids = array_map(function($single_result){

            return $single_result->order_id;

        }, $results);
        return $order_ids;

    }


    function woo_inst_package_table($product_packages_name, $show = array()){

        if(!empty($product_packages_name)){


            ?>


            <tr class="woo_inst_package_tr">
                <td colspan="5">
            <table class="table">
            <thead class="thead-light">
            <tr>
                <th scope="col">#</th>
                <th scope="col"><?php _e('Title', 'woo-installments') ?></th>
                <th scope="col"><?php _e('Price', 'woo-installments') ?></th>
                <th scope="col"><?php _e('Description', 'woo-installments') ?></th>
            </tr>
            </thead>
            <tbody>



            <?php

                $counter = 1;

            foreach ($product_packages_name as $index => $package){

                if(!empty($show) && !in_array($index, $show)){

                    continue;
                }


                ?>



                <tr>
                    <td scope="col"><?php echo $counter; ?></td>
                    <td scope="col"><?php echo $package['title'] ?></td>
                    <td scope="col"><?php echo wc_price($package['price']) ?></td>
                    <td scope="col"><?php echo $package['e_description'] ?></td>
                </tr>





                <?php
                $counter++;


            }


            ?>


                    </tbody>
                </table>
                </td>
            </tr>


            <?php



        }


    }




    function woo_inst_enrolled_courses_html(){

        global $woo_inst_msgs_updated, $woo_inst_settings, $current_user, $woo_inst_msgs;

        $ghost_product = sanitize_winst_data(woo_inst_ghost_product_injection());
        $woo_inst_products = isset($woo_inst_settings['woo_inst_products']) ? sanitize_winst_data($woo_inst_settings['woo_inst_products']) : array();
        $woo_inst_all = $woo_inst_products;
        $woo_inst_all[] = $ghost_product;

        $heading = array_key_exists('line-6', $woo_inst_msgs_updated) && $woo_inst_msgs_updated['line-6'] ? $woo_inst_msgs_updated['line-6'] : $woo_inst_msgs['line-6'];

        $current_user_courses = woo_inst_get_enrolled_courses_order_by_customer($current_user->ID);

//        pree($current_user_courses);

        if(empty($current_user_courses)){

            $course_orders = array();

        }else{

            $args = array(

                'numberposts' => -1,
                'post_type' => 'shop_order',
                'post_status' => 'any',
                'include' => $current_user_courses,

            );

            $course_orders = get_posts($args);



        }





        ?>


        <div class="row mb-3">
            <div class="col-md-12">

                <div class="h3">
                    <?php echo $heading;?>
                </div>

            </div>
        </div>

        <div class="row">

            <div class="col-md-12">






        <?php

            if(!empty($course_orders)):

        ?>

        <table class="table">
            <thead class="thead-dark">
            <tr>
                <th scope="col">#</th>
                <th scope="col"><?php _e('Order No.', 'woo-installments') ?></th>
                <th scope="col"><?php _e('Course Name', 'woo-installments') ?></th>
                <th scope="col"><?php _e('Purchased package', 'woo-installments') ?></th>
                <th scope="col"><?php _e('Action', 'woo-installments') ?></th>
            </tr>
            </thead>
            <tbody>

            <?php

                $counter = 1;



                foreach ($course_orders as $course_index => $course_order){

                    $order_id = $course_order->ID;
                    $order_cart_tiers = get_post_meta($order_id, 'woo_inst_cart_tiers', true);

                    

                    $course_order = new WC_Order($order_id);
                    $course_order_paid = $course_order->is_paid();

                    if(!$course_order_paid){
                        continue;
                    }

                    $order_items = $course_order->get_items();

                    if(!empty($order_items)){


                        foreach ($order_items as $order_item_index => $order_item){

                            $order_item_data = $order_item->get_data();

                            $product_id = $order_item_data['product_id'];
                            $product_packages_name = woo_inst_get_packages_data_array($product_id);



                            if(!in_array($order_item_data['product_id'], $woo_inst_all)){
                                continue;
                            }

                            if($product_id != $ghost_product){




                                ?>

                                    <tr>
                                        <th scope="row"><?php echo $counter; ?></th>
                                        <td><a href="<?php echo $course_order->get_view_order_url(); ?>" target="_blank">#<?php echo $course_order->get_id(); ?></a></td>
                                        <td><a class="text-decoration-none" href="<?php echo get_permalink($order_item_data['product_id']); ?>" target="_blank"><?php echo $order_item->get_name(); ?></a></td>
                                        <td><button class="btn btn-light btn-sm"><?php echo __('Full purchased', 'woo-installments'); ?></button></td>
                                        <td>
                                            <?php if(!empty($product_packages_name)){ ?>
                                                <button class="btn btn-primary btn-sm woo_inst_package_view"><?php echo __('view', 'woo-installments'); ?> <i class="fa fa-eye"></i></button>
                                            <?php } ?>
                                        </td>
                                    </tr>



                                <?php

                                woo_inst_package_table($product_packages_name);

                                $counter++;



                            }else{


                                if(!empty($order_cart_tiers)){


                                    foreach ($order_cart_tiers as $product_id => $packages){


                                        $product = new WC_Product($product_id);
                                        $product_packages_name = woo_inst_get_packages_data_array($product_id);


                                        $packages = array_filter($packages, function($value){

                                            return $value != 0;

                                        });

                                        $packages_ids = array_Keys($packages);

                                        $purchased_count = count($packages);
                                        $total_package = count($product_packages_name);

                                        $purchased_text = $purchased_count == $total_package ? __('Full purchased', 'woo-installments') : "$purchased_count ".__('Out of', 'woo-installments')." $total_package" ;


                                        ?>


                                        <tr>
                                            <th scope="row"><?php echo $counter; ?></th>
                                            <td><a href="<?php echo $course_order->get_view_order_url(); ?>" target="_blank">#<?php echo $course_order->get_id(); ?></a></td>
                                            <td><a class="text-decoration-none" href="<?php echo get_permalink($product->get_id()); ?>" target="_blank"><?php echo $product->get_name(); ?></a></td>
                                            <td><button class="btn btn-light btn-sm"><?php echo $purchased_text; ?></button></td>
                                            <td><button class="btn btn-primary btn-sm woo_inst_package_view"><?php echo __('view', 'woo-installments'); ?> <i class="fa fa-eye"></i></button></td>
                                        </tr>





                                        <?php

                                        woo_inst_package_table($product_packages_name, $packages_ids);

                                        $counter++;



                                    }



                                }




                            }



                        }



                    }


            ?>





            <?php


                }


            ?>


            </tbody>
        </table>




        <?php

            else:

                echo "<div class='alert alert-warning'>".__('Currently, not enrolled in any course.', 'woo-installments')."</div>";


        ?>




                    </div>

        </div>

                <?php

                    endif;



    }




    add_action( 'woocommerce_account_woo-online-courses_endpoint', 'woo_inst_online_courses_content' );
    function woo_inst_online_courses_content() {



        woo_inst_my_courses_html();

        ?>

        <hr>

        <?php



        woo_inst_enrolled_courses_html();


    }


    add_action('wp_ajax_woo_inst_save_playlist', 'woo_inst_save_playlist');

    function woo_inst_save_playlist(){

        $result = array(
                'status' => false,
                'alert_text' => '',
        );


        if(isset($_POST['woo_inst_playlist'])){


            if(!isset($_POST['woo_inst_playlist_nonce']) || !wp_verify_nonce($_POST['woo_inst_playlist_nonce'], 'woo_inst_playlist_nonce_action')){


                wp_die(__('Sorry, your nonce did not verify.', 'woo-installments'));


            }else{


                $woo_inst_playlist_option = get_option('woo_inst_playlist', array());


                $woo_inst_playlist = sanitize_winst_data($_POST['woo_inst_playlist']);
                $woo_inst_playlist_index = sanitize_winst_data($_POST['woo_inst_playlist_index']);

                $already_exist = false;

                if($woo_inst_playlist_index == -1){

                    $title = $woo_inst_playlist['title'];

                    $woo_inst_playlist_index = strtolower($title);
                    $woo_inst_playlist_index = str_replace(' ', '_', $woo_inst_playlist_index);
                    $woo_inst_playlist_index = str_replace('-', '_', $woo_inst_playlist_index);
                    $woo_inst_playlist_index = str_replace('__', '_', $woo_inst_playlist_index);



                    if(!array_key_exists($woo_inst_playlist_index, $woo_inst_playlist_option)){



                        $woo_inst_playlist_option[$woo_inst_playlist_index] = $woo_inst_playlist;

                        $result['alert_text'] = __('Playlist created successfully.', 'woo-installments');

                    }else{


                        $already_exist = true;
                        $result['status'] = 'exist';
                        $result['alert_text'] = __('Playlist with same title already exist, please enter unique title', 'woo-installments');


                    }



                }else{


                    if(array_key_exists($woo_inst_playlist_index, $woo_inst_playlist_option)){

                        $woo_inst_playlist_option[$woo_inst_playlist_index] = $woo_inst_playlist;

                        $result['alert_text'] = __('Playlist updated successfully.', 'woo-installments');

                    }

                }



                if(!$already_exist){



                    $result['status'] = update_option('woo_inst_playlist', $woo_inst_playlist_option);

                    if($result['status']){

                        ob_start();

                        woo_inst_course_playlist_body();

                        $content = ob_get_clean();

                        $result['playlist_body'] = $content;


                    }

                }

                if(!$result['status']){

                    $result['alert_text'] = '';
                }


            }

        }


        wp_send_json($result);


    }


    function woo_inst_course_playlist_body(){


        $woo_inst_playlist = get_option('woo_inst_playlist', array());


        if(!empty($woo_inst_playlist)){

            $playlist_counter = 1;

            foreach ($woo_inst_playlist as $playlist_index => $playlist){

                $urls = $playlist['urls'];



                ?>

                <tr data-title="<?php echo $playlist['title'] ?>">
                    <th scope="row"><?php echo $playlist_counter; ?></th>
                    <td><?php echo $playlist['title']; ?></td>
                    <td>[woo_inst_playlist id="<?php echo $playlist_index  ?>"]</td>
                    <td>

                        <span class="dashicons dashicons-edit-large edit" data-id="<?php echo $playlist_index ?>" title="<?php _e('Edit Playlist', 'woo-installments') ?>"></span>
                        <span class="dashicons dashicons-trash delete" data-id="<?php echo $playlist_index ?>" title="<?php _e('Delete Playlist', 'woo-installments') ?>"></span>
                        <span class="dashicons dashicons-insert view" data-id="<?php echo $playlist_index ?>" title="<?php _e('View Url', 'woo-installments') ?>"></span>

                    </td>
                </tr>


                <tr class="urls_row hide" data-id="<?php echo $playlist_index ?>">


                    <td colspan="4">

                        <table class="table mt-1 table-striped">
                            <thead class="table-light">

                            <tr>

                                <th scope="col">#</th>
                                <th scope="col">URL</th>

                            </tr>

                            </thead>

                            <tbody>


                <?php


                $counter_url = 1;
                foreach ($urls as $url_index => $url){

                    ?>


                        <tr>

                            <td><?php echo $counter_url; ?></td>
                            <td class="url_td"><a target="_blank" href="<?php echo $url; ?>"><?php echo $url; ?></a></td>

                        </tr>


                    <?php

                    $counter_url++;

                }


                ?>


                                </tbody>

                            </table>


                        </td>


                    </tr>

                <?php

                $playlist_counter++;


            }
        }else{


            ?>


                <tr>

                    <td colspan="4">
                        <div class="alert alert-info text-center">
                            <?php _e('No playlist found', 'woo-installments'); ?>
                        </div>
                    </td>

                </tr>


            <?php





        }






    }


    function woo_inst_course_playlist_content(){

        global $woo_inst_url;

        $upload_url = wp_get_upload_dir()['url'];
        $placeholder_url = $upload_url.'/your_video.mp4'


        ?>


            <div class="nav-tab-content hide">

                <div class="modal woo_inst_load_modal" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="ajax_load_modalLabel" >
                    <div class="modal-dialog" role="document" style="max-width: 50px;">
                        <div class="modal-content" style="margin-top: 45vh; width: max-content">

                            <img src="<?php echo  $woo_inst_url ?>images/loader.gif" style="width: 50px; height: 50px" />

                        </div>
                    </div>
                </div>




                <div class="row mt-3">
                    <div class="col-md-12">
                        <div class="alert alert-primary mt-3">
                            <?php _e('Video or Audio url can be media used to create a playlist with HTML5 Media Player.', 'woo-installments'); ?>
                        </div>
                    </div>
                </div>


                <div class="row mt-3 woo_inst_playlist_alerts">

                    <div class="col-md-12">
                        <div class="alert alert-success hide"></div>
                        <div class="alert alert-warning hide"></div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-12">
                        <div class="h5"><?php _e('Create Audio/Video Course Playlist', 'woo-installments')  ?></div>
                    </div>
                </div>


                <div class="row mt-3">
                    <div class="col-md-12">
                        <button class="btn btn-primary woo_inst_playlist_create_new">
                            <i class="fa fa-plus-circle"></i>
                            <?php _e("Add New", 'woo-installments')  ?>
                        </button>

                        <button class="btn btn-primary woo_inst_playlist_show_all hide">
                            <i class="fa fa-eye"></i>
                            <?php _e("Show all playlists", 'woo-installments')  ?>
                        </button>

                        <hr class="bg-primary" />

                    </div>
                </div>

                <form method="post" class="hide" id="woo_inst_playlist_form">

                    <input type="hidden" name="action" value="woo_inst_save_playlist">
                    <input type="hidden" name="woo_inst_playlist_index" value="-1">

                    <?php echo wp_nonce_field('woo_inst_playlist_nonce_action', 'woo_inst_playlist_nonce'); ?>

                <div class="row mt-3">
                    <div class="col-md-12">

                        <div class="row">

                            <div class="col-md-3">
                                <label for="woo_inst_playlist_title"><?php _e('Playlist Title', 'woo-installments')  ?> <span class="text-danger">*</span></label>
                            </div>

                            <div class="col-md-5">
                                <input type="text" name="woo_inst_playlist[title]" id="woo_inst_playlist_title" class="form-control" required placeholder="<?php _e('Playlist Title', 'woo-installments')  ?>">
                            </div>

                        </div>



                            <div class="row mt-3">

                                <div class="col-md-3">
                                    <label for=""><?php _e("Add playlist URLs", 'woo-installments')  ?></label>
                                </div>

                                <div class=" col-md-4 ">
                                    <button class="btn btn-info woo_inst_add_new_url">
                                        <i class="fa fa-plus-circle"></i>
                                        <?php _e("Add New URL", 'woo-installments')  ?>
                                    </button>
                                </div>
                            </div>

                            <div class="row mt-3">



                                <div class="offset-md-3 col-md-5 woo_inst_playlist_data">

                                    <div class="row mb-3 playlist_url">

                                        <div class="col-md-11 col-10">
                                            <input type="text" name="woo_inst_playlist[urls][]" class="form-control woo_inst_playlist_url" placeholder="<?php echo $placeholder_url;  ?>">
                                        </div>

                                        <div class="col-md-1 col-2 text-right">

                                            <button class="btn btn-danger btn-sm mt-1 remove_url">
                                                <i class="fa fa-trash"></i>
                                            </button>

                                        </div>

                                    </div>

                                </div>

                            </div>

                        <div class="row mt-5">
                            <div class="col-md-5 offset-md-3">
                                <button class="btn btn-primary woo_inst_playlist_save save">
                                    <i class="fa fa-save"></i>
                                    <?php _e("Save Playlist", 'woo-installments')  ?>
                                </button>

                                <button class="btn btn-primary woo_inst_playlist_save update hide">
                                    <i class="fa fa-save"></i>
                                    <?php _e("Update Playlist", 'woo-installments')  ?>
                                </button>
                            </div>
                        </div>





                    </div>




                </div>

                </form>

                <div class="row woo_inst_playlist_table_row">

                    <div class="col-md-12">


                        <table class="table woo_inst_playlist_table">
                            <thead class="thead-dark">
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col"><?php _e('Playlist title', 'woo-installments')  ?></th>
                                <th scope="col"><?php _e('Shortcode', 'woo-installments')  ?></th>
                                <th scope="col"></th>
                            </tr>
                            </thead>
                            <tbody>

                                <?php woo_inst_course_playlist_body(); ?>

                            </tbody>
                        </table>



                    </div>
                </div>

            </div>


        <?php

    }


    add_action('wp_ajax_woo_inst_delete_playlist', 'woo_inst_delete_playlist');
    function woo_inst_delete_playlist(){

        $result = array(
            'status' => false,
            'alert_text' => '',
        );


        if(isset($_POST['woo_inst_playlist_id'])){


            if(!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'woo_inst_nonce_action_common')){


                wp_die(__('Sorry, your nonce did not verify.', 'woo-installments'));


            }else{


                $woo_inst_playlist_option = get_option('woo_inst_playlist', array());


                $woo_inst_playlist_id = sanitize_winst_data($_POST['woo_inst_playlist_id']);


                if(array_key_exists($woo_inst_playlist_id, $woo_inst_playlist_option)){



                    unset($woo_inst_playlist_option[$woo_inst_playlist_id]);


                    $result['alert_text'] = __('Playlist deleted successfully.', 'woo-installments');

                    $result['status'] = update_option('woo_inst_playlist', $woo_inst_playlist_option);

                    if($result['status']){

                        ob_start();

                        woo_inst_course_playlist_body();

                        $content = ob_get_clean();

                        $result['playlist_body'] = $content;


                    }

                }


            }

        }


        wp_send_json($result);


    }

    add_shortcode('woo_inst_playlist', 'woo_inst_playlist_callback');

    function woo_inst_playlist_callback($attr){

        global $woo_inst_url;

        if(!isset($attr['id'])){


            return ;
        }

        $woo_inst_playlist_option = get_option('woo_inst_playlist', array());

        $playlist_id = $attr['id'];

        if(!array_key_exists($playlist_id, $woo_inst_playlist_option)){

            return '<div class="wi_alert wi_alert_danger wi_text_center">'.__('Playlist not exist.', 'woo-installments').'</div>';
        }

        $current_playlist = $woo_inst_playlist_option[$playlist_id];

        $playlist_title = $current_playlist['title'];
        $playlist_url = $current_playlist['urls'];

        ob_start();



        ?>

            <div class="woo_inst_playlist_wrapper">

                <div class="woo_inst_playlist_heading">
                    <h2><?php echo $playlist_title; ?></h2>
                </div>


                <div class="woo_inst_player">


                    <video controls poster="<?php echo $woo_inst_url.'images/audio.jpg' ?>">

                        <source src="<?php echo current($playlist_url); ?>" type="video/mp4">

                    </video>


                </div>


                <div class="woo_inst_urls">

                    <h3>
                        <?php _e('Course Playlist', 'woo-installments'); ?>
                    </h3>

                    <ul>

                        <?php

                            foreach ($playlist_url as $index => $url){

                                $title_text = __('Click to play', 'woo-installments');

                                $class = $index == 0 ? 'playing' : '';

                                echo "<li data-url='$url' title='$title_text' class='$class'>$url</li>";


                            }

                        ?>


                    </ul>



                </div>


            </div>


        <?php

        $content = ob_get_clean();


        return $content;

    }

    add_action('wp_ajax_woo_inst_get_paginated_table', 'woo_inst_get_paginated_table');
    add_action('wp_ajax_woo_inst_save_items_per_page', 'woo_inst_save_items_per_page');

    function woo_inst_get_paginated_table(){




        if(is_admin() && !empty($_POST) && isset($_POST['load_page'])){


            if(!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'woo_inst_nonce_action_common')){


                wp_die(__('Sorry, your nonce did not verify.', 'woo-installments'));


            }else{

                $page_number = sanitize_winst_data($_POST['load_page']);



                $products_data = woo_inst_get_products($page_number);
                $products = $products_data->products;

                ob_start();

                woo_inst_products_table_body($products);


                $content = ob_get_clean();

                echo $content;

                wp_die();

            }


        }

    }


    function woo_inst_save_items_per_page(){


        $result = array(

                'status' => false,
        );



        if(is_admin() && !empty($_POST) && isset($_POST['woo_inst_items_per_page'])){


            if(!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'woo_inst_nonce_action_common')){


                wp_die(__('Sorry, your nonce did not verify.', 'woo-installments'));


            }else{

                $woo_inst_items_per_page = sanitize_winst_data($_POST['woo_inst_items_per_page']);
                $result['status'] = update_option('woo_inst_items_per_page', $woo_inst_items_per_page);


            }


        }

        wp_send_json($result);

    }

    function woo_inst_pagination_html($max_num_pages, $current_page){



        $pages_array = array(5, 10, 50, 100, 250, 500, 1000);


        $wp_sis_items_per_page = get_option('woo_inst_items_per_page', 5);


        ?>
        <div class="row mt-3 justify-content-center">
            <input type="hidden" name="woo_inst_current_page" class="woo_inst_current_page" value="<?php echo $current_page; ?>">
            <div class="col-lg-9 col-md-8">

                <?php



                if($max_num_pages > 1):

                    ?>

                    <nav aria-label="...">
                        <ul class="woo_inst_pagination pagination justify-content-center" data-maxPage="<?php echo $max_num_pages; ?>">
                            <li class="page-item <?php echo $current_page <= 1 ? 'disabled' : ''; ?>" data-page="previous">
                                <a class="page-link" href="" tabindex="-1" ><?php _e("Previous","woo-installments"); ?></a>
                            </li>

                            <?php

                            for ($i = 1; $i <= $max_num_pages; $i++){

                                $active = '';
                                if($i == $current_page){
                                    $active = 'active';
                                }

                                $spiner = '<div class="spinner-border text-primary" role="status" style="display:none;">
                                            <span class="sr-only">Loading...</span>
                                            </div>';

                                echo "<li class='page-item $active' data-page='$i'><a class='page-link' href='' ><span class='text'>$i</span> $spiner</a></li>";

                            }

                            ?>


                            <li class="page-item <?php echo $max_num_pages == $current_page ? 'disabled': ''; ?>" data-page="next">
                                <a class="page-link " href="" ><?php _e("Next","woo-installments"); ?></a>
                            </li>
                        </ul>
                    </nav>

                <?php

                endif;

                ?>


            </div>

            <div class="col-lg-3 col-md-4 col-6 text-md-right text-center">

                <div class="input-group mb-3">
                    <div class="input-group-prepend">
                        <label class="input-group-text" for="woo_inst_options_items_per_page" style="line-height: 0.5"><?php _e("Items per page","woo-installments"); ?></label>
                    </div>

                    <select class="custom-select" name="woo_inst_options[items_per_page]" data-name="items_per_page" id="woo_inst_options_items_per_page">

                        <?php


                        if(!empty($pages_array)){
                            foreach ($pages_array as $page){


                                $selected_page = $page == $wp_sis_items_per_page ? 'selected' : '';

                                echo "<option value='$page' $selected_page>$page</option>";
                            }
                        }


                        ?>



                    </select>

                </div>

            </div>
        </div>

        <?php

    }


    function woo_inst_handle_custom_query_var( $query, $query_vars ) {


        if ( isset($query_vars['wpsis_product_price_compare']) && ! empty( $query_vars['wpsis_product_price_compare'] ) ) {

            $query['meta_query'][] = array(

                'key' => '_price',
                'value' => $query_vars['wpsis_product_price_compare'],
                'compare' => '>=',

            );
        }

        if ( isset($query_vars['_woo_inst_demo_content']) && $query_vars['_woo_inst_demo_content']) {

            $query['meta_query'][] = array(

                'key' => '_woo_inst_demo_content',
                'compare' => 'EXISTS',

            );
        }



        return $query;
    }
    add_filter( 'woocommerce_product_data_store_cpt_get_products_query', 'woo_inst_handle_custom_query_var', 10, 2 );

    function woo_inst_products_table_body($products){


        global $woo_inst_currency, $woo_inst_settings, $woo_inst_pro_settings, $woo_inst_pro, $woo_inst_pro_msg;


                foreach($products as $prod){

//        		$product = wc_get_product($prod->ID);
//        		$product->get_sale_price()
                $product = $prod;
                $prod = get_post($product->get_id());

                $is_demo_product = get_post_meta($prod->ID, '_woo_inst_demo_content', true);

                $ticked = in_array($prod->ID, $woo_inst_settings['woo_inst_products']);



        ?>

        <tr>
        <td>
            <input id="wip-<?php echo $prod->ID; ?>" <?php checked($ticked); ?> type="checkbox" name="woo_inst_settings[woo_inst_products][]" value="<?php echo $prod->ID; ?>" />
            <input type="hidden" name="woo_inst_settings[woo_inst_current_products][]" value="<?php echo $prod->ID; ?>">
        </td>
        <td><label for="wip-<?php echo $prod->ID; ?>"><?php echo $prod->post_title; ?></label> <?php if($is_demo_product): ?><span class="badge badge-primary woo_inst_demo_badge" title="<?php _e('This is a demo product installed for demonstration.', 'woo-installments'); ?>"><?php _e('Demo Content', 'woo-installments'); ?></span><?php endif; ?></td>

        <td><?php echo $product->get_price_html() ?></td>
        <td>
            <?php if(!$is_demo_product): ?><a href="<?php echo get_edit_post_link($prod->ID); ?>" target="_blank"><?php _e('Edit', 'woo-installments'); ?></a> -<?php endif; ?>
            <a href="<?php echo get_permalink($prod->ID); ?>" target="_blank"><?php _e('View', 'woo-installments'); ?></a>
        </td>
        <?php //if($woo_inst_pro){ ?>
        <td title="<?php echo $woo_inst_pro_msg; ?>"><input <?php checked($ticked && in_array($prod->ID, $woo_inst_pro_settings['package']) ); ?> <?php disabled(!$ticked || !$woo_inst_pro); ?> type="checkbox" name="woo_inst_pro_settings[package][]" value="<?php echo $prod->ID; ?>" />
        </td>
        <td title="<?php echo $woo_inst_pro_msg; ?>"><input <?php checked($ticked && in_array($prod->ID, $woo_inst_pro_settings['full']) ); ?> <?php disabled(!$ticked || !$woo_inst_pro); ?> type="checkbox" name="woo_inst_pro_settings[full][]" value="<?php echo $prod->ID; ?>" />
        </td>
        <?php //} ?>
        </tr>
        <?php
                }



    }

    function woo_inst_demo_products_table_body($products){


        global $woo_inst_currency, $woo_inst_settings, $woo_inst_pro_settings, $woo_inst_pro, $woo_inst_pro_msg;


        foreach($products as $prod){


            $product = $prod;
            $prod = get_post($product->get_id());

            $ticked = in_array($prod->ID, $woo_inst_settings['woo_inst_products']);
            $demo_message = __('Go to Course Products tab to change the settings', 'woo-installments');



            ?>

            <tr>
                <td>
                    <input title="<?php echo $demo_message; ?>" id="wip-<?php echo $prod->ID; ?>" <?php disabled(true); ?> <?php checked($ticked); ?> type="checkbox" name="woo_inst_settings[woo_inst_products][]" value="<?php echo $prod->ID; ?>" />
                </td>
                <td><label for="wip-<?php echo $prod->ID; ?>"><?php echo $prod->post_title; ?></label> <span class="badge badge-primary woo_inst_demo_badge" title="<?php _e('This is a demo product installed for demonstration.', 'woo-installments'); ?>"><?php _e('Demo Content', 'woo-installments'); ?></span></td>

                <td><?php echo $product->get_price_html() ?></td>
                <td><a href="<?php echo get_permalink($prod->ID); ?>" target="_blank"><?php _e('View', 'woo-installments'); ?></a>
                </td>
                <?php //if($woo_inst_pro){ ?>
                <td title="<?php echo $demo_message; ?>"><input <?php checked($ticked && in_array($prod->ID, $woo_inst_pro_settings['package']) ); ?> <?php disabled(true); ?> type="checkbox" name="woo_inst_pro_settings[package][]" value="<?php echo $prod->ID; ?>" />
                </td>
                <td title="<?php echo $demo_message; ?>"><input <?php checked($ticked && in_array($prod->ID, $woo_inst_pro_settings['full']) ); ?> <?php disabled(true); ?> type="checkbox" name="woo_inst_pro_settings[full][]" value="<?php echo $prod->ID; ?>" />
                </td>
                <?php //} ?>
            </tr>
            <?php
        }



    }


