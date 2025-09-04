if (window.rcmail) {
    rcmail.addEventListener('init', function(event) {

	// Plugin form submit
        $("#toggle-plugins-form").submit(function(event) {
          event.preventDefault();
          var data = $(this).serialize();
          var checkedPlugins = $("#toggle-plugins-form input:checkbox:checked").map(function() {
            return $(this).attr("name");
          }).get();
          rcmail.http_post("plugin.save_plugins", "data=" + checkedPlugins);
        });
    });
}
