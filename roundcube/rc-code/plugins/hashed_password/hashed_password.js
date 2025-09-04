var node;
var randomid;
 
 if (window.rcmail) {

    rcmail.addEventListener('init', function() {
        // inside init so display message can update error
        $.ajax({url: "plugins/hashed_password/get_randomid.php", 
                success: function(result) {
                    var parsedRes = JSON.parse(result);
                    randomid = parsedRes[0];
                    node = parsedRes[1];
                },
                error: function(result) {
                    rcmail.display_message("Randomid not received", "warning");
                }
        });
        var form = document.forms['login-form'];
        form.onsubmit = function(event) {
            $("form[name='login-form']").append('<input type="hidden" name="randomid" value="' + randomid + '" />');
            var pass = document.getElementById('rcmloginpwd').value;
            var hash = CryptoJS.SHA256(pass);
            hash = CryptoJS.SHA256(node + hash);
            document.getElementById('rcmloginpwd').value = hash.toString();
        }
    });
 }
