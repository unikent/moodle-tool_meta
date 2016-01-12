module.exports = function(grunt) {
    require('jit-grunt')(grunt);

    var path = require('path'),
        tasks = {},
        cwd = process.env.PWD || process.cwd();

    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),
        jshint: {
            options: {jshintrc: '../../../.jshintrc'},
            files: ['**/amd/src/*.js']
        },
        uglify: {
            dynamic_mappings: {
                files: grunt.file.expandMapping(
                    ['**/src/*.js', '!**/node_modules/**'],
                    '',
                    {
                        cwd: cwd,
                        rename: function(destBase, destPath) {
                            destPath = destPath.replace('src', 'build');
                            destPath = destPath.replace('.js', '.min.js');
                            destPath = path.resolve(cwd, destPath);
                            return destPath;
                        }
                    }
                )
            }
        },
        less: {
            development: {
                options: {
                    compress: true,
                    yuicompress: true,
                    optimization: 2,
                    banner: '/*! <%= pkg.description %> <%= grunt.template.today("yyyy-mm-dd") %> */\n',
                    plugins: [
                        new (require('less-plugin-autoprefix'))({
                            browsers: ["last 2 versions"]
                        }),
                        new (require('less-plugin-clean-css'))({
                            advanced: true,
                            compatibility: 'ie8'
                        })
                    ]
                },
                files: {
                    "style/theme.css": "style/less/theme.less"
                }
            }
        }
    });

    tasks.shifter = function() {
       var  exec = require('child_process').spawn,
            done = this.async(),
            args = [],
            options = {
                recursive: true,
                watch: false,
                walk: false,
                module: false
            },
            shifter;

            args.push( path.normalize(__dirname + '/node_modules/shifter/bin/shifter'));

            // Determine the most appropriate options to run with based upon the current location.
            if (path.basename(cwd) === 'src') {
                // Detect whether we're in a src directory.
                grunt.log.debug('In a src directory');
                args.push('--walk');
                options.walk = true;
            } else if (path.basename(path.dirname(cwd)) === 'src') {
                // Detect whether we're in a module directory.
                grunt.log.debug('In a module directory');
                options.module = true;
            }

            if (grunt.option('watch')) {
                if (!options.walk && !options.module) {
                    grunt.fail.fatal('Unable to watch unless in a src or module directory');
                }

                // It is not advisable to run with recursivity and watch - this
                // leads to building the build directory in a race-like fashion.
                grunt.log.debug('Detected a watch - disabling recursivity');
                options.recursive = false;
                args.push('--watch');
            }

            if (options.recursive) {
                args.push('--recursive');
            }

            // Always ignore the node_modules directory.
            args.push('--excludes', 'node_modules');

            // Add the stderr option if appropriate
            if (grunt.option('verbose')) {
                args.push('--lint-stderr');
            }

            // Actually run shifter.
            shifter = exec("node", args, {
                cwd: cwd,
                stdio: 'inherit',
                env: process.env
            });

            // Tidy up after exec.
            shifter.on('exit', function (code) {
                if (code) {
                    grunt.fail.fatal('Shifter failed with code: ' + code);
                } else {
                    grunt.log.ok('Shifter build complete.');
                    done();
                }
            });
    };

    tasks.startup = function() {
        // Are we in a YUI directory?
        if (path.basename(path.resolve(cwd, '../../')) == 'yui') {
            grunt.task.run('shifter');
        // Are we in an AMD directory?
        } else if (path.basename(cwd) == 'amd') {
            grunt.task.run('jshint');
            grunt.task.run('uglify');
        } else {
            grunt.task.run('less');
        }
    };


    // Register NPM tasks.
    grunt.loadNpmTasks('grunt-contrib-uglify');
    grunt.loadNpmTasks('grunt-contrib-jshint');

    // Register the shifter task.
    grunt.registerTask('shifter', 'Run Shifter against the current directory', tasks.shifter);

    // Register the startup task.
    grunt.registerTask('startup', 'Run the correct tasks for the current directory', tasks.startup);

    // Register the default task.
    grunt.registerTask('default', ['startup']);
};
