module.exports = function (grunt) {
  grunt.registerTask("custom", function custom() {
    var sassFileSettings = [
      {
        expand: true,
        src: "themes/*/scss/embedded-search.scss",
        rename: function (dest, src) {
          return src.replace('/scss/', '/css/').replace('.scss', '.css');
        }
      },
      {
        expand: true,
        src: "themes/*/scss/embedded-libraries.scss",
        rename: function (dest, src) {
          return src.replace('/scss/', '/css/').replace('.scss', '.css');
        }
      }
    ];
    grunt.initConfig({
      // SCSS compilation via dart-sass (already loaded in main Gruntfile)
      'dart-sass': {
        compile: {
          files: sassFileSettings,
          options: {
            outputStyle: "compressed",
          }
        }
      },
    });
    grunt.task.run('dart-sass');
  });
};
