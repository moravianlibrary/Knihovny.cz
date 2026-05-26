module.exports = function (grunt) {
  grunt.registerTask("custom", function custom() {
    var fs = require('fs');
    var path = require('path');
    var sassConfig = {};

    fs.readdirSync('themes').forEach(function(theme) {
      var srcFiles = ['embedded-search.scss', 'embedded-libraries.scss'].filter(function(f) {
        return fs.existsSync(path.join('themes', theme, 'scss', f));
      });
      if (!srcFiles.length) return;

      sassConfig[theme] = {
        options: {
          outputStyle: 'compressed',
          includePaths: [
            'themes/KnihovnyCz/scss',
            'themes/bootstrap5/scss',
            'themes/bootstrap5/node_modules',
            'vendor'
          ]
        },
        files: [{
          expand: true,
          cwd: path.join('themes', theme, 'scss'),
          src: srcFiles,
          dest: path.join('themes', theme, 'css'),
          ext: '.css'
        }]
      };
    });

    grunt.config.set('dart-sass', sassConfig);
    grunt.task.run('dart-sass');
  });
};
