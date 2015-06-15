<?php

class JQueryFilterWidget extends WP_Widget {
function __construct() {
	parent::__construct(
		'j_query_filter_widget', // Base ID
		__('Query Filter', 'j_query_filter'), // Name
		array( 'description' => __('Query filters widget', 'j_query_filter') ) // Args
	);
}

protected $form = array (
	array (
		'id' => 'title',
		'description' => 'Title',
		'default' => 'Filter results',
		'type' => 'text'
	),
	array (
		'id' => 'post_type',
		'description' => 'Post Type',
		'default' => 'post',
		'type' => 'select',
		'params_callback' => array(
			'static::getPostTypes',
		),
	),
	// array (
	// 	'id' => 'results_page',
	// 	'description' => 'Results page',
	// 	'default' => null,
	// 	'type' => 'pages'
	// ),
	// array (
	// 	'id' => 'items_page',
	// 	'description' => 'All items page (when no filters are selected)',
	// 	'default' => -1,
	// 	'type' => 'pages',
	// 	'params' => array(
	// 		'show_option_no_change' => 'Same as results page',
	// 	),
	// ),
	array (
		'id' => 'filters',
		'description' => 'Filtering parameters',
		'default' => array(
			array(
				'title' => 'Categories',
				'type'  => 'list',
				'source'=> 'tax',
				'tax'   => 'category',
			),
			array(
				'title' => 'Tags',
				'type'  => 'list',
				'source'=> 'tax',
				'tax'   => 'post_tag',
			),
		),
		'type' => 'textarea',
		'parser' => 'yaml',
	),
);


/**
 * Front-end display of widget.
 *
 * @see WP_Widget::widget()
 *
 * @param array $args     Widget arguments.
 * @param array $instance Saved values from database.
 */
public function widget( $args, $instance ) {
	echo $args['before_widget'];
	if ( ! empty( $instance['title'] ) ) {
		echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ). $args['after_title'];
	}

	// var_dump([$args, $instance]);

	// echo '<div class=".filters-list"><form id="filters-form"><ul class="filters-list"></ul></form></div>';

	echo $args['after_widget'];
}

/**
 * Back-end widget form.
 */
public function form( $instance ) {

	foreach ($this->form as $input) {
		if (isset ($instance[$input['id']]))
			$value = $instance[$input['id']];
		else
			$value = $input['default'];

		$class = '';
		$checked = 0;
		if ($input['type'] != 'checkbox')
			$class = 'widefat';
		else {
			$checked = $value;
			$value = 1;
			$class = 'checkbox';
		}
		if(isset($input['parser'])) {
			switch ($input['parser']) {
				case 'yaml':
					$value = UiJQueryFilter::EncodeYaml($value);
				break;
			}
		}
		$params = (isset($input['params'])) ?
			$input['params'] :
			(isset($input['params_callback']) && is_callable($input['params_callback'][0])) ?
				call_user_func_array($input['params_callback'][0],
					(isset($input['params_callback'][1]) && is_array($input['params_callback'][1])) ?
						$input['params_callback'][1] :
						array()
				):
				array();

		printf('<p><label for="%s">%s:</label>', $this->get_field_id($input['id']), $input['description']);
		switch ($input['type']) {
			case 'pages':
				echo "<div class='$class'>";
				wp_dropdown_pages(array_merge(array(
					'selected' => $value,
					'name'     => $this->get_field_name($input['id']),
					'id'       => $this->get_field_id($input['id']),
				), $params));
				echo '</div>';
			break;
			case 'select':
				printf('<select class="%s" id="%s" name="%s">', $class, $this->get_field_id($input['id']), $this->get_field_name($input['id']));
					foreach ($params as $slug => $name) {
						printf('<option value="%s" %s>%s</option>', $slug, selected($value, $slug, false), $name);
					}
				print ('</select>');
			break;
			case 'textarea':
				printf('<textarea class="%s" id="%s" name="%s" rows=20>%s</textarea></p>', $class, $this->get_field_id($input['id']), $this->get_field_name($input['id']), $value);
			break;
			case 'custom':
				if(isset($input['callback']) && is_callable($input['callback']))
					$input['callback']();
			break;
			default:
				printf('<input class="%s" id="%s" name="%s" type="%s" value="%s" %s/></p>', $class, $this->get_field_id($input['id']), $this->get_field_name($input['id']), $input['type'], $value, checked($checked, 1, false));
			break;
		}
	}
}

/**
 * Sanitize widget form values as they are saved.
 */
public function update( $new_instance, $old_instance ) {
	$instance = array();

	foreach($this->form as $input) {
		$instance[$input['id']] = ( ! empty( $new_instance[$input['id']] ) ) ? strip_tags( $new_instance[$input['id']] ) : '';
		if($input['type'] == 'number')
			$instance[$input['id']] = (int) $instance[$input['id']];
		if($input['type'] == 'checkbox')
			$instance[$input['id']] = (bool) $instance[$input['id']];
		if(isset($input['parser'])) {
			switch ($input['parser']) {
				case 'yaml':
					$instance[$input['id']] = UiJQueryFilter::ParseYaml($instance[$input['id']]);
				break;
			}
		}
	}

	return $instance;
}

public static function getPostTypes() {
	$post_types = get_post_types(array(
		'public' => true,
		'publicly_queryable' => true,
	), 'objects');
	$return = array();
	foreach ($post_types as $slug => $pt) {
		foreach (array(
			'public',
			'publicly_queryable',
			// 'has_archive'
		) as $value) {
			if(!$pt->$value) {
				continue 2;
			}
		}
		$return[$slug] = $pt->labels->name;
	}
	// var_dump($post_types);
	// var_dump($return);
	return $return;
} 

} // class Foo_Widget