<?php
/**
 * Roundcube Plugin Cloud Button
 * Plugin to add button in taskbar to open ideolve storage.
 *
 * @version 1.0.2
 * @author Alexander Pushkin <san4op@iideolve.com>
 * @copyright Copyright (c) 2019, Alexander Pushkin
 * @link https://github.com/san4op/roundcube_ideolve_button
 * @license GNU General Public License, version 3
 */

class ideolve_button extends rcube_plugin
{
	private $url;

	function init()
	{
		$this->load_config();
		$this->url = rcube::get_instance()->config->get('ideolve_button_url', '');

		if (!$this->url) {
			return;
		}

		$this->include_stylesheet($this->local_skin_path() . '/ideolve_button.css');
		$this->add_texts('localization/');

		if(!$this->initIdeolveConnector())
		{ 
			// set button status disabled
			rcube::write_log('errors', 'Unable to initialise ideolve connector');
		}
		else
		{
			rcube::console('Ideolve: Initialised ideolve connector');
			$this->add_button(array(
				'type'       => 'link',
				'label'      => 'ideolve',
				'href'       => $this->url,
				'target'     => '_blank',
				'class'      => 'button-ideolve',
				'classsel'   => 'button-ideolve button-selected',
				'innerclass' => 'button-inner'
			), 'taskbar');
		}	
	}

	private function initIdeolveConnector()
	{

		$RESULT=false;
	        while( true )
        	{
			if(!class_exists("ideolve_connector")) {
                	        require_once(dirname(__FILE__).'/ideolve_connector.php');
		        }

			$this->ideolveConnector = new ideolve_connector( rcube::get_instance() );
			if( $this->ideolveConnector == null )
				break;

			if( !$this->ideolveConnector->init() )
				break;

			$this->url = $this->ideolveConnector->getIdeolveAccessUrl();

			$RESULT=true;
        	        break;
        	}	
	        return $RESULT ;
	}
#########END##########
}

?>
