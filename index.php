<?php if ( ! defined( 'ABSPATH' ) ) exit; 
/*
Plugin Name: Online Course Content Selling Tool
Plugin URI: http://androidbubble.com/blog/wordpress/plugins/woo-installments
Description: Value your intellectual property, content, videos, what you do, what you write, what you think, share it and make it sellable.
Version: 1.3.9
Author: Fahad Mahmood 
Author URI: https://www.androidbubbles.com
Text Domain: woo-installments
Domain Path: /languages/
License: GPL2


Woo Installments is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 2 of the License, or any later version. Woo Installments is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details. You should have received a copy of the GNU General Public License along with Woo Installments. If not, see http://www.gnu.org/licenses/gpl-2.0.html.
*/ 
	
        
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
	global $woo_inst_currency, $woo_inst_settings, $woo_inst_variable_product, $woo_inst_activated, $woo_inst_pro_settings, $woo_inst_pro;
	global $woo_inst_premium_link, $woo_inst_data, $woo_inst_dir, $woo_inst_all_plugins, $woo_inst_plugins_activated, $woo_inst_url, $woo_inst_msgs, $woo_inst_pro_msg, $woo_inst_msgs_updated;
	
	$woo_inst_pro_settings = array('package'=>array(), 'full'=>array());
	$woo_inst_msgs = array(
		
		'line-1' => __('Alternatively you can get course in parts.', 'woo-installments'),
		'line-2' => __('Following tiers are available', 'woo-installments'),
		'line-3' => __('Click add to cart for complete purchase', 'woo-installments'),
		'line-4' => __('Proceed with Packages', 'woo-installments'),
		'line-5' => __('My Courses', 'woo-installments'),
		'line-6' => __('Enrolled Courses', 'woo-installments'),

	);

    $woo_inst_msgs_updated = get_option('woo_inst_msgs', array());
    $woo_inst_msgs_updated = (is_array($woo_inst_msgs_updated) ?$woo_inst_msgs_updated:$woo_inst_msgs);
	
	$woo_inst_activated = false;
	$woo_inst_url = plugin_dir_url( __FILE__ );
	$woo_inst_all_plugins = get_plugins();
	$woo_inst_plugins_activated = apply_filters( 'active_plugins', get_option( 'active_plugins' ));
	
	if(is_multisite()){			
		
		$active_sitewide_plugins = get_site_option( 'active_sitewide_plugins' );
		
		$woo_inst_plugins_activated = array_keys($active_sitewide_plugins);		
		
	}
	
	$woo_inst_wc_installed = array_key_exists('woocommerce/woocommerce.php', $woo_inst_all_plugins);
	$woo_inst_wc_activated = in_array('woocommerce/woocommerce.php', $woo_inst_plugins_activated);
	
		
	if($woo_inst_wc_installed && $woo_inst_wc_activated){
		$woo_inst_activated = true;
	}

	if(!$woo_inst_activated && isset($_GET['page']) && $_GET['page']=='woo_installments'){
		$wc_url = ($woo_inst_wc_installed?admin_url('plugins.php?s=woocommerce&plugin_status=all'):admin_url('plugin-install.php?s=woocommerce&tab=search&type=term'));
		echo '<a href="'.$wc_url.'" target="_blank">WooCommerce</a> '.__('is required to be installed and activated.', 'woo-installments');
		//print_r($woo_inst_plugins_activated);
		
	}else{
	
		$woo_inst_dir = plugin_dir_path( __FILE__ );
		$woo_inst_pro = file_exists($woo_inst_dir.'pro/wi_extended.php');
		
		$woo_inst_pro_msg = $woo_inst_pro?'':__('This feature is available in premium version.', 'woo-installments');
		
		$woo_inst_premium_link = 'https://shop.androidbubbles.com/product/woo-installments-pro';
		$woo_inst_data = get_plugin_data(__FILE__);
		
		
		include('inc/functions.php');
			
		register_activation_hook(__FILE__, 'woo_inst_start');
	
		//KBD END WILL REMOVE .DAT FILES	
	
		register_deactivation_hook(__FILE__, 'woo_inst_end' );
	
	
	
	
		add_action( 'wp_enqueue_scripts', 'woo_inst_register_scripts', 100 );
		
		if(is_admin()){
			add_action( 'admin_menu', 'woo_inst_menu' );	
			$plugin = plugin_basename(__FILE__); 
			add_filter("plugin_action_links_$plugin", 'woo_inst_plugin_links' );	
			
			add_action( 'admin_enqueue_scripts', 'woo_inst_admin_scripts', 99 );
			
			if($woo_inst_pro)
			add_action( 'admin_enqueue_scripts', 'woo_inst_pro_admin_style', 99 );
			
			if($woo_inst_pro){					
				include($woo_inst_dir.'pro/wi_extended.php');
			}
			
			
			
		}else{
			
	
			if($woo_inst_pro){					
				include($woo_inst_dir.'pro/wi_extended.php');
			}
			
		}
	
	}
		