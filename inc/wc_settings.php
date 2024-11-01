<?php defined( 'ABSPATH' ) or die( 'No script kiddies please!' );
	if ( !current_user_can( 'install_plugins' ) ) {
		wp_die( __( 'You do not have sufficient permissions to access this page.', 'woo-installments' ) );
	}

	global $woo_inst_currency, $woo_inst_settings, $woo_inst_activated, $woo_inst_pro_settings, $woo_inst_pro_msg;
	global $woo_inst_premium_link, $woo_inst_data, $woo_inst_pro, $wpdb, $woo_inst_dir, $woo_inst_url, $woo_inst_msgs;
	$wpurl = get_bloginfo('wpurl');
	
	$woo_inst_ptypes = get_option('woo_inst_ptypes', array( 'post', 'page' ));
	//pree($woo_inst_pro_settings);//exit;
	

?>


<div class="wrap wc_inst_settings_div">

        



        <div class="icon32" id="icon-options-general"><br></div><h2><?php echo $woo_inst_data['Name']; ?> <?php echo '('.$woo_inst_data['Version'].($woo_inst_pro?') Pro':')'); ?> - <?php _e("Settings","woo-installments"); ?></h2> 
    
         
        <h2 class="nav-tab-wrapper">

            <a class="nav-tab nav-tab-active"><?php _e("Course Products","woo-installments"); ?></a>
            <a class="nav-tab"><?php _e("Post Types","woo-installments"); ?></a>
            <a class="nav-tab"><?php _e("Audio/Video","woo-installments"); ?></a>
            <a class="nav-tab" style="float: right"><?php _e("Help","woo-installments"); ?></a>
            <a class="nav-tab"><?php _e("Customization","woo-installments"); ?></a>
            <a class="nav-tab premium-tab"><?php _e("Layouts","woo-installments"); ?></a>
            <a class="nav-tab"><?php _e("Demo","woo-installments"); ?></a>

        </h2>

         

<?php if(!$woo_inst_pro): ?>
<a title="<?php _e("Click here to download pro version","woo-installments"); ?>" style="background-color: #9B5C8F;    color: #fff !important;    padding: 2px 30px;    cursor: pointer;    text-decoration: none;    font-weight: bold;    right: 0;    position: absolute;    top: 0;    box-shadow: 1px 1px #ddd; display:none;" href="https://shop.androidbubbles.com/download/" target="_blank"><?php _e("Already a Pro Member?","woo-installments"); ?></a>
<?php endif; ?>


<?php if(!$woo_inst_activated): ?>
<div class="alert alert-warning my-3 text-center pb-5">
<div class="h3"><?php _e("You need WooCommerce plugin to be installed and activated.","woo-installments"); ?> <?php _e("Please","woo-installments"); ?> <a href="plugin-install.php?s=woocommerce&tab=search&type=term" target="_blank"><?php _e("Install","woo-installments"); ?></a> <?php _e("and","woo-installments"); ?>/<?php _e("or","woo-installments"); ?> <a href="plugins.php?plugin_status=inactive" target="_blank"><?php _e("Activate","woo-installments"); ?></a> WooCommerce <?php _e("plugin to proceed","woo-installments"); ?>.</div>

</div>
<?php exit; endif; ?>




<form class="nav-tab-content" action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="post">
<?php wp_nonce_field( 'woo_inst_settings_action', 'woo_inst_settings_field' ); ?>
<input type="hidden" name="woo_inst_tn" value="<?php echo isset($_GET['t']) ? esc_attr($_GET['t']) : '0'; ?>" />







