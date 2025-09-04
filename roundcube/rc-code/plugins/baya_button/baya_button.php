<?php

class baya_button extends rcube_plugin
{
	private $url;

	function init()
	{
		$this->url = rcube::get_instance()->config->get('baya_button_url', '');

		if (!$this->url) {
			return;
		}

		$this->include_stylesheet($this->local_skin_path() . '/baya_button.css');
		$this->add_texts('localization/');

		$this->add_button(array(
			'label'      => 'baya_button.baya',
			'href'       => $this->url,
			'target'     => '_blank',
			'class'      => 'button-baya',
			'classsel'   => 'button-baya button-selected',
			'innerclass' => 'button-inner'
		), 'taskbar');
	}
}
?>
