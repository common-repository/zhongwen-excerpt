<?php
/*
Plugin Name: Zhongwen Excerpt
Plugin URI: http://www.bianlei.com/zhongwen-excerpt/
Description: 中文摘要插件。
Version: 1.0.0
Author: BianLei
Author URI: http://www.bianlei.com/
License: GPL
*/

register_activation_hook( __FILE__, 'zhongwen_excerpt_install');   
register_deactivation_hook( __FILE__, 'zhongwen_excerpt_remove' );  

function zhongwen_excerpt_install() {  
    // $option, $value, $deprecated, $autoload
    add_option('zhongwen_excerpt_length', '120', '', 'yes');
	add_option('archive_excerpt_length', '120', '', 'yes');
	add_option('allowd_tag', '<a><b><blockquote><br><code><div><em><embed><h1><h2><h3><h4><h5><h6><i><iframe><img><li><p><pre><span><strong><table><td><tr><u><ul>', '', 'yes');
	add_option('read_more_link', '<div align="center"><button type="button" class="button-read-more">Read More | 阅读全文</button></div>', '', 'yes');
}

function zhongwen_excerpt_remove() {  
    delete_option('zhongwen_excerpt_length');
	delete_option('archive_excerpt_length');
	delete_option('allowd_tag');
	delete_option('read_more_link');
}

if( is_admin() ) {
	add_action('admin_menu', 'zhongwen_excerpt_menu');
}

function zhongwen_excerpt_menu(){
	// add_options_page( $page_title, $menu_title, $capability, $menu_slug, $function);
	add_options_page('Zhongwen Excerpt', 'Zhongwen Excerpt', 'administrator', 'zhongwen_excerpt', 'zhongwen_excerpt_options');
}

// Core function
if ( !function_exists('zhongwen_excerpt') ) {
	function zhongwen_excerpt ($text, $type) {
		//get the full post content
		global $post;
		//whether it is hooked to the_excerpt or the_content
		switch ($type) {
			case 'content':
				//in this case, the passed parameter $text is the full content
				//get the manual excerpt
				$manual_excerpt = $post->post_excerpt;
				break;
			case 'excerpt':
				//in this case, the passed parameter $text is the manual excerpt
				$manual_excerpt = $text;
				
				//get and trim the full post content
				$text = $post->post_content;
				$text = str_replace(']]>', ']]&gt;', $text);
				$text = trim($text);
				break;
			default:
				break;
		}
		//only show excerpt on home and archive pages. search result page should be considered archive page.
		if ( !is_home() && !is_archive() && !is_search() ) {
			return $text;
		}
		//if there is manual excerpt, show the manual excerpt
		if ( '' !==  $manual_excerpt ) {
			$text = $manual_excerpt;
			$text = zhongwen_excerpt_readmore ($text);
			return $text;
		}
		//if there is a <!--more--> tag, return stuff before that
		switch ($type) {
			case 'content':
				//in this case, the_content passes formatted content, which has turned <!--more--> tag into a link, which is in turn turned to special mark by me
		 		$more_position = stripos ($text, 'zhongwen_excerpt_HAS_MORE');
				if ($more_position !== false) {
					//remove zhongwen_excerpt_HAS_MORE at the end, which has 21 characters
					$text = substr ($text, 0, -21);
					$text = zhongwen_excerpt_readmore ($text);
					return $text;
				}
				break;
			case 'excerpt':
				//in this case, I get the raw content with the <!--more--> tag
		 		$more_position = stripos ($text, "<!--more-->");
				if ($more_position !== false) {
					$text = substr ($text, 0, $more_position);
					$text = zhongwen_excerpt_readmore ($text);
				    	return $text;
				}
				break;
			default:
				break;
		}
		//get the options
		$zhongwen_excerpt_length = get_option('zhongwen_excerpt_length') ? get_option('zhongwen_excerpt_length') : zhongwen_excerpt_length;
		$archive_excerpt_length = get_option('archive_excerpt_length') ? get_option('archive_excerpt_length') : ARCHIVE_EXCERPT_LENGTH;
		$allowd_tag = get_option('allowd_tag') ? get_option('allowd_tag') : ALLOWD_TAG;
		if ( is_home() ) {
			$length = $zhongwen_excerpt_length;
		} elseif ( is_archive() || is_search() ) {
			$length = $archive_excerpt_length;
		}
		//will make this an option for the user to decide
		$strip_short_post = true;
		 //if the post is already short and the user wants to strip tags
		if(($length > mb_strlen(strip_tags($text), 'utf-8')) && ($strip_short_post === true) ) {
			$text = strip_tags($text, $allowd_tag); 		
			$text = trim($text);
			return $text;
		}
		//other cases
		$text = strip_tags($text, $allowd_tag); 		
		$text = trim($text);
		//check if the character is worth counting (ie. not part of an HTML tag). From Bas van Doren's Advanced Excerpt, thanks to Bas van Doren.
		$num = 0;
		$in_tag = false;
		for ($i=0; $num<$length || $in_tag; $i++) {
			if(mb_substr($text, $i, 1) == '<')
				$in_tag = true;
			elseif(mb_substr($text, $i, 1) == '>')
				$in_tag = false;
			elseif(!$in_tag)
				$num++;
		}
		$text = mb_substr ($text,0,$i, 'utf-8');
		$text = trim($text);
		$text = zhongwen_excerpt_readmore ($text);
		return $text;
	}
}

