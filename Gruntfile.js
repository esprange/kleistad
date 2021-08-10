module.exports = function( grunt ) { // jshint ignore:line

	'use strict';

	// Project configuration.
	grunt.initConfig(
		{

			pkg: grunt.file.readJSON( 'package.json' ),

			checkwpversion: {
				options:{
					readme: 'readme.txt',
					plugin: 'kleistad.php'
				},
				check: { // Check plug-in version and stable tag match.
					version1: 'plugin',
					version2: 'readme',
					compare: '=='
				},
				check2: { // Check plug-in version and package.json match.
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
		}
	);

	grunt.util.linefeed = '\n';
	grunt.loadNpmTasks( 'grunt-contrib-cssmin' );
	grunt.loadNpmTasks( 'grunt-contrib-uglify' );
	grunt.loadNpmTasks( 'grunt-wp-readme-to-markdown' );
	grunt.loadNpmTasks( 'grunt-composer' );
	grunt.loadNpmTasks( 'grunt-checkwpversion' );
	grunt.loadNpmTasks( 'grunt-zip' );
	grunt.loadNpmTasks( 'grunt-shell' );
	grunt.registerTask(
		'oplevering',
		[
			'checkwpversion',
			'wp_readme_to_markdown',
			'uglify',
			'cssmin',
			'composer:update:no-autoloader:no-dev',
			'composer:dump-autoload:optimize',
			'zip',
			'shell:command',
			'composer:update'
		]
	);

};
