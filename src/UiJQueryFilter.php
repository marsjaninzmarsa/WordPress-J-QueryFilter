<?php
namespace Marsjaninzmarsa\WordPressQueryFilter;

class UiJQueryFilter {

public $form = array();
public $post_type;
public $style;
protected static $schema;
protected static $_instances = array();

function __construct($settings) {
	if(!static::$schema) {
		static::$schema = include 'schema.php';
	}
	$settings = array_merge(array(
		'post_type' => '',
		'filters'   => array()
	), $settings);
	$this->post_type = $settings['post_type'];
	$this->form      = $settings['filters'];
	$this->style     = ($settings['style']) ? 'default' : false;
	// var_dump($settings); die;
	static::$_instances[] = $this;
}

public static function GetInstances() {
	return static::$_instances;
}

public static function GetFilteredPT() {
	$pt = array();
	foreach (static::$_instances as $instance) {
		$pt[] = $instance->post_type;
	}
	return $pt;
}

public static function GetFilterForPT($pt) {
	foreach (static::$_instances as $key => $instance) {
		if($instance->post_type == $pt) {
			return $instance;
		}
	}
	return null;
}

public static function GetPageLinkBySlug($slug) {
	$page = get_page_by_path($slug);
	$id = @$page->ID;
	if(!$id) {
		return '';
	}
	if(function_exists('icl_object_id')) {
		$id = icl_object_id($id, 'page', true, ICL_LANGUAGE_CODE);
	}
	return get_page_link($id);
}

public function UiContentFilterGenerate() {
	$form = $this->form;
	foreach ($form as $key => $input) {
		if(!is_array($input))
			break;
		$form[$key]['title'] = $input['title'];
		if ($input['source'] == 'tax' && isset($input['tax'])) {
			$form[$key]['options'] = get_terms($input['tax']);
		} elseif ($input['source'] == 'meta' && isset($input['options']) && is_array($input['options'])) {
			foreach ($input['options'] as $subkey => $option) {
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
	foreach ($form as $key => $input) {
		if (!isset($input['name'])) {
			if (isset($input['key'])) {
				$form[$key]['name'] = $input['key'];
			} elseif (isset($input['tax'])) {
				$form[$key]['name'] = $input['tax'];
			}
		}
	}
	return $form;
}

private function ReindentOptions($form) {
	foreach ($form as $row=>$input) {
		if(is_array($input) && ($input['type'] == 'list' || $input['type'] == 'color_list')) {
			$parents=array();
			foreach($input['options'] as $key=>$val){
				$val = (array) $val;
				if(@$val['parent']==0){
					$parents[$key]=@$val['term_id'];  
				}
				$input['options'][$key] = $val;
			}
			// look for children and move them
			foreach($input['options'] as $key=>$val){
				if(@$val['parent']<>0){
					// check if parent exists
					$tokey=array_search($val['parent'],$parents);
					if($tokey!==false){
						// move child
						$input['options'][$tokey]['children'][] = $input['options'][$key];
						unset($input['options'][$key]);
					}
				}
			}
			$form[$row] = $input;
		}
	}
	return $form;
}

private function SanitizeOutput($output) {
	foreach ($output as $key => $input) {
		$input = (array) $input;
		foreach ($input as $subkey => $value) {
			if(isset(static::$schema[$subkey])) {
				switch (static::$schema[$subkey]['type']) {
					case 'string':
						$output[$key][$subkey] = $value = (string) $value;
						break;
					case 'int':
						$output[$key][$subkey] = $value = (int) $value;
						break;
					case 'bool':
						$output[$key][$subkey] = $value = (bool) $value;
						break;
					case 'array':
						$output[$key][$subkey] = $value = (array) $value;
					case 'arrays':
						$output[$key][$subkey] = $value = array_values($value);
						foreach ($value as $akey => $array) {
							$output[$key][$subkey][$akey] = $array = (array) $array;
						}
						break;
					default:
						$output[$key][$subkey] = $value = null;
						continue 2;
						break;
				}
			} else {
				$output[$key][$subkey] = null;
				continue;
			}
			if (isset(static::$schema[$key]['allowed'])) {
				if (!in_array($value, static::$schema[$key]['allowed']) && !array_key_exists($value, static::$schema[$key]['allowed'])) {
					$output[$key][$subkey] = null;
					continue;
				}
			}
		}
		$input = array_filter($input);
	}
	return $output;
}

private function QueryParricide($data, $input, $form) {
	// print('<pre>'); var_dump($input); print('</pre>');
	// var_dump($data[$input['name']]); die;
	if(isset($data[$input['name']])) {
		if(is_array($data[$input['name']])) {
			foreach ($data[$input['name']] as $key => $filter) {
				foreach ($input['options'] as $subkey => $option) {
					if($filter == $option['slug'] && isset($option['children']) && is_array($option['children'])) {
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
		}
		return $data[$input['name']];
	}
}

public function QueryFilter($data, $args) {
	$args = array_merge(array(
		'tax_query'  => array(),
		'meta_query' => array(),
	), $args);
	$data = array_merge(array(
		'sort'   => null,
		'sortby' => null,
	), $data);
	$form = $this->form;
	foreach ($form as $key => $input) {
		if ($input['source'] == 'tax' && isset($input['tax'])) {
			$form[$key]['options'] = get_terms($input['tax']);
		}
	}
	$form = $this->RefillForm($form);
	$form = $this->ReindentOptions($form);
	// print_r($form);
	foreach ($form as $key => $input) {
		switch ($input['source']) {
			case 'tax':
				if(!empty($data[$input['name']])) {
					// var_dump($data[$input['name']]);
					$terms = $this->QueryParricide($data, $input, $form);
					// var_dump($terms);

					$args['tax_query'][] = array(
						'taxonomy' => $input['tax'],
						'terms'    => $terms,
						'field'    => 'slug',
						'operator' => 'IN'
					);

				}
				break;
			case 'meta':
			// print('<pre>'); var_dump($input); print('</pre>');
				switch ($input['type']) {
					case 'range':
						if(strlen($data[$input['min_name']])>0 || !empty($data[$input['max_name']])) {
							$range = array(
								($data[$input['min_name']]) ? $data[$input['min_name']] : $input['min'],
								($data[$input['max_name']]) ? $data[$input['max_name']] : $input['max']
							);
							if($input['overflow_max'] && $range[1] >= $input['max']) {
								$args['meta_query'][] = array(
									'key'     => $input['key'],
									'value'   => $range[0],
									'type'    => 'numeric',
									'compare' => '>='
								);
							} else {
								$args['meta_query'][] = array(
									'key'     => $input['key'],
									'value'   => $range,
									'type'    => 'numeric',
									'compare' => 'BETWEEN'
								);
							}
						}
						break;

					case 'text':
						$parrice = $this->QueryParricide($data, $input, $form);
						if(!is_null($parrice))
							$args['meta_query'][] = array(
								'key'     => $input['key'],
								'value'   => $parrice,
								'compare' => 'LIKE',
								'type'    => 'string',
							);
						break;

					default:
						$parrice = $this->QueryParricide($data, $input, $form);
						if(!is_null($parrice))
							$args['meta_query'][] = array(
								'key'     => $input['key'],
								'value'   => $parrice,
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


}