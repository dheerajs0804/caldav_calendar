function jsfunction(user, password){
	console.log("Post Message Block");
      	// const popupUrl = 'http://127.0.0.1:5500';
       	const popupUrl = 'https://intchat.mithiskyconnect.com/webchat/app.html';
	const popup = window.open(popupUrl);
       	const toMessage = {
      		jid : user,
	        password : password,
     		status : 400,
        }

	const postMessageInterval = setInterval(() => {
       		if(toMessage.status === 200) {
            		// popup.postMessage(toMessage, popupUrl);
	               	clearInterval(postMessageInterval);
			window.close();
	        }else if(toMessage.status === 400) {
       		      	popup.postMessage(toMessage, popupUrl);
        	}	
	}, 100);
           
       	window.addEventListener('message', (e) => {
           	const data = e.data;
	        const origin = e.origin;
      	        console.log(origin, data, e);
               
                let popupDomain = new URL(popupUrl);
	        console.log(popupDomain)
       	        if(origin === popupDomain.origin) {
              		toMessage.status = data.status;
                }
       	})
}

