//get sessiontime from conf file
var timeoutInMiliseconds = rcmail.env.sessionexpirytime ; 
//console.log(timeoutInMiliseconds);
var timeoutId; 
  
function startTimer() { 
    // window.setTimeout returns an Id that can be used to start and stop a timer
  //  console.log("start timer");
    timeoutId = window.setTimeout(doInactive, timeoutInMiliseconds)
}
  
function doInactive() {
    //if session expire, then clear cookies.
    //console.log("expired");
    $.ajax({
    type: "post",
    url: "plugins/session_expiry/clear_cookie.php"
   });

}
 
function setupTimers () {
    //console.log("setup");
    document.addEventListener("mousemove", resetTimer, false);
    document.addEventListener("mousedown", resetTimer, false);
    document.addEventListener("keypress", resetTimer, false);
    document.addEventListener("touchmove", resetTimer, false);
    document.addEventListener("keydown", resetTimer, false);
    document.addEventListener("keyup", resetTimer, false);

    window.focus()
    //detect iframe click select event
    window.addEventListener("blur", () => {
      setTimeout(() => {
         if (document.activeElement.tagName === "IFRAME") {
      //      console.log("clicked");
            resetTimer();
         }
     });
    },false);

    //detect keyboard event while composing mail
    try{
        setTimeout(() => {
                        var iframeobj = document.getElementById('composebody_ifr');
                        var texteditorevent = iframeobj.contentDocument.getElementById('tinymce');
			//console.log("keyboard event");
                        texteditorevent.addEventListener("keydown",resetTimer,false);

        },3000);

     }catch(error){}

     
    startTimer();
}

function resetTimer() {
   // console.log("reset"); 
    window.clearTimeout(timeoutId)
    startTimer();
}
 
$(document).ready(function(){
  //console.log("start");
  setupTimers();
});

