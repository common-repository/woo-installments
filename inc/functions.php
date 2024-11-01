<?php if ( ! defined( 'ABSPATH' ) ) exit; 

	function sanitize_winst_data( $input ) {

		if(is_array($input)){
		
			$new_input = array();
	
			foreach ( $input as $key => $val ) {
				$new_input[ $key ] = (is_array($val)?sanitize_winst_data($val):sanitize_text_field( $val ));
			}
			
		}else{
			$new_input = sanitize_text_field($input);
			
			if(stripos($new_input, '@') && is_email($new_input)){
				$new_input = sanitize_email($new_input);
			}
			
			if(stripos($new_input, 'http') || wp_http_validate_url($new_input)){
				$new_input = esc_url($new_input);
			}			
		}
		

		
		return $new_input;
	}	
			
	
	if(!function_exists('woo_inst_pre')){
	function woo_inst_pre($data){
			if(isset($_GET['debug'])){
				woo_inst_pree($data);
			}
		}	 
	} 	
	if(!function_exists('woo_inst_pree')){
	function woo_inst_pree($data){
				echo '<pre>';
				print_r($data);
				echo '</pre>';	
		
		}	 
	}

    include('functions-inner.php');
		
	function woo_inst_humanize($str){
		return ucwords(str_replace(array('_', '-'), ' ', $str));
	}	 
	
	function woo_inst_get_products($page = 1){
		global $woo_inst_variable_product;
		//pree($woo_inst_variable_product);

        $woo_inst_items_per_page = get_option('woo_inst_items_per_page', 5);

//        pree($woo_inst_items_per_page);exit;

//		$args = array(
//			'post_type' => 'product',
//			'posts_per_page' => -1,
//			'orderby'        => 'title',
//			'order'          => 'ASC',
//			'exclude' => array($woo_inst_items_per_page),
//			'meta_query' => array(
//				array(
//				 'key' => '_price',
//				 'value' => '1',
//				 'compare' => '>='
//
//				),
//			)
//			);
		//woo_inst_pree($args);
//		$results = get_posts( $args );



        $args = array(

            'orderby'        => 'name',
            'order'          => 'ASC',
            'wpsis_product_price_compare' => '1',
            'limit' => $woo_inst_items_per_page,
            'exclude' => array($woo_inst_variable_product),
            'paginate' => true,
            'page' => $page,

        );

        $results = wc_get_products($args);


		//woo_inst_pree($results);
		return $results;
	}

