<?php if ( ! defined( 'ABSPATH' ) ) exit; 
//THIS FILE IS JUST A SAMPLE TO INTEGRATE WITH A 3RD PARTY STANDALONE APPLICATION HAVING DIFFERENT DATABASE FOR COURSE MANAGEMENT OR LMS

if(!defined('WOO_INST_EXCEPTIONS')){
	_e('Sorry, you cannot get exceptions.', 'woo-installments');
	
}elseif(defined('WOO_INST_EXCEPTIONS') && WOO_INST_EXCEPTIONS!='01010'){
	_e('Sorry, you cannot get exceptions.', 'woo-installments');	
}else{
	
}

global $woo_inst_api_public;


function woo_inst_api_public($url, $params = array()){
	
	$url = $url.'wp-admin/admin-ajax.php';
	
	//pree($url);
	$ch = curl_init();
	$data = array(
					'action' => 'woo_inst_api_public',
					'woo_inst_api_public' => date('Ym').substr($_SERVER['HTTP_HOST'], -6, 6)
			);	
	$data = $data + $params;					
	
	//pree($data);
	//pree($url);
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
	$output = curl_exec($ch);		
	//pree($output);
	$ret = json_decode($output);

	

	
	return $ret;
	
}




	$params = array(
					'user_id'=>$_SESSION['USER']->idnumber,
					'user_email'=>$_SESSION['USER']->email
			);
	
	//pree($params);exit;
	//pre($_SESSION['USER']->idnumber);
	//pre($_SERVER);exit;
	$module_check = (explode('/', $_SERVER['REQUEST_URI']));
	if(!empty($module_check)){
		$module = 0;
		foreach($module_check as $val){
			if($module)
			continue;
			
			$val = strtolower($val);
			if(substr($val, 0, strlen('module'))=='module'){
				$module = str_replace('module', '', $val);
			}
		}
		$scoid = isset($_GET['scoid'])?$_GET['scoid']:0;	
		$post_id = 0;
		if($module==0){
			//pree($_GET);			
			switch($scoid){
				case 63:
					$module = 1;
				break;
				case 64:
					$module = 2;
				break;
				case 65:
					$module = 3;
				break;
				case 66:
					$module = 4;
				break;
				case 67:
					$module = 5;
				break;
				case 68:
					$module = 6;
				break;
				case 69:
					$module = 7;
				break;
	
			}				
		}		
		switch($module){
			case 1:
				$post_id = 2807;
			break;
			case 2:
				$post_id = 2808;
			break;
			case 3:
				$post_id = 2809;
			break;
			case 4:
				$post_id = 2810;
			break;
			case 5:
				$post_id = 2811;
			break;
			case 6:
				$post_id = 2812;
			break;
			case 7:
				$post_id = 2813;
			break;

		}

		$params['scoid'] = $scoid;
		$params['module'] = $module;
		$params['post_id'] = $post_id;
		//pree($url);
		//pree($params);exit;
		$woo_inst_api_public = woo_inst_api_public($url, $params);
		
		//pree($woo_inst_api_public);exit;
		
		if($woo_inst_api_public->the_content!=''){
			
		
?>
<style type="text/css">
	.wi_exceptions_hanlder{
		text-align:center;
		font-family:Arial, Helvetica, sans-serif;
	}
	.wi_exceptions_hanlder .logo{
		margin: 0 0 40px 0;
		
	}	
	.wi_exceptions_hanlder .logo img{

		height:100px;
	}
	.wi_exceptions_hanlder ul{
		list-style:none;
	}
	.wi_exceptions_hanlder ul li{
		font-size:18px;
		line-height:22px;
		margin-bottom:10px;
	}
	.wi_exceptions_hanlder ul li a{
		color:#15A24B;
		text-decoration:none;
		
	}
	.wi_exceptions_hanlder ul li a:hover{
		color:#42B06B;
		
	}

</style>
<div class="wi_exceptions_hanlder">
<div class="logo"><a target="_blank" href="<?php echo $url; ?>"><img src="<?php echo $url.'wp-content/uploads/2016/02/logogenezing3.png'; ?>" /></a></div>
<?php		
		//pree($woo_inst_api_public->debug);
		echo ($woo_inst_api_public->the_content);
		
?>
</div>
<?php		
		exit;
		}
	}
	

