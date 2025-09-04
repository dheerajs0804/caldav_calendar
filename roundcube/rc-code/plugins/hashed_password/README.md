# Hashed Password

=============================

### Plugin for added security -

### does a salted hash of the password in the front end

The salt is retrieved from the salt_generator.php at startup using
jquery ajax. On submit, the password is hashed, then rehashed after
prepending the salt to it. This double hash is sent along with the
salt(which is sent as a hidden field). The prefix and the salt are
prepended to the double hash and sent to dovecot for further
authentication.

## steps to activate plugin in roundcube baya_v4

> cd skyconnect/config
> vi config.inc.php
if hashed_password plugin is not present in the plugin array, add it as below

$config['plugins'] = array(
'hashed_password'
);

or if present uncomment it 

$config['plugins'] = array(
//'hashed_password'
);

## to enable debugging

set hp_debug to true in main config.inc.php

> cd skyconnect/config
> vi config.inc.php

$config['hp_debug'] = true;

if not present add this in main config.inc.php:

// Hashed Password options    
// ---------------------------------    
// option to set debugging on or off    
// Default: false
    
$config['hp_debug'] = false;

