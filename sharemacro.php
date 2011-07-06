<?php
/*
Plugin Name: ShareMacro: Share/Bookmark/Email Button
Plugin URI: http://www.sharemacro.com/webmasters
Description: Empower your users with automated sharing to multiple services. Optionally become an affiliate and make money at the same time. http://www.sharemacro.com/webmasters
Version: 1.0.3
Author: ShareMacro
Author URI: http://www.sharemacro.com/webmasters
*/

// Pre-2.6 compatibility
if ( ! defined('WP_CONTENT_URL') )
	define( 'WP_CONTENT_URL', get_option('siteurl') . '/wp-content');
if ( ! defined( 'WP_PLUGIN_URL' ) )
	define( 'WP_PLUGIN_URL', WP_CONTENT_URL . '/plugins' );
	
$SM_SHARE_SAVE_plugin_basename = plugin_basename(dirname(__FILE__));
$SM_SHARE_SAVE_plugin_url_path = WP_PLUGIN_URL.'/'.$SM_SHARE_SAVE_plugin_basename; // /wp-content/plugins/share-macro

// Fix SSL
if (is_ssl())
	$SM_SHARE_SAVE_plugin_url_path = str_replace('http:', 'https:', $SM_SHARE_SAVE_plugin_url_path);

$SM_SHARE_SAVE_options = get_option('sharemacro_options');

function SM_SHARE_SAVE_init() {
	global $SM_SHARE_SAVE_plugin_url_path,
		$SM_SHARE_SAVE_plugin_basename, 
		$SM_SHARE_SAVE_options;
	
	if (get_option('SM_SHARE_SAVE_button')) {
	    SM_SHARE_SAVE_migrate_options();
	    $SM_SHARE_SAVE_options = get_option('sharemacro_options');
	}
  
	
	load_plugin_textdomain('share-macro',
		$SM_SHARE_SAVE_plugin_url_path.'/languages',
		$SM_SHARE_SAVE_plugin_basename.'/languages');
		
	if ($SM_SHARE_SAVE_options['display_in_excerpts'] != '-1') {
		// Excerpts use strip_tags() for the_content, so cancel if Excerpt and append to the_excerpt instead
		add_filter('get_the_excerpt', 'SM_SHARE_SAVE_remove_from_content', 9);
		add_filter('the_excerpt', 'SM_SHARE_SAVE_add_to_content', 98);
	}
}
add_filter('init', 'SM_SHARE_SAVE_init');

function SM_SHARE_SAVE_link_vars($linkname = FALSE, $linkurl = FALSE) {
	global $post;
	
	$linkname		= ($linkname) ? $linkname : get_the_title($post->ID);
	$linkname_enc	= rawurlencode( $linkname );
	$linkurl		= ($linkurl) ? $linkurl : get_permalink($post->ID);
	$linkurl_enc	= rawurlencode( $linkurl );	
	
	return compact( 'linkname', 'linkname_enc', 'linkurl', 'linkurl_enc' );
}

//include_once(dirname(__FILE__).'/' . 'services.php');

// Combine SHAREMACRO_SHARE_SAVE_ICONS and SHAREMACRO_SHARE_SAVE_BUTTON
function SHAREMACRO_SHARE_SAVE_KIT( $args = false ) {
	
	if ( ! isset($args['html_container_open']))
		$args['html_container_open'] = "<div class=\"sm_kit sharemacro_list\">";
	if ( ! isset($args['html_container_close']))
		$args['html_container_close'] = "</div>";
	// Close container element in SHAREMACRO_SHARE_SAVE_BUTTON, not prematurely in SHAREMACRO_SHARE_SAVE_ICONS
	$html_container_close = $args['html_container_close']; // Cache for _BUTTON
	unset($args['html_container_close']); // Avoid passing to SHAREMACRO_SHARE_SAVE_ICONS since set in _BUTTON
				
	if ( ! isset($args['html_wrap_open']))
		$args['html_wrap_open'] = "";
	if ( ! isset($args['html_wrap_close']))
		$args['html_wrap_close'] = "";
	
   // $kit_html = SHAREMACRO_SHARE_SAVE_ICONS($args);
	
	$args['html_container_close'] = $html_container_close; // Re-set because unset above for _ICONS
	unset($args['html_container_open']);  // Avoid passing to SHAREMACRO_SHARE_SAVE_BUTTON since set in _ICONS
    
	$kit_html .= SHAREMACRO_SHARE_SAVE_BUTTON($args);
	
	if($args['output_later'])
		return $kit_html;
	else
		echo $kit_html;
}