<?php


    $current_page = isset($_REQUEST['woo_inst_current_page']) && $_REQUEST['woo_inst_current_page'] ? $_REQUEST['woo_inst_current_page'] : 1;



	$products_data = woo_inst_get_products($current_page);

	$products = $products_data->products;
    $max_num_pages = $products_data->max_num_pages;


	
	if(!empty($products)){
?>
<div class="alert alert-primary my-3"><?php _e('Enable/Disable Course Products', 'woo-installments'); ?></div>


<?php woo_inst_pagination_html($max_num_pages, $current_page) ?>


<table border="0">
<thead>
<th></th>
<th><?php _e('Product', 'woo-installments'); ?></th>
<th><?php _e('Price', 'woo-installments'); ?></th>
<th></th>
<?php //if($woo_inst_pro){ ?>
<th title="<?php echo $woo_inst_pro_msg; ?>" class="premium-col"><?php _e('Packages', 'woo-installments'); ?> <small>(<?php _e('On/Off', 'woo-installments'); ?>)</small></th>
<th title="<?php echo $woo_inst_pro_msg; ?>" class="premium-col"><?php _e('Full Course Buy', 'woo-installments'); ?> <small>(<?php _e('On/Off', 'woo-installments'); ?>)</small></th>
<?php //} ?>
</thead>
<tbody id="woo_inst_products_body">
<?php

woo_inst_products_table_body($products);
?>
</tbody>
</table>
<input type="hidden" name="woo_inst_settings[woo_inst_products][]" value="0" />
<input type="hidden" name="woo_inst_pro_settings[package][]" value="0" />
<input type="hidden" name="woo_inst_pro_settings[full][]" value="0" />
<p class="submit"><input type="submit" value="<?php _e('Save Changes', 'woo-installments'); ?>" class="button button-primary" id="submit" name="submit"></p>
<?php    
	}else{
?>		
<div class="alert alert-primary my-3"><?php echo __('WooCommerce Products not found.', 'woo-installments'); ?> <a href="<?php echo admin_url('edit.php?post_type=product'); ?>" target="_blank"><?php echo __('Click here', 'woo-installments'); ?></a> <?php _e('to add WooCommerce products.', 'woo-installments'); ?></div>

<div class="alert alert-primary my-3"><?php echo __('Add WooCommerce Products with a price more than', 'woo-installments').' '.$woo_inst_currency.'1.'; ?></div>

<div style="clear:both">
<a href="<?php echo $example = $woo_inst_url.'images/screenshot-2.png'; ?>" target="_blank">
<img src="<?php echo $example; ?>" style=" width:auto" />
</a>
</div>

<?php 
	} 
?>




</form>


<form class="nav-tab-content hide" action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="post">
<?php wp_nonce_field( 'woo_inst_ptypes_action', 'woo_inst_ptypes_field' ); ?>
<input type="hidden" name="woo_inst_tn" value="<?php echo isset($_GET['t']) ? esc_attr($_GET['t']) : '0'; ?>" />
<div class="alert alert-primary my-3">
    <?php _e('Select from the following post types to enable courses.', 'woo-installments'); ?>
    <div class="mt-2">
        <strong><?php _e('Note', 'woo-installments'); ?>:</strong> <?php _e('Post & Page are enabled by default. If you will uncheck all, still post & page will work.', 'woo-installments'); ?> <?php _e('So at least select one.', 'woo-installments'); ?>
    </div>

</div>
<?php if(count($woo_inst_ptypes)==1): ?>
<div style="clear:both">
<a href="<?php echo $example = $woo_inst_url.'images/screenshot-4.png'; ?>" target="_blank">
<img src="<?php echo $example; ?>" style="height:200px; width:auto" />
</a>
</div>
<?php endif; //pree($woo_inst_ptypes); ?>
<?php
$post_types = get_post_types();
if(!empty($post_types)){
	//pree($woo_inst_ptypes);
?>
<ul class="woo_inst_ptypes">
<input type="hidden" value="0" name="woo_inst_ptypes[types][]" />
<?php	
	foreach($post_types as $key=>$val){

	    $args = array(
	            'numberposts' => -1,
	            'post_type' => $val,
	            'post_status' => 'publish',
        );

	    $posts = get_posts($args);


?>
<li>
<label for="<?php echo $key; ?>" style=" display: inline-block; max-width: 200px; min-width: 200px; text-align: justify">
    <input style="margin-right: 10px; margin-bottom: 0" id="<?php echo $key; ?>" type="checkbox" name="woo_inst_ptypes[types][]" <?php checked(in_array($key, $woo_inst_ptypes)); ?> value="<?php echo $key; ?>" /><?php echo woo_inst_humanize($val); ?>
</label>

    <?php if(!empty($posts) && in_array($key, $woo_inst_ptypes)){ ?>

    &nbsp;&nbsp;&nbsp;
    <span class="woo_inst_expand_collapse">

        <a class="woo_inst_expand"> (<?php _e('Expand', 'woo-installments') ?>)</a>
        <a class="woo_inst_collapse" style="display: none"> (<?php _e('Collapse', 'woo-installments') ?>)</a>

    </span>

    <?php } ?>


    <ul class="woo_inst_ptype_group" style="display: none">

        <?php

            if(!empty($posts) && in_array($key, $woo_inst_ptypes)){

                foreach ($posts as $post){

                    $is_demo_product = get_post_meta($post->ID, '_woo_inst_demo_content', true);


                    ?>

                            <li class="woo_inst_ptype_group_item">
                                <div class="row" >
                                    <div class="col-md-6">
                                        <span class="woo_inst_item_title"><a href="<?php echo $is_demo_product ? get_permalink($post->ID): get_edit_post_link($post->ID); ?>" target="_blank"><?php echo $post->post_title ?></a> </span>
                                        <?php if($is_demo_product): ?><span class="badge badge-primary woo_inst_demo_badge" title="<?php _e('This is a demo post installed for demonstration.', 'woo-installments'); ?>"><?php _e('Demo Content', 'woo-installments'); ?></span><?php endif; ?>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="woo_inst_selection_pkg_placeholder"><?php _e('Associate / Link Packages', 'woo-installments') ?></div>
                                        <?php woo_inst_get_pkg_enabled_select($post->ID) ?>
                                    </div>
                                </div>
                            </li>

                    <?php
                }

            }

        ?>

    </ul>

</li>
<?php
	}
?>
</ul>
<?php	
}
?>
<p class="submit"><input type="submit" value="<?php _e('Save Changes', 'woo-installments'); ?>" class="button button-primary" id="submit" name="submit"></p>
</form>

    <?php if(function_exists('woo_inst_course_playlist_content')){ woo_inst_course_playlist_content(); } ?>

