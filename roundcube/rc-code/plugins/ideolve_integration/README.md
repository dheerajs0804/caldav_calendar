# skyconnect+ideolve integartion app

=============================


## steps to activate plugin in roundcube baya_v4

> cd roundcubemail/config
> vi config.inc.php
if ideolve_integration plugin is not present in the plugin array, add it as below

$config['plugins'] = array(
'ideolve_integration'
);

or if present uncomment it

$config['plugins'] = array(
//'ideolve_integration'
);

## to enable debugging

set ideolve_debug to true in main config.inc.php

> cd roundcubemail/config
> vi config.inc.php

$config['ideolve_debug'] = true;

if not present add this in main config.inc.php:

// ideolve integration options
// ---------------------------------
// option to set debugging on or off
// Default: false

$config['ideolve_debug'] = false;

Add below conf in  main config.inc.php:
```
//session timeout for ws
$config['ws_timeout'] = 30;
//connection timeout for ws
$config['ws_connectiontimeout'] = 30;
