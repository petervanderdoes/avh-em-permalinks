module.exports = (grunt) ->

	# Project configuration
	grunt.initConfig
		pkg: grunt.file.readJSON('package.json')

		wp_deploy:
			default:
				options:
					plugin_slug: '<%= pkg.name %>'
					build_dir: 'release/svn/'
					assets_dir: 'assets/'

		clean:
			release: [
				'release/<%= pkg.version %>/'
				'release/svn/'
			]
			js: [
				'js/*.js'
				'!js/*.min.js'
				'js/*.src.coffee'
				'js/*.js.map'
				'!js/*.min.js.map'
			]

		copy:
			main:
				src: [
					'**'
					'!node_modules/**'
					'!release/**'
					'!assets/**'
					'!.git/**'
					'!.sass-cache/**'
					'!js/**/*.src.coffee'
					'!img/src/**'
					'!Gruntfile.*'
					'!package.json'
					'!.gitignore'
					'!.gitmodules'
					'!tests/**'
					'!bin/**'
					'!.travis.yml'
					'!phpunit.xml'
				]
				dest: 'release/<%= pkg.version %>/'
			svn:
				cwd: 'release/<%= pkg.version %>/'
				expand: yes
				src: '**'
				dest: 'release/svn/'

		compress:
			default:
				options:
					mode: 'zip'
					archive: './release/<%= pkg.name %>.<%= pkg.version %>.zip'
				expand: yes
				cwd: 'release/<%= pkg.version %>/'
				src: [ '**/*' ]
				dest: '<%= pkg.name %>/'

	# Load other tasks
	grunt.loadNpmTasks 'grunt-contrib-clean'
	grunt.loadNpmTasks 'grunt-contrib-copy'
	grunt.loadNpmTasks 'grunt-contrib-compress'
	grunt.loadNpmTasks 'grunt-text-replace'
	grunt.loadNpmTasks 'grunt-wp-deploy'

	# Default task
	grunt.registerTask 'default', [
	]

	# Build task
	grunt.registerTask 'build', [
		'default'
		'clean'
		'copy:main'
		#'compress' # Can comment this out for WordPress.org plugins
	]

	# Prepare a WordPress.org release
	grunt.registerTask 'release:prepare', [
		'copy:svn'
	]

	# Deploy out a WordPress.org release
	grunt.registerTask 'release:deploy', [
		'wp_deploy'
	]

	# WordPress.org release task
	grunt.registerTask 'release', [
		'release:prepare'
		'release:deploy'
	]

	grunt.util.linefeed = '\n'