//check if the post has a <!--more--> tag
//the_content passes formatted content, which has turned <!--more--> tag into a link, so I have to leave a special mark
function zhongwen_excerpt_has_more($more)
{
	if ( '' !== $more) {
		return 'zhongwen_excerpt_HAS_MORE';
	} 
}
add_filter('the_content_more_link', 'zhongwen_excerpt_has_more');

if ( !function_exists('zhongwen_excerpt_readmore')) {
	function zhongwen_excerpt_readmore ($text) {
		//get options
		$read_more_link = get_option('read_more_link') ? get_option('read_more_link') : READ_MORE_LINK;
		//add read_more_link
		$text .= "[......]";
		$text = force_balance_tags($text);
		$text .= "<p class='read-more'><a href='".get_permalink()."'>".$read_more_link."</a></p>";
		return $text;
	}
}

if ( !function_exists('zhongwen_excerpt_for_excerpt') ) {
	function zhongwen_excerpt_for_excerpt ($text) {
		return zhongwen_excerpt($text, 'excerpt');
	}
}
add_filter('get_the_excerpt', 'zhongwen_excerpt_for_excerpt', 9);

if ( !function_exists('zhongwen_excerpt_for_content') ) {
	function zhongwen_excerpt_for_content ($text) {
		return zhongwen_excerpt($text, 'content');
	}
}
add_filter('the_content', 'zhongwen_excerpt_for_content', 9);

function zhongwen_excerpt_options() {
	?>
	<h1><?php _e( 'Zhongwen Excerpt 设置页面' , 'zhongwen_excerpt'); ?></h1>
	<form name="form1" method="post" action="options.php">
	<?php wp_nonce_field('update-options'); ?>
	<table class="form-table">
		<form>
		<div align="">首页摘要字数</div><input type="text" name="zhongwen_excerpt_length" value="<?php echo get_option('zhongwen_excerpt_length'); ?>" />
		<div align="">其它页面摘要字数</div><input type="text" name="archive_excerpt_length" value="<?php echo get_option('archive_excerpt_length'); ?>" />
		<br>
		<div>允许显示的HTML标签</div><textarea name="allowd_tag" style="width:568px" /><?php echo get_option('allowd_tag'); ?></textarea>
		<div>“阅读更多”链接样式（支持CSS代码）</div><textarea name="read_more_link" style="width:568px" /><?php echo get_option('read_more_link'); ?></textarea>
		</form>
	</table>
	<input type="hidden" name="action" value="update" />
	<input type="hidden" name="page_options" value="zhongwen_excerpt_length,archive_excerpt_length, allowd_tag, read_more_link" />
	<p class="submit">
	<input type="submit" class="button-primary" name="Submit" value="<?php _e('保存更改' , 'zhongwen_excerpt') ?>" />
	</p>
	</form>
	<?php
}

?>