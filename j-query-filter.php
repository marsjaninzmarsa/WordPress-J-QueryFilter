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

if(!extension_loaded('yaml')) {
	require_once "spyc/spyc.php";
}

class UiJQueryFilter {

public $form = array();
protected static $schema;

function __construct() {
	if(!static::$schema) {
		static::$schema = include 'schema.php';
	}
}

public static function LoadYaml($file) {
	if(extension_loaded('yaml')) {
		return yaml_parse_file($file);
	} else {
		return Spyc::YAMLLoad($file);
	}
}

public function UiContentFilterGenerate() {
	$form = $this->form;
	foreach ($form as $key => $imput) {
		if(!is_array($imput))
			break;
		$form[$key]['title'] = __($imput['title'], 'twentythirteen');
		if ($imput['source'] == 'tax' && isset($imput['tax'])) {
			$form[$key]['options'] = get_terms($imput['tax']);
		} elseif ($imput['source'] == 'meta' && isset($imput['options']) && is_array($imput['options'])) {
			foreach ($imput['options'] as $subkey => $option) {
				$form[$key]['options'][$subkey]['name'] = __($option['name'], 'twentythirteen');
			}
		}
		if(!is_array($form[$key]['options'])) {
			unset($form[$key]);
		}
	}
	$form = $this->RefillForm($form);
	$form = $this->ReindentOptions($form);
	return $this->SanitizeOutput($form);
}

private function RefillForm($form) {
	foreach ($form as $key => $imput) {
		if (!isset($imput['name'])) {
			if (isset($imput['tax'])) {
				$form[$key]['name'] = $imput['tax'];
			} elseif (isset($imput['key'])) {
				$form[$key]['name'] = $imput['key'];
			}
		}
	}
	return $form;
}

private function ReindentOptions($form) {
	foreach ($form as $row=>$imput) {
		if(is_array($imput) && ($imput['type'] == 'list' || $imput['type'] == 'color_list')) {
			$parents=array();
			foreach($imput['options'] as $key=>$val){
				$val = (array) $val;
				if(@$val['parent']==0){
					$parents[$key]=@$val['term_id'];  
				}
				$imput['options'][$key] = $val;
			}
			// look for children and move them
			foreach($imput['options'] as $key=>$val){
				if(@$val['parent']<>0){
					// check if parent exists
					$tokey=array_search($val['parent'],$parents);
					if($tokey!==false){
						// move child
						$imput['options'][$tokey]['children'][] = $imput['options'][$key];
						unset($imput['options'][$key]);
					}
				}
			}
			$form[$row] = $imput;
		}
	}
	return $form;
}

private function SanitizeOutput($input) {
	foreach ($input as $key => $imput) {
		$imput = (array) $imput;
		foreach ($imput as $subkey => $value) {
			if(isset($this->schema[$subkey])) {
				switch ($this->schema[$subkey]['type']) {
					case 'string':
						$input[$key][$subkey] = $value = (string) $value;
						break;
					case 'int':
						$input[$key][$subkey] = $value = (int) $value;
						break;
					case 'arrays':
						$input[$key][$subkey] = $value = array_values($value);
						foreach ($value as $akey => $array) {
							$input[$key][$subkey][$akey] = $array = (array) $array;
						}
						break;
					default:
						$input[$key][$subkey] = $value = null;
						continue 2;
						break;
				}
			} else {
				$input[$key][$subkey] = null;
				continue;
			}
			if (isset($this->schema[$key]['allowed'])) {
				if (!in_array($value, $this->schema[$key]['allowed']) && !array_key_exists($value, $this->schema[$key]['allowed'])) {
					$input[$key][$subkey] = null;
					continue;
				}
			}
		}
		$imput = array_filter($imput);
	}
	return $input;
}

private function QueryParricide($data, $input, $form) {
	// print('<pre>'); var_dump($input); print('</pre>');
	// var_dump($data[$input['name']]);
	foreach ($data[$input['name']] as $key => $filter) {
		foreach ($input['options'] as $subkey => $option) {
			if($filter == $option['slug']) {
				// echo "key: $key, subkey: $subkey \n";
				foreach ($option['children'] as $row => $children) {
					// var_dump($filter);
					// var_dump($option);
					// var_dump($children);
					if(in_array($children['slug'], $data[$input['name']])) {
						// var_dump($data[$input['name']]);
						// var_dump($data[$input['name']][$key]);
						unset($data[$input['name']][$key]);
					}
				}
			}
		}
	}
	return $data[$input['name']];
}

public function QueryFilter($data, $args) {
	$form = $this->form;
	foreach ($form as $key => $imput) {
		if ($imput['source'] == 'tax' && isset($imput['tax'])) {
			$form[$key]['options'] = get_terms($imput['tax']);
		}
	}
	$form = $this->RefillForm($form);
	$form = $this->ReindentOptions($form);
	// print_r($form);
	foreach ($form as $key => $imput) {
		switch ($imput['source']) {
			case 'tax':
				if(!empty($data[$imput['name']])) {
					// var_dump($data[$imput['name']]);
					$terms = $this->QueryParricide($data, $imput, $form);
					// var_dump($terms);

					$args['tax_query'][] = array(
						'taxonomy' => $imput['tax'],
						'terms'    => $terms,
						'field'    => 'slug',
						'operator' => 'IN'
					);

				}
				break;
			case 'meta':
			// print('<pre>'); var_dump($imput); print('</pre>');
				switch ($imput['type']) {
					case 'range':
						if(strlen($data[$imput['min_name']])>0 || !empty($data[$imput['max_name']])) {
							$range = array(
								($data[$imput['min_name']]) ? $data[$imput['min_name']] : $imput['min'],
								($data[$imput['max_name']]) ? $data[$imput['max_name']] : $imput['max']
							);
							if($imput['overflow_max'] && $range[1] >= $imput['max']) {
								$args['meta_query'][] = array(
									'key'     => $imput['key'],
									'value'   => $range[0],
									'type'    => 'numeric',
									'compare' => '>='
								);
							} else {
								$args['meta_query'][] = array(
									'key'     => $imput['key'],
									'value'   => $range,
									'type'    => 'numeric',
									'compare' => 'BETWEEN'
								);
							}
						}
						break;
					
					default:
						$dupa = $this->QueryParricide($data, $imput, $form);
						if(!is_null($dupa))
							$args['meta_query'][] = array(
								'key'     => $imput['key'],
								'value'   => $dupa,
								'compare' => 'IN',
								// 'type'    => 'string',
							);
						break;
				}
				break;
		}
	}
	if(count($args['tax_query']) > 1) {
		$args['tax_query']['relation'] = 'AND';
	}
	if(count($args['meta_query']) > 1) {
		$args['meta_query']['relation'] = 'AND';
	}
	
	// if (!empty($data['sprzedane']) || !empty($data['wyroznione'])) {
	// 	$args['meta_query'] = array();
	// }
	
	// if (!empty($data['sprzedane']) && $data['sprzedane'] == 1) {
	// 	$args['meta_query'][] = array(
	// 		'key' => 'sprzedane',
	// 		'value' => 1,
	// 		'compare' => '='
	// 	);
	// }
	
	// if (!empty($data['wyroznione']) && $data['wyroznione'] == 1) {
	// 	$args['meta_query'][] = array(
	// 		'key' => 'wyroznione',
	// 		'value' => 1,
	// 		'compare' => '='
	// 	);
	// }
	
	if ($data['sort'] && $data['sortby']) {
		if ($data['sortby'] == 'asc' || $data['sortby'] == 'desc') {
			$args['orderby']  = 'meta_value_num';
			$args['meta_key'] = 'cena';
			$args['order'] = strtoupper($data['sortby']);
		}
	}
	/* else {
		$args['orderby']  = 'meta_value_num';
		$args['meta_key'] = 'cena';
		$args['order'] = 'asc';
	}*/

	// print('<pre>'); var_dump($args); print('</pre>');
	return $args;
}

/* public function GenerateAplacaForm() {
	$form    = $this->UiContentGenerate();
	$schema  = array('type' => 'object');
	$options = array();



	foreach ($form as $key => $option) {
		$schema['properties'][$option['name']] = array(

		);
	}
} */

}

$sidebarQueryFilter = new UiJQueryFilter;

$sidebarQueryFilter->form = UiJQueryFilter::LoadYaml(dirname(__FILE__) . '/form.yaml');

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
	wp_localize_script( 'j-query-filter', 'sidebar_query_filter', $sidebarQueryFilter->UiContentFilterGenerate() );
	wp_localize_script( 'j-query-filter', 'sidebar_query_filter_ajax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ), 'search_url' => get_permalink() ) );
	wp_enqueue_script( 'j-query-filter' );
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