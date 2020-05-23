module.exports = function( grunt ) { // jshint ignore:line

	'use strict';

	// Project configuration
	grunt.initConfig( {

		pkg: grunt.file.readJSON( 'package.json' ),

		checkwpversion: {
			options:{
				readme: 'readme.txt',
				plugin: 'kleistad.php'
			},
			check: { //Check plug-in version and stable tag match
				version1: 'plugin',
				version2: 'readme',
				compare: '=='
			},
			check2: { //Check plug-in version and package.json match
				version1: 'plugin',
				version2: '<%= pkg.version %>',
				compare: '=='
			}
		},

		wp_readme_to_markdown: {
			your_target: {
				files: {
					'README.md': 'readme.txt'
				}
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
		},

		uglify: {
			dev: {
				options: {
					mangle: {
						reserved: ['jQuery']
					}
				},
				files: [{
					expand: true,
					src: [ '*.js', '!*.min.js' ],
					dest: 'public/js',
					cwd: 'public/js',
					rename: function( dst, src ) {
						return dst + '/' + src.replace( '.js', '.min.js' );
					}
				}]
			}
		},

		cssmin: {
			target: {
				files: [{
					expand: true,
					cwd: 'public/css',
					src: [ '*.css', '!*.min.css' ],
					dest: 'public/css',
					ext: '.min.css'
				}]
			}
		},

		clean : {
					google: [
						'vendor/google/apiclient-services/src/Google/Service/**/*',
						'!vendor/google/apiclient-services/src/Google/Service/Calendar/**',
						'!vendor/google/apiclient-services/src/Google/Service/Calendar.php'
					]
		},

		zip: {
			'using-router': {
				router: function( filepath ) {
					return 'kleistad/' + filepath;
				},
				src: [
					'kleistad.php',
					'README.txt',
					'LICENSE.txt',
					'public/**/*',
					'admin/**/*',
					'includes/**/*',
					'vendor/**/*'
				],
				dest: 'zip/kleistad.zip'
			}
		},
		
		shell: {
			command: 'ftp -i -s:plugin_upload.ftp'
		}
	} );

	grunt.util.linefeed = '\n';
	grunt.loadNpmTasks( 'grunt-rewrite' );
	grunt.loadNpmTasks( 'grunt-contrib-cssmin' );
	grunt.loadNpmTasks( 'grunt-contrib-uglify' );
	grunt.loadNpmTasks( 'grunt-contrib-clean' );
	grunt.loadNpmTasks( 'grunt-wp-readme-to-markdown' );
	grunt.loadNpmTasks( 'grunt-composer' );
	grunt.loadNpmTasks( 'grunt-checkwpversion' );
	grunt.loadNpmTasks( 'grunt-zip' );
	grunt.loadNpmTasks( 'grunt-shell' );
	grunt.loadNpmTasks( 'grunt-version' );
	grunt.registerTask( 'checkversion', ['checkwpversion'] );
	grunt.registerTask( 'readme', ['wp_readme_to_markdown'] );
	grunt.registerTask( 'oplevering',
		[
			'checkversion',
			'readme',
			'uglify',
			'cssmin',
			'composer:update:no-autoloader',
			'clean',
			'composer:dump-autoload',
			'zip',
			'shell:command'
		]
	);

};