<form class="nav-tab-content hide">
<input type="hidden" name="woo_inst_tn" value="<?php echo isset($_GET['t']) ? esc_attr($_GET['t']) : '0'; ?>" />

    <div class="alert alert-primary mt-3 mb-5">
        <?php _e('For more details and queries please', 'woo-installments'); ?> <a href="http://demo.androidbubble.com/contact/" target="_blank"><?php _e('contact us', 'woo-installments') ?></a>
        <?php _e("or visit", 'woo-installments'); ?> <a href="https://wordpress.org/support/plugin/woo-installments" target="_blank"><?php _e('support forum', 'woo-installments') ?>.</a>
    </div>

    <div class="row mt-5">
        <div class="col-md-12 text-center">

            <div class="h2 mb-5 text-primary"><?php _e('How to use?', 'woo-installments'); ?></div>

            <ul class="woo_inst_tutorials">
                <li>
                    <iframe style="width:716px; height:500px;" src="https://www.youtube.com/embed/2UNIlUjccvs" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                </li>
            </ul>

        </div>
    </div>



</form>

<?php

	$woo_inst_msgs_updated = get_option('woo_inst_msgs', array());
	$woo_inst_msgs_updated = (is_array($woo_inst_msgs_updated)?$woo_inst_msgs_updated:$woo_inst_msgs);
	//woo_inst_pree($woo_inst_msgs);

?>

<form class="nav-tab-content hide woo_inst_optional_wrapper" action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="post">

<?php wp_nonce_field( 'woo_inst_msgs_action', 'woo_inst_msgs_field' ); ?>
<input type="hidden" name="woo_inst_tn" value="<?php echo isset($_GET['t']) ? esc_attr($_GET['t']) : '0'; ?>" />
<div class="alert alert-primary my-3"><?php _e('Update text notifications', 'woo-installments'); ?></div>

