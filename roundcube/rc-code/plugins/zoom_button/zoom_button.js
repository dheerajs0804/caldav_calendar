//checking if input is empty, then display alert message 
if (window.rcmail) {
  rcmail.addEventListener('init', function(evt) {
    // register command
    rcmail.register_command('plugin.zoom-save', function(){ 
      var input_clientid = rcube_find_object('_clientid');
      var input_clientsecret = rcube_find_object('_clientsecret');
      var reWhiteSpace = new RegExp(/\s/);

      if(input_clientid && input_clientid.value=='') {
          alert(rcmail.gettext('noclientid','zoom_button'));          
          input_clientid.focus();
	 
      }
      else if(input_clientsecret && input_clientsecret.value==''){
          alert(rcmail.gettext('noclientsecret','zoom_button'));
          input_clientsecret.focus();
      }else if(reWhiteSpace.test(input_clientid.value)){
	        alert(rcmail.gettext('The field not contain empty space','zoom_button'));
	        input_clientid.focus();
      }
      else if(reWhiteSpace.test(input_clientsecret.value)) {
          alert(rcmail.gettext('The field not contain empty space','zoom_button'));
          input_clientsecret.focus();
      }

      else
        rcmail.gui_objects.zoomform.submit();
    }, true);
  })
}
