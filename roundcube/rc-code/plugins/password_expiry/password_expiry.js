if (window.rcmail) {
  rcmail.addEventListener('init', function(evt) {

	console.log(document.getElementById('rcmloginuser'));

	var data = 'Your password has expired. To login and use your account, please set a new password.';
	document.getElementById('login-form').style.display='none';
//	document.getElementById("layout-content").parentNode.style.p="75px";

	//$('#layout-content').dialog({ modal:true, resizable:false, closeOnEscape: true, width:420 });
	$("#layout-content").dialog({dialogClass:"passExp", modal : true, show : "fade", hide : "fade",resizable:false, closeOnEscape: false,position: { my: "top+150", at: "top"} ,open:function(){
	$(this).append(
            $('<form />', {onsubmit:"return checkpassword()", method: 'POST' }).append(
                $('<div />', { class: 'appm', text: 'Password Expired, Update New Password' }),
		$('<br />'),
		$('<input />', { id: 'mailid', name: 'mailid', placeholder: 'Mail ID', type: 'text', value: getCookie("username") }),
		$('<br />'),
                $('<input />', { id: 'currentpass', name: 'currentpass', placeholder: 'Current Password', type: 'password' }),
		$('<span />',  { id: 'currentPassMsg', class:'errorMsg'}),
		$('<br />'),
                $('<input />', { id: 'newpass', name: 'newpass', placeholder: 'New Password', type: 'password'}),
		$('<span />',  { id: 'newPassMsg', class:'errorMsg'}),
		$('<br />'),
                $('<input />', { id: 'confirmpass', name: 'confirmpass', placeholder: 'Confirm Password', type: 'password' }),
                $('<span />',  { id: 'confirmMsg', class:'errorMsg'}),
		$('<br />'),
		$('<span />',  { id: 'captchaSpan'}).append(
		$('<img />',   { id: 'gencaptcha', name: 'gencaptcha', src: '/plugins/password_expiry/captcha.php'}),
                $('<input />', { id: 'usercaptcha', name: 'usercaptcha', placeholder: 'Captcha', type: 'text' })),
		$('<span />',  { id: 'captchaMsg', class:'errorMsg'}),
		$('<br />'),
                $('<input />', { id: 'savebutton', class:'btn-primary', type: 'submit', value: 'Save' }),
		$('<br />')
            )
        )
	}});

	const userCaptchaBox = document.getElementById("usercaptcha");
  	userCaptchaBox.onpaste = e => { e.preventDefault();
       		return false;
  		};
	const newPassBox = document.getElementById("newpass");
        newPassBox.onpaste = e => { e.preventDefault();
                return false;
        };
	const currentPassBox = document.getElementById("currentpass");
        currentPassBox.onpaste = e => { e.preventDefault();
                return false;
        };
    const confirmPassBox = document.getElementById("confirmpass");
        confirmPassBox.onpaste = e => { e.preventDefault();
                return false;
        };
	
	try{
		document.getElementById("mailid").readOnly=true;
		document.getElementById('captchaSpan').style.display='flex';
		document.getElementById('gencaptcha').style = 'text-align:center';
		document.getElementById('gencaptcha').style.margin='0 20px 0 0';
		document.getElementById("ui-id-1").closest('div').style.display='none';
		document.getElementById("currentpass").required = true;
		document.getElementById("newpass").required = true;
		document.getElementById("usercaptcha").required = true;
                document.getElementById("confirmpass").required = true;
        	document.getElementById("mailid").required = true;
		document.getElementById("newPassMsg").style.cssText = "font-size:13px; color:red; ";
		document.getElementById("confirmMsg").style.cssText = "font-size:13px; color:red; ";
		document.getElementById("captchaMsg").style.cssText = "font-size:13px; color:red; ";
		document.getElementById("currentPassMsg").style.cssText = "font-size:13px; color:red; ";

	}catch(error){}
	console.log(document.getElementById("layout-content").parentNode);


  });
}

