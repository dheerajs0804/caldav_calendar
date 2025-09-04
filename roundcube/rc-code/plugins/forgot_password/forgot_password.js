/*
 * forgot_password.js
 *
 * Jscript (jquery) for forgot_password plugin to add a link on login screen
 *
 */
if ( forgot_password_link ) {
    $(document).ready(function($){
        $('#login-form input[type="password"]').parent().parent().after('<a class="home" style="text-align:right;float:right;color:red;margin-top:-20px;margin-bottom: 10px;" id="forgot_password" href=' + forgot_password_link + '><br>Forgot Password?</br></a><br>');

    });
}

$(document).ready(function($){
    $('#forgot_password').click(function(event){
	if( $('#login-form input[id="rcmloginuser"]').val().length == 0 ){
	    alert("Please enter username to use forgot password.");
	    event.preventDefault();
	}
    });
});
