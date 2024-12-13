<?php return array (
  'title' => 
  array (
    'type' => 'string',
  ),
  'type' => 
  array (
    'type' => 'string',
    'allowed' => 
    array (
      'list' => 
      array (
        'tax_query' => 
        array (
          'operator' => 'IN',
        ),
      ),
      'range' => 
      array (
        'meta_query' => 
        array (
          'compare' => 'BETWEEN',
        ),
      ),
      'text' => 
      array (
        'meta_query' => 
        array (
          'compare' => 'LIKE',
        ),
      ),
      'color_list' => 
      array (
        'extends' => 'list',
      ),
    ),
  ),
  'options' => 
  array (
    'type' => 'arrays',
  ),
  'name' => 
  array (
    'type' => 'string',
  ),
  'min' => 
  array (
    'type' => 'int',
  ),
  'max' => 
  array (
    'type' => 'int',
  ),
  'step' => 
  array (
    'type' => 'int',
  ),
  'min_name' => 
  array (
    'type' => 'string',
  ),
  'max_name' => 
  array (
    'type' => 'string',
  ),
  'orientation' => 
  array (
    'type' => 'string',
    'allowed' => 
    array (
      0 => 'horizontal',
      1 => 'vertical',
    ),
  ),
  'collapse' => 
  array (
    'type' => 'bool',
  ),
  'classes' => 
  array (
    'type' => 'array',
  ),
); ?>