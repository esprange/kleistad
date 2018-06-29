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
	'target_php_version'              => null,
	'quick_mode'                      => false,
	'backward_compatibility_checks'   => true,


	// A list of directories that should be parsed for class and
	// method information. After excluding the directories
	// defined in exclude_analysis_directory_list, the remaining
	// files will be statically analyzed for errors.
	'directory_list'                  => [
		"\\xampp\\htdocs\\wordpress",
		// 'vendor',
		// 'includes',
		// 'admin',
		// 'public',
	],

	// A directory list that defines files that will be excluded
	// from static analysis, but whose class and method
	// information should be included.
	'exclude_analysis_directory_list' => [
		"\\xampp\\htdocs\\wordpress",
		"\\xampp\\htdocs\\wordpress\\wp-content\\plugins\\kleistad\\vendor\\",
		"\\xampp\\htdocs\\wordpress\\wp-content\\plugins\\kleistad\\tests\\",
//		"\\xampp\\htdocs\\",
	],
	'include_analysis_directory_list' => [
	 	"\\xampp\\htdocs\\wordpress\\wp-content\\plugins\\kleistad",
	],
	'exclude_file_list' => [
		'bin/',
		'node_modules/',
		'tests/',
		'tmp/',
		'vendor/',
		'.phan/',
		'.vscode/',
	],

	// Add any issue types (such as 'PhanUndeclaredMethod')
    // to this black-list to inhibit them from being reported.
    'suppress_issue_types' => [
        // 'PhanUndeclaredMethod',
        // 'PhanUndeclaredClassMethod',
        // 'PhanUndeclaredClassConstant',
		// 'PhanUndeclaredFunction',
		'PhanRedefinedExtendedClass',
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