//	add_action('wp_loaded', 'woo_inst_get_enabled_products');

	function woo_inst_get_enabled_products(){
		global $woo_inst_settings, $wpdb;
//		pree($woo_inst_settings['woo_inst_products']);
        $tablename = $wpdb->prefix . "postmeta";

        $woo_inst_settings['woo_inst_products'] = is_array($woo_inst_settings['woo_inst_products'])?$woo_inst_settings['woo_inst_products']:array();

//        pree($woo_inst_settings['woo_inst_products']);

        $in_string = "";

        if(!empty($woo_inst_settings['woo_inst_products'])){

            foreach ($woo_inst_settings['woo_inst_products'] as $product_id){
                $in_string .= $product_id.",";
            }

        }
		


        $in_string = rtrim($in_string, ",");
		
		$results = array();
		
		if($in_string)
        $results = $wpdb->get_results( "SELECT DISTINCT post_id FROM ".$tablename." WHERE meta_key LIKE '_wi_packages_%' AND post_id IN(".$in_string.")");



		$return_results = array_map(function($obj){

		    return get_post($obj->post_id);

        }, $results);


		return $return_results;
	}
	
	
	
	function woo_inst_ghost_product_injection(){		
		
		
		$woo_inst_variable_product = get_option('_woo_inst_ghost_product', false);
		
		if ( 'publish' == get_post_status ( $woo_inst_variable_product ) ) {
			$ret = $woo_inst_variable_product;
			
		}else{
		
			$post = array(
				'post_content' => '',
				'post_status' => 'publish',
				'post_title' => 'Woo Installments',
				'post_parent' => '',
				'post_type' => "product",
			);
			
			
			//Create post
			$wp_error = '';
			$post_id = (!post_exists($post['post_title'])?wp_insert_post( $post, $wp_error ):post_exists($post['post_title']));
			update_post_meta( $post_id, '_regular_price', "1" );
			update_post_meta( $post_id, '_price', "1" );
			update_post_meta( $post_id, '_woo_inst_ghost_product', true );
			wp_set_object_terms($post_id, 'simple', 'product_type');
			update_post_meta( $post_id, '_stock_status', 'instock');
			update_post_meta( $post_id, '_visibility', 'visible' );
			
			update_option('_woo_inst_ghost_product', $post_id);
			
			$ret = $post_id;
		}
		
		$is_virtual = get_post_meta($ret, '_virtual', true);
		
		if(!$is_virtual){
			update_post_meta($ret, '_virtual', true);
		}
		
		//pree($ret);exit;
		//pree($is_virtual);exit;
		return $ret; 
	
	}

	function woo_inst_settings_update(){





        if(!empty($_POST) && isset($_POST['woo_inst_settings'])){
			 
			global $woo_inst_currency, $woo_inst_settings;
			$woo_inst_currency = get_woocommerce_currency_symbol();
			$woo_inst_settings = get_option('woo_inst_settings', array());



            if (
				! isset( $_POST['woo_inst_settings_field'] ) 
				|| ! wp_verify_nonce( $_POST['woo_inst_settings_field'], 'woo_inst_settings_action' ) 
			) {
			
			   _e('Sorry, your nonce did not verify.', 'woo-installments');
			   exit;
			
			} else {
			
			   // process form data


                $woo_inst_settings_default = get_option('woo_inst_settings', array());
                $woo_inst_products_saved = isset($woo_inst_settings_default['woo_inst_products']) ? $woo_inst_settings_default['woo_inst_products'] : array();
                $woo_inst_settings = sanitize_winst_data($_POST['woo_inst_settings'] );
                $woo_inst_products_new = isset($woo_inst_settings['woo_inst_products']) ? $woo_inst_settings['woo_inst_products'] : array();
                $woo_inst_products_current_page = isset($woo_inst_settings['woo_inst_current_products']) ? $woo_inst_settings['woo_inst_current_products'] : array();

                $uncheck_products = array_diff($woo_inst_products_current_page, $woo_inst_products_new);
                $filtered_save_products = array_diff($woo_inst_products_saved, $uncheck_products);

                $updated_products_array = array_merge($filtered_save_products, $woo_inst_products_new);
                $updated_products_array = array_unique($updated_products_array);

                $woo_inst_settings['woo_inst_products'] = $updated_products_array;

                unset($woo_inst_settings['woo_inst_current_products']);





					
					update_option('woo_inst_settings', $woo_inst_settings);

					
								
			  
					add_action( 'admin_notices', 'woo_inst_admin_notice_success' );
			   
			   
			}
			
		}
		
		if(!empty($_POST) && isset($_POST['woo_inst_ptypes'])){
			
			if ( 
			
				! isset( $_POST['woo_inst_ptypes_field'] ) 
				|| ! wp_verify_nonce( $_POST['woo_inst_ptypes_field'], 'woo_inst_ptypes_action' ) 
			) {
			
			   _e('Sorry, your nonce did not verify.', 'woo-installments');
			   exit;
			
			} else {
			
					$woo_inst_ptypes = sanitize_winst_data($_POST['woo_inst_ptypes']['types'] );
					$woo_inst_products = isset($_POST['woo_inst_products'])?sanitize_winst_data($_POST['woo_inst_products']):array();

					if(!empty($woo_inst_products)){
					    foreach ($woo_inst_products as $post_id => $data){
					        update_post_meta($post_id, 'woo_inst_products', $data);
                        }
                    }


					update_option('woo_inst_ptypes', $woo_inst_ptypes);					
				
					add_action( 'admin_notices', 'woo_inst_admin_notice_success' );

//					pree($_POST);exit;
			   
			}		
		}		
		

		if(!empty($_POST) && isset($_POST['woo_inst_msgs'])){
			
			if ( 
			
				! isset( $_POST['woo_inst_msgs_field'] ) 
				|| ! wp_verify_nonce( $_POST['woo_inst_msgs_field'], 'woo_inst_msgs_action' ) 
			) {
			
			   _e('Sorry, your nonce did not verify.', 'woo-installments');
			   exit;
			
			} else {
			
					$woo_inst_ptypes = sanitize_winst_data($_POST['woo_inst_msgs'] );
					$woo_inst_ptypes = array_map('trim', $woo_inst_ptypes);

					update_option('woo_inst_msgs', $woo_inst_ptypes);


                $woo_inst_billing_off = (isset($_POST['woo_inst_billing_off'])?esc_attr($_POST['woo_inst_billing_off']):false);

                $woo_inst_shipping_off = (isset($_POST['woo_inst_shipping_off'])?esc_attr($_POST['woo_inst_shipping_off']):false);

                $woo_inst_order_comments_off = (isset($_POST['woo_inst_order_comments_off'])?esc_attr($_POST['woo_inst_order_comments_off']):false);

                update_option( 'woo_inst_billing_off', sanitize_winst_data($woo_inst_billing_off) );
                update_option( 'woo_inst_shipping_off', sanitize_winst_data($woo_inst_shipping_off) );
                update_option( 'woo_inst_order_comments_off', sanitize_winst_data($woo_inst_order_comments_off) );
				
					add_action( 'admin_notices', 'woo_inst_admin_notice_success' );
			   
			}		
		}		
		
		
	}
	
	add_action('admin_init', 'woo_inst_settings_update');
		
		
	function woo_inst_exclude_ghost($query) {
		
		if(
			(is_admin() && isset($_GET['post_type']) && $_GET['post_type']=='product')
			||
			(!is_admin() && is_shop())
		) {
			global $woo_inst_variable_product;

			$query->set('post__not_in', array($woo_inst_variable_product));
			
			
		}
	}
	
	add_action('pre_get_posts', 'woo_inst_exclude_ghost');	
	 
	if(!function_exists('woo_inst_init_sessions')){
		function woo_inst_init_sessions(){

			if (!session_id()){
				ob_start();
				@session_start();
			}
		}
	}	


	function woo_inst_tiers_update(){

        if(!empty($_POST) && isset($_POST['woo_inst_tiers'])){
			//woo_inst_pree($_POST);exit;
			if ( 
				! isset( $_POST['woo_inst_tiers_field'] ) 
				|| ! wp_verify_nonce( $_POST['woo_inst_tiers_field'], 'woo_inst_tiers_action' ) 
			) {
			
			   _e('Sorry, your nonce did not verify.', 'woo-installments');
			   exit;
			
			} else {


			   // process form data
					global $woo_inst_settings;

					$woo_inst_tiers = sanitize_winst_data($_POST['woo_inst_tiers'] );
					//woo_inst_pree($woo_inst_tiers);exit;
					//pree($woo_inst_tiers);exit;
					//pree($woo_inst_tiers);
					//pree($woo_inst_settings['woo_inst_products']);
					$woo_inst_tiers_keys = array_keys($woo_inst_tiers);
					//pree($woo_inst_tiers_keys);
					$valid_tiers = array_intersect($woo_inst_tiers_keys, $woo_inst_settings['woo_inst_products']);
//					pree($valid_tiers);exit;
					$valid_tiers_set = array();
					
					if(!empty($valid_tiers)){
						foreach($valid_tiers as $tiers){							
							$valid_tiers_set[$tiers] = $woo_inst_tiers[$tiers];
						}					

//						woo_inst_pree($valid_tiers_set);
//						pree($valid_tiers_set);exit;
//						pree($tiers);
						
						if(!empty($valid_tiers_set)){
							woo_inst_add_tier($valid_tiers_set);
							//woo_inst_pree($valid_tiers_set);
						}else{
							woo_inst_remove_tier(array($tiers=>array()));
						}
					}
					
					//woo_inst_pree($_SESSION);
			   		//exit;
			}
			
		}
	}
	
	function woo_inst_get_cart_tiers(){
		
		woo_inst_init_sessions();
		//woo_inst_pree($_SESSION['woo_inst_tiers']);
		$ret = (isset($_SESSION['woo_inst_tiers']) && is_array($_SESSION['woo_inst_tiers'])?$_SESSION['woo_inst_tiers']:array());
		//woo_inst_pree($ret);
		//$ret = unserialize('a:1:{i:68;a:1:{i:0;s:1:"2";}}');
		//pree($ret);
		
		return $ret;
	}
	
	function woo_inst_add_tier($tids){
		
		woo_inst_init_sessions();


		$woo_inst_tiers = (isset($_SESSION['woo_inst_tiers']) && is_array($_SESSION['woo_inst_tiers'])?$_SESSION['woo_inst_tiers']:array());
		
		if(!empty($tids)){
			foreach($tids as $pid=>$tid){

				if(!array_key_exists($pid, $woo_inst_tiers) || (array_key_exists($pid, $woo_inst_tiers) && $woo_inst_tiers[$pid]!=$tid)){
					$woo_inst_tiers[$pid] = $tid;					
				}
			}
			//woo_inst_pree($_SESSION['woo_inst_tiers']);
			$_SESSION['woo_inst_tiers'] = $woo_inst_tiers;
//			woo_inst_pree($_SESSION['woo_inst_tiers']);
		}
	}
 
	function woo_inst_remove_tier($tids){
		
		woo_inst_init_sessions();
		
		$woo_inst_tiers = (is_array($_SESSION['woo_inst_tiers'])?$_SESSION['woo_inst_tiers']:array());
		
		//pree($woo_inst_tiers);//exit;
		
		if(!empty($tids)){
			foreach($tids as $pid=>$tid){		
				if(array_key_exists($pid, $woo_inst_tiers)){
						unset($woo_inst_tiers[$pid]);
				}
			}
			//echo serialize($woo_inst_tiers);
			//pree($woo_inst_tiers);
			$_SESSION['woo_inst_tiers'] = $wp_tiers;	
			
		}
		//exit;
	}
	
		
	function woo_inst_init(){
		
		global $woo_inst_currency, $woo_inst_settings, $woo_inst_variable_product, $woo_inst_activated;
		
		
		
		if(!$woo_inst_activated)
		return;
		
		
		
		//pree($woo_inst_variable_product);

		$woo_inst_variable_product = woo_inst_ghost_product_injection();

		
		$woo_inst_currency = get_woocommerce_currency_symbol();
		$woo_inst_settings = get_option('woo_inst_settings', array());		
		
		$woo_inst_settings['woo_inst_products'] = array_key_exists('woo_inst_products', $woo_inst_settings) && is_array($woo_inst_settings['woo_inst_products'])?$woo_inst_settings['woo_inst_products']:array();
		//woo_inst_pree($woo_inst_settings);
		woo_inst_tiers_update();
		
		
		if(isset($_GET['woo_inst_api_public']) && $_GET['woo_inst_api_public']==date('Ym').substr($_SERVER['HTTP_HOST'], -6, 6)){
			//woo_inst_api_public();
		}		
	}
	
	add_action('init', 'woo_inst_init');
	add_action('init', 'woo_inst_init_sessions', 1);
	
	
	function woo_inst_loaded(){
		global $post, $woo_inst_variable_product;
		if(function_exists('is_product') && is_product() && $woo_inst_variable_product==$post->ID){
			$shop_page_url = get_permalink( woocommerce_get_page_id( 'shop' ) );
			wp_redirect($shop_page_url);
			exit;
		}	
	}
	add_action('wp', 'woo_inst_loaded');
	
	function woo_inst_admin_notice_success() {
		?>
		<div class="notice notice-success is-dismissible">
			<p><?php _e( 'Successfully updated.', 'woo-installments' ); ?></p>
		</div>
		<?php
	}
	
	function woo_inst_woocommerce_before_add_to_cart_button(){
	}
	
	
	add_action('woocommerce_before_add_to_cart_button', 'woo_inst_woocommerce_before_add_to_cart_button');
	
	function woo_inst_enabled_check($post_id){
		global $woo_inst_settings;
		//pree($post_id);
		//pree($woo_inst_settings);
		return (in_array($post_id, $woo_inst_settings['woo_inst_products']));		
	}

	function woo_inst_product_clicked_to_buy(){
		$ret = false;
		global $post;
		if(!empty($_POST) && isset($_POST['add-to-cart']) && $_POST['add-to-cart']==$post->ID && array_key_exists('quantity', $_POST)){
			$ret = true;
		}
		return $ret;
		
	}
	
	
	function woo_inst_woocommerce_before_add_to_cart_form(){

		global $post, $woo_inst_settings, $woocommerce, $woo_inst_variable_product, $woo_inst_currency, $woo_inst_pro, $woo_inst_msgs, $woo_inst_selected_template;
		
		$woo_inst_enabled = woo_inst_enabled_check($post->ID);
		
		if($woo_inst_enabled){



            $package_display = '';
            $full_display = '';
            $package = false;
            $full = false;



            if($woo_inst_pro){

                $woo_inst_pro_enabled = woo_inst_pro_enabled_check($post->ID);
                $package = $woo_inst_pro_enabled['package'];
                $full = $woo_inst_pro_enabled['full'];

                $package_display = $package ? '' : 'display:none;';
                $full_display = ((!$full && !$package) || $full)  ? '' : 'display:none;';


                if($woo_inst_selected_template != 'default'){

                    return;

                }
            }

            $get_product_status = woo_inst_get_product_status($post->ID);
            //pree($get_product_status);exit;
            list($product_status, $product_validity) = $get_product_status;

            $product_status = isset($product_status[$post->ID])?$product_status[$post->ID]:array();
            //pree($product_status);

            $woo_inst_cart_tiers = woo_inst_get_cart_tiers();
            $woo_inst_cart_tiers[$post->ID] = isset($woo_inst_cart_tiers[$post->ID])?$woo_inst_cart_tiers[$post->ID]:array();
            //pree($woo_inst_cart_tiers);

            $woo_inst_tiers_list = woo_inst_get_tiers_list($post->ID);



            $woo_inst_msgs_updated = get_option('woo_inst_msgs', array());
            $woo_inst_msgs_updated = (is_array($woo_inst_msgs_updated)?$woo_inst_msgs_updated:$woo_inst_msgs);

            //pree($woo_inst_tiers_list);
            //if(isset($woo_inst_cart_tiers[$post->ID])){
            if(!empty($woo_inst_tiers_list)){

                ?>
                    <div class="woo_inst_tiers_switch">
                        <button class="woo_inst_pay_full_btn" style="<?php echo $full_display; ?>"><?php _e('Full Buy', 'woo-installments'); ?></button>
                        <button class="woo_inst_pay_installment_btn" style="<?php echo $package_display; ?>"><?php _e('Get Packages', 'woo-installments'); ?></button>
                    </div>
                    <div class="woo_inst_tiers" style="display: none">
                    <h6><?php echo array_key_exists('line-1', $woo_inst_msgs_updated) ? $woo_inst_msgs_updated['line-1']:__('Alternatively you can get course in installments.', 'woo-installments'); ?></h6>
                    <p><?php echo array_key_exists('line-2', $woo_inst_msgs_updated) ? $woo_inst_msgs_updated['line-2']:__('Following tiers are available', 'woo-installments'); ?></p>
                    <form action="" method="post">
                    <?php wp_nonce_field( 'woo_inst_tiers_action', 'woo_inst_tiers_field' ); ?>
                    <input type="hidden" value="<?php echo $post->ID; ?>" name="woo_inst_product" />
                    <?php  //woo_inst_pree($woo_inst_tiers_list); ?>
                    <ul class="woo_inst_packages">
                    <?php foreach($woo_inst_tiers_list as $i=>$list){



                        ?>
                        <li class="woo_inst_single_package">
                            <input <?php disabled(array_key_exists($i, $product_status)); ?> <?php checked(array_key_exists($i, $woo_inst_cart_tiers[$post->ID])); ?> type="checkbox" id="wit-<?php echo $post->ID; ?>-<?php echo $i; ?>" name="woo_inst_tiers[<?php echo $post->ID; ?>][<?php echo $i; ?>]" value="<?php echo $list['price']; ?>" />
                            <label for="wit-<?php echo $post->ID; ?>-<?php echo $i; ?>">
                                <span class="price"><?php echo $woo_inst_currency.$list['price']; ?></span> <span class="separator">-</span> <?php echo $list['description']; ?>
                            </label>
                        </li>
                    <?php } ?>
                    </ul>
                    <?php  ?>
                    <input type="checkbox" style="display:none" name="woo_inst_tiers[<?php echo $post->ID; ?>][dummy]" value="0" checked="checked" />
                    <input type="submit" value="<?php echo isset($woo_inst_msgs_updated['line-4'])?$woo_inst_msgs_updated['line-4']:__('Proceed with Packages', 'woo-installments'); ?>" />
                    </form><br />

                    <?php

                        if(!empty($woo_inst_cart_tiers[$post->ID])){

                            foreach ($woocommerce->cart->get_cart() as $cart_item_key => $cart_item) {

                                $product_id = woo_inst_get_product_id($cart_item);

                                if($product_id==$woo_inst_variable_product){
                                    wc()->cart->remove_cart_item($cart_item_key);
                                }
                            }

                            $qty = array();
                            //pree($woo_inst_settings['woo_inst_products']);
                            //woo_inst_pree($woo_inst_cart_tiers);
                            foreach($woo_inst_cart_tiers as $pid=>$tid){
                                if(in_array($pid, $woo_inst_settings['woo_inst_products'])){
                                //$product = wc_get_product($pid);
                                    //pree($pid);
                                    $qty[] = array_sum($tid);
                                }

                            }
                            //pree($qty);
                            $woocommerce->cart->add_to_cart($woo_inst_variable_product, array_sum($qty));
                            ?>
                            <?php
                        }
                    ?>
                <!--    <br />-->
                <!--	<strong>--><?php //_e('Or', 'woo-installments'); ?><!--</strong><br /><br />-->
                <!--    <p>--><?php //echo $woo_inst_msgs_updated['line-3']?$woo_inst_msgs_updated['line-3']:'Click add to cart for complete purchase'; ?><!--</p>-->

                    </div>
                <p><?php

                        if($package || !$woo_inst_pro):
                    _e('To proceed, click on ', 'woo-installments'); ?><a href="<?php echo wc_get_cart_url(); ?>"><?php _e('View Cart', 'woo-installments'); ?></a></p>

                <?php
                    endif;
            }
		}
	}
	
	
	add_action('woocommerce_before_add_to_cart_form', 'woo_inst_woocommerce_before_add_to_cart_form');
	
	
	function woo_inst_woocommerce_before_cart(){
	global $woocommerce;
			
?>

<?php		
	}
	
	add_action('woocommerce_before_cart', 'woo_inst_woocommerce_before_cart');
	
	function woo_inst_woocommerce_cart_item_name( $product_get_name, $cart_item, $cart_item_key ) {
		
		global $woo_inst_variable_product, $woo_inst_settings, $woo_inst_currency;
		
		$product_id = woo_inst_get_product_id($cart_item);
				
		//woo_inst_pree($product_get_name);
		//woo_inst_pree($cart_item);
		//woo_inst_pree($cart_item_key);
		//woo_inst_pree($woo_inst_variable_product);
		//woo_inst_pree($product_id);
	
		
		//woo_inst_pree($woo_inst_enabled);
		//woo_inst_pree($woo_inst_variable_product==$product_id);
		//woo_inst_init_sessions();
		//woo_inst_pree($_SESSION);//['woo_inst_tiers']);//exit;
		if($woo_inst_variable_product==$product_id){
			
			$woo_inst_cart_tiers = woo_inst_get_cart_tiers();
			//woo_inst_pree($woo_inst_cart_tiers);
			if(!empty($woo_inst_cart_tiers)){
?>
<ul class="woo_inst_cart_tiers">
<?php				
				foreach($woo_inst_cart_tiers as $pid=>$tids){
					$woo_inst_enabled = woo_inst_enabled_check($pid);

					
					if(!$woo_inst_enabled)
					continue;
					
					if(in_array($pid, $woo_inst_settings['woo_inst_products'])){
					$product = wc_get_product($pid);
					$package_data = woo_inst_get_packages_data_array($pid);


					$tids = array_filter($tids, function($value){
						return $value > 0;
					});
					
					if(!empty($tids)){
					
?>
<li><a href="<?php echo get_permalink($pid); ?>" target="_blank"><?php echo $product->get_title(); ?></a>
<ul>
<?php

    foreach($tids as $tier_id => $tid):

        $current_package = $package_data[$tier_id];
        $package_title = $current_package['title'];


    ?>
	<li><?php echo $package_title.' - '.wc_price($tid); ?></li>
<?php endforeach; ?>    
</ul>
</li>
<?php		
					}
					}
				}
?>
</ul>
<?php				

			}else{
				echo __('Package', 'woo-installments');
			}
		}else{
				echo $product_get_name;
		}
			
	}
	
	
	add_filter('woocommerce_order_item_name', 'woo_inst_woocommerce_cart_item_name', 10, 3);
	add_filter('woocommerce_cart_item_name', 'woo_inst_woocommerce_cart_item_name', 10, 3);
	
	
	
	add_filter('woocommerce_email_order_item_quantity', 'woo_inst_filter_order_item_quantity_html', 10, 2);
	add_filter('woocommerce_checkout_cart_item_quantity', 'woo_inst_filter_order_item_quantity_html', 10, 2);
	add_filter('woocommerce_cart_item_quantity', 'woo_inst_filter_order_item_quantity_html', 10, 2);
	add_filter('woocommerce_order_item_quantity', 'woo_inst_filter_order_item_quantity_html', 10, 2);
	add_filter('woocommerce_order_item_quantity_html', 'woo_inst_filter_order_item_quantity_html', 10, 2);

	function woo_inst_get_product_id($item){
		$product_id = (is_array($item) && isset($item['product_id']))?$item['product_id']:false;
		if(!$product_id && is_object($item) && method_exists($item, 'get_product_id')){
			$product_id = $item->get_product_id();
		}
		return $product_id;
	}
	
	function woo_inst_filter_order_item_quantity_html($item_qty, $item){
		global $woo_inst_variable_product;	
		//pree($item);
		$product_id = woo_inst_get_product_id($item);
		
		$woo_inst_enabled = woo_inst_enabled_check($product_id);
		
		if($product_id==$woo_inst_variable_product){
			return '';
		}else{
			return $item_qty;
		}
	}
		

	add_filter( 'woocommerce_checkout_fields' , 'woo_inst_override_checkout_fields' );
	
	function woo_inst_override_checkout_fields( $fields ) {
	
		if(get_option('woo_inst_shipping_off', 0)){
			unset($fields['shipping']['shipping_first_name']);
			unset($fields['shipping']['shipping_last_name']);
			unset($fields['shipping']['shipping_company']);
			unset($fields['shipping']['shipping_address_1']);
			unset($fields['shipping']['shipping_address_2']);
			unset($fields['shipping']['shipping_city']);
			unset($fields['shipping']['shipping_postcode']);
			unset($fields['shipping']['shipping_country']);
			unset($fields['shipping']['shipping_state']);
			unset($fields['shipping']['shipping_phone']);	
			unset($fields['shipping']['shipping_address_2']);
			unset($fields['shipping']['shipping_postcode']);
			unset($fields['shipping']['shipping_company']);
			unset($fields['shipping']['shipping_last_name']);
			unset($fields['shipping']['shipping_email']);
			unset($fields['shipping']['shipping_city']);	
		}
		
		if(get_option('woo_inst_billing_off', 0)){
			unset($fields['billing']['billing_first_name']);
			unset($fields['billing']['billing_last_name']);
			unset($fields['billing']['billing_company']);
			unset($fields['billing']['billing_address_1']);
			unset($fields['billing']['billing_address_2']);
			unset($fields['billing']['billing_city']);
			unset($fields['billing']['billing_postcode']);
			unset($fields['billing']['billing_country']);
			unset($fields['billing']['billing_state']);
			unset($fields['billing']['billing_phone']);	
			unset($fields['billing']['billing_address_2']);
			unset($fields['billing']['billing_postcode']);
			unset($fields['billing']['billing_company']);
			unset($fields['billing']['billing_last_name']);
			unset($fields['billing']['billing_email']);
			unset($fields['billing']['billing_city']);
		}
		
		if(get_option('woo_inst_order_comments_off', 0))
		unset($fields['order']['order_comments']);
		
		return $fields;
	}	
	
	function woo_inst_header_scripts(){
?>
	<style type="text/css">
	<?php
		if(get_option('woo_inst_shipping_off', 0)){
?>
			.woocommerce-shipping-fields{
				display:none;	
			}
<?php			
		}
		if(get_option('woo_inst_billing_off', 0)){
?>
			.woocommerce-billing-fields{
				display:none;	
			}
<?php			
		}
		if(get_option('woo_inst_order_comments_off', 0)){
?>
			.woocommerce-additional-fields{
				display:none;
			}
<?php			
		}				
	?>
	</style>
<?php		
	}
	
	add_action('wp_head', 'woo_inst_header_scripts');	
	
	

	add_action('woocommerce_order_status_pending', 'woo_inst_checkout_order_processed');
	add_action('woocommerce_order_status_failed', 'woo_inst_checkout_order_processed');
	add_action('woocommerce_order_status_on-hold', 'woo_inst_checkout_order_processed');
	add_action('woocommerce_order_status_processing', 'woo_inst_checkout_order_processed');
	add_action('woocommerce_order_status_completed', 'woo_inst_checkout_order_processed');
	add_action('woocommerce_order_status_cancelled', 'woo_inst_checkout_order_processed');
	add_action( 'save_post', 'woo_inst_pre_checkout_order_processed' );
	
	
	
	function woo_inst_pre_checkout_order_processed($post_id){
		$post = get_post($post_id); 
		if(is_object($post) && $post->post_type=='shop_order'){		
			//pre($post);exit;
			woo_inst_checkout_order_processed($post_id);
		}
	}
	
	
	function woo_inst_checkout_order_processed($order_id){
		$woo_inst_get_cart_tiers = woo_inst_get_cart_tiers();
		$woo_inst_cart_tiers = get_post_meta($order_id, 'woo_inst_cart_tiers', true);
		//pree($order_id);
		//pree($woo_inst_cart_tiers);exit;
		if(empty($woo_inst_cart_tiers)){
			update_post_meta( $order_id, 'woo_inst_cart_tiers', sanitize_winst_data($woo_inst_get_cart_tiers));
			woo_inst_init_sessions();
			unset($_SESSION['woo_inst_tiers']);	
		}
	}
	
	function woo_inst_redirectcustom($order_id){
		$order = new WC_Order( $order_id );
		if($order->get_status()!='failed'){
			//unset($_SESSION['woo_inst_tiers']);	 
		}
	}

	//add_action( 'woocommerce_thankyou', 'woo_inst_redirectcustom');		
		
	add_action( 'woocommerce_before_order_itemmeta', 'woo_inst_woocommerce_before_order_itemmeta', 10, 3 );
	
	function woo_inst_woocommerce_before_order_itemmeta( $item_id, $item, $_product ){
		
		global $woo_inst_currency;
		$order_id = $item->get_order_id();
		$woo_inst_tiers = get_post_meta($order_id, 'woo_inst_cart_tiers', true);
		
		if(!empty($woo_inst_tiers)){
			foreach($woo_inst_tiers as $pid=>$tids){
				$product = wc_get_product($pid);
                $package_data = woo_inst_get_packages_data_array($pid);


                ?>
<div class="woo_inst_tiers">
<?php 

$tids = array_filter($tids, function($value){
	return $value > 0;
});

if(!empty($tids)):		
 ?>

<a href="<?php echo get_permalink($pid); ?>" target="_blank"><?php echo $product->get_title(); ?></a>

<ul>
<?php foreach($tids as $tier_id => $tid):


    $current_package = $package_data[$tier_id];
    $package_title = $current_package['title'];

    ?>
    <li><?php echo $package_title.' - '.wc_price($tid); ?></li>
<?php endforeach; ?>
</ul>
<?php endif; ?>
</div>
<?php
			}
		}
		
	}	
	function woo_inst_product_validity($product_id){
		$packages_validity = get_post_meta($product_id, 'woo_packages_validity', true);			
		//pree($packages_validity);
		$packages_validity = is_array($packages_validity)?current($packages_validity):0;
		//pree($packages_validity);
		return $packages_validity;
	}


	function woo_inst_packages_validity($order_date, $product_id){
		
		$ret = true;
		$packages_validity = woo_inst_product_validity($product_id);
		
		$now = time(); 
		$order_date = strtotime($order_date);
		$datediff = $now - $order_date;		
		$days_passed = floor($datediff / (60 * 60 * 24));	
		
		if($packages_validity>0){
			//pree($packages_validity);
			$total_remaining = ($packages_validity-$days_passed);
			//pree($total_remaining);
			$ret = ($total_remaining>0);
		}
		//pree($ret);
		return $ret;
	}
	
	function woo_inst_get_product_status($pid=false, $user_id=0){
		global $woo_inst_variable_product;
		//pree($woo_inst_variable_product);
		$ret = array();
		$validity[$pid] = array();
		$user_id = ($user_id?$user_id:get_current_user_id());
		$args =  array(
			'numberposts' => -1,
			'meta_key'    => '_customer_user',
			'meta_value'  => $user_id,
			'post_type'   => wc_get_order_types(),
			'post_status' => array(3), //array_keys( wc_get_order_statuses() ),
		);
		/*
		$args['meta_query'] = array(
				array(
				 'key' => 'woo_inst_cart_tiers',
				 'compare' => 'EXISTS' // this should work...
				),
		);
		*/
		
		//pree($args);
		$customer_orders = get_posts($args);		
		//pree($customer_orders);exit;

        $woo_inst_tiers = array();
		
		if(!empty($customer_orders)){
			foreach($customer_orders as $order){
				if(!in_array($order->post_status, array('wc-completed', 'wc-processing')))
				continue;
				
				$order_data = wc_get_order( $order->ID );
				//pree(get_post_meta($order->ID));exit;
				$order_date = $order->post_date;
				//pree($order_data-> get_items());

				$woo_inst_cart_tiers = get_post_meta($order->ID, 'woo_inst_cart_tiers', true);		
				//pree($woo_inst_cart_tiers);		
				$woo_inst_cart_tiers = is_array($woo_inst_cart_tiers)?$woo_inst_cart_tiers:array();
				$woo_inst_cart_tiers = array_map(function($data){ return array_filter($data); },  $woo_inst_cart_tiers);
				//pree($woo_inst_cart_tiers);
				$woo_inst_tiers[$order->ID] = $woo_inst_cart_tiers;

				foreach( $order_data-> get_items() as $item_key => $item_values ){


					//pree($item_values);
					//pree($item_values->get_product_id());
					//pree($item_values->get_quantity());
					//pree($woo_inst_variable_product);
					if($woo_inst_variable_product==$item_values->get_product_id()){
						$is_valid = woo_inst_packages_validity($order_date, $pid);
						$package = array_key_exists($pid, $woo_inst_cart_tiers) ? $woo_inst_cart_tiers[$pid]: array();
						$package_key = (!empty($package)?array_keys($package):array());
						$package_key = (!empty($package_key)?current($package_key):'');
						//pree($package);
						//pree($item_values->get_quantity());
						//pree($package_key);
						//pree($package[$package_key]);
						if($package_key && $package[$package_key]==$item_values->get_quantity()){
							$validity[$pid][$package_key] = $is_valid;
						}
					}
					//pree($pid);
					if($item_values->get_product_id()==$pid){
						//pree($pid);
						$ret[$pid][] = '*';
						
					}
				}

				
				//pree($woo_inst_tiers);//exit;
				//pree($is_valid);
				
				if($pid){
					
					
					$ret[$pid] = isset($ret[$pid])?$ret[$pid]:array();
					//pree($ret);
					$arr = isset($woo_inst_tiers[$order->ID][$pid])?$woo_inst_tiers[$order->ID][$pid]:array();										
					//pree($arr);
					
					$ret[$pid] = ($arr + $ret[$pid]);
					
				}else{
					$ret = ($ret + $woo_inst_tiers);
				}
			}
		}
		//pree($ret);exit;
		$arr = array($ret, $validity, $woo_inst_tiers);
		//pree($arr);
		//pree($arr);exit;
		return $arr;
		
	}
	
	function woo_inst_woocommerce_product_write_panel_tabs() {	
		
		global $post;
		
		$woo_inst_enabled = woo_inst_enabled_check($post->ID);
		
		
		
	
?>
		<li class="woo_inst_settings_tab attribute_options"><a href="#woo_inst_settings_tab_area"><span><?php _e( 'Online Course Settings', 'woo-installments' ); ?></span></a></li>
<?php        
	
	}
	add_filter( 'woocommerce_product_write_panel_tabs', 'woo_inst_woocommerce_product_write_panel_tabs' );
	
	function woo_inst_get_tiers_list($post_id, $raw=false){


		
		
		
		$woo_inst_tiers_list = get_post_meta( $post_id, 'woo_inst_tiers_list', true );
		
		/*if($raw){
			$ret = $woo_inst_tiers_list;
		}elseif(trim($woo_inst_tiers_list)){
			$arr = explode("\n", $woo_inst_tiers_list);
			array_walk($arr,
                function(&$val){

                    list($price, $desc) = explode('|', $val);
                    $val = array('price'=>$price, 'description'=>$desc);

			});
			$ret = $arr;
		}*/

		$ret = woo_inst_get_packages_data_array($post_id);
		
		return $ret;
	}
	
	function woo_inst_woocommerce_product_write_panels(){
		global $woo_inst_currency, $post, $woo_inst_pro, $woo_inst_url;
		
		$woo_inst_enabled = woo_inst_enabled_check($post->ID);

		$product_meta = get_post_meta($post->ID);
		$packages_name = woo_inst_get_tiers_list($post->ID);
		

		$package = false;
		if($woo_inst_pro){
			$woo_inst_pro_enabled = woo_inst_pro_enabled_check($post->ID);		
			$package = $woo_inst_pro_enabled['package'];
			
		}
		
		$packages_validity = woo_inst_product_validity($post->ID);
		//pree($packages_validity);

?>
<div id="woo_inst_settings_tab_area" class="panel woocommerce_options_panel" data-product="<?php echo $post->ID ?>" data-product_price="<?php echo wc_get_product()->get_price() ?>">
<?php if($woo_inst_enabled && $package){ ?>
<div class="options_group">
<p class="form-field">
<label for="package_label"><?php _e( 'Package Heading', 'woo-installments' ); ?></label><input type="text" placeholder="<?php echo $post->post_title; ?> <?php _e( 'Packages or Installments', 'woo-installments' ); ?>" name="woo_packages_heading" id="package_label" value="<?php echo get_post_meta($post->ID, 'woo_packages_heading', true); ?>" /><br /><br />
</p>
</div>
<?php } ?>

<div class="options_group">
<?php if($woo_inst_enabled){ ?>
<div class="woo_inst_alert"><?php _e( 'Please define packages for this product', 'woo-installments' ); ?></div>
<p class="form-field">

    <div class="container-fluid">

        <div class="row woo_inst_add_package_row">
            <div class="col-md-12" style="text-align: center">

                <button class="woo_inst_btn add_package"><?php _e( 'Add Package', 'woo-installments' ); ?></button>

            </div>
        </div>


        <?php if(empty($packages_name)):

            $package_btn = 'flex';
            $save_btn = 'none';

        ?>


        <?php else:



            $package_btn = 'none';
            $save_btn = 'flex';


            $total_package = sizeof($packages_name);
            $counter = 1;


            foreach ($packages_name as $package_id => $package_data){


                $woo_inst_price = array_key_exists('price', $package_data) ? $package_data['price'] : '';
                $woo_inst_title = array_key_exists('title', $package_data) ? $package_data['title'] : '';
                $woo_inst_image = array_key_exists('image', $package_data) ? $package_data['image'] : '';
                $woo_inst_description = array_key_exists('e_description', $package_data) ? $package_data['e_description'] : '';
                $placeholder_display = 'block';
                $img_display = 'none';
                $woo_inst_img_src = '';

                if($woo_inst_image){

                    $woo_inst_img_src = current(wp_get_attachment_image_src($woo_inst_image));
                    $placeholder_display = 'none';
                    $img_display = 'block';

                }

                $display = $counter == $total_package ? 'inline' : 'none';




        ?>



        <div class="row woo_inst_input_row" data-package="<?php echo $package_id ?>">

            <div class="col-md-7">

                <div class="row">



                    <div class="col-md-6">

                        <div class="woo-inst-input-group">
                            <div class="input-group-prepend">
                                <div class="input-group-text"><?php echo $woo_inst_currency ?></div>
                            </div>

                            <input type="number" step="0.01" min="0" value="<?php echo $woo_inst_price ?>" class="woo_inst_input woo_inst_package_price" name="woo_inst_package_data[<?php echo $package_id ?>][price]" placeholder="<?php _e( 'Price', 'woo-installments' ) ?>" title="<?php _e( 'Enter package price', 'woo-installments' ) ?>">

                        </div>

                    </div>

                    <div class="col-md-6">
                        <input type="text" value="<?php echo $woo_inst_title ?>" class="woo_inst_input woo_inst_package_title" name="woo_inst_package_data[<?php echo $package_id ?>][title]" placeholder="<?php _e( 'Title', 'woo-installments' ) ?>" title="<?php _e( 'Enter package title', 'woo-installments' ) ?>">
                    </div>

                </div>

                <div class="row">

                    <div class="col-md-12">
                        <textarea class="woo_inst_input woo_inst_package_description" name="woo_inst_package_data[<?php echo $package_id ?>][e_description]" placeholder="<?php _e( 'Description', 'woo-installments' ) ?>" title="<?php _e( 'Enter package description', 'woo-installments' ) ?>"><?php echo $woo_inst_description ?></textarea>
                    </div>



                </div>

            </div>

            <div class="col-md-3">
                <div class="woo_inst_selection_placeholder"><?php _e('Link course content', 'woo-installments') ?></div>
                <?php woo_inst_get_ptypes_selection($post->ID, $package_id) ?>
            </div>

            <div class="col-md-2">
                <button class="woo_inst_btn woo_inst_add_row" style="display: none"><?php _e('Add', 'woo-installments') ?></button>
                <button class="woo_inst_btn woo_inst_del_row"><?php _e('Remove', 'woo-installments') ?></button>
                <div class="">
                    <div class="woo_inst_image_placeholder" style="display: <?php echo $placeholder_display ?>" title="<?php _e('Click here to select image', 'woo-installments') ?>"><?php _e('Image', 'woo-installments') ?></div>
                    <div class="woo_inst_package_img" style="display: <?php echo $img_display ?>" title="<?php _e('Click here to change image', 'woo-installments') ?>">
                        <img src="<?php echo $woo_inst_img_src ?>" class="" />
                        <input type="hidden" name="woo_inst_package_data[<?php echo $package_id ?>][image]" value="<?php echo $woo_inst_image ?>" >
                        <span class="woo_inst_remove_img">&times;</span>
                    </div>
                </div>
            </div>
        </div>

        <?php

                $counter++;

            }

            endif; ?>





        <div class="row woo_inst_clone_row" data-package="1" style="display: none">

            <div class="col-md-6">

                <div class="row">



                    <div class="col-md-4">


                        <div class="woo-inst-input-group">
                            <div class="input-group-prepend">
                                <div class="input-group-text"><?php echo $woo_inst_currency ?></div>
                            </div>

                            <input type="number" step="0.01" min="0" class="woo_inst_input woo_inst_package_price" name="" placeholder="<?php _e( 'Price', 'woo-installments' ) ?>" title="<?php _e( 'Enter package price', 'woo-installments' ) ?>">

                        </div>

                    </div>

                    <div class="col-md-8">
                        <input type="text" class="woo_inst_input woo_inst_package_title" name="" placeholder="<?php _e( 'Title', 'woo-installments' ) ?>" title="<?php _e( 'Enter package title', 'woo-installments' ) ?>">
                    </div>

                </div>

                <div class="row">

                    <div class="col-md-12">
                        <textarea class="woo_inst_input woo_inst_package_description" name="" placeholder="<?php _e( 'Description', 'woo-installments' ) ?>" title="<?php _e( 'Enter package description', 'woo-installments' ) ?>"></textarea>
                    </div>


                </div>

            </div>

            <div class="col-md-3">
                <div class="woo_inst_selection_placeholder"><?php _e('Link course content', 'woo-installments') ?></div>
                <?php woo_inst_get_ptypes_selection() ?>
            </div>

            <div class="col-md-2">
                <button class="woo_inst_btn woo_inst_add_row" style="display: none"><?php echo __('Add', 'woo-installments'); ?></button>
                <button class="woo_inst_btn woo_inst_del_row"><?php echo __('Remove', 'woo-installments'); ?></button>
                <div class="">
                    <div class="woo_inst_image_placeholder" title="<?php _e('Click here to select image', 'woo-installments') ?>">
                        <?php _e('Image', 'woo-installments') ?>
                    </div>
                    <div class="woo_inst_package_img" style="display: none" title="<?php _e('Click here to change image', 'woo-installments') ?>">
                        <img src="" />
                        <input type="hidden" name="" value="" >
                        <span class="woo_inst_remove_img">&times;</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="row woo_inst_save_row" style="display: <?php echo $save_btn ?>">

            <div class="col-md-12">

                <input type="submit" name="woo_inst_save_package" class="button button-secondary" value="<?php _e('Save Changes', 'woo-installments') ?>" style="margin-left: 0">

            </div>

        </div>

        </div>


<!---->
<!--<div class="row">-->
<!--    <div class="col-md-12">-->
<!--        <label for="tier_label">--><?php //_e( 'Installment Slab', 'woo-installments' ); ?><!-- (--><?php //echo $woo_inst_currency; ?><!--)</label><textarea style="height:200px" placeholder="10|Package-1: First 10 Lessons" name="tiers_list" id="tier_label"></textarea><br />-->
<!--    </div>-->
<!--</div>-->


        <div class="row woo_inst_example_picture" style="display: <?php echo $package_btn ?>">

            <div class="col-md-12">

                <img class="woo_inst_img_thumbnail" src="<?php echo $woo_inst_url ?>images/example.png" />

            </div>

        </div>


</p>
<?php  }else{ ?>
<div class="woo_inst_alert_validity"><?php _e( 'Packages are not enabled for this product.', 'woo-installments' ); ?> <a href="<?php echo admin_url('admin.php?page=woo_installments'); ?>" target="_blank"><?php _e( 'Click here', 'woo-installments' ); ?></a> <?php _e( 'to enable', 'woo-installments' ); ?>.</div>
<?php } ?>
</div>

<div class="options_group">
<div class="form-field woo_inst_alert_validity">
<strong><?php _e( 'Validity?', 'woo-installments' ); ?></strong>
<?php //if(!empty($woo_packages_validity)){ foreach($woo_packages_validity as $packages_validity){ ?>
<input type="number" placeholder="30" name="woo_packages_validity[]" id="package_validity" value="<?php echo $packages_validity; ?>" min="0" /><?php _e( 'Days', 'woo-installments' ); ?><br /><br />
<p><?php _e( 'How it works?', 'woo-installments'); ?><br /><?php _e('Each slab will use the above defined number of days as valid access and after that customer has to purchase the remaining required packages or full product to access the course, page, post or classes etc. ', 'woo-installments' ); ?><br />
<br />
<?php _e( 'Note: Leave it to zero "0" for no restrictions.', 'woo-installments'); ?></p>
<?php //} } ?>
</div>
</div>


</div>
<?php		
	}	
	add_filter( 'woocommerce_product_data_panels', 'woo_inst_woocommerce_product_write_panels' );
	
	function woo_inst_woocommerce_process_product_meta($post_id){
		
		$woo_inst_enabled = woo_inst_enabled_check($post_id);
		
		if ( isset($_POST['tiers_list']) && $woo_inst_enabled) {
			
			$tiers_list_updated = implode( "\n", array_map( 'sanitize_text_field', explode( "\n", $_POST['tiers_list'] ) ) );
			update_post_meta( $post_id, 'woo_inst_tiers_list', sanitize_winst_data($tiers_list_updated) );
		} 		
		

		if ( isset($_POST['woo_packages_validity']) && $woo_inst_enabled) {
			
			$woo_packages_validity = array_map( 'sanitize_text_field', $_POST['woo_packages_validity']);
			
			update_post_meta( $post_id, 'woo_packages_validity', sanitize_winst_data($woo_packages_validity) );
		} 		
				
		
		
	}
	add_action( 'woocommerce_process_product_meta', 'woo_inst_woocommerce_process_product_meta' );	
		
	function woo_inst_add_meta_box() {
	
		$screens = get_option('woo_inst_ptypes', array( 'post', 'page' ));
		$screens =  array_filter($screens, function($val){return ($val!='0'); });
		//pree($screens);
	
		foreach ( $screens as $screen ) {
	
			add_meta_box(
				'woo_inst_products_metabox',
				__( 'Packages Control', 'woo-installments' ),
				'woo_inst_products_metabox_content',
				$screen,
				'side',
				'high'
			);
		}
		
	
	}
	add_action( 'add_meta_boxes', 'woo_inst_add_meta_box', 2 );
	
	
	function woo_inst_products_metabox_content( $post ){
		
		global $woo_inst_currency;
		
?>
<?php
	$products = woo_inst_get_enabled_products();
	//pree($products);
	
	if(!empty($products)){
		
		$applied_products = get_post_meta($post->ID, 'woo_inst_products', true);
		$applied_products = is_array($applied_products)?$applied_products: array();
		//pree($applied_products);
?>
<?php _e('To access this page, users need to buy the following tiers', 'woo-installments'); ?>
<select name="woo_inst_products[]" style="width:100%; height:200px" multiple="multiple">
<option value="0">Select</option>
<?php	
		
		foreach($products as $prod){
		$product = wc_get_product($prod->ID);
		$woo_inst_tiers_list = woo_inst_get_tiers_list($prod->ID);
		
		if(!empty($woo_inst_tiers_list)){
?>
    <optgroup label="<?php echo $prod->post_title.' '.$woo_inst_currency.$product->get_price(); ?>">
<?php	foreach($woo_inst_tiers_list as $package_key => $list){ $key = $prod->ID.'|'.$package_key; ?>
        <option value="<?php echo $key; ?>" <?php selected( in_array($key, $applied_products) ); ?>><?php echo $woo_inst_currency.$list['price'].' - '.$list['description']; ?></option>
<?php } ?>        
    </optgroup>

    
<?php
		}
		}
?>
</select>
<?php wp_nonce_field( 'woo_inst_meta_action', 'woo_inst_meta_field' ); ?>
<?php    
	}else{
?>
<?php _e('No products are found as packages.', 'woo-installments'); ?>
<?php		
	}
?>		
<?php		
	}
		
	
	function woo_inst_save_metabox_callback( $post_id ) {
		
		if(is_admin() && isset($_POST['woo_inst_meta_field'])){
					
			if ( 
				! isset( $_POST['woo_inst_meta_field'] ) 
				|| ! wp_verify_nonce( $_POST['woo_inst_meta_field'], 'woo_inst_meta_action' ) 
			) {
			
			   _e('Sorry, your nonce did not verify.', 'woo-installments');
			   exit;
			
			} else {
			
			   // process form data
			   
				//pree($_POST);exit;
				$woo_inst_products = sanitize_winst_data($_POST['woo_inst_products'] );
				update_post_meta($post_id, 'woo_inst_products', $woo_inst_products);		   
			}
		
		}
		 
	}
	add_action( 'save_post', 'woo_inst_save_metabox_callback' );
	
	
	add_filter('the_content', 'woo_inst_the_content');
	
	
	
	
	function woo_inst_the_content( $content_original, $user_data=array(), $post_id=false ) { 
		// Check if we're inside the main loop in a single post page.
		$user_id = $user_email = false;
		if(!empty($user_data)){
			list($user_id, $user_email) = $user_data;
			
			$bid = get_user_by('id', $user_id);
			$bem = get_user_by('email', $user_email);
			
			if($bid->ID!=$bem->ID){
				$user_id = $bem->ID;
			}
		}			

		
		$outside = ($post_id?true:false);
		$content = $content_original;
		$debug = array();
		if ( ((is_single() || is_page()) && in_the_loop() && is_main_query()) || $user_id ) {
			//pree($content);

			global $post, $woo_inst_currency;
			$post_id = ($post_id?$post_id:$post->ID);
			$applied_products = get_post_meta($post_id, 'woo_inst_products', true);
			$applied_products = is_array($applied_products)?$applied_products: array();
//			pree($applied_products);
			if(!empty($applied_products)){
				
//				pree($user_id);
				
				if(is_user_logged_in() || $user_id){
					$pids = array();
					foreach($applied_products as $prods){

					    if($prods == 0 ) continue;

						list($pid, $tid) = explode('|', $prods);
						
						$woo_inst_enabled_check = woo_inst_enabled_check($pid);
						
						if($woo_inst_enabled_check){
							
							$pids[$pid] = (isset($pids[$pid]) && is_array($pids[$pid]))?$pids[$pid]:array();
							
							if(!in_array($tid, $pids[$pid]))
							$pids[$pid][] = $tid;
							
							
						}
					}

//					pree($pids);exit;
					

					if(!empty($pids)){
						foreach($pids as $pid=>$tids){

//						    pree($tids);exit;



							$get_product_status = woo_inst_get_product_status($pid, $user_id);

//							pree($get_product_status);exit;
							
							$debug['get_product_status'] = $get_product_status;
//							pree($get_product_status);
//							exit;
							
							list($product_status, $product_validity) = $get_product_status;		
							$debug['product_status'] = $product_status;
							$debug['product_validity'] = $product_validity;
							//$debug['woo_inst_tiers_order'] = $woo_inst_tiers_order;
//                            pree($product_status);
//                            pree($product_validity);
//                            exit;
							
							
							
//							pree($product_status);
							//pree($debug);
							//pree($product_validity);
							
							$purchased_actual = isset($product_status[$pid])?array_keys($product_status[$pid]):array();
							
							$debug['purchased_actual'] = $purchased_actual;

							$purchased = array_intersect($tids, $purchased_actual);
//
//							$debug['tids'] = $tids;
//							$debug['purchased'] = $purchased;

							//pree($purchased_actual);
							//pree($tids);
							//pree($purchased);
							
							$diff = array_diff($tids, $purchased);
							//pree($diff);

							$debug['diff'] = $diff;
							
							$validity_count = woo_inst_product_validity($pid);
							//pree($validity_count);
							//pree($validity_applicable);
							$validity_applicable = ($validity_count>0 && !in_array('*', $purchased_actual));//product_status
							$is_valid = false;
							
							//pree($validity_applicable);
							
//							pree($purchased);
							
							
							$debug['valids'] = array();
							if($validity_applicable){								
								if(!empty($purchased)){
									foreach($purchased as $slabs){
										$is_valid = (array_key_exists($pid, $product_validity) && !empty($product_validity[$pid]) && array_key_exists($slabs, $product_validity[$pid]) && $product_validity[$pid][$slabs]);	
										$debug['valids'][$pid.'_'.$slabs] = ($is_valid?1:0).'_'.$product_validity[$pid][$slabs];
										if($is_valid)
										continue;
									}
								}
							}
//
//							pree($diff);
//							pree($purchased_actual);
							

							if(!empty($diff) && !in_array('*', $purchased_actual)){//product_status
								$content = '<div class="woo_inst_alert">'.__('Sorry for the inconvenience, you are required to purchase the following package(s) to proceed.', 'woo-installments').'</div>';
								$product = wc_get_product($pid);
								$woo_inst_tiers_list = woo_inst_get_tiers_list($pid);
								//pree($woo_inst_tiers_list);
								
								if(!empty($woo_inst_tiers_list)){
									$content .= '<ul>';
									foreach($woo_inst_tiers_list as $pkg_id => $list){
									
										if(in_array($pkg_id, $diff))
										$content .= '<li><a href="'.get_permalink($pid).'" target="_blank">'.$woo_inst_currency.$list['price'].' - '.$list['title'].'</a></li>';
									}
									$content .= '</ul>';			
								}else{
									$content = $content_original;
								}
								
							}elseif($validity_applicable && !$is_valid){
								
								$all_done = true;
								
								$content = '<div class="woo_inst_alert">'.__('Sorry for the inconvenience, you are required to purchase the remaining package(s) to proceed.', 'woo-installments').'</div>';
								$product = wc_get_product($pid);
								$woo_inst_tiers_list = woo_inst_get_tiers_list($pid);
								$content .= '<ul>';
								if(!empty($woo_inst_tiers_list)){
									foreach($woo_inst_tiers_list as $list){ 
									
										if(!in_array($list['price'], $purchased_actual)){
											$content .= '<li><a href="'.get_permalink($pid).'" target="_blank">'.$woo_inst_currency.$list['price'].' - '.$list['description'].'</a></li>';
											$all_done = false;
										}
									}
								}					
								$content .= '</ul>';			
								$content .= __('Note:', 'woo-installments');
								$content .= __('Every package increases your access with another ', 'woo-installments').$validity_count.__('days', 'woo-installments').'.';
								
								if($all_done){
									$content = $content_original;
								}
								
							}
							
						}
					}
				}else{
					$content = sprintf(__('Please', 'woo-installments').' %s '.__('to proceed', 'woo-installments').'.', '<a href="'.wp_login_url().'">'.__('login', 'woo-installments').'</a>');
				}
				
			}
			
			
			
		}else{
			
		}
		
	
	 
		return ($outside?array('content'=>$content, 'debug'=>$debug):$content);
	}
	


	function woo_inst_menu()
	{
		global $woo_inst_pro, $woo_inst_data;
		
		$woo_inst_name = explode('Content', $woo_inst_data['Name']);
		
		$title = current($woo_inst_name).($woo_inst_pro?' '.__('Pro', 'woo-installments').'':'');
		add_submenu_page('woocommerce', __( $title, 'woo-installments' ), __( $title, 'woo-installments' ), 'manage_woocommerce', 'woo_installments', 'woo_installments' );



	}

	function woo_installments(){ 



		if ( !current_user_can( 'install_plugins' ) )  {



			wp_die( __( 'You do not have sufficient permissions to access this page.', 'woo-installments' ) );



		}



		global $wpdb; 

		

				
		include('wc_settings.php');	

		

	}	

	
	if(!function_exists('woo_inst_start')){
	function woo_inst_start(){	



				
		}	





	}

	if(!function_exists('woo_inst_end')){
	function woo_inst_end(){	

		}
	}	

	if(!function_exists('woo_inst_is_woocommerce_page')){
		function woo_inst_is_woocommerce_page () {
				if(  function_exists ( "is_woocommerce" ) && is_woocommerce()){
						return true;
				}
				$woocommerce_keys   =   array ( "woocommerce_shop_page_id" ,
												"woocommerce_terms_page_id" ,
												"woocommerce_cart_page_id" ,
												"woocommerce_checkout_page_id" ,
												"woocommerce_pay_page_id" ,
												"woocommerce_thanks_page_id" ,
												"woocommerce_myaccount_page_id" ,
												"woocommerce_edit_address_page_id" ,
												"woocommerce_view_order_page_id" ,
												"woocommerce_change_password_page_id" ,
												"woocommerce_logout_page_id" ,
												"woocommerce_lost_password_page_id" ) ;
				foreach ( $woocommerce_keys as $wc_page_id ) {
						if ( get_the_ID () == get_option ( $wc_page_id , 0 ) ) {
								return true ;
						}
				}
				return false;
		}	
	}

	function woo_inst_plugin_links($links) { 
		global $woo_inst_premium_link, $woo_inst_pro;

		$settings_link = '<a href="admin.php?page=woo_installments">'.__('Settings', 'woo-installments').'</a>';
		
		if($woo_inst_pro){
			array_unshift($links, $settings_link); 
		}else{
			 
			$woo_inst_premium_link = '<a href="'.esc_url($woo_inst_premium_link).'" title="'.__('Go Premium', 'woo-installments').'" target=_blank>'.__('Go Premium', 'woo-installments').'</a>'; 
			array_unshift($links, $settings_link, $woo_inst_premium_link); 
		
		}
		
		
		return $links; 
	}

	function woo_inst_is_course_page(){


        $str_pos = strpos($_SERVER['REQUEST_URI'],'woo-online-courses');

        return !$str_pos === false;
    }

    function woo_inst_register_scripts() {

	    global $post, $woo_inst_selected_template, $woo_inst_pro;
		
		if(empty($post)){ return; }

        $woo_inst_pro_enabled = woo_inst_pro_enabled_check($post->ID);
        $package = $woo_inst_pro_enabled['package'];
        $full = $woo_inst_pro_enabled['full'];
        $template_array = array('default', 'package');

		$translation_array = array(

            'hide_full_package' => $woo_inst_pro && !in_array($woo_inst_selected_template, $template_array) && $package && !$full,

        );
		
		
		wp_enqueue_script(
			'wi-scripts',
			plugins_url('js/scripts.js', dirname(__FILE__)),
			array('jquery')
		);

		wp_localize_script('wi-scripts', 'woo_inst_obj', $translation_array);


        if(is_product() || woo_inst_is_course_page()){

            wp_enqueue_script(
                'wi-bs-script',
                plugins_url('js/bootstrap.min.js', dirname(__FILE__)),
                array('jquery')
            );

            wp_enqueue_style( 'wi-bs-front', plugins_url('css/bootstrap.min.css', dirname(__FILE__)), array(), date('mhi') );

        }
		
		wp_enqueue_style( 'wi-front', plugins_url('css/front-style.css', dirname(__FILE__)), array(), date('mhi') );
		wp_enqueue_style( 'wi-fa-min', plugins_url('css/font-awesome.min.css', dirname(__FILE__)), array(), date('mhi') );

	
	}
	
		
	function woo_inst_admin_scripts() {
		
		global $css_arr;


        if(is_admin() && isset($_GET['page']) && $_GET['page'] == 'woo_installments'){


            wp_enqueue_script(
                'wi-jqform-script',
                plugins_url('js/jquery.form.min.js', dirname(__FILE__)),
                array('jquery')
            );

            wp_enqueue_script(
                'wi-bs-script',
                plugins_url('js/bootstrap.min.js', dirname(__FILE__)),
                array('jquery')
            );



            wp_enqueue_style( 'wi-bs-admin', plugins_url('css/bootstrap.min.css', dirname(__FILE__)), array(), date('mhi') );
            wp_enqueue_style( 'wi-fa-min', plugins_url('css/font-awesome.min.css', dirname(__FILE__)), array(), date('mhi') );

        }


        wp_enqueue_style( 'wi-bs-grid-front', plugins_url('css/bootstrap-grid.min.css', dirname(__FILE__)), array(), date('mhi') );
        wp_register_style('wi-admin', plugins_url('css/admin-style.css', dirname(__FILE__)));
		
		
		wp_enqueue_style( 'wi-admin' );


		
		wp_enqueue_script(
			'wi-scripts',
			plugins_url('js/admin-scripts.js?t='.time(), dirname(__FILE__)),
			array('jquery')
		);			
		
		
		$translation_array = array(

			'this_url' => admin_url( 'admin.php?page=woo_installments' ),
			
			'woo_inst_tab' => (isset($_GET['t'])?$_GET['t']:'0'),
            'required_warning' => __('Please enter price and title for all packages to add more.', 'woo-installments'),
            'del_confirm' => __('Do you want to remove this package?', 'woo-installments'),
            'del_confirm_playlist' => __('Are you sure? you want to delete this playlist.', 'woo-installments'),
            'no_packages_defined' => __('No packages defined yet.', 'woo-installments'),
            'empty_alert' => __('Please fill all required fields', 'woo-installments'),
            'url_alert' => __('At lease one url required to save a playlist', 'woo-installments'),
            'woo_inst_nonce' => wp_create_nonce('woo_inst_nonce_action_common'),

		);
		
		wp_localize_script( 'wi-scripts', 'woo_inst_obj', $translation_array );
		
	}
			
	
	function woo_inst_pro_admin_style() {
		
	}
		
	function woo_inst_handle_qty_field($product_quantity, $cart_item_key, $cart_item ) {
		global $woo_inst_variable_product;
		//woo_inst_pree($cart_item);
		if($cart_item['product_id']==$woo_inst_variable_product)
		return __('N/A', 'woo-installments');
		else
		return $product_quantity;
	}
	add_filter( 'woocommerce_cart_item_quantity', 'woo_inst_handle_qty_field', 10, 3 );	

	function woo_inst_handle_price_field($price, $cart_item, $cart_item_key  ) {
		global $woo_inst_variable_product;

		if($cart_item['product_id']==$woo_inst_variable_product)
		return __('Package', 'woo-installments');
		else
		return $price;
	}
	add_filter( 'woocommerce_cart_item_price', 'woo_inst_handle_price_field', 10, 3 );	
	
	add_action( 'init', 'woo_inst_load_textdomain' );

	function woo_inst_load_textdomain() {
		global $woo_inst_dir;
		//echo $woo_inst_dir . 'i18n/languages';
		load_plugin_textdomain( 'woo-installments', false, $woo_inst_dir . 'languages/' ); 
	}
	
	function woo_inst_api_public(){
		
		$details = array(':)');
		
		$posted = (isset($_POST['action']) && $_POST['action']=='woo_inst_api_public')?$_POST:$_GET;
		$posted_clean = array();

		if(!empty($posted)){
			foreach($posted as $k=>$v){
				$k = str_replace(array('amp;'), '', $k);
				$posted_clean[$k] = $v;
			}
		}
		$posted = $posted_clean;
		
		
		if(isset($posted['woo_inst_api_public']) && $posted['woo_inst_api_public']==date('Ym').substr($_SERVER['HTTP_HOST'], -6, 6)){
		
			$details = array();
			//THESE DETAILS ARE SAFE TO PROVIDE PUBLICLY
			
			$details['get_bloginfo'] = get_bloginfo();
			$details['is_user_logged_in'] = is_user_logged_in()?'true':'false';
			$details['user_email'] = $posted['user_email'];
			
			$the_content = woo_inst_the_content('', array($posted['user_id'], $details['user_email']), $posted['post_id']);
			$details['debug'] = $the_content['debug'];
			$details['the_content'] = $the_content['content'];
			
		
		}
		
		echo json_encode($details);exit;
	
		
	}
	
	add_action('wp_ajax_woo_inst_api_public', 'woo_inst_api_public');
	add_action('wp_ajax_nopriv_woo_inst_api_public', 'woo_inst_api_public');
	
	function filter_woocommerce_cart_item_thumbnail( $product_get_image, $cart_item, $cart_item_key ) { 
		global $woo_inst_variable_product;
		//pree($woo_inst_variable_product);
		//pree($product_get_image);
		//pree($cart_item);
		if($cart_item['product_id']==$woo_inst_variable_product){
			$tiers = woo_inst_get_cart_tiers();
			$tier_keys = array_keys($tiers);
			//pree($tier_keys);
			if(!empty($tier_keys)){
				$tier_key = current($tier_keys);
				$tier_image = get_the_post_thumbnail_url( $tier_key, 'post-thumbnail' );
				//pree($tier_image);
				$product_get_image = ($tier_image?'<img src="'.$tier_image.'" />':'');
			}
		}
		// make filter magic happen here... 
		return $product_get_image; 
	}; 
			 
	// add the filter 
	add_filter( 'woocommerce_cart_item_thumbnail', 'filter_woocommerce_cart_item_thumbnail', 10, 3 );

	if(!function_exists('woo_inst_get_existing_demo_ids')){
	    function woo_inst_get_existing_demo_ids($number_content = -1){

            $existing_demo_args = array(

                'numberposts' => $number_content,
                'post_type' => 'any',
                'post_status' => 'publish',
                'fields' => 'ids',
                'meta_query' => array(
                    array(

                        'key' => '_woo_inst_demo_content',
                        'compare' => "EXIST",
                    )
                )

            );

            return get_posts($existing_demo_args);

        }
    }

    add_action('wp_ajax_woo_inst_add_demo_content', 'woo_inst_add_demo_content');

    if(!function_exists('woo_inst_add_demo_content')){


        function woo_inst_add_demo_content(){


            if(!empty($_POST) && isset($_POST['woo_inst_add_content'])){

                $result = array(
                        'status' => false,
                        'message' => ''
                );


                if(!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'woo_inst_nonce_action_common')){


                    wp_die(__('Sorry, your nonce did not verify.', 'woo-installments'));

                }else{


                    //do your action


                    $new_demo_product_collection = array();
                    $new_demo_product_collection = woo_inst_add_new_demo_content();


                    if(!empty($new_demo_product_collection)){

                        $result['status'] = true;
                        $result['message'] = __('Demo content created successfully', 'woo-installments');
                    }


                }

                wp_send_json($result);

            }
        }
    }


    add_action('wp_ajax_woo_inst_remove_demo_content', 'woo_inst_remove_demo_content');

    if(!function_exists('woo_inst_remove_demo_content')){


        function woo_inst_remove_demo_content(){



            if(!empty($_POST) && isset($_POST['woo_inst_remove_content'])){
                $result = array(
                    'status' => false,
                );

                if(!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'woo_inst_nonce_action_common')){


                    wp_die(__('Sorry, your nonce did not verify.', 'woo-installments'));

                }else{


                    //do your action


                    $existing_demo_content = woo_inst_get_existing_demo_ids(1);


                    if(!empty($existing_demo_content)){

                       $remove_status = woo_inst_remove_new_demo_content();

                       if($remove_status){

                           $result['status'] = true;
                           $result['message'] = __('Demo content removed successfully', 'woo-installments');

                       }

                    }



                }


                wp_send_json($result);


            }


        }
    }

    if(!function_exists('woo_inst_add_new_demo_content')){

        function woo_inst_add_new_demo_content(){

            global $woo_inst_dir, $package_prefix;

            include_once('demo-data.php');

            $woo_inst_settings_default = get_option('woo_inst_settings', array());
            $woo_inst_pro_settings = get_option('woo_inst_pro_settings', array());
            $woo_inst_package = isset($woo_inst_pro_settings['package']) ? $woo_inst_pro_settings['package'] : array();
            $woo_inst_full_course = isset($woo_inst_pro_settings['full']) ? $woo_inst_pro_settings['full'] : array();
            $woo_inst_products_saved = isset($woo_inst_settings_default['woo_inst_products']) ? $woo_inst_settings_default['woo_inst_products'] : array();

            $new_demo_product_collection = array();
            if($demo_products && !empty($demo_products)){

                $new_demo_product_collection = array();
                foreach($demo_products as $index => $demo_product){


                    $product_title = $demo_product['product_title'];
                    $product_description = $demo_product['description'];
                    $price = $demo_product['price'];
                    $packages = $demo_product['packages'];
                    $demo_term_slug = 'woo_inst_demo_course';
                    $demo_course_term = get_term_by('slug', $demo_term_slug, 'product_cat');


                    if(!$demo_course_term){

                        wp_insert_term(__('Demo Course', 'woo-installments'), 'product_cat', array(

                                'description' => '',
                                'slug' => $demo_term_slug,

                        ));

                        $demo_course_term = get_term_by('slug', $demo_term_slug, 'product_cat');

                    }





                    if($product_title && $product_description && is_int($price) && is_array($packages) && !empty($packages)){

                        $existing_product = get_page_by_title($product_title, OBJECT, 'product');


                        $new_demo_product = $existing_product ? new WC_Product($existing_product->ID) : new WC_Product();
                        $new_demo_product->set_name($product_title);
                        $new_demo_product->set_description($product_description);
                        $new_demo_product->set_regular_price($price);
                        $new_demo_product->set_virtual(true);
                        $new_demo_product->set_category_ids(array($demo_course_term->term_id));

                        $new_demo_product->save();

                        $new_demo_product_id = $new_demo_product->get_id();



                        if(!empty($packages)){


                            $new_demo_product_collection[] = $new_demo_product_id;
                            $package_id = 1;

                            foreach($packages as $package_index => $package){


                                $bind_post = $package['bind_post'];

                                $existing_post = get_page_by_title($bind_post['post_title'], OBJECT, 'post');


                                $post_args = array(

                                    'post_status' => 'publish',
                                    'post_author' => get_current_user_id(),
                                    'post_title' => $bind_post['post_title'],
                                    'post_content' => $bind_post['post_content'],

                                );

                                if($existing_post){

                                    $post_args['ID'] = $existing_post->ID;
                                }

                                $post_id = wp_insert_post($post_args);

                                if($post_id){

                                    $product_package = $new_demo_product_id.'|'.$package_id;
                                    update_post_meta($post_id, 'woo_inst_products', array($product_package));
                                    update_post_meta($post_id, '_woo_inst_demo_content', true);

                                }


                                unset($package['bind_post']);

                                update_post_meta($new_demo_product_id, $package_prefix.'_'.$package_id, $package);
                                $package_id++;

                            }


                            update_post_meta($new_demo_product_id, '_woo_inst_demo_content', true);
                            update_post_meta($new_demo_product_id, '_woo_inst_package_id', $package_id);

                        }

                    }

                }

                if(!empty($new_demo_product_collection)){

                    $woo_inst_pro_settings['package'] = array_merge($woo_inst_package, $new_demo_product_collection);
                    $woo_inst_pro_settings['full'] = array_merge($woo_inst_full_course, $new_demo_product_collection);
                    $woo_inst_settings_default['woo_inst_products'] = array_merge($woo_inst_products_saved, $new_demo_product_collection);

                    $woo_inst_pro_settings['package'] = array_unique($woo_inst_pro_settings['package']);
                    $woo_inst_pro_settings['full'] = array_unique($woo_inst_pro_settings['full']);
                    $woo_inst_settings_default['woo_inst_products'] = array_unique($woo_inst_settings_default['woo_inst_products']);
                    update_option('woo_inst_pro_settings', $woo_inst_pro_settings);
                    update_option('woo_inst_settings', $woo_inst_settings_default);

                    return $new_demo_product_collection;

                }

            }

            return $new_demo_product_collection;

        }
    }

    if(!function_exists('woo_inst_remove_new_demo_content')){

        function woo_inst_remove_new_demo_content(){


            $new_demo_product_collection = woo_inst_get_existing_demo_ids(-1);



            if(!empty($new_demo_product_collection)){

                $woo_inst_settings_default = get_option('woo_inst_settings', array());
                $woo_inst_pro_settings = get_option('woo_inst_pro_settings', array());
                $woo_inst_package = isset($woo_inst_pro_settings['package']) ? $woo_inst_pro_settings['package'] : array();
                $woo_inst_full_course = isset($woo_inst_pro_settings['full']) ? $woo_inst_pro_settings['full'] : array();
                $woo_inst_products_saved = isset($woo_inst_settings_default['woo_inst_products']) ? $woo_inst_settings_default['woo_inst_products'] : array();


                $woo_inst_pro_settings['package'] = array_diff($woo_inst_package, $new_demo_product_collection);
                $woo_inst_pro_settings['full'] = array_diff($woo_inst_full_course, $new_demo_product_collection);
                $woo_inst_settings_default['woo_inst_products'] = array_diff($woo_inst_products_saved, $new_demo_product_collection);
                update_option('woo_inst_pro_settings', $woo_inst_pro_settings);
                update_option('woo_inst_settings', $woo_inst_settings_default);



                $del_array = array();

                if(!empty($new_demo_product_collection)){

                    foreach($new_demo_product_collection as $key => $post_id){

                        $del_status = wp_delete_post($post_id);
                        $del_array[] = $del_status != false;

                    }
                }

                return in_array(true, $del_array);


            }

        }
    }

    function woo_inst_filter_demo_content($query) {
        global $typenow;

        if (($typenow == 'product' || $typenow == 'post') && is_admin() && $query->is_main_query() ) {


            $meta_query = array(
                array(

                    'key' => '_woo_inst_demo_content',
                    'compare' => 'NOT EXISTS',
                ),
            );
            $query->set('meta_query',$meta_query);
        }
    }
    add_action( 'pre_get_posts', 'woo_inst_filter_demo_content' );

    function woo_inst_get_demo_products(){



        $args = array(

            'orderby'        => 'name',
            'order'          => 'ASC',
            '_woo_inst_demo_content' => true,
            'limit' => -1,

        );

        $results = wc_get_products($args);

        //woo_inst_pree($results);
        return $results;
    }



