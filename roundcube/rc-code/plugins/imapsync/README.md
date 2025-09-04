#Roundcube imapsync plugin

**Roundcube imapsync plugin** is a **Roundcube** plugin, which allows users to sync their mail from external mailboxes

	- really fast and reliable, as imapsync is
	- auto-syncing all IMAP-Folders
	- including a completely rewritten and much more secure imapsync.pl Perl Script

##Screenshot

![Screenshot](https://cloud.githubusercontent.com/assets/8064903/23556852/4b069624-002e-11e7-8313-0c9896b8efdb.png)

##Prerequisites
1. **Roundcube**
2. Database (e.g. **MySQL**)
3. **imapsync** itself

##Installation
1. First you need to install **imapsync** itself. For **Debian** you can do so by 
	```bash
	
	apt-get install imapsync
	
	```
	or get it from the source (**recommended**), e.g. for Debian/Ubuntu:
	```bash
	
	apt-get update
	apt-get install cpanminus git makepasswd rcs perl-doc libio-tee-perl git libmail-imapclient-perl libdigest-md5-file-perl libterm-readkey-perl libfile-copy-recursive-perl build-essential make automake libunicode-string-perl libauthen-ntlm-perl libcrypt-ssleay-perl libdigest-hmac-perl libfile-copy-recursive-perl libio-compress-perl libio-socket-inet6-perl libio-socket-ssl-perl libio-tee-perl libmodule-scandeps-perl libnet-ssleay-perl libpar-packer-perl libreadonly-perl libterm-readkey-perl libtest-pod-perl libtest-simple-perl libunicode-string-perl liburi-perl
	cpanm -i JSON::WebToken Test::MockObject Unicode::String Data::Uniqid
	cd /tmp
	git clone https://github.com/imapsync/imapsync
	cd imapsync
	mkdir dist
	make install
	
	```
	
2. Next you should extract the **Roundcube imapsync plugin** archive into your **Roundcube** `plugins` folder creating "imapsync" folder there.
	- You can do so either by using `composer` for which there is `composer.json`, still you need to follow further installation steps since those could not be accomplished with `composer`
	- Alternatively you can clone this github repo (**recommended**), e.g.:
  	```bash
	
	apt-get install git
	cd /path/to/your/roundcube/plugins/
	git clone https://github.com/server-gurus/RCimapSync.git ./imapsync
	
	```
  
3. After that you need to enable newly installed plugin by adding it to **Roundcube** plugin list. For **Debian** related config file is `/etc/roundcube/main.inc.php` (for Plesk it is `config.inc.php`) and relevant setting is 
	```php
	
	$config['plugins'] = array('plugin1','plugin2',[...],'imapsync');
	
	```
Appending `, 'imapsync'` to the list of plugins will suffice.

4. Unless default settings are suitable for you, you need to configure the plugin. See the [settings section](#settings) for more information.

5. You need to create additional table in your (e.g. roundcube) database using the supplied `mysql.initial.sql` file. Hint: if you are using Plesk you have to open the mysql command line with 
	```bash
	mysql -uadmin -p`cat /etc/psa/.psa.shadow`
	```
	(https://kb.plesk.com/de/3472)

	- Change the database to whatever you wanna use, e.g.:
	```mysql
	use roundcubemail;
	```
	- Open the mysql.initial.sql and copy it to the mysql shell.
	- Create a user and give necessary rights to the database and table, e.g.:
	```mysql
	CREATE USER 'imapsync'@'127.0.0.1' IDENTIFIED BY 'onepassword';
	GRANT ALL PRIVILEGES ON roundcubemail.imapsync TO 'imapsync'@'127.0.0.1';
	FLUSH PRIVILEGES;
	EXIT
	```

6. Open the `/bin/` Folder. Rename the config file from `config.sample` to `config.conf`. Place the `/bin/` Folder to where apropriate or let it in his place - your security, your choice.

7. Next open `config.conf` and adapt it to your config made in Step 5.
	```perl
	my $db_host="127.0.0.1";
	my $db_name="roundcubemail";
	my $db_username="imapsync";
	my $db_password="onepassword";
	```
8. Next step is to configure **cron** for regular mail checking with `sudo crontab -u mail -e` or change your `/etc/crontab`. For example for 5 minute intervals add this: `*/5 * * * * /var/mail/imapsync.pl >/dev/null 2>&1`. Worth noting that even if you configure cron for a 5 minutes interval, imapsync will still abide user configured checking interval. As a result setting bigger intervals here manifests them as intervals available to fetchmail, that is setting `0 * * * *` here overrides any user setting wich is less then hour

9. You might also need to install `liblockfile-simple-perl` and ( `libsys-syslog-perl` or `libunix-syslog-perl` ) on **Debian**-based systems.

##Settings
In case you need to edit default-set settings, you may copy `config.inc.php.dist` to `config.inc.php` and edit setings as desired in the latter file, which will override defaults.

##License
This software distributed under the terms of the GNU General Public License as published by the Free Software Foundation

Further details on the GPL license can be found at http://www.gnu.org/licenses/gpl.html

By contributing to **Roundcube imapsync plugin**, authors release their contributed work under this license

##Acknowledgements
###Original author

Arthur Mayer, https://github.com/flames (fork)

Christian Nowak, https://github.com/chr1sn0

Christian Bischoff, https://github.com/DaCHRIS

###List of contributors

For a complete list of contributors, refer to [Github project contributors page](https://github.com/server-gurus/RCimapSync/network/members)

####Currently maintained by
* [servergurus](https://github.com/server-gurus)
