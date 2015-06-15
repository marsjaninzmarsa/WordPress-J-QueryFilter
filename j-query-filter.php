<?php
/*
Plugin Name: J-QueryFilter
Plugin URI: http://niewiarowski.it/
Description: Advanced taxonomy and meta post filtering plugin.
Author: Jakub 'marsjaninzmarsa' Niewiarowski
Author URI: http://niewiarowski.it/
Version: 0.0.1
License: GPL v3
*/

if(!extension_loaded('yaml') && !class_exists('Spyc')) {
	require_once "spyc/spyc.php";
}

spl_autoload_register(function($class) {
	if(is_file($file = dirname(__FILE__) . "/inc/$class.php")) {
		include_once $file;
	}
});


// Registering Widget
add_action('widgets_init', function() {
	return register_widget("JQueryFilterWidget");
});

add_action('wp_register_sidebar_widget', function($widget) {
	if($widget['classname'] == 'widget_j_query_filter_widget') {
		$number   = $widget['params'][0]['number'];
		$settings = $widget['callback'][0]->get_settings()[$number];
		$sidebarQueryFilter = new UiJQueryFilter($settings);
	}
});


// $sidebarQueryFilter->form = UiJQueryFilter::LoadYaml(dirname(__FILE__) . '/form.yaml');

add_action( 'wp_enqueue_scripts', 'enqueue_and_register_j_query_filter' );
add_action( 'wp_ajax_nopriv_sidebar_query_filter', 'j_query_filter' );
add_action( 'wp_ajax_sidebar_query_filter', 'j_query_filter' );

function enqueue_and_register_j_query_filter(){

	/* jQuery Deserialize Script */
	wp_register_script('jquery-deserialize', plugins_url('js/jquery.deserialize.js', __FILE__), array('jquery'));
	wp_enqueue_script('jquery-deserialize');

	/* PURL */
	wp_register_script('purl', plugins_url('js/purl.js', __FILE__));
	wp_enqueue_script('purl');
	/* jQuery Color */
	wp_enqueue_script('jquery-color');
	/* jQueryFilter */
	wp_register_script( 'j-query-filter', plugins_url('js/j-query-filter.js', __FILE__), array('jquery-ui-slider', 'jquery-form', 'jquery-deserialize', 'jquery-color', 'purl') );
	global $sidebarQueryFilter;
	// wp_localize_script( 'j-query-filter', 'sidebar_query_filter', $sidebarQueryFilter->UiContentFilterGenerate() );
	// wp_localize_script( 'j-query-filter', 'sidebar_query_filter_ajax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ), 'search_url' => get_permalink() ) );
	// wp_enqueue_script( 'j-query-filter' );
}

function wyszukiwarka_ofert($data) {
	global $sidebarQueryFilter, $wp_query;
	if (!empty($data)) {
		$args = array(
			'post_type' => 'bm_work_offer',
			'posts_per_page' => 16,
			'offset' => (isset($_REQUEST['offset']) && $_REQUEST['offset']) ? $_REQUEST['offset'] : 0,
			'suppress_filters' => true,
			'post_status' => (in_array('administrator', wp_get_current_user()->roles)) ? 'any' : 'publish',
		);

		$args = $sidebarQueryFilter->QueryFilter($data, $args);

		$wp_query = new WP_Query($args);
		// echo '<pre>'; var_dump($wp_query); echo '</pre>';
		$wp_query->has_results = true;
	} else {
		$wp_query->has_results = false;
	}
}

function j_query_filter($args = false) {
	// die('dupa');
	global $wp_query;
	wyszukiwarka_ofert(($args)? $args: $_GET);
	global $post;
	foreach ($wp_query->posts as $key => $post) {
		get_template_part('theme-template-parts/content/content', 'offer-item-13');
		printf('<div hidden data-lp="%s"></div>', $key + get_query_var('offset') + 1);
	}
	if($wp_query->post_count && $wp_query->post_count <= $wp_query->query_vars['posts_per_page']) {
		print('<div hidden class="eot"></div>');
	}
	if(isset($_REQUEST['search']) && !$_REQUEST['offset'] && !$wp_query->posts) {
		printf('<div class="noResults">%s</div>', __('Brak wyników wyszukiwania', 'twentythirteen'));
	}
	// print('<pre>'); var_dump($wp_query); print('</pre>');
	// get_template_part('content', 'obrazy');
	if (defined('DOING_AJAX') && DOING_AJAX) exit;
}

add_action('parse_query', function($query) {
	if($query->is_main_query() && $query->is_post_type_archive() && in_array($pt = $query->query_vars['post_type'], UiJQueryFilter::GetFilteredPT())) {
		$query_vars = $query->query;
		$sidebarQueryFilter = UiJQueryFilter::GetFilterForPT($pt);
		$args = array(
			'post_type' => $pt,
			// 'posts_per_page' => 16,
			// 'offset' => (isset($_REQUEST['offset']) && $_REQUEST['offset']) ? $_REQUEST['offset'] : 0,
			'suppress_filters' => true,
			// 'post_status' => (in_array('administrator', wp_get_current_user()->roles)) ? 'any' : 'publish',
		);
		$query->query_vars = $sidebarQueryFilter->QueryFilter($_GET, $args);
		// var_dump($query);
	}
	return;
});