<ul>
	<li>
        <label for="woo_inst_msgs_line_1" class="woo_inst_msg_label">
            <i class="fa fa-info-circle"></i>
            <?php _e('Help text at single product pages about packages', 'woo-installments'); ?>
        </label>
        <textarea id="woo_inst_msgs_line_1" name="woo_inst_msgs[line-1]" placeholder="<?php echo $line_1 = $woo_inst_msgs['line-1']; ?>"><?php echo (isset($woo_inst_msgs_updated['line-1'])?$woo_inst_msgs_updated['line-1']:$line_1); ?></textarea>
    </li>

    <li>
        <label for="woo_inst_msgs_line_2" class="woo_inst_msg_label">
            <i class="fa fa-info-circle"></i>
            <?php _e('Info text about packages at single product page above the package selection', 'woo-installments'); ?>
        </label>
        <textarea id="woo_inst_msgs_line_2" name="woo_inst_msgs[line-2]" placeholder="<?php echo $line_2 = $woo_inst_msgs['line-2']; ?>"><?php echo (isset($woo_inst_msgs_updated['line-2'])?$woo_inst_msgs_updated['line-2']:$line_2); ?></textarea>
    </li>

    <li>
        <label for="woo_inst_msgs_line_3" class="woo_inst_msg_label">
            <i class="fa fa-info-circle"></i>
            <?php _e('Help text for purchasing full product', 'woo-installments'); ?>
        </label>
        <textarea id="woo_inst_msgs_line_3" name="woo_inst_msgs[line-3]" placeholder="<?php echo $line_3 = $woo_inst_msgs['line-3']; ?>"><?php echo (isset($woo_inst_msgs_updated['line-3'])?$woo_inst_msgs_updated['line-3']:$line_3); ?></textarea>
    </li>

    <li>
        <label for="woo_inst_msgs_line_4" class="woo_inst_msg_label">
            <i class="fa fa-info-circle"></i>
            <?php _e('Button text at single product page for purchasing packages', 'woo-installments'); ?>
        </label>
        <textarea id="woo_inst_msgs_line_4" name="woo_inst_msgs[line-4]" placeholder="<?php echo $line_4 = $woo_inst_msgs['line-4']; ?>"><?php echo (isset($woo_inst_msgs_updated['line-4'])?$woo_inst_msgs_updated['line-4']:$line_4); ?></textarea>
    </li>

    <li>
        <label for="woo_inst_msgs_line_5" class="woo_inst_msg_label">
            <i class="fa fa-info-circle"></i>
            <?php _e('Heading at my account page for courses you are offering:', 'woo-installments'); ?>
        </label>
        <textarea id="woo_inst_msgs_line_5" name="woo_inst_msgs[line-5]" placeholder="<?php echo $line_5 = $woo_inst_msgs['line-5']; ?>"><?php echo (isset($woo_inst_msgs_updated['line-5'])?$woo_inst_msgs_updated['line-5']:$line_5); ?></textarea>
    </li>

    <li>
        <label for="woo_inst_msgs_line_6" class="woo_inst_msg_label">
            <i class="fa fa-info-circle"></i>
            <?php _e('Heading at my account page for courses you have enrolled:', 'woo-installments'); ?>
        </label>
        <textarea id="woo_inst_msgs_line_6" name="woo_inst_msgs[line-6]" placeholder="<?php echo $line_6 = $woo_inst_msgs['line-6']; ?>"><?php echo (isset($woo_inst_msgs_updated['line-6'])?$woo_inst_msgs_updated['line-6']:$line_6); ?></textarea>
    </li>

</ul>

<p class="submit"><input type="submit" value="<?php _e('Save Changes', 'woo-installments'); ?>" class="button button-primary" id="submit" name="submit"></p>

    <div class="woo_inst_optional">
    <h3><?php _e("Optional","woo-installments"); ?></h3>
    
        <fieldset>
    
            <ul>
    
          
    
            <li <?php echo(get_option('woo_inst_billing_off', 0)?'class="selected"':''); ?>>
    
            <input class="woo_inst_checkout_options" id="woo_inst_billing_off" name="woo_inst_billing_off" type="checkbox" value="1" <?php echo(get_option('woo_inst_billing_off', 0)?'checked="checked"':''); ?> /><label for="woo_inst_billing_off"><?php _e("Billing Details","woo-installments"); ?> <strong><?php _e("On","woo-installments"); ?></strong>/<strong><?php _e("Off","woo-installments"); ?></strong></label>
    
            </li>
    
            <li <?php echo(get_option('woo_inst_shipping_off', 0)?'class="selected"':''); ?>>
    
            <input class="woo_inst_checkout_options" id="woo_inst_shipping_off" name="woo_inst_shipping_off" type="checkbox" value="1" <?php echo(get_option('woo_inst_shipping_off', 0)?'checked="checked"':''); ?> /><label for="woo_inst_shipping_off"><?php _e("Shipping Details","woo-installments"); ?> <strong><?php _e("On","woo-installments"); ?></strong>/<strong><?php _e("Off","woo-installments"); ?></strong></label>
    
            </li>
    
            <li <?php echo(get_option('woo_inst_order_comments_off', 0)?'class="selected"':''); ?>>
    
            <input class="woo_inst_checkout_options" id="woo_inst_order_comments_off" name="woo_inst_order_comments_off" type="checkbox" value="1" <?php echo(get_option('woo_inst_order_comments_off', 0)?'checked="checked"':''); ?> /><label for="woo_inst_order_comments_off"><?php _e("Order Comments","woo-installments"); ?> <strong><?php _e("On","woo-installments"); ?></strong>/<strong><?php _e("Off","woo-installments"); ?></strong></label>
    
            </li>
    
            <li style="text-align:center; padding:100px 0 0 0;">
            

