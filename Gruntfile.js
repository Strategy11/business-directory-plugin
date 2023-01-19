// vim: ts=2:sw=2
const path = require('path');
const fs = require('fs');
const glob = require('glob');
const _ = require('underscore');

module.exports = function( grunt ) {
	grunt.config.set('compress.version', '');

  grunt.wpbdp = {
    registered: {},
    registerModule: function( config ) {
      var basedir = config.path;
      var id      = config.id || path.basename(basedir).replace('business-directory-', '').replace('businessdirectory-themes/', '');

      this.registered[ id ] = config;

      var less_config    = {};
      var uglify_config  = {};
      var makepot_config = {};
      var potomo_config  = {};

      if ( ! _.isEmpty( config.less ) ) {
        config.less.forEach(function(g) {
          glob.sync( path.join( basedir, g ), {} ).forEach(function(f) {
            if ( f.endsWith( '.min.css' ) ) {
              return;
            }

            if ( ! f.endsWith( '.less' ) && ! f.endsWith( '.css' ) ) {
              return;
            }

            less_config[ f.replace( 'less/', '' ).replace( '.css', '.min.css' ).replace( '.less', '.min.css' ) ] = f;
          });
        });
      }

      if ( ! _.isEmpty( config.js ) ) {
        config.js.forEach(function(g) {
          glob.sync( path.join( basedir, g ), {ignore: ['../**/**.min.js', '**/*.min.js']} ).forEach(function(f) {
            uglify_config[ f.replace( '.js', '.min.js' ) ] = f;
          });
        });
      }

      if ( config.i18n || ! _.isEmpty( config.i18n ) ) {
        var textDomain = config.i18n.textDomain || 'wpbdp-' + id;
        var domainPath = path.join( basedir, config.i18n.domainPath || 'translations/' );

        if ( ! fs.existsSync( domainPath ) ) {
            domainPath = path.join( basedir, config.i18n.domainPath || 'languages/' );
        }

        if ( fs.existsSync( domainPath ) ) {
          makepot_config = {
            options: {
              cwd: basedir,
              domainPath: domainPath.replace(basedir, ''),
              potFilename: textDomain + '.pot',
              exclude: ['vendors/.*'],
              updatePoFiles: true
            }
          };
          potomo_config = {
            options: {
              poDel: false,
            },
            files: [{
              expand: true,
              cwd: domainPath,
              src: ['*.po'],
              dest: domainPath,
              ext: '.mo',
              nonull: true
            }]
          };
        }
      }

      if ( ! _.isEmpty( less_config ) ) {
        grunt.config.set( 'less.' + id, {options: {cleancss: false, compress: true, strictImports: true}, files: less_config} );
        grunt.config.set( 'watch.' + id + '_less', {
          files: [path.join(basedir, '**/*.less'), path.join(basedir, '**/**/*.less'), path.join(basedir, '**/*.css'), '!' + path.join(basedir, 'vendors/**/*'), '!' + path.join(basedir, '**/*.min.css'), '!' + path.join(basedir, 'assets/vendor/**/*')],
          tasks: [ 'less:' + id ]
        } );
      }

      if ( ! _.isEmpty( uglify_config ) ) {
        grunt.config.set( 'uglify.' + id, {options: { mangle: false }, files: uglify_config} );
        grunt.config.set( 'watch.' + id + '_js', {
          files: [path.join(basedir, '**/*.js'), '!' + path.join(basedir, 'vendors/**/*'), '!' + path.join(basedir, '**/*.min.js'), '!' + path.join(basedir, 'assets/vendor/**/*')],
          tasks: [ 'uglify:' + id ]
        } );
      }

      if ( ! _.isEmpty( makepot_config ) ) {
        grunt.config.set( 'makepot.' + id, makepot_config );
        grunt.config.set( 'potomo.' + id, potomo_config );
      }

      // Compress config.
      grunt.config.set( 'compress.' + id, {
        options: {
          archive: '../' + path.basename(basedir) + '-<%= compress.version %>.zip',
          mode: 'zip'
        },
        expand: true,
        cwd: basedir,
		dest: path.basename(basedir),
        src: [
			'**/*', '!**/*~', '!**/**.less', '!**/tests/**', '!**/**/less',
			'!**/.*', '!**/phpcs.xml', '!**/phpunit.xml', '!**/composer.json',
			'!**/package.json', '!**/package-lock.json', '!**/node_modules/**',
			'!**/*.md', '!**/*.yml', '!**/zip-cli.php',
			'!**/stubs.php', '!**phpstan.**'
		]
      } );

	  grunt.config.set( 'replace.setversion-' + id, {
		  src: [
			  basedir + '/business-directory-' + id + '.php',
			  basedir + '/includes/class-wpbdp.php',
			  basedir + '/classes/class-wpbdp-premium-module.php',
			  basedir + '/includes/models/*module.php',
			  basedir + '/theme.json',
			  basedir + '/themes/default/theme.json'
		  ],
		  overwrite: true,
		  replacements: [
			  {
				  from: /Version:(\s)*(\d+\.)(\d+\.)?(\*|\d+)?([\da-z-A-Z-]+(?:\.[\da-z-A-Z-]+)*)?(\b)?/g,
				  to: 'Version: <%= compress.version %>'
			  },
			  {
				  from: /(\b)*\$this\-\>version(\s)*= \'(\d+\.)(\d+\.)?(\*|\d+)?([\da-z-A-Z-]+(?:\.[\da-z-A-Z-]+)*)?\'/g,
				  to: '$this->version = \'<%= compress.version %>\''
			  },
			  {
				  from: /\"version\"\:(\s)* \"(\d+\.)(\d+\.)?(\*|\d+)?([\da-z-A-Z-]+(?:\.[\da-z-A-Z-]+)*)?\"/g,
				  to: '"version": "<%= compress.version %>"'
			  },
			  {
				  from: /\$version(\s)*\= \'(\d+\.)(\d+\.)?(\*|\d+)?([\da-z-A-Z-]+(?:\.[\da-z-A-Z-]+)*)?\'/g,
				  to: '$version = \'<%= compress.version %>\''
			  },
			  {
				  from: /define\( \'WPBDP_VERSION\', \'(\d+\.)(\d+\.)?(\*|\d+)?([\da-z-A-Z-]+(?:\.[\da-z-A-Z-]+)*)?\'/g,
				  to: 'define( \'WPBDP_VERSION\', \'<%= compress.version %>\''
			  }
		  ]
	  });

	  grunt.config.set( 'replace.stabletag-' + id, {
		  src: [
			  basedir +'/README.txt',
			  basedir + '/README.TXT'
		  ],
		  overwrite: true,
		  replacements: [
			  {
				  from: /Stable tag:\ .*/g,
				  to: 'Stable tag: <%= compress.version %>'
			  }
		  ]
	  });

	  grunt.config.set( 'replace.comment-' + id, {
		  src: [
			  basedir + '/*.php',
			  basedir + '/**/*.php',
			  basedir + '/!node_modules/**',
			  basedir + '/!translations/**',
			  basedir + '/!languages/**',
			  basedir + '/!tests/**'
		  ],
		  overwrite: true,
		  replacements: [
			  {
				  from: 'since x.x',
				  to: 'since <%= compress.version %>'
			  }
		  ]
	  });
    }
  };

  var config = {
    pkg: grunt.file.readJSON('package.json'),
    less: {
    },
    uglify: {
    },
	replace: {},
    compress: {
    }
  };

  grunt.loadNpmTasks('grunt-contrib-watch');
  grunt.loadNpmTasks('grunt-contrib-clean');
  grunt.loadNpmTasks('grunt-contrib-less');
  grunt.loadNpmTasks('grunt-contrib-uglify');
  grunt.loadNpmTasks('grunt-wp-i18n');
  grunt.loadNpmTasks('grunt-potomo');
  grunt.loadNpmTasks('grunt-contrib-compress');
  grunt.loadNpmTasks('grunt-text-replace');

  grunt.initConfig( config );

  grunt.registerTask('default', []);
  grunt.registerTask('i18n', '', function(t) {
    grunt.task.run('makepot:' + t);
    grunt.task.run('potomo:' + t);
  });
  grunt.registerTask('minify', '', function(t) {
    // Release everything.
    if ( 'all' === t ) {
      Object.keys(grunt.wpbdp.registered).forEach(function(i) {
        grunt.task.run('minify:' + i);
      });

      return;
    }

    if ( 'undefined' != typeof grunt.config.get( 'less.' + t ) ) {
      grunt.task.run('less:' + t);
    }

    if ( 'undefined' != typeof grunt.config.get( 'uglify.' + t ) ) {
      grunt.task.run('uglify:' + t);
    }
  });

  grunt.registerTask('setversion', function(t, v){
  	grunt.config.set('compress.version', v);

  	grunt.task.run('replace:setversion-' + t );

	if ( ! v.includes('b') ) {
		// Is stable version.
		grunt.task.run('replace:stabletag-' + t );
		grunt.task.run('replace:comment-' + t );
	}
  });

  grunt.registerTask('release', function(t, v) {
    // Release everything.
    if ( 'all' === t || 'undefined' === typeof t ) {
      Object.keys(grunt.wpbdp.registered).forEach(function(i) {
        grunt.task.run('release:' + i);
      });

      return;
    }

	if ( t === 'core' ) {
		t = 'plugin';
	}

    if ( 'undefined' === typeof grunt.config.get( 'compress.' + t ) ) {
      return;
    }

	grunt.config.set('compress.version', v);

	grunt.task.run('setversion:' + t + ':' + v );
    grunt.task.run('minify:' + t);
	grunt.task.run('i18n:' + t);
    grunt.task.run('compress:' + t );
  });

  // Core.
  grunt.wpbdp.registerModule({
    path: '../business-directory-plugin',
    less: [
      'assets/css/less/debug.less',
      'assets/css/less/widgets.less',
      'assets/css/less/wpbdp.less',
      'assets/css/less/admin.less',
      'assets/css/less/admin-manual-upgrade.less',
      'assets/css/less/admin-csv-import.less',
      'assets/css/less/admin-export.less',
      'assets/css/less/admin-listing-metabox.less'
    ],
    js: [
      'assets/js/*.js',
      'assets/vendor/jquery-breakpoints/jquery-breakpoints.js',
    ],
    i18n: {textDomain: 'business-directory-plugin', domainPath: 'languages/'}
  });

  // Premium modules.
  grunt.wpbdp.registerModule({path: '../business-directory-2checkout', js: [], i18n: true});
  grunt.wpbdp.registerModule({path: '../business-directory-attachments', js: ['resources/*.js'], i18n: true});
  grunt.wpbdp.registerModule({path: '../business-directory-categories', less: ['resources/*.css'], js: ['resources/*.js'], i18n: true});
  grunt.wpbdp.registerModule({path: '../business-directory-claim-listings', less: ['resources/*.less'], js: ['resources/*.js'], i18n: true});
  grunt.wpbdp.registerModule({path: '../business-directory-discount-codes', less: ['resources/*.less'], js: ['resources/*.js'], i18n: true});
  grunt.wpbdp.registerModule({path: '../business-directory-featured-levels', less: ['resources/*.css'], js: ['resources/*.js'], i18n: true});
  grunt.wpbdp.registerModule({path: '../business-directory-attachments', less: ['resources/*.css'], js: ['resources/*.js'], i18n: true});
  grunt.wpbdp.registerModule({path: '../business-directory-googlemaps', less: ['resources/*.css'], js: ['resources/*.js'], i18n: true});
  grunt.wpbdp.registerModule({path: '../business-directory-payfast', i18n: true});
  grunt.wpbdp.registerModule({path: '../business-directory-paypal', i18n: true});
  grunt.wpbdp.registerModule({path: '../business-directory-ratings', less: ['resources/*.css'], js: ['resources/*.js'], i18n: true});
  grunt.wpbdp.registerModule({path: '../business-directory-regions', less: ['resources/css/*.css'], js: ['resources/js/*.js'], i18n: true});
  grunt.wpbdp.registerModule({path: '../business-directory-stripe', js: ['resources/*.js'], i18n: true});
  grunt.wpbdp.registerModule({path: '../business-directory-zipcodesearch', less: ['resources/*.css'], js: ['resources/*.js'], i18n: true});
  grunt.wpbdp.registerModule({path: '../business-directory-premium', less: ['resources/*.css'], js: ['resources/*.js'], i18n: {'textDomain':'wpbdp-pro'}});

  // Custom modules.
  grunt.wpbdp.registerModule({path: '../business-directory-migrate', less: [], js: ['js/*.js'], i18n: true});

  
  // Themes
  grunt.wpbdp.registerModule({path: './themes/default', less: ['assets/*.css'], js: [], i18n: true});
  grunt.wpbdp.registerModule({path: '../../businessdirectory-themes/business-card', less: ['assets/*.css'], js: [], i18n: true});
  grunt.wpbdp.registerModule({path: '../../businessdirectory-themes/elegant-business', less: ['assets/*.css'], js: [], i18n: true});
  grunt.wpbdp.registerModule({path: '../../businessdirectory-themes/modern-business', less: ['assets/*.css'], js: [], i18n: true});
  grunt.wpbdp.registerModule({path: '../../businessdirectory-themes/mobile-compact', less: ['assets/*.css'], js: [], i18n: true});
  grunt.wpbdp.registerModule({path: '../../businessdirectory-themes/tabbed-business', less: ['assets/*.css'], js: [], i18n: true});
  grunt.wpbdp.registerModule({path: '../../businessdirectory-themes/modern-filtered', less: ['assets/*.css'], js: [], i18n: {'textDomain':'wpbdp-modern-filtered'}});
  grunt.wpbdp.registerModule({path: '../../businessdirectory-themes/elegant-grid', less: ['assets/*.css'], js: [], i18n: true});
  grunt.wpbdp.registerModule({path: '../../businessdirectory-themes/restaurant', less: ['assets/*.css'], js: [], i18n: true});
};
