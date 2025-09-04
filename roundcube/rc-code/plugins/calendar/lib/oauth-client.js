window.rcmail && rcmail.addEventListener('init', function(evt) {

    var update_ui = function(props) {
        $.ajax({
            type: 'GET',
            dataType: 'json',
            url: rcmail.url('oauth_status', props),
            success: function (data) {
                if (data.is_logged_in) {
                    $(".oauth-logout").show();
                    $(".oauth-login").hide();
                } else {
                    $(".oauth-logout").hide();
                    $(".oauth-login").show();
                }
            }
        });
    };

    rcmail.register_command('oauth-login', function (props) {
        props = JSON.parse(props);

        var win = window.open(rcmail.url('oauth_redirect', props));
        var timer = window.setInterval(function() {
            if (win.closed !== false) { // !== is required for compatibility with Opera
                window.clearInterval(timer);
                update_ui(props);
            }
        }, 250);

    }, true);

    rcmail.register_command('oauth-logout', function (props) {
        props = JSON.parse(props);
        $.ajax({
            type: 'GET',
            dataType: 'html',
            url: rcmail.url('oauth_logout', props),
            success: function() {
                update_ui(props);
            }
        });

    }, true);
});