function SHAREMACRO_SHARE_SAVE_BUTTON( $args = array() ) {
	
	// $args array = output_later, html_container_open, html_container_close, html_wrap_open, html_wrap_close, linkname, linkurl

	global $SM_SHARE_SAVE_plugin_url_path;
	
	$linkname = (isset($args['linkname'])) ? $args['linkname'] : FALSE;
	$linkurl = (isset($args['linkurl'])) ? $args['linkurl'] : FALSE;

	$args = array_merge($args, SM_SHARE_SAVE_link_vars($linkname, $linkurl)); // linkname_enc, etc.
	
	$defaults = array(
		'linkname' => '',
		'linkurl' => '',
		'linkname_enc' => '',
		'linkurl_enc' => '',
		'output_later' => FALSE,
		'html_container_open' => "<div class=\"sm_kit sharemacro_list\">",
		'html_container_close' => '',
		'html_wrap_open' => '',
		'html_wrap_close' => '',
	);
	
	$args = wp_parse_args( $args, $defaults );
	extract( $args );
	
	/* ShareMacro button */
	
	$is_feed = is_feed();
	$button_target = '';
	$button_href_querystring = ($is_feed) ? '#url=' . $linkurl_enc . '&amp;title=' . $linkname_enc  : '';
	$options = get_option('sharemacro_options');
	//echo $options['button'];exit;
	if( ! $options['button'] ) {
		$button_fname	= 'sharemacro_button_icons.png';
		$button_width	= ' width="177"';
		$button_height	= ' height="23"';
		$button_src		= $SM_SHARE_SAVE_plugin_url_path.'/'.$button_fname;
	} else if( $options['button'] == 'sharemacro_icon_16.gif|16|16' ) {
		$button_attrs	= explode( '|', $options['button'] );
		$button_fname	= $button_attrs[0];
		$button_width	= ' width="'.$button_attrs[1].'"';
		$button_height	= ' height="'.$button_attrs[2].'"';
		$button_src		= $SM_SHARE_SAVE_plugin_url_path.'/'.$button_fname;
		$button_text	= '<span class="sharemacro-logo "><strong>share</strong>macro</span>';
	}else if( $options['button'] == 'sharemacro_icon_16_icon.gif|16|16' ) {
		$button_attrs	= explode( '|', $options['button'] );
		$button_fname	= $button_attrs[0];
		$button_width	= ' width="'.$button_attrs[1].'"';
		$button_height	= ' height="'.$button_attrs[2].'"';
		$button_src		= $SM_SHARE_SAVE_plugin_url_path.'/'.$button_fname;
		$button_text	= '<span class="sharemacro-logo "><strong>share</strong>macro</span> <img src="'.$SM_SHARE_SAVE_plugin_url_path.'/icons/gmail.png" alt="gmail"/> <img src="'.$SM_SHARE_SAVE_plugin_url_path.'/icons/facebook.png" alt="facebook"/> <img src="'. $SM_SHARE_SAVE_plugin_url_path.'/icons/twitter.png" alt="twitter"/> ';
		
	} 
	else if( $options['button'] == 'CUSTOM' ) {
		$button_src		= $options['button_custom'];
		$button_width	= '';
		$button_height	= '';
	}
	else if( $options['button'] == 'TEXT' ) {
		$button_text	= '<span class="sharemacro-logo "><strong>share</strong>macro</span>';
	}
	else if( $options['button'] == 'TEXT-ONLY' ) {
		$button_text	= stripslashes($options['button_text']);
	}
	else {
		$button_attrs	= explode( '|', $options['button'] );
		$button_fname	= $button_attrs[0];
		$button_width	= ' width="'.$button_attrs[1].'"';
		$button_height	= ' height="'.$button_attrs[2].'"';
		$button_src		= $SM_SHARE_SAVE_plugin_url_path.'/'.$button_fname;
		$button_text	= '';
	}
	
	if( $button_fname == 'favicon.png' || $button_fname == 'share_16_16.png' ) {
		if( ! $is_feed) {
			$style_bg	= 'background:url('.$SM_SHARE_SAVE_plugin_url_path.'/'.$button_fname.') no-repeat scroll 9px 0px !important;';
			$style		= ' style="'.$style_bg.'padding:0 0 0 30px;display:inline-block;height:16px;line-height:16px;vertical-align:middle"'; // padding-left:30+9 (9=other icons padding)
		}
	}
	
	if( $button_text && (!$button_fname || $button_fname == 'favicon.png' || $button_fname == 'share_16_16.png') ) {
		$button			= $button_text;
	} else {
		$style = '';
		$button			= '<img src="'.$button_src.'"'.$button_width.$button_height.' alt="Share"/> '.$button_text;
	}
	$button_link="http://www.sharemacro.com/share";
	if($options['make_money'] =='1') {
		$button_link="http://".$options['cbid'].".sharemacro.hop.clickbank.net/?cblandingpage=share\" rel=\"nofollow";
	}
	
	$button_html = $html_container_open . $html_wrap_open . '<span class="sharemacro-widget" ><a class="sharemacro-link" href="'.$button_link.'"'
		. $style . $button_target
		. '>' . $button . '</a></span>' . $html_wrap_close . $html_container_close;
	
	// If not a feed
	if( ! $is_feed ) {
		$http_or_https = (is_ssl()) ? 'https' : 'http';
	
		global $SM_SHARE_SAVE_external_script_called;
		if ( ! $SM_SHARE_SAVE_external_script_called ) {
			// Use local cache?
			$cache = ($options['cache']=='1') ? TRUE : FALSE;
			$upload_dir = wp_upload_dir();
			$static_server = ($cache) ? $upload_dir['baseurl'] . '/sharemacro' : '';
			$postooltip="bottom center";
			if ($options['position_1'] !="") {
				$postooltip=$options['position_1'].' '. $options['position_2'];
			}
			$colorSelect=$options['color'];
			if($colorSelect=="" )
			{
				$colorSelect='light';
			}
			$strBid='';
			if($options['make_money'] =='1') {
				$strBid='var sharemacro_cbid = "'.$options['cbid'].'";';
			}
			// Enternal script call + initial JS + set-once variables
			$initial_js = $strBid. '
var sharemacro_preset = "ui-tooltip-'.$colorSelect.' ui-tooltip-rounded sharemacro-tooltip";
var sharemacro_tooltip_position = "'.$postooltip.'";' . "\n";
			$additional_js = '';
			$external_script_call = '</script>';
			$SM_SHARE_SAVE_external_script_called = true;
		}
		else {
			$external_script_call = "\n//--></script>";
			$initial_js = '';
		}
			
		$button_javascript = "\n" . '<script type="text/javascript">' .$initial_js ."\n"
						. $external_script_call . "\n\n";
		
		//add_filter('wp_head', returnStr($button_javascript));
		$button_html .= $button_javascript;
	
	}
	
	if ( $output_later )
		return $button_html;
	else
		echo $button_html;
}


if (!function_exists('SM_wp_footer_check')) {
	function SM_wp_footer_check()
	{
		// If footer.php exists in the current theme, scan for "wp_footer"
		$file = get_template_directory() . '/footer.php';
		if( is_file($file) ) {
			$search_string = "wp_footer";
			$file_lines = @file($file);
			
			foreach($file_lines as $line) {
				$searchCount = substr_count($line, $search_string);
				if($searchCount > 0) {
					return true;
					break;
				}
			}
			
			// wp_footer() not found:
			echo "<div class=\"plugin-update\">" . __("Your theme needs to be fixed. To fix your theme, use the <a href=\"theme-editor.php\">Theme Editor</a> to insert <code>&lt;?php wp_footer(); ?&gt;</code> just before the <code>&lt;/body&gt;</code> line of your theme's <code>footer.php</code> file.") . "</div>";
		}
	}  
}

function SM_SHARE_SAVE_auto_placement($title) {
	global $SM_SHARE_SAVE_auto_placement_ready;
	$SM_SHARE_SAVE_auto_placement_ready = true;
	
	return $title;
}


/**
 * Remove the_content filter and add it for next time 
 */
