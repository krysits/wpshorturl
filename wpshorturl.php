<?php
/**
 * @package   wpshorturl
 * @author    Kristaps Ledins aka @krysits.COM <krysits@gmail.com>
 * @license   GPLv3
 * @link      https://0k.lv/wpshorturl
 * @version   0.1
 * @copyright 2020 Kristaps Ledins aka @krysits.COM
 */

/*
Plugin Name: wpshorturl
Plugin URI: https://0k.lv/wpshorturl
Description: Plugin supports popular URL shortener services like `0k.lv` and `2my.site`
Author: Kristaps Ledins aka @krysits.COM
Version: 0.1
Author URI: https://krysits.com/
*/

add_shortcode('wpshorturl', 'wpshorturl_shortlink');

function wpshorturl_service( $post )
{
	$serviceDomains = ['0k.lv', '2my.site'];
	$domain = get_option('wpshorturl_domain') ?: $serviceDomains[0];
	$oldUrl = get_permalink( $post );
	$requestUrl = 'https://'.$domain.'/add.php?url=' . $oldUrl;
	return file_get_contents($requestUrl);
}

function wpshorturl_get_link($original, $post_id)
{
	if (!in_array(get_post_type($post_id), ['page', 'post']))
		return $original;
	
	if (0 == $post_id) {
		$post = get_post();
		$post_id = $post->ID;
	}
	
	$shortlink = get_post_meta($post_id, '_shorturl', true);
	
	if (!$shortlink) {
		$shortlink = wpshorturl_service($post_id);
		update_post_meta($post_id, '_shorturl', $shortlink);
	}
	
	return ($shortlink) ? $shortlink : $original;
}

function wpshorturl_shortlink($atts = array()) {
	
	$post = get_post();
	
	$defaults = array(
		'text'    => '',
		'title'   => '',
		'before'  => '',
		'after'   => '',
		'post_id' => $post->ID, // Use the current post by default, or pass an ID
	);
	
	extract(shortcode_atts($defaults, $atts));
	
	$permalink = get_permalink($post_id);
	$shortlink = wpshorturl_get_link($permalink, $post_id);
	
	if (empty($text))
		$text = $shortlink;
	
	if (empty($title))
		$title = the_title_attribute(array('echo' => false));
	
	$output = '';
	
	if (!empty($shortlink)) {
		$output = apply_filters('the_shortlink', '<a rel="shortlink" href="' . esc_url($shortlink) . '" title="' . $title . '">' . $text . '</a>', $shortlink, $text, $title);
		$output = $before . $output . $after;
	}
	
	return $output;
}

add_action('admin_init', 'wpshorturl_init' );
add_action('admin_menu', 'wpshorturl_add_page');

// Init plugin options to white list our options
function wpshorturl_init(){
	register_setting( 'wpshorturl_options', 'wpshorturl_domain' );
}

// Add menu page
function wpshorturl_add_page() {
	add_options_page('wpshorturl options', 'wpshorturl', 'manage_options', 'wpshorturl_options', 'wpshorturl_do_page');
}

// Draw the menu page itself
function wpshorturl_do_page() {
	?>
	<div class="wrap">
		<h2>wpshorturl options</h2>
		<form method="post" action="options.php">
			<?php settings_fields('wpshorturl_options'); ?>
			<?php $option = get_option('wpshorturl_domain'); ?>
			<table class="form-table">
				<tr valign="top">
					<th scope="row">Select URL shortener domain</th>
					<td>
						<select name="wpshorturl_domain">
							<option <?= $option == '0k.lv' ? 'selected':''?>>0k.lv</option>
							<option <?= $option == '2my.site' ? 'selected':''?>>2my.site</option>
						</select>
				</tr>
			</table>
			<p class="submit">
				<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
			</p>
		</form>
	</div>
	<?php
}