<a href="https://wordpress.org/support/plugin/woo-installments/" target="_blank">
<img src="<?php echo $woo_inst_url.'images/icon-256x256.png'; ?>" style="height:200px; width:auto" />
</a>
            
            </li>                   
    
            </ul>
    
        </fieldset>
    
    
    </div>
</form>

   <?php do_action('woo_inst_add_new_tab_form') ?>


<div class="nav-tab-content hide">

    <div class="row">
        <div class="col-md-12">

            <div class="alert alert-primary mt-3 mb-2" style="width: 100%; line-height: 1.5;">
                <?php _e('It is safe to try demo content for a quick start.', 'woo-installments'); ?>
            </div>

        </div>
    </div>

    <div class="row">
        <div class="col-md-12">

            <div class="alert alert-success mt-3 mb-5 woo_inst_demo_alert hide" style=" width: 100%; line-height: 1.5;">

            </div>

        </div>
    </div>

    <div class="row mt-3 justify-content-center woo_inst_add_demo_row">
        <div class="col-md-3">

            <div class="mb-3">
                <button class="btn btn-primary btn-block" id="woo_inst_add_demo_content">
                    <i class="fa fa-plus-circle"></i>
                    <?php _e('Install Demo Content', 'woo-installments') ?>
                    <div class="spinner-border text-light w_spinner d-none" role="status">
                        <span class="sr-only"><?php _e('Loading', 'woo-installments') ?>...</span>
                    </div>
                </button>
            </div>


            <div class="mb-3">

                <button class="btn btn-danger btn-block" id="woo_inst_remove_demo_content">
                    <i class="fa fa-trash"></i>
                    <?php _e('Uninstall Demo Content', 'woo-installments') ?>
                    <div class="spinner-border text-light w_spinner d-none" role="status">
                        <span class="sr-only"><?php _e('Loading', 'woo-installments') ?>...</span>
                    </div>

                </button>

            </div>


        </div>

    </div>

    <div class="row">
        <div class="col-md-12">

            <div class="" action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="post">
                <?php


                $current_page = isset($_REQUEST['woo_inst_current_page']) && $_REQUEST['woo_inst_current_page'] ? $_REQUEST['woo_inst_current_page'] : 1;



                $demo_products = woo_inst_get_demo_products();


                if(!empty($demo_products)){
                    ?>


                    <table border="0">
                        <thead>
                        <th></th>
                        <th><?php _e('Product', 'woo-installments'); ?></th>
                        <th><?php _e('Price', 'woo-installments'); ?></th>
                        <th></th>
                        <?php //if($woo_inst_pro){ ?>
                        <th title="<?php echo $woo_inst_pro_msg; ?>" class="premium-col"><?php _e('Packages', 'woo-installments'); ?> <small>(<?php _e('On/Off', 'woo-installments'); ?>)</small></th>
                        <th title="<?php echo $woo_inst_pro_msg; ?>" class="premium-col"><?php _e('Full Course Buy', 'woo-installments'); ?> <small>(<?php _e('On/Off', 'woo-installments'); ?>)</small></th>
                        <?php //} ?>
                        </thead>
                        <tbody id="woo_inst_products_body">
                        <?php

                            woo_inst_demo_products_table_body($demo_products);

                        ?>
                        </tbody>
                    </table>

                    <?php
                }else{

                }
                ?>




            </div>

        </div>
    </div>

</div>

</div>

<script type="text/javascript" language="javascript">
jQuery(document).ready(function($) {
	
	<?php if(isset($_POST['woo_inst_tn'])): ?>

	

		$('.nav-tab-wrapper .nav-tab:nth-child(<?php echo $_POST['woo_inst_tn']+1; ?>)').click();

	

	<?php endif; ?>	
	
});	
</script>

<style type="text/css">
<?php echo implode('', $css_arr); ?>
	#wpfooter{
		display:none;
	}
<?php if(!$woo_inst_pro): ?>

	#adminmenu li.current a.current {
		font-size: 12px !important;
		font-weight: bold !important;
		padding: 6px 0px 6px 12px !important;
	}
	#adminmenu li.current a.current,
	#adminmenu li.current a.current span:hover{
		color:#9B5C8F;
	}
	#adminmenu li.current a.current:hover,
	#adminmenu li.current a.current span{
		color:#fff;
	}	
<?php endif; ?>
	.woocommerce-message,
	.update-nag{
		display:none;
	}
</style>
