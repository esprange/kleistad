<?php

/**
 * This configuration will be read and overlaid on top of the
 * default configuration. Command line arguments will be applied
 * after this file is read.
 */
return [

	'symplify_ast'                    => true,
	// Supported values: '7.0', '7.1', '7.2', null.
	// If this is set to null,
	// then Phan assumes the PHP version which is closest to the minor version
	// of the php executable used to execute phan.
	'target_php_version'              => '7.2',
	'quick_mode'                      => true,
	'backward_compatibility_checks'   => true,
	'analyze_signature_compatibility' => true,
	'minimum_severity'                => 0,  // 0 is lowest, 10 is high
	'allow_missing_properties'        => false,
	'null_casts_as_any_type'          => false,
	'null_casts_as_array'             => false,
	'array_casts_as_null'             => false,
	'scalar_implicit_cast'            => false,
	'scalar_implicit_partial'         => [],


	'ignore_undeclared_variables_in_global_scope' => false,

	// A list of directories that should be parsed for class and
	// method information. After excluding the directories
	// defined in exclude_analysis_directory_list, the remaining
	// files will be statically analyzed for errors.
	'directory_list'                  => [
		'.',
		"\\referentie",
	],

	// A directory list that defines files that will be excluded
	// from static analysis, but whose class and method
	// information should be included.
	'exclude_analysis_directory_list' => [
		'vendor',
		'tests',
		"\\referentie",
	],

	// Add any issue types (such as 'PhanUndeclaredMethod')
	// to this black-list to inhibit them from being reported.
	'suppress_issue_types'            => [
		// 'PhanUndeclaredMethod',
		// 'PhanUndeclaredClassMethod',
		// 'PhanUndeclaredClassConstant',
		// 'PhanUndeclaredFunction',
		//'PhanRedefinedExtendedClass',
	],



	// A list of plugin files to execute.
	// See https://github.com/phan/phan/tree/master/.phan/plugins for even more.
	// (Pass these in as relative paths.
	// Base names without extensions such as 'AlwaysReturnPlugin'
	// can be used to refer to a plugin that is bundled with Phan)
	'plugins'                         => [
		// checks if a function, closure or method unconditionally returns.
		// can also be written as 'vendor/phan/phan/.phan/plugins/AlwaysReturnPlugin.php'
		'AlwaysReturnPlugin',
		// Checks for syntactically unreachable statements in
		// the global scope or function bodies.
		'UnreachableCodePlugin',
		'DollarDollarPlugin',
		'DuplicateArrayKeyPlugin',
		'PregRegexCheckerPlugin',
		'PrintfCheckerPlugin',
	],
];
