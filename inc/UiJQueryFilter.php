<?php

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

public static function LoadYaml($file) {
	if(extension_loaded('yaml')) {
		return yaml_parse_file($file);
	} else {
		return Spyc::YAMLLoad($file);
	}
}
public static function ParseYaml($yaml) {
	if(extension_loaded('yaml')) {
		return yaml_parse($yaml);
	} else {
		return Spyc::YAMLLoadString($yaml);
	}
}
public static function EncodeYaml($array) {
	if(extension_loaded('yaml')) {
		return yaml_emit($array);
	} else {
		return Spyc::YAMLDump($array);
	}
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
	foreach ($form as $key => $imput) {
		if(!is_array($imput))
			break;
		$form[$key]['title'] = $imput['title'];
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
			if (isset($imput['key'])) {
				$form[$key]['name'] = $imput['key'];
			} elseif (isset($imput['tax'])) {
				$form[$key]['name'] = $imput['tax'];
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
			if(isset(static::$schema[$subkey])) {
				switch (static::$schema[$subkey]['type']) {
					case 'string':
						$input[$key][$subkey] = $value = (string) $value;
						break;
					case 'int':
						$input[$key][$subkey] = $value = (int) $value;
						break;
					case 'bool':
						$input[$key][$subkey] = $value = (bool) $value;
						break;
					case 'array':
						$input[$key][$subkey] = $value = (array) $value;
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
			if (isset(static::$schema[$key]['allowed'])) {
				if (!in_array($value, static::$schema[$key]['allowed']) && !array_key_exists($value, static::$schema[$key]['allowed'])) {
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

					case 'text':
						$parrice = $this->QueryParricide($data, $imput, $form);
						if(!is_null($parrice))
							$args['meta_query'][] = array(
								'key'     => $imput['key'],
								'value'   => $parrice,
								'compare' => 'LIKE',
								'type'    => 'string',
							);
						break;

					default:
						$parrice = $this->QueryParricide($data, $imput, $form);
						if(!is_null($parrice))
							$args['meta_query'][] = array(
								'key'     => $imput['key'],
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