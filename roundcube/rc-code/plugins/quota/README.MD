A plugin that shows/update quota information with a pie chart for Roundcube using configured drivers.

## Supported drivers
1) xf_dovecot_webservice
2) xf_courier_webservice

## How to install this plugin in Roundcube?

1. Create folder `quota` in `ROUNDCUBE_HOME/plugins` if it does not exist.
2. Copy all plugin files there.

3. Add following config in `ROUNDCUBE_HOME/config/config.inc.php`.

```php
<?php

//---------------------------------------
//START Quota configurations
//---------------------------------------
//Select driver for quota
$config['quota_driver'] = 'xf_dovecot_webservice';

// enable chart presentation
// Disable for 'xf_courier_webservice' driver
$config['enable_chart_presentation'] = true;

// enable text presentation
// Disable for 'xf_courier_webservice' driver
$config['enable_text_presentation'] = true;

// enable update quota
//Disable for 'xf_dovecot_webservice'
$config['update_quota'] = true;


//---------------------------------------
//END Quota configurations
//---------------------------------------

```

4. Edit `ROUNDCUBE_HOME/config/config.inc.php` locate `$config['plugins']` and add `'quota',` there:

```php
<?php

// some other codes...

$config['plugins'] = array(
    // some other plugins...
    'quota', // <-- add this
);
```

