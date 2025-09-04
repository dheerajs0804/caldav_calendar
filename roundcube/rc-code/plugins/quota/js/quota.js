window.rcmail && rcmail.addEventListener('init', function(evt) {

  // register command handler
  rcmail.register_command('plugin.update-quota', function() {
        rcmail.gui_objects.quotaform.submit();
        var lock = rcmail.set_busy(true, 'quota.updatingquota');
  }, true);

});
