module.exports = function (grunt) {
  grunt.registerTask("custom", function custom() {
    var lessFileSettings = [
      {
        expand: true,
        src: "themes/*/less/embedded-search.less",
        rename: function (dest, src) {
          return src.replace('/less/', '/css/').replace('.less', '.css');
        }
      },
      {
        expand: true,
        src: "themes/*/less/embedded-libraries.less",
        rename: function (dest, src) {
          return src.replace('/less/', '/css/').replace('.less', '.css');
        }
      }
    ];
    grunt.initConfig({
      // LESS compilation
      less: {
        compile: {
          files: lessFileSettings,
          options: {
            compress: true,
          }
        }
      },
    });
    grunt.task.run('less');
  });
};
