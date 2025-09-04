/*
 * demo_account.js
 *
 * Jscript (jquery) for demo_account plugin to hardcore user and password for demo domain.
 *
 */

$(document).ready(function($){

         $('#rcmloginuser').val(demouser);
         $('#rcmloginpwd').val(demouserpass);
         $('#rcmloginuser, #rcmloginpwd').attr('readonly', true);

         //Remove forgot password link if access using demo account
         $('#forgot_password').remove();
});
