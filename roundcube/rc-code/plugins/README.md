CalDAV/iCAL Support for Roundcube Calendar
==========================================
This repository was forked from [roundcubemail-plugins-kolab](http://git.kolab.org/roundcubemail-plugins-kolab) and contains a modified version of the Roundcube calendar plugin that enables client support for CalDAV and iCAL calendar resources. We added a feature branch [feature_caldav](https://gitlab.awesome-it.de/kolab/roundcube-plugins/tree/feature_caldav) with the modified calendar plugin and we try to frequently merge the latest release tags from upstream. You can find further information and a short introduction to this plugin on our [website](http://awesome-it.de/2014/02/22/Kolab-CalDAV-iCAL/).

Requirements
============
* Roundcube 1.0-RC or higher
* Optional: Kolab 3.1 or higher

Installation
============
* Clone this repo and checkout the appropriate tag from the `feature_caldav` branch. Please note that we assume that you are using Kolab Groupware and that there is supported version of Kolab Roundcubemail Plugins installed.
For example if `roundcubemail-plugins-kolab-3.1.13` is installed, you must checkout `roundcubemail-plugins-kolab-caldav-3.1.13[-rn]`. If you can't find an appropriate tag in the `feature_caldav` branch, you're version of Kolab Roundcubemail Plugins is not supported.

    ```bash
    $ cd /path/to/your/roundcube/
    $ git clone https://gitlab.awesome-it.de/kolab/roundcube-plugins.git plugins-caldav
    $ cd plugins-caldav 
    $ git checkout roundcubemail-plugins-kolab-caldav-<VERSION>-<REVISION>
    ```
    
* Only the calendar plugin was modified, so you only have to replace the calendar plugin:

    ```bash
    $ cd /path/to/your/roundcube/    
    $ mv plugins/calendar plugins/calendar.orig
    $ cd plugins
    $ ln -s ../plugins-caldav/plugins/calendar
    ```

    If you use Roundcubemail without Kolab, you should put all plugins from the `feature_caldav` branch into your plugins folder. Please note that this is only rarely tested and we won't offer any support for this right now.

* Install/update dependencies using PHP Composer (https://getcomposer.org/)

    ```bash
    $ cd plugins/calendar/lib
    $ composer install
    ```

* Copy `plugins/calendar.orig/config.inc.php` to the new plugin folder and modify accordingly:

    ```bash
    $ cp plugins/calendar.orig/config.inc.php plugins/calendar/config.inc.php
    $ vi plugins/calendar/config.inc.php
    ```

    If you replace existing Kolab Roundcubemail Plugins as described above, make sure to copy the configuration files of those plugins to the new plugin folders!

* The calendar setting `calendar_driver` now accepts an array with calendar drivers you want to enable:

    ```php
    $config['calendar_driver'] = array("kolab", "caldav", "ical");
    ```

    Note that the very first array value is used a default driver e.g. for creating events via email if no calendar was chosen.
    Further you can drop the Kolab dependency of the calendar by remocing the `kolab` driver.

* It is always a good idea to set a new crypt key to be used for encryption of you CalDAV passwords:

    ```php
    $config['calendar_crypt_key'] = 'some_random_characters`;
    ```

* Update Roundcube's MySQL database:

    ```bash
    $ mysql -h <db-host> -u <db-user> -p <db-name> < /path/to/your/roundcube/plugins-caldav/plugins/calendar/drivers/database/SQL/mysql.initial.sql

    # For CalDAV support
    $ mysql -h <db-host> -u <db-user> -p <db-name> < /path/to/your/roundcube/plugins-caldav/plugins/calendar/drivers/caldav/SQL/mysql.initial.sql

    # For iCAL support
    $ mysql -h <db-host> -u <db-user> -p <db-name> < /path/to/your/roundcube/plugins-caldav/plugins/calendar/drivers/ical/SQL/mysql.initial.sql
    ```

* Make sure that the calendar plugin is enabled the Roundcube config e.g. `/path/to/your/roundcube/config/config.inc.php`:

    ```php
    $config['plugins'] = array(
        // ...
        'calendar',
        // ...
    );
    ```

* You should now be able to select one of your configured drivers when creating a new calendar.

Troubleshooting
===============

Enabling debug mode in `config.inc.php` will output additional debug information to `/path/to/your/roundcube/logs/console`:

```php
$config['calendar_caldav_debug'] = true;
$config['calendar_ical_debug'] = true;
```

If you find any bugs, please fill an issue in our [bug tracker](https://gitlab.awesome-it.de/kolab/roundcube-plugins/issues).

License
=======

CalDAV/iCAL Extension
---------------------

Copyright (C) 2014, Awesome Information Technology GbR <info@awesome-it.de>
 
This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as
published by the Free Software Foundation, either version 3 of the
License, or (at your option) any later version.
 
This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU Affero General Public License for more details.
 
You should have received a copy of the GNU Affero General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.

Kolab Plugins
-------------
See http://git.kolab.org/roundcubemail-plugins-kolab/tree/plugins/calendar/LICENSE.
