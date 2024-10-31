<?php
/*
Plugin Name: PM Tools
Plugin URI: http://www.polarismedia.de/pm-tools/
Description: Polaris Media UG Toolbox
Version: 1.4.10
Author: Karl Kowald
Author URI: http://www.polarismedia.de/
License: GNU GPLv2
*/

class PMTools {
	function PMTools() 
	{
		global $wpdb;
		$this->base_name = plugin_basename(__FILE__);
		$this->wpdb = $wpdb;		
		if(!is_admin())
		{
			add_shortcode('subpagemenu', array($this,'subpagemenu_shortcode'));
			if($this->get_option('lissabon') == 1)
			{
				//Lissaboneffekt BEGIN
				add_action('get_header', array($this,'lissabon_init'));
				add_action('wp_head', array($this,'lissabon_head_manipulation'));
				//Lissaboneffekt END
			}
			if($this->get_option('panorama') == 1)
			{
				//Panoramaplugin Frontend BEGIN
				add_action('get_header', array($this,'panorama_plugin_init'));
				add_action('wp_head', array($this,'panorama_plugin_manipulation'));
				add_shortcode('panorama', array($this,'panorama_shortcode'));
				//Panoramaplugin Frontend END
			}
			if($this->get_option('guestbook') == 1)
			{
				//G?stebuch Frontend BEGIN
				add_filter('comment_form_defaults', array($this,'guestbook_change_title'),1);
				add_shortcode('gaestebuch', array($this,'guestbook_shortcode'));				
				//G?stebuch Frontend END	
			}
			if($this->get_option('commentspamblacklist') == 1 OR $this->get_option('guestbook') == 1)
			{
				//Comment Spam Blacklist BEGIN
				add_filter('check_comment_flood', array($this,'commentspamblacklist_blacklist'));
				//Comment Spam Blacklist END
			}
			if($this->get_option('parentlink') == 1)
			{
				//Parentlink BEGIN
				add_filter('the_content', array($this,'parentlink_contentfilter'), 1);
				//Parentlink END
			}
			if($this->get_option('hoversubmenu') == 1)
			{
				//Hoversubmenu Frontend BEGIN
				add_action('get_header', array($this,'hoversubmenu_plugin_init'));
				add_action('wp_head', array($this,'hoversubmenu_manipulation'));
				//Hoversubmenu Frontend END
			}
			if($this->get_option('fancybox') == 1)
			{
				//Fancybox Frontend BEGIN
				add_action('get_header', array($this,'fancybox_plugin_init'));
				add_action('wp_head', array($this,'fancybox_plugin_manipulation'));
				//Fancybox Frontend END
			}
			
			// qTranslate Filter für BodyTag
			if(function_exists('qtrans_getLanguage'))
			{
				add_filter('body_class', array($this,'qtrans_body_class_language'));
			}

			//Global f?r &shy; Entfernung wenn AiOSEO aktiviert
			if(class_exists('All_in_One_SEO_Pack'))
			{
				add_filter('aioseop_title_single', array($this,'aioseop_title'));
				add_filter('aioseop_home_page_title', array($this,'aioseop_title'));
				add_filter('aioseop_title_page', array($this,'aioseop_title'));
			}
				//Global f?r &shy; Entfernung
				add_filter('single_post_title',array($this,'aioseop_title'));				
				//Global f?r gallery-style
				add_action('wp_head', array($this,'gallery_style_manipulation'));
				add_filter('gallery_style', create_function('$a', 'return preg_replace("%<style type=\'text/css\'>(.*?)</style>%s", "", $a);'));
				//Global f?r Adminbar entfernen
				add_action('init', array($this,'adminbar_disable'));
		}
		else
		{
			//Ctrl + S Save Function
			add_action('admin_init', array($this, 'init_ctrl_s_function'));
			//Bugfix fuer Nordlicht
			add_action( 'admin_init',array($this,'init_admin_init'), 1);
			add_action('admin_menu',array($this,'init_admin_menu'));
			//Column ID Ausgabe BEGIN
			add_filter('manage_pages_custom_column', array($this,'atc_pccolumn'));
			add_filter('manage_pages_columns', array($this,'atc_pcolumns'),100);
			add_filter('manage_posts_custom_column', array($this,'atc_pccolumn'));
			add_filter('manage_posts_columns', array($this,'atc_pcolumns'),100);
			//Column ID Ausgabe END
			if ($this->is_current_page('plugins'))
			{
				add_action('activate_' .$this->base_name,array($this,'init_plugin_options'));
				add_action('admin_notices',array($this,'show_version_notice'));
			}
			if($this->get_option('commentspamblacklist') == 1 OR $this->get_option('guestbook') == 1)
			{
				//Comment Spam Blacklist BEGIN
				add_filter('spam_comment', array($this,'commentspamblacklist_filter'));
				//Comment Spam Blacklist END
			}
		}
	}
//Ctrl + S Save Function BEGIN
	function init_ctrl_s_function()
	{
		wp_enqueue_script('wp_save', plugins_url('pm-tools/js/wp_save.js'), array('jquery'));
	}
//Ctrl + S Save Function END
//Fix fuer Nordlicht BEGIN
	function init_admin_init()
	{
		if(preg_match('|Qt/|',$_SERVER['HTTP_USER_AGENT']) > 0)
		{
		        echo " ";
		}
		return true;	
	}
//Fix fuer Nordlicht END

//Adminbar Funktionen BEGIN
	function adminbar_disable()
	{
		if(!current_user_can('edit_posts')) {
			//Adminbar remove for registered readers
			add_filter( 'show_admin_bar', '__return_false' );
		}
	}
//Adminbar Funktionen END
//Lissabon Funktionen BEGIN
	function lissabon_init()
	{
		wp_enqueue_script('jquery-easing', plugins_url('pm-tools/js/jquery.easing.1.3.js'),array('jquery'),'1.3');	
	}
	function lissabon_head_manipulation()
	{
		if($this->get_option('lissabon_speed_main'))
		{
			$lissabon_speed_main = $this->get_option('lissabon_speed_main');
		}
		else
		{
			$lissabon_speed_main = 400;	
		}
		if($this->get_option('lissabon_speed_sub'))
		{
			$lissabon_speed_sub = $this->get_option('lissabon_speed_sub');
		}
		else
		{
			$lissabon_speed_sub = 200;	
		}
		//Based on http://tympanus.net/codrops/2010/07/16/slide-down-box-menu/
		echo "				<script type=\"text/javascript\">
            jQuery(function() {
                jQuery('".$this->get_option('lissabon_selector')."').bind('mouseenter',function(){
					var elem = jQuery(this);
					elem.find('img')
						 .stop(true)
						 .animate({
							'width':'".$this->get_option('lissabon_itemwidth')."px',
							'height':'".$this->get_option('lissabon_itemheight')."px',
							'left':'0px'
						 },".$lissabon_speed_main.",'easeOutBack')
						 .andSelf()
						 .find('.sdt_wrap')
					     .stop(true)
						 .animate({'top':(".$this->get_option('lissabon_normalgroesse')."+((".$this->get_option('lissabon_itemheight')."-elem.find('.sdt_wrap').height())/2))+'px'},".($lissabon_speed_main+100).",'easeOutBack')
						 .andSelf()
						 .find('.sdt_active')
					     .stop(true)
						 .animate({'height':'".$this->get_option('lissabon_itemheight')."px'},".($lissabon_speed_main-100).",function(){
						var sub_menu = elem.find('.sdt_box');
						if(sub_menu.length){
							var left = '".$this->get_option('lissabon_itemwidth')."px';
							if(elem.parent().children().length === elem.index()+1)
							{
								left = '-".$this->get_option('lissabon_itemwidth')."px';
							}
							sub_menu.show().animate({'left':left},".$lissabon_speed_sub.");
						}	
					});
				}).bind('mouseleave',function(){
					var elem = jQuery(this);
					var sub_menu = elem.find('.sdt_box');
					if(sub_menu.length)
					{
						sub_menu.hide().css('left','0px');
					}
					elem.find('.sdt_active')
						 .stop(true)
						 .animate({'height':'0px'},300)
						 .andSelf().find('img')
						 .stop(true)
						 .animate({
							'width':'0px',
							'height':'0px',
							'left':'85px'},400)
						 .andSelf()
						 .find('.sdt_wrap')
						 .stop(true)
						 .animate({'top':((".$this->get_option('lissabon_normalgroesse')."-elem.find('.sdt_wrap').height())/2)+'px'},500);
				});
            });
      </script>";
	}
//Lissabon Funktionen END
//Panorama Funktionen BEGIN
	function panorama_plugin_init()
	{
		global $post;
		$this->post = $post;
		$this->panorama_found = false;
		if (isset($this->post->ID))
		{
			$pos = preg_match('/\[panorama(.)*\]/', $this->post->post_content, $matches);
			//$pos = strpos($this->post->post_content,'[panorama');
			if(!$pos)
				return;
			$split1 = preg_split('/\"/', $matches[0]);
			$this->panorama_found = $split1[1];
			wp_register_script('panorama-jq', plugins_url('pm-tools/js/jquery.panorama.js'),array('jquery'),'1.0');
			wp_register_style('panorama-css', plugins_url('pm-tools/css/jquery.panorama.css'),array(),'1.0');
			wp_enqueue_script('panorama-jq');
			wp_enqueue_style('panorama-css');
		}
	}
	function panorama_plugin_manipulation()
	{
		if(!$this->panorama_found)
		{	
			return; 
		}
		echo '<script type="text/javascript">
		jQuery(document).ready(function($) {
			$("img.panorama").panorama({
	                 viewport_width: '.$this->get_option('panorama_width').',
	                 speed: '.$this->get_option('panorama_speed').'
	         });
		});
		</script>';
		return;
	}
	function panorama_shortcode($atts)
	{
		extract(shortcode_atts(array(
			'url' => '',
			'width' => 0,
			'height' => 0,
			'alt' => 'Panorama-Image',
		), $atts));
		$img_str = '<img src="'.$url.'" class="panorama" width="'.$width.'" height="'.$height.'" alt="'.$alt.'" />';
		return $img_str;
	}
//Panorama Funktionen END
//Subpagemenu Funktionen BEGIN
	function subpagemenu_shortcode($atts)
	{
		global $post;
		extract(shortcode_atts(array(
			'pid' => $post->ID,
			'parentid' => $post->post_parent
		),$atts));
		$par_set = 0;
		if($parentid != 0) 
		{
			$par_data = get_page($parentid);
			if($par_data->post_parent != 0) {
				$par_set = 2;
				$page_id = $par_data->post_parent;
				$page_id2 = $parentid;
			}
			else
			{
				$page_id = $parentid;
				$par_set = 1;
			}
		}
		if($pid != 0 && count(get_pages('child_of='.$pid)) != 0) 
		{
			if($par_set == 0)
			{
				$page_id = $pid;
				$par_set = 1;
			}
			else
			{
				$page_id2 = $pid;
				$par_set = 2;   
			}
		} 
		if($par_set == 2)
		{ 
			$content = '
			<div id="subnavi">
			<ul>
			'.wp_list_pages('title_li=&child_of='.$page_id.'&sort_column=menu_order&depth=1&echo=0').'
			</ul>
			</div>
			<div class="platzhalter">
			&nbsp;
			</div>
			<div id="subnavi2">
			<ul>
			'.wp_list_pages('title_li=&child_of='.$page_id2.'&sort_column=menu_order&depth=1&echo=0').'
			</ul>
			</div>';
		} 
		elseif($par_set == 1) 
		{ 
			$content = '
			<div id="subnavi">
			<ul>
			'.wp_list_pages('title_li=&child_of='.$page_id.'&sort_column=menu_order&depth=1&echo=0').'
			</ul>
			</div>';
		}
		return $content;
	}
//Subpagemenu Funktionen END
//Guestbook Funktionen BEGIN
	function guestbook_shortcode() {
		ob_start();
		comments_template( '', true );
		$return = ob_get_contents();
		ob_end_clean();
		return $return;
	}	
	function guestbook_change_title($defaults) {
		global $post;
		$this->post = $post;
		if(isset($this->post->ID))
		{
			$pos = preg_match('/\[gaestebuch(.)*\]/', $this->post->post_content, $matches);
			if(!$pos)
			{
				return $defaults;
			}
			$defaults['title_reply'] = htmlentities(utf8_decode($this->get_option('guestbook_title_relply'))); 
			$defaults['label_submit'] = htmlentities(utf8_decode($this->get_option('guestbook_label_submit'))); 
			$defaults['comment_notes_before'] = htmlentities(utf8_decode($this->get_option('guestbook_comment_notes_before'))); 
			$defaults['comment_notes_after'] = '';
			return $defaults;
		}
		return $defaults;
	}
//Guestbook Funktionen END
//Comment Spam Blacklist Funktionen BEGIN
	function commentspamblacklist_filter($csb_id)
	{
		// $cbs_id = CommentID
		$csb_ips = $this->wpdb->get_results("SELECT comment_author_IP FROM ".$this->wpdb->comments." WHERE comment_ID = '".$csb_id."'");
	/* (Neues System) */
		$csb_antwort = file_get_contents("http://api.polarismedia.de/wp-anti-spam/addip/".$csb_ips[0]->comment_author_IP);
	} 
	function commentspamblacklist_blacklist($csb_ip)
	{
		$csb_antwort = file_get_contents("http://api.polarismedia.de/wp-anti-spam/checkip/".$csb_ip);
		if($csb_antwort == "NOK")
		{
			die('Spam is not allowed!');
		}	
	}
//Comment Spam Blacklist Funktionen END
//Parentlink BEGIN
	function parentlink_contentfilter($content)
	{
		global $post;
		$this->post = $post;
		if(function_exists('qtrans_init')) {
			$backtext = __('<!--:de-->Zur&uuml;ck<!--:--><!--:en-->back<!--:-->');
			$contentcheck = preg_replace("|<!--more-->|",'<span id="more-' . $this->post->ID . '"></span>',qtrans_useCurrentLanguageIfNotFoundShowAvailable($this->post->post_content));
		} else {
			$backtext = "Zur&uuml;ck";
			$contentcheck = $this->post->post_content;
		}
		if (isset($this->post->ID) && $content == $contentcheck)
		{
			if(is_page($this->post->ID) && $this->get_option('parentlink_page') == 1 && $this->post->post_parent != 0)
			{
				//Add Link
				if($this->get_option('parentlink_title') == 1)
				{
					//Eigener name	
					$content .= '<p><a class="sublink page_sublink" href="'.get_permalink($this->post->post_parent).'">'.get_post($this->post->post_parent)->post_title.'</a></p>';
				}
				else
				{
					//Zurueck
					$content .= '<p><a class="sublink page_sublink" href="'.get_permalink($this->post->post_parent).'">'.$backtext.'</a></p>';
				}
			}
			elseif(is_single($this->post->ID) && $this->get_option('parentlink_post') == 1)
			{
				$catdaten = get_the_category($this->post->ID);
				//Add Link
				if($this->get_option('parentlink_title') == 1)
				{
					//Eigener name	
					$content .= '<p><a class="sublink single_sublink" href="'.get_category_link($catdaten[0]->cat_ID).'">'.$catdaten[0]->cat_name.'</a></p>';
				}
				else
				{
					//Zurueck
					$content .= '<p><a class="sublink single_sublink" href="'.get_category_link($catdaten[0]->cat_ID).'">'.$backtext.'</a></p>';
				}
			}
		}
		return $content;
	}
//Parentlink END
//Hoversubmenu Funktionen BEGIN
	function hoversubmenu_plugin_init()
	{
		wp_enqueue_script('jquery');
	}
	function hoversubmenu_manipulation()
	{
		$hoverclass = $this->get_option('hoversubmenu_css');
		if(!$hoverclass)
		{
			$hoverclass = 'ul.topnav li a';
		}
		if(strpos($hoverclass,",") === false)
		{
			//Nur 1 Video'
			$hcs[0] = $hoverclass;
		}
		else
		{
			$hcs = split(",",$hoverclass);
		}
		echo '<script type="text/javascript">'."\n";
		foreach($hcs as $hc)
		{
			echo 'jQuery(document).ready(function() {
			 jQuery("'.$hc.'").hover(function(){
			  jQuery(this).addClass("subhover");
			  jQuery(this).parent().children("ul").slideDown();
			  jQuery(this).parent().hover(function(){},
			  function(){
			   jQuery(this).children("a").removeClass("subhover");
			   jQuery(this).find("ul").stop(true, true).slideUp();
			  });
			 });
			});'."\n";
		}
		echo '</script>'."\n";
		return;
	}
