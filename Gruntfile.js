module.exports = function( grunt ) {

	'use strict';
	var banner = '/**\n * <%= pkg.homepage %>\n * Copyright (c) <%= grunt.template.today("yyyy") %>\n * This file is generated automatically. Do not edit.\n */\n';
	// Project configuration
	grunt.initConfig( {

		pkg: grunt.file.readJSON( 'package.json' ),

		wp_readme_to_markdown: {
			your_target: {
				files: {
					'README.md': 'readme.txt'
				}
			},
		},

		checkwpversion: {
			options:{
				readme: 'readme.txt',
				plugin: 'kleistad.php',
			},
			check: { //Check plug-in version and stable tag match
				version1: 'plugin',
				version2: 'readme',
				compare: '==',
			},
			check2: { //Check plug-in version and package.json match
				version1: 'plugin',
				version2: '<%= pkg.version %>',
				compare: '==',
			}
		},

		zip: {
			'long-format': {
				src: [ 'public/**/*', 'admin/**/*', 'includes/**/*', 'vendor/**/*', '*.php', 'README.MD', 'LICENSE.txt' ],
				dest: 'tmp/kleistad.zip'
			}
		}
	});

	grunt.loadNpmTasks( 'grunt-wp-readme-to-markdown' );
	grunt.registerTask( 'readme', ['wp_readme_to_markdown'] );
	grunt.loadNpmTasks( 'grunt-checkwpversion' );
	grunt.registerTask( 'checkversion', ['checkwpversion'] );
	grunt.loadNpmTasks( 'grunt-zip' );
	grunt.util.linefeed = '\n';

};
