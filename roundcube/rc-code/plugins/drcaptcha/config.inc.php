<?php
// -----------------------
// rcaptcha Plugin options
// -----------------------

// Height of captcha image. Width is auto calculated
$rcmail_config['drcaptcha_height'] = '40';

// List all possible characters used in code generation , similar looking characters and vowels have been removed
// lower case letters such as g, p, q, y get cut off default:23456789ABCDEFGHKMNPRSTVWXYZ
$rcmail_config['drcaptcha_characters'] = '#@$&123456789BCDEFGHKMNPRSTVWXYZabcdefhiklmnoprtuvxz';

//Size of chars in generated code
$rcmail_config['drcaptcha_codesize'] = 5;

//Factor 0.0 - 1 used for calculating margins around text
$rcmail_config['drcaptcha_font_factor'] = 0.75;

//Colour of text hex rgb0075C8
$rcmail_config['drcaptcha_text_colour'] = '006fe6';

//Whitelist ip (format '192.168.1.0/24,172.16.1.1')
//$rcmail_config['drcaptcha_whitelist'] = '192.168.0.56/16';

//Delay in second on wrong code
$rcmail_config['drcaptcha_error_delay'] = 3;