//Hoversubmenu Funktionen END
//All_in_One_SEO_Pack Addon BEGIN
	function aioseop_title($title)
	{
		return utf8_encode(str_replace(array("?","&shy;"),"",utf8_decode($title)));
	}
//All_in_One_SEO_Pack Addon END
//qTranslate Body_Class Addon BEGIN
	function qtrans_body_class_language($classes)
	{
		$classes[] = 'qtrans-lang-'. qtrans_getLanguage();
		return $classes;
	}
//qTranslate Body_Class Addon END
//Gallery Style Funktionen BEGIN
	function gallery_style_manipulation()
	{
		global $post;
		$this->post = $post;
		if (isset($this->post->ID))
		{
			$pos = preg_match('/\[gallery(.)*\]/', $this->post->post_content, $matches);
			if(!$pos)
			{
				return;
			}
			$pos = preg_match('/columns=\"[0-9]*\"/', $matches[0], $matches1);
			$split1 = preg_split('/\"/', $matches1[0]);
			$columns = intval($split1[1]);
			$itemwidth = $columns > 0 ? floor(100/$columns) : 33;

?>
			<style type='text/css'>
			.gallery {
				margin: auto;
			}
			.gallery .gallery-item {
				float: left;
				margin-top: 10px;
				text-align: center;
				width: <?php echo $itemwidth; ?>%;			}
			.gallery img {
				border: 2px solid #cfcfcf;
			}
			.gallery .gallery-caption {
				margin-left: 0;
			}
		</style>
<?php
		}
		return;
	}
