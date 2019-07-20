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

		zip: {
			'using-router': {
				router: function( filepath ) {
					if ( -1 === filepath.search( 'vendor/google/apiclient-services/src/Google/Service' ) ) {
						return 'kleistad/' + filepath;
					}
					if ( -1 === filepath.search( 'Calendar' ) ) {
						return null;
					}
					return 'kleistad/' + filepath;
				},
				src: [
					'*.php',
					'README.txt',
					'LICENSE.txt',
					'public/**/*',
					'admin/**/*',
					'includes/**/*',
					'vendor/**/*'
				],
				dest: '//fileserver/web/kleistad_plugin/kleistad.zip'
			}
		}
	});

	grunt.loadNpmTasks( 'grunt-rewrite' );
	grunt.loadNpmTasks( 'grunt-contrib-cssmin' );
	grunt.loadNpmTasks( 'grunt-contrib-uglify' );
	grunt.loadNpmTasks( 'grunt-wp-readme-to-markdown' );
	grunt.registerTask( 'readme', ['wp_readme_to_markdown'] );
	grunt.loadNpmTasks( 'grunt-checkwpversion' );
	grunt.registerTask( 'checkversion', ['checkwpversion'] );
	grunt.loadNpmTasks( 'grunt-zip' );
	grunt.loadNpmTasks( 'grunt-version' );
	grunt.registerTask( 'oplevering', [ 'checkversion', 'readme', 'uglify', 'cssmin', 'zip' ] );
	grunt.util.linefeed = '\n';

};
