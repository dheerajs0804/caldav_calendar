//checking if input is empty, then display alert message 
if (window.rcmail) {
  rcmail.addEventListener('init', function(evt) {
    // register command
    rcmail.register_command('plugin.ideolve-save', function(){ 
      var input_clientid = rcube_find_object('_clientid');
      var input_clientsecret = rcube_find_object('_clientsecret');
      var reWhiteSpace = new RegExp(/\s/);

      if(input_clientid && input_clientid.value=='') {
          alert(rcmail.gettext('noclientid','ideolve_integration'));          
          input_clientid.focus();
	 
      }
      else if(input_clientsecret && input_clientsecret.value==''){
          alert(rcmail.gettext('noclientsecret','ideolve_integration'));
          input_clientsecret.focus();
      }else if(reWhiteSpace.test(input_clientid.value)){
	        alert(rcmail.gettext('The field not contain empty space','ideolve_integartion'));
	        input_clientid.focus();
      }
      else if(reWhiteSpace.test(input_clientsecret.value)) {
          alert(rcmail.gettext('The field not contain empty space','ideolve_integartion'));
          input_clientsecret.focus();
      }

      else
        rcmail.gui_objects.ideolveform.submit();
    }, true);
  })
}