function checkpassword()
{
	var currentpass = document.getElementById("currentpass").value;
	var newpass = document.getElementById("newpass").value;
        var confirmpass = document.getElementById("confirmpass").value;
	var mailid = document.getElementById("mailid").value;	
	var userCaptcha = removeSpaces(document.getElementById('usercaptcha').value);	
	document.getElementById('usercaptcha').value = userCaptcha;
	
	var checkpass = new RegExp("^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[!@#\$%\^&\*])(?=.{8,})"); 
	var passUppercase = new RegExp("^(?=.*[A-Z])(?=.{1,})");
	var passLowercase = new RegExp("^(?=.*[a-z])(?=.{1,})");
	var passDigit = new RegExp("^(?=.*[0-9])(?=.{1,})");
	var passSpecialChar = /^(?=.*[!@#$%^&*]){1,}$/; 
		
        if(newpass != confirmpass){
                displayMessage("confirmMsg","Password do not match");
		return false;
        }else if(newpass == currentpass) {
		displayMessage("newPassMsg","New password have to be different from the old one.");
		return false;
	}else if(newpass.length < 8) {
		displayMessage("newPassMsg","Password must be at least 8 characters long.");
                return false;
	}else if (!newpass.match(passUppercase)){
		displayMessage("newPassMsg","Password must be at least one uppercase character.");
                return false;
	}else if(!newpass.match(passLowercase)) {
		displayMessage("newPassMsg","Password must be at least one lowercase character.");
                return false;
	}else if(!newpass.match(passDigit)) {
		displayMessage("newPassMsg","Password must be at least one number.");
                return false;
	}else if(!newpass.match(checkpass)) {
		 displayMessage("newPassMsg","Password must be at least one special character.");
                return false;
	}else if(newpass.match(checkpass) && newpass == confirmpass && currentpass != "") {
                document.getElementById("currentpass").value = encryptPassword(currentpass);
                document.getElementById("newpass").value = encryptPassword(newpass);
                document.getElementById("confirmpass").value = encryptPassword(confirmpass);
		//$("#layout-content").dialog("close");
		updatePass();
                return false;
	}else{
		return false;
	}
	
}

function encryptPassword(password)
{
        var public_key = "-----BEGIN PUBLIC KEY-----\
        MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDC870g7FwxWknhd1g4DS5kcmgf\
        5zqRyzf5bMNRLgLWfHqHg59bJdmObGtncQcts6396WEJO8YuLiVYTFeosXjJXWaI\
        g+98HKWAtksWgaK6Y2yEpQCTJSmkJh+J492vL4iEqk7ILB8hnVpA5zfH22tQKi3P\
        VUHCNT2FXuxYX6tSpQIDAQAB\
        -----END PUBLIC KEY-----";

        var encrypt_obj = new JSEncrypt();
	encrypt_obj.setPublicKey(public_key);
        var encrypted = encrypt_obj.encrypt(password);
	return encrypted;
}

function removeSpaces(string)
{
        return string.split(' ').join('');
}

function displayMessage(id,msg)
{
	document.getElementById(id).innerHTML = msg;
   	setTimeout( function() {
        document.getElementById(id).innerHTML = "";
    }, 5000);
}

function updatePass()
{
	var currentpass = document.getElementById("currentpass").value;
        var newpass = document.getElementById("newpass").value;
        var confirmpass = document.getElementById("confirmpass").value;
	var userCaptcha = document.getElementById('usercaptcha').value;
	var mailid = document.getElementById('mailid').value;

	$.ajax({  
         type:"POST",  
         url:"/plugins/password_expiry/updatepass.php",
	 async:false, 
         data:"usercaptcha="+encodeURIComponent(userCaptcha)+'&currentpass='+encodeURIComponent(currentpass)+'&newpass='+encodeURIComponent(newpass)+'&confirmpass='+encodeURIComponent(confirmpass)+'&mailid='+encodeURIComponent(mailid), 
         success:function(data){ 


		if (data == "Wrong Captcha, Please retry again"){
                    displayMessage("captchaMsg",data);
                    document.getElementById("currentpass").value = "";
                    document.getElementById("newpass").value = "";
                    document.getElementById("confirmpass").value = "";
                    document.getElementById('usercaptcha').value = "";
               }else{

                    alert(data);
                    document.location = location.protocol + '//' + location.host;;
                }

	//	$("#layout-content").dialog({dialogClass:"messagedisplay", modal : true, show : "fade", hide : "fade",resizable:false, closeOnEscape: false, width:390, height:70, open:function(){$(this).html(data)}});
	//	alert(data);
		//document.location = location.protocol + '//' + location.host;;
	}});  
}

