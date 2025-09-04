addCss('/program/resources/froala/froala_editor.pkgd.min.css');
//addCss('/roundcubemail/program/resources/froala/font_awesome.min.css');
addCss('/program/js/froala/themes/royal.min.css');
addCss('/program/resources/froala/froala_style.min.css');

function addCss(url) {
    var head  = document.getElementsByTagName('head')[0];
    var link  = document.createElement('link');
    link.id   = 'froala_css';
    link.rel  = 'stylesheet';
    link.type = 'text/css';
    link.href = url;
    link.media = 'all';
    head.appendChild(link);
}

//addJs('/roundcubemail/program/js/froala/font_awesome.min.js');


function addJs(url) {
   var head = document.getElementsByTagName('head')[0];
   var script = document.createElement('script');
   script.type = 'text/javascript';
   script.src = url;
   head.appendChild(script);
}
