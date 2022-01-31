module.exports = function(grunt) {
  grunt.registerTask("custom", function custom() {
    grunt.initConfig({
      // LESS compilation
      less: {
        compile: {
          files: {
            "./themes/KnihovnyCz/css/embedded-search.css": "./themes/KnihovnyCz/less/embedded-search.less"
          },
          options: {
            compress: true,
          }
        }
      },
    });
    grunt.task.run('less');
  });
};
