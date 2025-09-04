# micro chat app

=============================


## steps to activate plugin in roundcube baya_v4

> cd roundcubemail/config
> vi config.inc.php
if chat plugin is not present in the plugin array, add it as below

$config['plugins'] = array(
'chat'
);

or if present uncomment it

$config['plugins'] = array(
//'chat'
);

## to enable debugging

set chat_debug to true in main config.inc.php

> cd roundcubemail/config
> vi config.inc.php

$config['chat_debug'] = true;

if not present add this in main config.inc.php:

// Server side filter options
// ---------------------------------
// option to set debugging on or off
// Default: false

$config['chat_debug'] = false;

Add below conf in  main config.inc.php:
```
//session timeout for ws
$config['ws_timeout'] = 30;
//connection timeout for ws
$config['ws_connectiontimeout'] = 30;