function SM_SHARE_SAVE_remove_from_content($content) {
	remove_filter('the_content', 'SM_SHARE_SAVE_add_to_content', 98);
	add_filter('the_content', 'SM_SHARE_SAVE_add_to_content_next_time', 98);
	
	return $content;
}

/**
 * Apply the_content filter "next time"
 */
function SM_SHARE_SAVE_add_to_content_next_time($content) {
	add_filter('the_content', 'SM_SHARE_SAVE_add_to_content', 98);
	
	return $content;
}


function SM_SHARE_SAVE_add_to_content($content) {
	global $SM_SHARE_SAVE_auto_placement_ready;
	
	$is_feed = is_feed();
	$options = get_option('sharemacro_options');
  
	if( ! $SM_SHARE_SAVE_auto_placement_ready)
		return $content;
		
	if (get_post_status(get_the_ID()) == 'private')
		return $content;
	
	if ( 
		( 
			// Tags
			// <!--sharesave--> tag
			strpos($content, '<!--sharesave-->')===false || 
			// <!--nosharesave--> tag
			strpos($content, '<!--nosharesave-->')!==false
		) &&
		(
			// Posts
			// All posts
			( ! is_page() && $options['display_in_posts']=='-1' ) ||
			// Front page posts		
			( is_home() && $options['display_in_posts_on_front_page']=='-1' ) ||
			// Category posts (same as Front page option)
			( is_category() && $options['display_in_posts_on_front_page']=='-1' ) ||
			// Tag Cloud posts (same as Front page option) - WP version 2.3+ only
			( function_exists('is_tag') && is_tag() && $options['display_in_posts_on_front_page']=='-1' ) ||
			// Date-based archives posts (same as Front page option)
			( is_date() && $options['display_in_posts_on_front_page']=='-1' ) ||
			// Author posts (same as Front page option)	
			( is_author() && $options['display_in_posts_on_front_page']=='-1' ) ||
			// Search results posts (same as Front page option)
			( is_search() && $options['display_in_posts_on_front_page']=='-1' ) || 
			// Posts in feed
			( $is_feed && ($options['display_in_feed']=='-1' ) ||
			
			// Pages
			// Individual pages
			( is_page() && $options['display_in_pages']=='-1' ) ||
			// <!--nosharesave-->						
			( (strpos($content, '<!--nosharesave-->')!==false) )
		)
		)
	)	
		return $content;
	
	$kit_args = array(
		"output_later" => true,
		"html_container_open" => ($is_feed) ? "" : "<div class=\"sm_kit sharemacro_list\">",
		"html_container_close" => ($is_feed) ? "" : "</div>",
		"html_wrap_open" => ($is_feed) ? "" : "",
		"html_wrap_close" => ($is_feed) ? " " : "",
	);
	
	if ( ! $is_feed ) {
		$container_wrap_open = '<div class="sharemacro_share_save_container">';
		$container_wrap_close = '</div>';
	} else {
		$container_wrap_open = '<p>';
		$container_wrap_close = '</p>';
	}
	
	$options['position'] = isset($options['position']) ? $options['position'] : 'bottom';
	
	if ($options['position'] == 'both' || $options['position'] == 'top') {
		// Prepend to content
		$content = $container_wrap_open.SHAREMACRO_SHARE_SAVE_KIT($kit_args) . $container_wrap_close . $content;
	}
	if ( $options['position'] == 'bottom' || $options['position'] == 'both') {
		// Append to content
		$content .= $container_wrap_open.SHAREMACRO_SHARE_SAVE_KIT($kit_args) . $container_wrap_close;
	}
	
	return $content;
}

// Only automatically output button code after the_title has been called - to avoid premature calling from misc. the_content filters (especially meta description)
add_filter('the_title', 'SM_SHARE_SAVE_auto_placement', 9);
add_filter('the_content', 'SM_SHARE_SAVE_add_to_content', 98);


function SM_SHARE_SAVE_button_css_IE() {
/* IE support for opacity: */ ?>
<!--[if IE]>
<style type="text/css">
.sharemacro_list a img{filter:alpha(opacity=70)}
.sharemacro_list a:hover img,.sharemacro_list a.sharemacro_share_save img{filter:alpha(opacity=100)}
</style>
<![endif]-->
<?php
}

function SM_SHARE_SAVE_stylesheet() {
	global $SM_SHARE_SAVE_options, $SM_SHARE_SAVE_plugin_url_path;
	
	//wp_enqueue_style('SM_SHARE_SAVE', 'http://cdn.sharemacro.com/js/qtip/jquery.qtip.sharemacro.css', false, '1.3');
	//add_filter('wp_head', 'SM_SHARE_SAVE_button_css_IE');
	// Use stylesheet?
	/*
	if ($SM_SHARE_SAVE_options['inline_css'] != '-1' && ! is_admin()) {
		//wp_enqueue_style('SM_SHARE_SAVE', $SM_SHARE_SAVE_plugin_url_path . '/sharemacro.min.css', false, '1.3');
		
		wp_enqueue_style('SM_SHARE_SAVE2', $SM_SHARE_SAVE_plugin_url_path . '/jquery.qtip.sharemacro.css', false, '1.3');
		// Conditional inline CSS stylesheet for IE
		
	}
	*/
}

//add_action('wp_print_styles', 'SM_SHARE_SAVE_stylesheet');

if (!is_admin()) {
add_action('init', 'mfDevLoadScripts');

}


function mfDevLoadScripts() {
	
    wp_register_script( 'jquery.widget', 'http://cdn.sharemacro.com/js/qtip/jquery.widget.min.js');
    wp_enqueue_script( 'jquery.widget' );
}


/*****************************
		OPTIONS
******************************/


function SM_SHARE_SAVE_migrate_options() {
	
	$options = array(
		//'inline_css' => '1',  Modernly used for "Use CSS Stylesheet?"
		'cache' => '-1',
		'display_in_posts_on_front_page' => '1',
		'display_in_posts' => '1',
		'display_in_pages' => '1',
		'display_in_feed' => '1',
		'show_title' => '-1',
		'onclick' => '-1',
		'button' => 'sharemacro_button.png|106|23',
		'button_custom' => '',
		'additional_js_variables' => '',
		'button_text' => 'Share/Bookmark',
		'display_in_excerpts' => '1',
		'active_services' => Array(),
	);
	
	$namespace = 'SM_SHARE_SAVE_';
  
	foreach ($options as $option_name => $option_value) {
		$old_option_name = $namespace . $option_name;  
		$old_option_value = get_option($old_option_name);
		
		if($old_option_value === FALSE) {
			// Default value
		    $options[$option_name] = $option_value;
		} else {
			// Old value
		    $options[$option_name] = $old_option_value;
		}
		
		delete_option($old_option_name);
	}
	
	update_option('sharemacro_options', $options);
	
	$deprecated_options = array(
		'button_opens_new_window',
		'hide_embeds',
	);
	
	foreach ($deprecated_options as $option_name) {
		delete_option($namespace . $option_name);
	}
	
}

