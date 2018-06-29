module.exports = function( grunt ) {

	'use strict';
	var banner = '/**\n * <%= pkg.homepage %>\n * Copyright (c) <%= grunt.template.today("yyyy") %>\n * This file is generated automatically. Do not edit.\n */\n';
	var path = require('path');

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
			'using-router': {
				router: function (filepath) {
					return 'kleistad/' + filepath;
				  },
				src: [
					'*.php',
					'README.txt',
					'README.MD',
					'LICENSE.txt',
					'public/**/*',
					'admin/**/*',
					'includes/**/*',
					'vendor/**/*',
				],
				dest: '//fileserver/web/kleistad_plugin/kleistad.zip'
			}
		},

		version: {
			plugin: {
				options: {
					prefix: 'Version\\s*'
				},
				src: [ 'kleistad.php' ]
			},
			readme: {
				options: {
					prefix: 'Stable tag\\s*'
				},
				src: [ 'README.txt' ]
			}
		}
	});

	grunt.loadNpmTasks( 'grunt-wp-readme-to-markdown' );
	grunt.registerTask( 'readme', ['wp_readme_to_markdown'] );
	grunt.loadNpmTasks( 'grunt-checkwpversion' );
	grunt.registerTask( 'checkversion', ['checkwpversion'] );
	grunt.loadNpmTasks( 'grunt-zip' );
	grunt.loadNpmTasks( 'grunt-version' );
	grunt.util.linefeed = '\n';

};
