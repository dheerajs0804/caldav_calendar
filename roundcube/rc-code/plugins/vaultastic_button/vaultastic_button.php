<?php

class vaultastic_button extends rcube_plugin
{
	private $url;

	function init()
	{
		$this->url = rcube::get_instance()->config->get('vaultastic_button_url', '');

		if (!$this->url) {
			return;
		}

		$this->include_stylesheet($this->local_skin_path() . '/vaultastic_button.css');
		$this->add_texts('localization/');

		if( strcasecmp($this->is_archiving_enabled(), "Yes") == 0 ){
	            $this->add_button(array(
			  'type'       => 'link',
                          'label'      => 'vaultastic_button.vaultastic',
                          'href'       => $this->url,
                          'target'     => '_blank',
                          'class'      => 'button-vaultastic',
                          'classsel'   => 'button-vaultastic button-selected',
                          'innerclass' => 'button-inner'
                     ), 'taskbar');
		}

	}

	private function is_archiving_enabled()
	{
	        //Get xf instance
        	include_once '/var/www/html/skyconnect/plugins/xf_directory/xf.php';
	        $xf_instance = xf::get_instance();

        	//Check archiving is enabled for user or not
	        $userproperties = $xf_instance->get_xf_user_properties(array('enablepersonalmailarchiving'));
        
		return $userproperties['enablepersonalmailarchiving'];
	}
}

?>