//Gallery Style Funktionen END
//Fancybox Funktionen BEGIN
	function fancybox_plugin_init()
	{
		wp_enqueue_script('fancybox-jq', plugins_url('pm-tools/js/jquery.fancybox-1.3.4.pack.js'),array('jquery'),'1.3.4');
		wp_enqueue_style('fancybox-css', plugins_url('pm-tools/css/jquery.fancybox-1.3.4.css'),array(),'1.3.4');
	}
	function fancybox_plugin_manipulation()
	{
		echo '<script type="text/javascript">
		jQuery(function(){
				jQuery.fn.getTitle = function() {
			var arr = jQuery("a.fancybox");
			jQuery.each(arr, function() {
				var title = jQuery(this).children("img").attr("title");
				jQuery(this).attr(\'title\',title);
			});
		};
		// Supported file extensions
		var thumbnails = \'a:has(img)[href$=".bmp"],a:has(img)[href$=".gif"],a:has(img)[href$=".jpg"],a:has(img)[href$=".jpeg"],a:has(img)[href$=".png"],a:has(img)[href$=".BMP"],a:has(img)[href$=".GIF"],a:has(img)[href$=".JPG"],a:has(img)[href$=".JPEG"],a:has(img)[href$=".PNG"]\';

		jQuery(thumbnails).each(function(){
			jQuery(this).addClass("fancybox")
			if(!jQuery(this).attr("rel")){
				jQuery(this).attr("rel","fancybox");
			}
			jQuery(this).getTitle();
		});


			jQuery("a.fancybox").fancybox({
			\'autoScale\': true,
			\'padding\': 10,
			\'opacity\': true,
			\'speedIn\': 500,
			\'speedOut\': 500,
			\'speedChange\': 300,
			\'overlayShow\': true,
			\'overlayColor\': "#666666",
			\'overlayOpacity\': 0.3,
			\'enableEscapeButton\': true,
			\'showCloseButton\': true,
			\'hideOnOverlayClick\': true,
			\'hideOnContentClick\': false,
			\'width\':  560,
			\'height\':  340
		});

});
		</script>';
		return;
	}
//Fancybox Funktionen END

//Columnsadd BEGIN
	function atc_pcolumns($pc) 
	{
		if(isset($pc['cb']))			$new_pc['cb'] = '';
		if(isset($pc['title']))		$new_pc['title'] = '';
		$new_pc['ID'] = "ID";
		return array_merge($new_pc, $pc);
	}

function atc_pccolumn($pcn) 
{
	global $post;
	if( $pcn == 'ID' ) 
	{
		echo $post->ID;
	}
	return $pcn;
}


//Columnsadd END

	function is_current_page($page) 
	{
		switch($page) 
		{
			case 'home':
			return (!empty($_REQUEST['page']) && $_REQUEST['page'] == $this->base_name);
			case 'index':
			case 'plugins':
			return (!empty($GLOBALS['pagenow']) && $GLOBALS['pagenow'] == sprintf('%s.php', $page));
			default:
			return false;
		}
	}
	function show_version_notice() {
		if ($this->is_min_wp('3.0')) {
			return;
		}
		echo sprintf(
		'<div class="error"><p><strong>%s</strong> %s</p></div>',
		'PM Tools',
		'ben&ouml;tigt WP 3.0 oder h&ouml;her'
		);
	}
	function init_plugin_options()
	{
		add_option('pmtools',array(),'','no');
	}
	function init_admin_menu() 
	{
		$pages = array('pmtools_settings' => array(
                'Einstellungen', 
                'Einstellungen',
                'show_admin_menu'
            ),
            'pmtools_panorama' => array(
                'Panorama Addon', 
                'Panorama Addon',
                'show_admin_panorama'
            ),
            'pmtools_guestbook' => array(
                'G&auml;stebuch', 
                'G&auml;stebuch',
                'show_admin_guestbook'
            ),
            'pmtools_parentlink' => array(
                'Sublink Addon', 
                'Sublink Addon',
                'show_admin_parentlink'
            ),
            'pmtools_hoversubmenu' => array(
                'HoverSubmenu', 
                'HoverSubmenu',
                'show_admin_hoversubmenu'
            ),
            'pmtools_lissabon' => array(
                'Lissabon Addon', 
                'Lissabon Addon',
                'show_admin_lissabon'
            ),
            'pmtools_help' => array(
                'Hilfe', 
                'Hilfe',
                'show_admin_help'
            )
            );

		add_menu_page('PM Tools','PM Tools','manage_options','pmtools_settings','', plugins_url('pm-tools/images/logo_small.png'));

        $submenu_pages = array();
        
        foreach ($pages as $slug => $titles) {
            $submenu_pages[] = add_submenu_page('pmtools_settings', $titles[0] . ' | Polaris Media Tools', $titles[1], ($this->is_min_wp('2.8') ? 'manage_options' : 9),$slug,array($this,$titles[2]));
        }

		
	}
	function is_min_wp($version) 
	{
		return version_compare($GLOBALS['wp_version'],$version. 'alpha','>=');
	}
	function get_option($field) 
	{
		if (!$options = wp_cache_get('pmtools')) 
		{
			$options = get_option('pmtools');
			wp_cache_set('pmtools',$options);
		}
		return @$options[$field];
	}
	function update_option($field, $value) 
	{
		$this->update_options(array($field => $value));
	}
	function update_options($data) 
	{
		$options = array_merge((array)get_option('pmtools'),$data);
		update_option('pmtools',$options);
		wp_cache_set('pmtools',$options);
	}
	function check_user_can() 
	{
		if (current_user_can('manage_options') === false || current_user_can('edit_plugins') === false || !is_user_logged_in()) 
		{
			wp_die('You do not have permission to access!');
		}
	}

//Admin-Pages BEGIN
	function show_admin_menu() 
	{
		if (!$this->is_min_wp('2.8')) 
		{
			$this->check_user_can();
		}
		if (!empty($_POST)) 
		{
			check_admin_referer('pmtools');
			$options = array(
			'lissabon'=> $_POST['pmtools_lissabon'],
			'panorama'=> $_POST['pmtools_panorama'],
			'commentspamblacklist'=> $_POST['pmtools_commentspamblacklist'],
			'guestbook'=> $_POST['pmtools_guestbook'],
			'parentlink'=> $_POST['pmtools_parentlink'],
			'hoversubmenu'=> $_POST['pmtools_hoversubmenu'],
			'fancybox'=> $_POST['pmtools_fancybox']
			);
			if (empty($options['lissabon'])) {
				$options['lissabon'] = 0; // Dekativiert als Standard
			}
			if (empty($options['panorama'])) {
				$options['panorama'] = 0; // Deaktiviert als Standard
			}
			if (empty($options['guestbook'])) {
				$options['guestbook'] = 0; //Deaktiviert als Standard
			}
			if (empty($options['commentspamblacklist'])) {
				$options['commentspamblacklist'] = 0; //Deaktiviert als Standard
			}
			if (empty($options['parentlink'])) {
				$options['parentlink'] = 0; //Deaktiviert als Standard
			}
			if (empty($options['hoversubmenu'])) {
				$options['hoversubmenu'] = 0; //Deaktiviert als Standard
			}
			if (empty($options['fancybox'])) {
				$options['fancybox'] = 0; //Deaktiviert als Standard
			}
			$this->update_options($options); ?>
			<div id="message" class="updated fade">
				<p>
					<strong>
						Einstellungen gespeichert
					</strong>
				</p>
			</div>
			<?php } ?>
			<div class="wrap">
				<div class="icon32"></div>
				<h2>
					PM Tools - Allgemeine Einstellungen
				</h2>
				<form method="post" action="">
					<?php wp_nonce_field('pmtools') ?>
					<div id="poststuff">
						<div class="postbox">
							<h3>
								Allgemeine Einstellungen
							</h3>
							<div class="inside">
								<ul>
									<li>
										<div>
											<input type="checkbox" name="pmtools_panorama" value="1"<?php checked($this->get_option('panorama'),1); ?>>
											<label for="pmtools_panorama">
												| Panorama-Addon aktivieren?
											</label>
										</div>
									</li>
									<li>
										<div>
											<input type="checkbox" name="pmtools_guestbook" value="1"<?php checked($this->get_option('guestbook'),1); ?>>
											<label for="pmtools_guestbook">
												| G&auml;stebuch aktivieren?
											</label>
										</div>
									</li>
									<li>
										<div>
											<input type="checkbox" name="pmtools_commentspamblacklist" value="1"<?php checked($this->get_option('commentspamblacklist'),1); ?>>
											<label for="pmtools_commentspamblacklist">
												| Comment Spam Polarismedia API aktivieren?
											</label>
										</div>
									</li>
									<li>
										<div>
											<input type="checkbox" name="pmtools_parentlink" value="1"<?php checked($this->get_option('parentlink'),1); ?>>
											<label for="pmtools_parentlink">
												| Elternseite/Kategorie automatisch verlinken AddOn aktivieren? (Sublink Addon)
											</label>
										</div>
									</li>
									<li>
										<div>
											<input type="checkbox" name="pmtools_hoversubmenu" value="1"<?php checked($this->get_option('hoversubmenu'),1); ?>>
											<label for="pmtools_hoversubmenu">
												| HoverSubmenu aktivieren?
											</label>
										</div>
									</li>
									<li>
										<div>
											<input type="checkbox" name="pmtools_fancybox" value="1"<?php checked($this->get_option('fancybox'),1); ?>>
											<label for="pmtools_fancybox">
												| Fancybox aktivieren?
											</label>
										</div>
									</li>
									<li>
										<div>
											<input type="checkbox" name="pmtools_lissabon" value="1"<?php checked($this->get_option('lissabon'),1); ?>>
											<label for="pmtools_lissabon">
												| Lissaboneffekt aktivieren?
											</label>
										</div>
									</li>
								</ul>
								<br clear="all" />
								<p>
									<input type="submit" name="pmtools_submit" class="button-primary" value="Einstellungen speichern" />
								</p>
							</div>
						</div>
						<div class="postbox">
							<h3>
								&Uuml;ber PM Tools
							</h3>
						<div class="inside">
							<p>
								<?php $this->show_plugin_info() ?>
							</p>
						</div>
					</div>
				</div>
			</form>
		</div>
<?php 
	}

	function show_admin_lissabon() 
	{
		if (!$this->is_min_wp('2.8')) 
		{
			$this->check_user_can();
		} 
		if (!empty($_POST)) 
		{
			check_admin_referer('pmtools');
			$options = array(
			'lissabon_selector'=> $_POST['pmtools_lissabon_selector'],
			'lissabon_itemwidth'=> $_POST['pmtools_lissabon_itemwidth'],
			'lissabon_itemheight'=> $_POST['pmtools_lissabon_itemheight'],
			'lissabon_normalgroesse'=> $_POST['pmtools_lissabon_normalgroesse'],
			'lissabon_speed_main'=> $_POST['pmtools_lissabon_speed_main'],
			'lissabon_speed_sub'=> $_POST['pmtools_lissabon_speed_sub']
			);
			if (empty($options['lissabon_selector'])) {
				$options['lissabon_selector'] = "#sdt_menu > li"; // Standard
			}
			if (empty($options['lissabon_itemwidth'])) {
				$options['lissabon_itemwidth'] = 170; // Standard
			}
			if (empty($options['lissabon_itemheight'])) {
				$options['lissabon_itemheight'] = 170; // Standard
			}
			if (empty($options['lissabon_normalgroesse'])) {
				$options['lissabon_normalgroesse'] = 65; // Standard
			}
			if (empty($options['lissabon_speed_main'])) {
				$options['lissabon_speed_main'] = 400;
			}
			if (empty($options['lissabon_speed_sub'])) {
				$options['lissabon_speed_sub'] = 200;
			}
			$this->update_options($options); ?>
			<div id="message" class="updated fade">
				<p>
					<strong>
						Einstellungen gespeichert
					</strong>
				</p>
			</div>
			<?php } ?>
			<div class="wrap">
				<div class="icon32"></div>
				<h2>
					PM Tools - Lissabon Addon
				</h2>
				<form method="post" action="">
					<?php wp_nonce_field('pmtools') ?>
					<div id="poststuff">
						<div class="postbox">
							<h3>
								Nutzungshinweise &amp; Einstellungen
							</h3>
							<div class="inside">
								<ul>
									<li>
										<div>
											1. Schritt: Unter den PM Tools Einstellungen aktivieren.<br />
											2. Schritt: Standardwerte f&uuml;r Selector und Itembreite/Itemh&ouml;he/Non-Aktiv-H&ouml;he hier setzen<br />
											3. Schritt: CSS-Werte in der allgemeinen CSS-Datei einf&uuml;gen<br />
											4. Schritt: Bilder der Reihenfolge nach benennen (1.jpg, 2.jpg, etc.)<br />
											5. Schritt: einf&uuml;gen: wp_nav_menu(array('walker' =&gt; new Walker_Nav_Menu_Lissabon(), 'image_file_extension' =&gt; 'jpg', 'image_path' =&gt; 'images/lissabon/', 'class_prefix' =&gt; 'std_'));
										</div>
									</li>
									<li>
										<div>
											<input type="text" name="pmtools_lissabon_selector" value="<?php echo $this->get_option('lissabon_selector'); ?>" />
											<label for="pmtools_lissabon_selector">
												| Der CSS-Selector (Standardwert: #sdt_menu &gt; li)
											</label>
										</div>
									</li>
									<li>
										<div>
											<input type="text" name="pmtools_lissabon_itemwidth" value="<?php echo $this->get_option('lissabon_itemwidth'); ?>" />
											<label for="pmtools_lissabon_itemwidth">
												| Einzelbildweite
											</label>
										</div>
									</li>
									<li>
										<div>
											<input type="text" name="pmtools_lissabon_itemheight" value="<?php echo $this->get_option('lissabon_itemheight'); ?>" />
											<label for="pmtools_lissabon_itemheight">
												| Einzelbildh&ouml;he
											</label>
										</div>
									</li>
									<li>
										<div>
											<input type="text" name="pmtools_lissabon_normalgroesse" value="<?php echo $this->get_option('lissabon_normalgroesse'); ?>" />
											<label for="pmtools_lissabon_normalgroesse">
												| Standardh&ouml;he der Navileiste (Non-Hover)
											</label>
										</div>
									</li>
									<li>
										<div>
											<input type="text" name="pmtools_lissabon_speed_main" value="<?php echo $this->get_option('lissabon_speed_main'); ?>" />
											<label for="pmtools_lissabon_speed_main">
												| Standardgeschwindigkeit f&uuml;r Hauptmen&uuml;
											</label>
										</div>
									</li>
									<li>
										<div>
											<input type="text" name="pmtools_lissabon_speed_sub" value="<?php echo $this->get_option('lissabon_speed_sub'); ?>" />
											<label for="pmtools_lissabon_speed_sub">
												| Standardgeschwindigkeit f&uuml;r Submen&uuml;
											</label>
										</div>
									</li>
								</ul>
								<br clear="all" />
								<p>
									<input type="submit" name="pmtools_submit" class="button-primary" value="Einstellungen speichern" />
								</p>
							</div>
						</div>
						<div class="postbox">
							<h3>
								&Uuml;ber Polaris Media Tools
							</h3>
						<div class="inside">
							<p>
								<?php $this->show_plugin_info() ?>
							</p>
						</div>
					</div>
				</div>
			</form>
		</div>
<?php 
	}


	function show_admin_panorama() 
	{
		if (!$this->is_min_wp('2.8')) 
		{
			$this->check_user_can();
		} 
		if (!empty($_POST)) 
		{
			check_admin_referer('pmtools');
			$options = array(
			'panorama_width'=> $_POST['pmtools_panorama_width'],
			'panorama_speed'=> $_POST['pmtools_panorama_speed']
			);
			if (empty($options['panorama_width'])) {
				$options['panorama_width'] = 600; // Standard
			}
			if (empty($options['panorama_speed'])) {
				$options['panorama_speed'] = 20000; // Standard
			}
			$this->update_options($options); ?>
			<div id="message" class="updated fade">
				<p>
					<strong>
						Einstellungen gespeichert
					</strong>
				</p>
			</div>
			<?php } ?>
			<div class="wrap">
				<div class="icon32"></div>
				<h2>
					PM Tools - Panorama Addon
				</h2>
				<form method="post" action="">
					<?php wp_nonce_field('pmtools') ?>
					<div id="poststuff">
						<div class="postbox">
							<h3>
								Nutzungshinweise &amp; Einstellungen
							</h3>
							<div class="inside">
								<ul>
									<li>
										<div>
											1. Schritt: Unter den PM Tools Einstellungen aktivieren.<br />
											2. Schritt: Shortcode auf Seite oder Artikel einf&uuml;gen: [panorama url=&quot;URL&quot; width=&quot;bildweite&quot; height=&quot;bildhoehe&quot; alt=&quot;beschreibung&quot;]<br />
											3. Schritt: Standardwerte f&uuml;r Geschwindigkeit und Contentbreite hier setzen
										</div>
									</li>
									<li>
										<div>
											<input type="text" name="pmtools_panorama_width" value="<?php echo $this->get_option('panorama_width'); ?>" />
											<label for="pmtools_panorama_width">
												| Contentbreite in Pixel
											</label>
										</div>
									</li>
									<li>
										<div>
											<input type="text" name="pmtools_panorama_speed" value="<?php echo $this->get_option('panorama_speed'); ?>" />
											<label for="pmtools_panorama_speed">
												| Geschwindigkeit (je gr&ouml;&szlig;er die Zahl desto langsamer)
											</label>
										</div>
									</li>
								</ul>
								<br clear="all" />
								<p>
									<input type="submit" name="pmtools_submit" class="button-primary" value="Einstellungen speichern" />
								</p>
							</div>
						</div>
						<div class="postbox">
							<h3>
								&Uuml;ber Polaris Media Tools
							</h3>
						<div class="inside">
							<p>
								<?php $this->show_plugin_info() ?>
							</p>
						</div>
					</div>
				</div>
			</form>
		</div>
<?php 
	}

	function show_admin_guestbook() 
	{
		if (!$this->is_min_wp('2.8')) 
		{
			$this->check_user_can();
		} 
		if (!empty($_POST)) 
		{
			check_admin_referer('pmtools');
			$options = array(
			'guestbook_title_relply'=> $_POST['pmtools_guestbook_title_relply'],
			'guestbook_label_submit'=> $_POST['pmtools_guestbook_label_submit'],
			'guestbook_comment_notes_before'=> $_POST['pmtools_guestbook_comment_notes_before']
			);
			if (empty($options['guestbook_title_relply'])) {
				$options['guestbook_title_relply'] = 'Tragen Sie sich in unser G?stebuch ein!'; // Standard
			}
			if (empty($options['guestbook_label_submit'])) {
				$options['guestbook_label_submit'] = 'Ins G?stebuch eintragen ?'; // Standard
			}
			if (empty($options['guestbook_comment_notes_before'])) {
				$options['guestbook_comment_notes_before'] = 'Ihre E-Mail-Adresse wird nicht ver?ffentlicht. Erforderliche Felder sind markiert *'; // Standard
			}
			$this->update_options($options); ?>
			<div id="message" class="updated fade">
				<p>
					<strong>
						Einstellungen gespeichert
					</strong>
				</p>
			</div>
			<?php } ?>
			<div class="wrap">
				<div class="icon32"></div>
				<h2>
					PM Tools - G&auml;stebuch
				</h2>
				<form method="post" action="">
					<?php wp_nonce_field('pmtools') ?>
					<div id="poststuff">
						<div class="postbox">
							<h3>
								Nutzungshinweise &amp; Einstellungen
							</h3>
							<div class="inside">
								<ul>
									<li>
										<div>
											1. Schritt: Unter den PM Tools Einstellungen aktivieren.<br />
											2. Schritt: Shortcode auf Seite oder Artikel einf&uuml;gen: [gaestebuch]<br />
											3. Schritt: Standardtexte hier setzen
										</div>
									</li>
									<li>
										<div>
											<input type="text" name="pmtools_guestbook_title_relply" value="<?php echo $this->get_option('guestbook_title_relply'); ?>" />
											<label for="pmtools_guestbook_title_relply">
												| Formulartitel
											</label>
										</div>
									</li>
									<li>
										<div>
											<input type="text" name="pmtools_guestbook_label_submit" value="<?php echo $this->get_option('guestbook_label_submit'); ?>" />
											<label for="pmtools_guestbook_label_submit">
												| Text des Absendebuttons
											</label>
										</div>
									</li>
									<li>
										<div>
											<textarea name="pmtools_guestbook_comment_notes_before" cols="30" rows="4"><?php echo $this->get_option('guestbook_comment_notes_before'); ?></textarea>
											<label for="pmtools_guestbook_comment_notes_before">
												| Beschreibungstext zwischen Formular&uuml;berschrift und Formularfeldern
											</label>
										</div>
									</li>
								</ul>
								<br clear="all" />
								<p>
									<input type="submit" name="pmtools_submit" class="button-primary" value="Einstellungen speichern" />
								</p>
							</div>
						</div>
						<div class="postbox">
							<h3>
								&Uuml;ber Polaris Media Tools
							</h3>
						<div class="inside">
							<p>
								<?php $this->show_plugin_info() ?>
							</p>
						</div>
					</div>
				</div>
			</form>
		</div>
<?php 
	}

	function show_admin_parentlink() 
	{
		if (!$this->is_min_wp('2.8')) 
		{
			$this->check_user_can();
		} 
		if (!empty($_POST)) 
		{
			check_admin_referer('pmtools');

			$options = array(
			'parentlink_post'=> $_POST['pmtools_parentlink_post'],
			'parentlink_page'=> $_POST['pmtools_parentlink_page'],
			'parentlink_title'=> $_POST['pmtools_parentlink_title']
			);
			if (empty($options['parentlink_post'])) {
				$options['parentlink_post'] = 0; // Standard
			}
			if (empty($options['parentlink_page'])) {
				$options['parentlink_page'] = 0; // Standard
			}
			if (empty($options['parentlink_title'])) {
				$options['parentlink_title'] = 0; // Standard
			}
			$this->update_options($options); ?>
			<div id="message" class="updated fade">
				<p>
					<strong>
						Einstellungen gespeichert
					</strong>
				</p>
			</div>
			<?php } ?>
			<div class="wrap">
				<div class="icon32"></div>
				<h2>
					PM Tools - Eltern- &amp; Kategorielinks automatisch setzen
				</h2>
				<form method="post" action="">
					<?php wp_nonce_field('pmtools') ?>
					<div id="poststuff">
						<div class="postbox">
							<h3>
								Einstellungen
							</h3>
							<div class="inside">
								<ul>
									<li>
										<div>
											<input type="checkbox" name="pmtools_parentlink_post" value="1"<?php checked($this->get_option('parentlink_post'),1); ?>>
											<label for="pmtools_parentlink_post">
												| Automatische Linksetzung f&uuml;r Artikel aktivieren?
											</label>
										</div>
									</li>
									<li>
										<div>
											<input type="checkbox" name="pmtools_parentlink_page" value="1"<?php checked($this->get_option('parentlink_page'),1); ?>>
											<label for="pmtools_parentlink_page">
												| Automatische Linksetzung f&uuml;r Seiten aktivieren?
											</label>
										</div>
									</li>
									<li>
										<div>
											<input type="checkbox" name="pmtools_parentlink_title" value="1"<?php checked($this->get_option('parentlink_title'),1); ?>>
											<label for="pmtools_parentlink_title">
												| Titel des Links soll Name der Elternseite / Kategorie sein? (default &quot;zur&uuml;ck&quot;)
											</label>
										</div>
									</li>
								</ul>
								<br clear="all" />
								<p>
									<input type="submit" name="pmtools_submit" class="button-primary" value="Einstellungen speichern" />
								</p>
							</div>
						</div>
						<div class="postbox">
							<h3>
								&Uuml;ber Polaris Media Tools
							</h3>
						<div class="inside">
							<p>
								<?php $this->show_plugin_info() ?>
							</p>
						</div>
					</div>
				</div>
			</form>
		</div>
<?php 
	}

	function show_admin_hoversubmenu() 
	{
		if (!$this->is_min_wp('2.8')) 
		{
			$this->check_user_can();
		} 
		if (!empty($_POST)) 
		{
			check_admin_referer('pmtools');
			$options = array(
			'hoversubmenu_css'=> $_POST['pmtools_hoversubmenu_css']
			);
			if (empty($options['hoversubmenu_css'])) {
				$options['hoversubmenu_css'] = "ul.topnav li a"; // Standard
			}
			$this->update_options($options); ?>
			<div id="message" class="updated fade">
				<p>
					<strong>
						Einstellungen gespeichert
					</strong>
				</p>
			</div>
			<?php } ?>
			<div class="wrap">
				<div class="icon32"></div>
				<h2>
					PM Tools - Hover Submenu Addon
				</h2>
				<form method="post" action="">
					<?php wp_nonce_field('pmtools') ?>
					<div id="poststuff">
						<div class="postbox">
							<h3>
								Nutzungshinweise &amp; Einstellungen
							</h3>
							<div class="inside">
								<ul>
									<li>
										<div>
											1. Schritt: Unter den PM Tools Einstellungen aktivieren.<br />
											2. Schritt: Korrekte CSS-Definitionen in die style.css integrieren<br />
											3. Schritt: Ansprechklasse hier definieren (Standard: ul.topnav li a)
										</div>
									</li>
									<li>
										<div>
											<input type="text" name="pmtools_hoversubmenu_css" value="<?php echo $this->get_option('hoversubmenu_css'); ?>" />
											<label for="pmtools_hoversubmenu_css">
												| Ansprechklasse f&uuml;r Ajax: Mehrere durch Beistriche trennen
											</label>
										</div>
									</li>
								</ul>
								<br clear="all" />
								<p>
									<input type="submit" name="pmtools_submit" class="button-primary" value="Einstellungen speichern" />
								</p>
							</div>
						</div>
						<div class="postbox">
							<h3>
								&Uuml;ber Polaris Media Tools
							</h3>
						<div class="inside">
							<p>
								<?php $this->show_plugin_info() ?>
							</p>
						</div>
					</div>
				</div>
			</form>
		</div>
<?php 
	}

	function show_admin_help() 
	{
		if (!$this->is_min_wp('2.8')) 
		{
			$this->check_user_can();
		} 
?>
			<div class="wrap">
				<div class="icon32"></div>
				<h2>
					PM Tools - Hilfeseite
				</h2>
					<div id="poststuff">
						<div class="postbox">
							<h3>
								Funktionsbeschreibung: layout_content()
							</h3>
							<div class="inside">
								<ul>
									<li>
										<div>
											Folgenden 1.Zeiler im Design hinterlegen um den Content einer Seite auszugeben:<br /><br />
											<b>&lt;?php global $PMTools; $PMTools-&gt;layout_content($post-&gt;ID, $echo); ?&gt;</b><br /><br />
											<b>$post-&gt;ID</b>: Ersetzen durch die ID des Postings wo der Content ausgegeben werden soll.<br /><br />
											<b>$echo</b>: Ersetzen durch true oder false, je nachdem ob die Funktion den Wert ausgeben soll oder in eine Variable returnen.<br /><br />
										</div>
									</li>
								</ul>
							</div>
						</div>
						<div class="postbox">
							<h3>
								Shortcodebeschreibung: [subpagemenu]
							</h3>
							<div class="inside">
								<ul>
									<li>
										<div>
											Folgenden 1.Zeiler im Design hinterlegen um automatisch Submenus auszugeben:<br /><br />
											<b>&lt;?php echo do_shortcode(&quot;[subpagemenu pid='&quot;.$post-&gt;ID.&quot;' parentid='&quot;.$post-&gt;post_parent.&quot;']&quot;); ?&gt;</b><br /><br />
											<b>$post-&gt;ID</b>: Optionaler Wert, kann weggelassen werden. Falls an einer Stelle ein Submenu einer anderen Page ausgegeben werden m&uuml;sste.<br /><br />
											<b>$post-&gt;post_parent</b>: Optionaler Wert, kann weggelassen werden. Falls an einer Stelle ein Submenu einer anderen Page ausgegeben werden m&uuml;sste.<br /><br />
											<b>Ausgabe</b>: Bis zu 2 Subebenen m&ouml;glich. ID der Boxen: &quot;subnavi&quot; bzw. &quot;subnavi2&quot;<br /><br />
											<b>Anwendbarkeit</b>: Kann auch via [subpagemenu ...] &uuml;ber das Backend direkt in den Content geschrieben werden.<br /><br />
										</div>
									</li>
								</ul>
							</div>
						</div>
						<div class="postbox">
							<h3>
								&Uuml;ber Polaris Media Tools
							</h3>
						<div class="inside">
							<p>
								<?php $this->show_plugin_info() ?>
							</p>
						</div>
					</div>
				</div>
		</div>
<?php 
	}


//Admin-Pages END
//Funktionen BEGIN
	function layout_content($my_id, $echo = true)
	{
		$post_id_ads = get_post($my_id);
		$intro = $post_id_ads->post_content;
		$intro = htmlentities($intro, ENT_COMPAT, "UTF-8");
		$intro = nl2br(preg_replace(array("|&amp;nbsp;|","|&lt;|","|&gt;|","|&quot;|"),array("&nbsp;","<",">","\""),$intro));
		$intro = apply_filters('the_content', $intro);
		if($echo == true)
		{
			echo $intro;
			return;
		}
		else
		{
			return $intro;	
		}
	}    
//Funktionen END

	function show_plugin_info() 
	{
		$data = get_plugin_data(__FILE__);
		echo sprintf('%s %s %s <a href="http://www.polarismedia.de/pm-tools/" target="_blank">PM Tools by Polaris Media</a> | <a href="http://www.polarismedia.de/" target="_blank">%s</a>',
		'Polaris Media Tools f&uuml;r WordPress',
		$data['Version'],
		'von',
		'Mehr &uuml;ber Polaris Media UG'
		);
	}
}
$PMTools = new PMTools();

/* WalkerClass extention for Lissaboneffekt BEGIN */
if($PMTools->get_option('lissabon') == 1)
{
	global $pm_walker_count;
	$pm_walker_count = 1;
	class Walker_Nav_Menu_Lissabon extends Walker_Nav_Menu {
		var $tree_type = array( 'post_type', 'taxonomy', 'custom' );
		var $db_fields = array( 'parent' => 'menu_item_parent', 'id' => 'db_id' );
		function start_lvl(&$output, $depth = 0, $args = array()) {
			if($depth == 0){
				$output .= "\n\t\t<div class=\"". $args->class_prefix ."box\">";
			}
		}
		function end_lvl(&$output, $depth = 0, $args = array()) {
			if($depth == 0){
				$output .= "\n\t\t</div>";
			}
		}
		function start_el(&$output, $item, $depth = 0, $args = array(), $id = 0) {
			global $wp_query, $pm_walker_count;
			$indent = ( $depth ) ? str_repeat( "\t", $depth ) : '';
			$class_names = $value = '';
			$classes = empty( $item->classes ) ? array() : (array) $item->classes;
			$classes[] = 'menu-item-' . $item->ID;
			$class_names = join( ' ', apply_filters( 'nav_menu_css_class', array_filter( $classes ), $item ) );
			$class_names = ' class="' . esc_attr( $class_names ) . '"';
			$id = apply_filters( 'nav_menu_item_id', 'menu-item-'. $item->ID, $item, $args );
			$id = strlen( $id ) ? ' id="' . esc_attr( $id ) . '"' : '';
			$attributes  = ! empty( $item->attr_title ) ? ' title="'  . esc_attr( $item->attr_title ) .'"' : '';
			$attributes .= ! empty( $item->target )     ? ' target="' . esc_attr( $item->target     ) .'"' : '';
			$attributes .= ! empty( $item->xfn )        ? ' rel="'    . esc_attr( $item->xfn        ) .'"' : '';
			$attributes .= ! empty( $item->url )        ? ' href="'   . esc_attr( $item->url        ) .'"' : '';
			$args->image_path			= ! empty( $args->image_path )				? $args->image_path				: 'images/lissabon/';
			$args->image_file_extension	= ! empty( $args->image_file_extension )	? $args->image_file_extension	: 'jpg';
			$args->class_prefix			= ! empty( $args->class_prefix )			? $args->class_prefix			: 'lissabon_';
			if($depth == 0){
				$image_file_name = $pm_walker_count;
				$pm_walker_count++;
	/*			$image_file_name = str_replace( array( get_bloginfo('url'), '/'), '', $item->url);
				if(str_replace(array('http','?'),array('',''), $image_file_name) != $image_file_name){
					$image_file_name = $item->ID;
				}
	*/
				$output .= "\n". $indent ."\t".'<li' . $id . $value . $class_names .'>';
				$item_output = ! empty( $args->before ) ? "\n". $indent ."\t\t".		$args->before : '';
				$item_output .= "\n". $indent ."\t\t".									'<a class="'. $args->class_prefix .'link"'. $attributes .'>';
				$item_output .= ! empty( $args->link_before ) ? "\n". $indent ."\t\t"	.$args->link_before : '';
				$item_output .= "\n". $indent ."\t\t\t".								'<img class="'. $args->class_prefix .'image" alt="" src="'. get_template_directory_uri() .'/'. $args->image_path . $image_file_name .'.'. $args->image_file_extension .'" />';
				$item_output .= "\n". $indent ."\t\t\t".								'<span class="'. $args->class_prefix .'active"></span>';
				$item_output .= "\n". $indent ."\t\t\t".								'<span class="'. $args->class_prefix .'wrap">';
				$item_output .= "\n". $indent ."\t\t\t\t".								'<span class="'. $args->class_prefix .'title">'. apply_filters( 'the_title', $item->title, $item->ID ) .'</span>';
				$item_output .= ! empty( $item->attr_title ) ? "\n". $indent ."\t\t\t".	'<span class="'. $args->class_prefix .'description">'. esc_attr( $item->attr_title ) .'</span>' : '';
				$item_output .= "\n". $indent ."\t\t\t".								'</span>';
				$item_output .= ! empty( $args->link_after ) ? "\n". $indent ."\t\t".	$args->link_after : '';
				$item_output .= "\n". $indent ."\t\t".									'</a>';
				$item_output .= ! empty( $args->after ) ? "\n". $indent ."\t\t".		$args->after : '';
			}else{
				$item_output .= ! empty( $args->before ) ? "\n". $indent ."\t\t".		$args->before : '';
				$item_output .= "\n". $indent ."\t\t".'<a class="'. $args->class_prefix .'link '. $args->class_prefix .'link_depth_'. ( $depth -1 ) .'"'. $attributes .'>';
				if($depth >= 2){ $item_output .= str_repeat( "-", $depth -1 ) .' '; }
				$item_output .= 														$args->link_before . apply_filters( 'the_title', $item->title, $item->ID ) . $args->link_after;
				$item_output .= 														'</a>';
				$item_output .= ! empty( $args->after ) ? "\n". $indent ."\t\t".		$args->after : '';
			}
			$output .= apply_filters( 'walker_nav_menu_start_el', $item_output, $item, $depth, $args );
		}
		function end_el(&$output, $item, $depth = 0, $args = array()) {
			if($depth == 0){
				$output .= "\n\t</li>";
			}
		}
	}
}
/* WalkerClass extention for Lissaboneffekt END */


?>
