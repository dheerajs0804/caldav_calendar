if (window.rcmail) {
    rcmail.addEventListener("init", (function(evt) {
        rcmail.register_command("plugin.filters-delete", (function() {
            rcmail.goto_url("plugin.filters-delete");
        }), true);
        rcmail.register_command("plugin.filters-save", (function() {
            var input_searchstring = rcube_find_object("_searchstring");
            if (input_searchstring && input_searchstring.value == "") {
                alert(rcmail.gettext("The field Contains cannot be empty.", "server_side_filter"));
                input_searchstring.focus();
            }else if(input_searchstring.value.indexOf('"')>=0){ 
		alert(rcmail.gettext('The field Contains cannot have "', "server_side_filter"));
                input_searchstring.focus();
	    }
	    else if(input_searchstring.value.indexOf('**')>=0){
                alert(rcmail.gettext("The field Contains cannot have **", "server_side_filter"));
                input_searchstring.focus();
            }else rcmail.gui_objects.filtersform.submit();
        }), true);
    }));
}
