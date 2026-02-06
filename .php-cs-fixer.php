<?php

$finder = ( new PhpCsFixer\Finder() )
	->in( __DIR__ )
	->exclude( 'vendors' )
	->exclude( 'node_modules' );
$rules  = array(
	'phpdoc_order'                      => array(
		'order' => array(
			'package', 'since', 'deprecated',
			'see', 'link', 'global',
			'var', 'param',
			'throws', 'return',
		),
	),
	'phpdoc_scalar'                     => true,
	'phpdoc_trim'                       => true,
	'phpdoc_var_without_name'           => true,
	'phpdoc_indent'                     => true,
	'phpdoc_separation'                 => true,
	'align_multiline_comment'           => true,
	'short_scalar_cast'                 => true,
	'standardize_not_equals'            => true,
	'echo_tag_syntax'                   => true,
	'semicolon_after_instruction'       => true,
	//'no_useless_else'                   => true,
	//'no_superfluous_elseif'             => true,
	'phpdoc_types_order'                => array(
		'null_adjustment' => 'always_last',
	),
	'multiline_comment_opening_closing' => true,
);

$config = new PhpCsFixer\Config();
$config->setRules( $rules );

return $config->setFinder( $finder );

// Maybe include these.
//
// 'phpdoc_align'   => true,
// 'phpdoc_summary' => true,
// 'visibility_required' => true,
// 'return_assignment' => true,
// 'static_lambda' => true,