function SM_SHARE_SAVE_options_page() {

	global $SM_SHARE_SAVE_plugin_url_path,
		$SM_SHARE_SAVE_services;
	
	// Require admin privs
	if ( ! current_user_can('manage_options') )
		return false;
	
  $new_options = array();
  
  $namespace = 'SM_SHARE_SAVE_';
  
	// Make available services extensible via plugins, themes (functions.php), etc.
	$SM_SHARE_SAVE_services = apply_filters('SM_SHARE_SAVE_services', $SM_SHARE_SAVE_services);

    if (isset($_POST['Submit'])) {
		
		// Nonce verification 
		check_admin_referer('share-macro-update-options');

		$new_options['position'] = ($_POST['SM_SHARE_SAVE_position']) ? @$_POST['SM_SHARE_SAVE_position'] : 'bottom';
		$new_options['display_in_posts_on_front_page'] = (@$_POST['SM_SHARE_SAVE_display_in_posts_on_front_page']=='1') ? '1':'-1';
		$new_options['display_in_excerpts'] = (@$_POST['SM_SHARE_SAVE_display_in_excerpts']=='1') ? '1':'-1';
		$new_options['display_in_posts'] = (@$_POST['SM_SHARE_SAVE_display_in_posts']=='1') ? '1':'-1';
		$new_options['display_in_pages'] = (@$_POST['SM_SHARE_SAVE_display_in_pages']=='1') ? '1':'-1';
		$new_options['display_in_feed'] = (@$_POST['SM_SHARE_SAVE_display_in_feed']=='1') ? '1':'-1';
		//$new_options['show_title'] = (@$_POST['SM_SHARE_SAVE_show_title']=='1') ? '1':'-1';
		
		$new_options['button'] = @$_POST['SM_SHARE_SAVE_button'];
		$new_options['color'] = @$_POST['SM_SHARE_SAVE_color'];
		$new_options['button_custom'] = @$_POST['SM_SHARE_SAVE_button_custom'];
		$new_options['position_1'] = @$_POST['sharemacro_position_1'];
		$new_options['position_2'] = @$_POST['sharemacro_position_2'];
		$new_options['make_money'] = (@$_POST['SM_SHARE_SAVE_make_money']=='1') ? '1':'-1';	
		$new_options['cbid'] = trim(@$_POST['SM_SHARE_SAVE_cbid']);
		
		//$new_options['inline_css'] = (@$_POST['SM_SHARE_SAVE_inline_css']=='1') ? '1':'-1';
		$new_options['cache'] = (@$_POST['SM_SHARE_SAVE_cache']=='1') ? '1':'-1';
		
		
		
		// Store desired text if 16 x 16px buttons or TEXT is chosen:
		if( $new_options['button'] == 'favicon.png|16|16' )
			$new_options['button_text'] = $_POST['SM_SHARE_SAVE_button_favicon_16_16_text'];
		elseif( $new_options['button'] == 'share_16_16.png|16|16' )
			$new_options['button_text'] = $_POST['SM_SHARE_SAVE_button_share_16_16_text'];
		else
			$new_options['button_text'] = ( trim($_POST['SM_SHARE_SAVE_button_text']) != '' ) ? $_POST['SM_SHARE_SAVE_button_text'] : __('Share/Bookmark','share-macro');
			
		// Store chosen individual services to make active
		$active_services = Array();
		if ( ! isset($_POST['SM_SHARE_SAVE_active_services']))
			$_POST['SM_SHARE_SAVE_active_services'] = Array();
		foreach ( $_POST['SM_SHARE_SAVE_active_services'] as $dummy=>$sitename )
			$active_services[] = substr($sitename, 7);
		$new_options['active_services'] = $active_services;
		
		update_option('sharemacro_options', $new_options);
    
		?>
    	<div class="updated fade"><p><strong><?php _e('Settings saved.'); ?></strong></p></div>
		<?php
		
    } else if (isset($_POST['Reset'])) {
    	// Nonce verification 
		  check_admin_referer('share-macro-update-options');
		  
		  delete_option('sharemacro_options');
    }

    $options = get_option('sharemacro_options');
	
	function position_in_content($options, $option_box = FALSE) {
		
		if ( ! isset($options['position'])) {
			$options['position'] = 'bottom';
		}
		
		$positions = array(
			'bottom' => array(
				'selected' => ('bottom' == $options['position']) ? ' selected="selected"' : '',
				'string' => __('bottom', 'share-macro')
			),
			'top' => array(
				'selected' => ('top' == $options['position']) ? ' selected="selected"' : '',
				'string' => __('top', 'share-macro')
			),
			'both' => array(
				'selected' => ('both' == $options['position']) ? ' selected="selected"' : '',
				'string' => __('top &amp; bottom', 'share-macro')
			)
		);
		
		if ($option_box) {
			$html = '</label>';
			$html .= '<label>'; // Label needed to prevent checkmark toggle on SELECT click 
		    $html .= '<select name="SM_SHARE_SAVE_position">';
		    $html .= '<option value="bottom"' . $positions['bottom']['selected'] . '>' . $positions['bottom']['string'] . '</option>';
		    $html .= '<option value="top"' . $positions['top']['selected'] . '>' . $positions['top']['string'] . '</option>';
		    $html .= '<option value="both"' . $positions['both']['selected'] . '>' . $positions['both']['string'] . '</option>';
			$html .= '</select>';
		    
		    return $html;
		} else {
			$html = '<span class="SM_SHARE_SAVE_position">';
			$html .= $positions[$options['position']]['string'];
			$html .= '</span>';
			
			return $html;
		}
	}
	
    ?>
    
    <?php SM_wp_footer_check(); ?>
    
    <div class="wrap">
	
	<div id="icon-options-general" class="icon32"></div>
	
	<h2><?php _e( 'ShareMacro: Share/Save ', 'share-macro' ) . _e( 'Settings' ); ?></h2>

    <form method="post" action="">
    
	<?php wp_nonce_field('share-macro-update-options'); ?>
    
        <table class="form-table">
          	<tr valign="top">
            <th scope="row"><?php _e("Select Button", "share-macro"); ?></th>
            <td><fieldset>
            	
                <label>
                	<input name="SM_SHARE_SAVE_button" value="sharemacro_button.png|106|23" type="radio"<?php if($options['button']=='sharemacro_button.png|106|23') echo ' checked="checked"'; ?>
                    	style="margin:9px 0;vertical-align:middle">
                    <img src="<?php echo $SM_SHARE_SAVE_plugin_url_path.'/sharemacro_button.png'; ?>" width="106" height="23" border="0" style="padding:9px;vertical-align:middle"
                    	onclick="this.parentNode.firstChild.checked=true"/>
                </label><br>
                <label>
                	<input name="SM_SHARE_SAVE_button" value="sharemacro_button_icons.png|177|23" type="radio"<?php if( !$options['button'] || $options['button']=='sharemacro_button_icons.png|177|23' ) echo ' checked="checked"'; ?>
                    	style="margin:9px 0;vertical-align:middle">
                    <img src="<?php echo $SM_SHARE_SAVE_plugin_url_path.'/sharemacro_button_icons.png'; ?>" width="177" height="23" border="0" style="padding:9px;vertical-align:middle"
                    	onclick="this.parentNode.firstChild.checked=true"/>
                </label><br>
                <label>
                	<input name="SM_SHARE_SAVE_button" value="sharemacro_icon_16.gif|16|16" type="radio"<?php if($options['button']=='sharemacro_icon_16.gif|16|16') echo ' checked="checked"'; ?>
                    	style="margin:9px 0;vertical-align:middle">
                    <img src="<?php echo $SM_SHARE_SAVE_plugin_url_path.'/sharemacro_icon_16.gif'; ?>" width="16" height="16" border="0" style="padding:9px;vertical-align:middle" onclick="this.parentNode.firstChild.checked=true"/>
					<span class="sharemacro-logo "><strong><?php _e("share"); ?></strong><?php _e('macro');?></span>
						
				</label><br>
				<label>
                	<input name="SM_SHARE_SAVE_button" value="sharemacro_icon_16_icon.gif|16|16" type="radio"<?php if($options['button']=='sharemacro_icon_16_icon.gif|16|16') echo ' checked="checked"'; ?>
                    	style="margin:9px 0;vertical-align:middle">
                    <img src="<?php echo $SM_SHARE_SAVE_plugin_url_path.'/sharemacro_icon_16_icon.gif'; ?>" width="16" height="16" border="0" style="padding:9px;vertical-align:middle" onclick="this.parentNode.firstChild.checked=true"/>
					<span class="sharemacro-logo "><strong><?php _e("share"); ?></strong><?php _e('macro');?></span>  <img src="<?php echo $SM_SHARE_SAVE_plugin_url_path.'/icons/gmail.png'; ?>" alt="gmail"/> <img src="<?php echo $SM_SHARE_SAVE_plugin_url_path.'/icons/facebook.png'; ?>" alt="facebook"/> <img src="<?php echo $SM_SHARE_SAVE_plugin_url_path.'/icons/twitter.png'; ?>" alt="twitter"/> 
						
				</label><br>
				<label>
                	<input name="SM_SHARE_SAVE_button" value="sharemacro_icon_16.gif|16|16|1" type="radio"<?php if( !$options['button'] || $options['button']=='sharemacro_icon_16.gif|16|16|1' ) echo ' checked="checked"'; ?>
                    	style="margin:9px 0;vertical-align:middle">
                    <img src="<?php echo $SM_SHARE_SAVE_plugin_url_path.'/sharemacro_icon_16.gif'; ?>" width="16" height="16" border="0" style="padding:9px;vertical-align:middle"
                    	onclick="this.parentNode.firstChild.checked=true"/>
                </label><br>
				<label>
                	<input name="SM_SHARE_SAVE_button" value="sharemacro_icon_32.gif|32|32" type="radio"<?php if( !$options['button'] || $options['button']=='sharemacro_icon_32.gif|32|32' ) echo ' checked="checked"'; ?>
                    	style="margin:9px 0;vertical-align:middle">
                    <img src="<?php echo $SM_SHARE_SAVE_plugin_url_path.'/sharemacro_icon_32.gif'; ?>" width="32" height="32" border="0" style="padding:9px;vertical-align:middle"
                    	onclick="this.parentNode.firstChild.checked=true"/>
                </label><br>
				<label>
                	<input name="SM_SHARE_SAVE_button" value="TEXT" type="radio"<?php if( !$options['button'] || $options['button']=='TEXT' ) echo ' checked="checked"'; ?> style="margin:9px 0;vertical-align:middle" >
					<span class="sharemacro-logo "><strong><?php _e("share"); ?></strong><?php _e('macro');?></span>                   
                </label><br/>
				<label>
                	<input name="SM_SHARE_SAVE_button" value="CUSTOM" type="radio"<?php if( $options['button'] == 'CUSTOM' ) echo ' checked="checked"'; ?>
                    	style="margin:9px 0;vertical-align:middle">
					<span style="margin:0 9px;vertical-align:middle"><?php _e("Image URL"); ?>:</span>
				</label>
  				<input name="SM_SHARE_SAVE_button_custom" type="text" class="code" size="50" onclick="e=document.getElementsByName('SM_SHARE_SAVE_button');e[e.length-2].checked=true" style="vertical-align:middle"
                	value="<?php echo $options['button_custom']; ?>" /><br>
				<label>
                	<input name="SM_SHARE_SAVE_button" value="TEXT-ONLY" type="radio"<?php if( $options['button'] == 'TEXT-ONLY' ) echo ' checked="checked"'; ?>
                    	style="margin:9px 0;vertical-align:middle">
					<span style="margin:0 9px;vertical-align:middle"><?php _e("Text only"); ?>:</span>
				</label>
                <input name="SM_SHARE_SAVE_button_text" type="text" class="code" size="50" onclick="e=document.getElementsByName('SM_SHARE_SAVE_button');e[e.length-1].checked=true" style="vertical-align:middle;width:150px"
                	value="<?php echo ( trim($options['button_text']) != '' ) ? stripslashes($options['button_text']) : __('Share/Bookmark','share-macro'); ?>" />
                
            </fieldset></td>
            </tr>
            <tr valign="top">
            <th scope="row"><?php _e('Placement', 'share-macro'); ?></th>
            <td><fieldset>
                <label>
                	<input id="SM_SHARE_SAVE_display_in_posts" name="SM_SHARE_SAVE_display_in_posts" type="checkbox"<?php 
						if($options['display_in_posts']!='-1') echo ' checked="checked"'; ?> value="1"/>
                	<?php printf(__('Display at the %s of posts', 'share-macro'), position_in_content($options, TRUE)); ?> <strong>*</strong>
                </label><br/>
                <label>
                	&nbsp; &nbsp; &nbsp; <input class="SM_SHARE_SAVE_child_of_display_in_posts" name="SM_SHARE_SAVE_display_in_excerpts" type="checkbox"<?php 
						if($options['display_in_excerpts']!='-1') echo ' checked="checked"';
						if($options['display_in_posts']=='-1') echo ' disabled="disabled"';
						?> value="1"/>
					<?php printf(__('Display at the %s of post excerpts', 'share-macro'), position_in_content($options)); ?>
				</label><br/>
				<label>
                	&nbsp; &nbsp; &nbsp; <input class="SM_SHARE_SAVE_child_of_display_in_posts" name="SM_SHARE_SAVE_display_in_posts_on_front_page" type="checkbox"<?php 
						if($options['display_in_posts_on_front_page']!='-1') echo ' checked="checked"';
						if($options['display_in_posts']=='-1') echo ' disabled="disabled"';
						?> value="1"/>
                    <?php printf(__('Display at the %s of posts on the front page', 'share-macro'), position_in_content($options)); ?>
				</label><br/>
                
				<label>
                	&nbsp; &nbsp; &nbsp; <input class="SM_SHARE_SAVE_child_of_display_in_posts" name="SM_SHARE_SAVE_display_in_feed" type="checkbox"<?php 
						if($options['display_in_feed']!='-1') echo ' checked="checked"'; 
						if($options['display_in_posts']=='-1') echo ' disabled="disabled"';
						?> value="1"/>
					<?php printf(__('Display at the %s of posts in the feed', 'share-macro'), position_in_content($options)); ?>
				</label><br/>
                <label>
                	<input name="SM_SHARE_SAVE_display_in_pages" type="checkbox"<?php if($options['display_in_pages']!='-1') echo ' checked="checked"'; ?> value="1"/>
                    <?php printf(__('Display at the %s of pages', 'share-macro'), position_in_content($options, TRUE)); ?>
				</label>
                <br/><br/>
                <div class="setting-description">
                	<strong>*</strong> <?php _e("If unchecked, be sure to place the following code in <a href=\"theme-editor.php\">your template pages</a> (within <code>index.php</code>, <code>single.php</code>, and/or <code>page.php</code>)", "share-macro"); ?>: <span id="sharemacro_show_template_button_code" class="button-secondary">&#187;</span>
                    <div id="sharemacro_template_button_code">
                      <code>&lt;?php if( function_exists('SHAREMACRO_SHARE_SAVE_KIT') ) { SHAREMACRO_SHARE_SAVE_KIT(); } ?&gt;</code>
                    </div>
                    <noscript><code>&lt;?php if( function_exists('SHAREMACRO_SHARE_SAVE_KIT') ) { SHAREMACRO_SHARE_SAVE_KIT(); } ?&gt;</code></noscript>
                </div>
            </fieldset></td>
            </tr>
			
			
			<tr valign="top">
            <th scope="row"><?php _e('Select Design', 'share-macro'); ?></th>
            <td><fieldset>
                <label>
					<select name="SM_SHARE_SAVE_color" id="sharemacro_presets">
					  <option <?php if($options['color']=='light') echo ' selected'; ?> value="light">Light</option>
					  <option <?php if($options['color']=='dark') echo ' selected'; ?> value="dark">Dark</option>
					  <option <?php if($options['color']=='red') echo ' selected'; ?> value="red">Red</option>
					  <option <?php if($options['color']=='green') echo ' selected'; ?> value="green">Green</option>
					  <option <?php if($options['color']=='blue') echo ' selected'; ?> value="blue">Blue</option>
					  
					</select>
                	
                </label>
            </fieldset></td>
            </tr>
			
			<tr valign="top">
            <th scope="row"><?php _e('Select Tooltip Position relative to button', 'share-macro'); ?></th>
            <td><fieldset>
                <label>
					<select name="sharemacro_position_1" id="sharemacro_position_1">
					  <option <?php if($options['position_1']=='center') echo ' selected'; ?> value="center">Center</option>
					  <option <?php if($options['position1']=='left') echo ' selected'; ?> value="left">Left</option>
					  
					  <option <?php if($options['position_1']=='right') echo ' selected'; ?> value="right">Right</option>
					  <option <?php if($options['position_1']=='top') echo ' selected'; ?> value="top">Top</option>
					  <option <?php if($options['position_1']=='bottom') echo ' selected'; ?> value="bottom">Bottom</option>
					  
					</select>
					
                	
                </label>
				<label>
				<select name="sharemacro_position_2" id="sharemacro_position_2">
					<option <?php if($options['position_2']=='bottom') echo ' selected'; ?> value="bottom">Bottom</option>
					  <option <?php if($options['position_2']=='left') echo ' selected'; ?> value="left">Left</option>
					  <option <?php if($options['position_2']=='center') echo ' selected'; ?> value="center">Center</option>
					  <option <?php if($options['position_2']=='right') echo ' selected'; ?> value="right">Right</option>
					  <option <?php if($options['position_2']=='top') echo ' selected'; ?> value="top">Top</option>
					  
					  
					</select>
				</label>
				
            </fieldset></td>
            </tr>
			
			<tr valign="top">
            <th scope="row"><?php _e('Make Money?', 'share-macro'); ?></th>
            <td><fieldset>
            	<label for="SM_SHARE_SAVE_make_money">
					<input name="SM_SHARE_SAVE_make_money" id="SM_SHARE_SAVE_make_money"
                    	type="checkbox"<?php if($options['make_money']!='-1') echo ' checked="checked"'; ?> value="1"/>
            	</label><br/>
				
				<label class="lb_cbid">
					Enter your Clickbank ID: <input type="text" name="SM_SHARE_SAVE_cbid" value="<?php echo $options['cbid'];?>"/><br/>
					(Don't have one yet? Get it here <a rel="nofollow" href="http://advisory.reseller.hop.clickbank.net/" target="_blank">here</a>. By clicking on this link you will be taken to the Clickbank vendor / affiliate signup page. Vendor and affiliate accounts are the same at Clickbank.)
				</label>
            </fieldset></td>
            </tr>
			
			<tr valign="top">
            <th scope="row"><?php _e('Advanced Options', 'share-macro'); ?></th>
            <td><fieldset>
				<!--
            	<label for="SM_SHARE_SAVE_inline_css">
					<input name="SM_SHARE_SAVE_inline_css" id="SM_SHARE_SAVE_inline_css"
                    	type="checkbox"<?php //if($options['inline_css']!='-1') echo ' checked="checked"'; ?> value="1"/>
            	<?php //_e('Use CSS stylesheet', 'share-macro'); ?>
				</label><br/>
				-->
				<label for="SM_SHARE_SAVE_cache">
					<input name="SM_SHARE_SAVE_cache" id="SM_SHARE_SAVE_cache" 
                    	type="checkbox"<?php if($options['cache']=='1') echo ' checked="checked"'; ?> value="1"/>
            	<?php _e('Cache ShareMacro locally with daily cache updates', 'share-macro'); ?> <strong>**</strong>
				</label>
				<br/><br/>
                <div class="setting-description">
					<strong>**</strong> <?php _e("Only consider for sites with frequently returning visitors. Since many visitors will have ShareMacro cached in their browser already, serving ShareMacro locally from your site will be slower for those visitors.  Be sure to set far future cache/expires headers for image files in your <code>uploads/sharemacro</code> directory.", "share-macro"); ?>
				</div>
				
            </fieldset></td>
            </tr>
        </table>
        
        <p class="submit">
            <input class="button-primary" type="submit" name="Submit" value="<?php _e('Save Changes', 'share-macro' ) ?>" />
            <input id="SM_SHARE_SAVE_reset_options" type="submit" name="Reset" onclick="return confirm('<?php _e('Are you sure you want to delete all ShareMacro options?', 'share-macro' ) ?>')" value="<?php _e('Reset', 'share-macro' ) ?>" />
        </p>
    
    </form>
    
 

<?php
 
}

// Admin page header
function SM_SHARE_SAVE_admin_head() {
	if (isset($_GET['page']) && $_GET['page'] == 'sharemacro.php') {
      
		$options = get_option('sharemacro_options');
  
	?>
	<script type="text/javascript"><!--
	jQuery(document).ready(function(){
	
		// Toggle child options of 'Make money'
		if (jQuery('#SM_SHARE_SAVE_make_money').is(':checked'))
				jQuery('.lb_cbid').css('display','block');
			else 
				jQuery('.lb_cbid').css('display','none');
		
		jQuery('#SM_SHARE_SAVE_make_money').bind('change click', function(e){
			if (jQuery(this).is(':checked'))
				jQuery('.lb_cbid').css('display','block');
			else 
				jQuery('.lb_cbid').css('display','none');
		});
		
		// Toggle child options of 'Display in posts'
		jQuery('#SM_SHARE_SAVE_display_in_posts').bind('change click', function(e){
			if (jQuery(this).is(':checked'))
				jQuery('.SM_SHARE_SAVE_child_of_display_in_posts').attr('checked', true).attr('disabled', false);
			else 
				jQuery('.SM_SHARE_SAVE_child_of_display_in_posts').attr('checked', false).attr('disabled', true);
		});
		
		// Update button position labels/values universally in Placement section 
		jQuery('select[name="SM_SHARE_SAVE_position"]').bind('change click', function(e){
			var $this = jQuery(this);
			jQuery('select[name="SM_SHARE_SAVE_position"]').not($this).val($this.val());
			
			jQuery('.SM_SHARE_SAVE_position').html($this.find('option:selected').html());
		});
	
		var to_input = function(this_sortable){
			// Clear any previous services stored as hidden inputs
			jQuery('input[name="SM_SHARE_SAVE_active_services[]"]').remove();
			
			var services_array = jQuery(this_sortable).sortable('toArray'),
				services_size = services_array.length;
			if(services_size<1) return;
			
			for(var i=0;i<services_size;i++){
				if(services_array[i]!='') // Exclude dummy icon
					jQuery('form:first').append('<input name="SM_SHARE_SAVE_active_services[]" type="hidden" value="'+services_array[i]+'"/>');
			}
		};
	
		jQuery('#sharemacro_services_sortable').sortable({
			forcePlaceholderSize: true,
			items: 'li:not(#sharemacro_show_services, .dummy)',
			placeholder: 'ui-sortable-placeholder',
			opacity: .6,
			tolerance: 'pointer',
			update: function(){to_input(this)}
		});
		
		// Service click = move to sortable list
		var moveToSortableList = function(){
			if( jQuery('#sharemacro_services_sortable li').not('.dummy').length==0 )
				jQuery('#sharemacro_services_sortable').find('.dummy').hide();
			
			jQuery(this).toggleClass('sharemacro_selected')
			.unbind('click', moveToSortableList)
			.bind('click', moveToSelectableList)
			.clone()
			.html( jQuery(this).find('img').clone().attr('alt', jQuery(this).attr('title')) )
			.hide()
			.insertBefore('#sharemacro_services_sortable .dummy')
			.fadeIn('fast');
			
			jQuery(this).attr( 'id', 'old_'+jQuery(this).attr('id') );
		};
		
		// Service click again = move back to selectable list
		var moveToSelectableList = function(){
			jQuery(this).toggleClass('sharemacro_selected')
			.unbind('click', moveToSelectableList)
			.bind('click', moveToSortableList);
	
			jQuery( '#'+jQuery(this).attr('id').substr(4).replace(/\./, '\\.') )
			.hide('fast', function(){
				jQuery(this).remove();
			});
			
			
			if( jQuery('#sharemacro_services_sortable li').not('.dummy').length==1 )
				jQuery('#sharemacro_services_sortable').find('.dummy').show();
			
			jQuery(this).attr('id', jQuery(this).attr('id').substr(4));
		};
		
		// Service click = move to sortable list
		jQuery('#sharemacro_services_selectable li').bind('click', moveToSortableList);
        
        // Form submit = get sortable list
        jQuery('form').submit(function(){to_input('#sharemacro_services_sortable')});
        
        // Auto-select active services
        <?php
		$admin_services_saved = is_array($_POST['SM_SHARE_SAVE_active_services']) || isset($_POST['Submit']);
		$active_services = ( $admin_services_saved )
			? $_POST['SM_SHARE_SAVE_active_services'] : $options['active_services'];
		if( ! $active_services )
			$active_services = Array();
		$active_services_last = end($active_services);
		if($admin_services_saved)
			$active_services_last = substr($active_services_last, 7); // Remove sm_wp_
		$active_services_quoted = '';
		foreach ($active_services as $service) {
			if($admin_services_saved)
				$service = substr($service, 7); // Remove sm_wp_
			$active_services_quoted .= '"'.$service.'"';
			if ( $service != $active_services_last )
				$active_services_quoted .= ',';
		}
		?>
        var services = [<?php echo $active_services_quoted; ?>];
        jQuery.each(services, function(i, val){
        	jQuery('#sm_wp_'+val).click();
		});
		
		// Add/Remove Services
		jQuery('#sharemacro_services_sortable .dummy:first').after('<li id="sharemacro_show_services"><?php _e('Add/Remove Services', 'share-macro'); ?> &#187;</li>');
		jQuery('#sharemacro_show_services').click(function(e){
			jQuery('#sharemacro_services_selectable, #sharemacro_services_info').slideDown('fast');
			jQuery(this).fadeOut('fast');
		});
		jQuery('#sharemacro_show_template_button_code').click(function(e){
			jQuery('#sharemacro_template_button_code').slideDown('fast');
			jQuery(this).fadeOut('fast');
		});
		jQuery('#sharemacro_show_css_code').click(function(e){
			jQuery('#sharemacro_css_code').slideDown('fast');
			jQuery(this).fadeOut('fast');
		});
	});
	--></script>

	<style type="text/css">
	.ui-sortable-placeholder{background-color:transparent;border:1px dashed #AAA !important;}
	.sharemacro_admin_list{list-style:none;padding:0;margin:0;}
	.sharemacro_admin_list li{-webkit-border-radius:9px;-moz-border-radius:9px;border-radius:9px;}
	
	#sharemacro_services_selectable{clear:left;display:none;}
	#sharemacro_services_selectable li{cursor:crosshair;float:left;width:150px;font-size:11px;margin:0;padding:6px;border:1px solid transparent;_border-color:#FAFAFA/*IE6*/;overflow:hidden;}
	<?php // white-space:nowrap could go above, but then webkit does not wrap floats if parent has no width set; wrapping in <span> instead (below) ?>
	#sharemacro_services_selectable li span{white-space:nowrap;}
	#sharemacro_services_selectable li:hover, #sharemacro_services_selectable li.sharemacro_selected{border:1px solid #AAA;background-color:#FFF;}
	#sharemacro_services_selectable li.sharemacro_selected:hover{border-color:#F00;}
	#sharemacro_services_selectable li:active{border:1px solid #000;}
  #sharemacro_services_selectable img{margin:0 4px;width:16px;height:16px;border:0;vertical-align:middle;}
	#sharemacro_services_selectable .sharemacro_special_service{padding:3px 6px;}
	#sharemacro_services_selectable .sharemacro_special_service img{width:auto;height:20px;}
	
	#sharemacro_services_sortable li, #sharemacro_services_sortable li.dummy:hover{cursor:move;float:left;padding:9px;border:1px solid transparent;_border-color:#FAFAFA/*IE6*/;}
	#sharemacro_services_sortable li:hover{border:1px solid #AAA;background-color:#FFF;}
	#sharemacro_services_sortable li.dummy, #sharemacro_services_sortable li.dummy:hover{cursor:auto;background-color:transparent;}
	#sharemacro_services_sortable img{width:16px;height:16px;border:0;vertical-align:middle;}
	#sharemacro_services_sortable .sharemacro_special_service img{width:auto;height:20px;}
	
	li#sharemacro_show_services{border:1px solid #DFDFDF;background-color:#FFF;cursor:pointer;}
	li#sharemacro_show_services:hover{border:1px solid #AAA;}
	#sharemacro_services_info{clear:left;display:none;}
	
	#sharemacro_template_button_code, #sharemacro_css_code{display:none;}
	
	#SM_SHARE_SAVE_reset_options{color:red;margin-left: 15px;}
	
	span.sharemacro-logo {
    font-family: "Century Gothic";
    font-size: 14px;
	color: #0000FF;
	}
  </style>
<?php

	}
}

add_filter('admin_head', 'SM_SHARE_SAVE_admin_head');

function SM_SHARE_SAVE_add_menu_link() {
		
	if( current_user_can('manage_options') ) {
		$page = add_options_page(
			'ShareMacro: '. __("Share/Save", "share-macro"). " " . __("Settings")
			, __("ShareMacro Buttons", "share-macro")
			, 'activate_plugins' 
			, basename(__FILE__)
			, 'SM_SHARE_SAVE_options_page'
		);
		
		/* Using registered $page handle to hook script load, to only load in ShareMacro admin */
        add_filter('admin_print_scripts-' . $page, 'SM_SHARE_SAVE_scripts');
	}
}

function SM_SHARE_SAVE_scripts() {
	wp_enqueue_script('jquery-ui-sortable');
}

add_filter('admin_menu', 'SM_SHARE_SAVE_add_menu_link');

// Place in Option List on Settings > Plugins page 
function SM_SHARE_SAVE_actlinks( $links, $file ){
	//Static so we don't call plugin_basename on every plugin row.
	static $this_plugin;
	if ( ! $this_plugin ) $this_plugin = plugin_basename(__FILE__);
	
	if ( $file == $this_plugin ){
		$settings_link = '<a href="options-general.php?page=sharemacro.php">' . __('Settings') . '</a>';
		array_unshift( $links, $settings_link ); // before other links
	}
	return $links;
}



add_filter("plugin_action_links", 'SM_SHARE_SAVE_actlinks', 10, 2);


?>