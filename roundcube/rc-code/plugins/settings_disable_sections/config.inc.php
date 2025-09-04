<?php
//Add sections in array to disable.
//$config['settings_disable_sections'] = array('<section1>','<section2>',...);
$config['settings_disable_sections'] = array('folders');
   
//Add section wise options to disable it from sections
//$config['disable_section_options'] = array('<section1>' => array('sectionop1','sectionop2'), ...)
$config['disable_section_options'] = array(
      'general' => array('skin'),
      'addressbook' => array(''),
      'compose' => array('')
      );

//Add settings in array to disable
//Values allowed by default: preferences, folders, identities and responses
//All others settings sections are displayed by plugins
//To hide settings tab occurred by plugin, need to register plugin in standard way using 'setting_action' hook.
//$config[disable_settings] = array('<setting_label>');
$config['disable_settings'] = array();
