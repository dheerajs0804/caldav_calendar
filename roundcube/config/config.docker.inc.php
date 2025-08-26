<?php
  $config['db_dsnw'] = 'sqlite:////var/roundcube/db/roundcube.db?mode=0646';
  $config['db_dsnr'] = '';
  $config['imap_host'] = 'ssl://intmail.mithi.com:993';
  $config['smtp_host'] = 'tls://intmail.mithi.com:587';
  $config['username_domain'] = '';
  $config['temp_dir'] = '/tmp/roundcube-temp';
  $config['skin'] = 'elastic';
  $config['request_path'] = '/';
  $config['plugins'] = array_filter(array_unique(array_merge($config['plugins'], ['archive', 'zipdownload'])));
  
$config['des_key'] = getenv('ROUNDCUBEMAIL_DES_KEY');
