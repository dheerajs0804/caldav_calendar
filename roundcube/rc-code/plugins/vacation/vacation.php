<?php
/*
 * Vacation plugin that adds a new tab to the settings section
 * to enable forward / out of office replies.
 *
 * @package	plugins
 * @uses	rcube_plugin
 * @author	Jasper Slits <jaspersl@gmail.com>
 * @version	1.9
 * @license     GPL
 * @link	https://sourceforge.net/projects/rcubevacation/
 * @todo	See README.TXT
*/

// Load required dependencies
require 'lib/vacationdriver.class.php';
require 'lib/dotforward.class.php';
require 'lib/vacationfactory.class.php';
require 'lib/VacationConfig.class.php';

class vacation extends rcube_plugin {

    public $task = 'settings';
    private $v = "";
    private $inicfg = "";
    private $enableVacationTab = true;
    private $vcObject;

    public function init() {
        $this->add_texts('localization/', array('vacation'));
        $this->load_config();
        
        $this->inicfg = $this->readIniConfig();

        

        // Don't proceed if the current host does not support vacation
        if (!$this->enableVacationTab) {
            return false;
        }

        $this->v = VacationDriverFactory::Create($this->inicfg['driver']);

        $this->v->setIniConfig($this->inicfg);
        $this->register_action('plugin.vacation', array($this, 'vacation_init'));
        $this->register_action('plugin.vacation-save', array($this, 'vacation_save'));
        $this->register_handler('plugin.vacation_form', array($this, 'vacation_form'));
        // The vacation_aliases method is defined in vacationdriver.class.php so use $this->v here
        $this->register_action('plugin.vacation_aliases', array($this->v, 'vacation_aliases'));
        $this->include_script('vacation.js');
        $this->include_stylesheet('skins/elastic/vacation.css');
        $this->rcmail = rcmail::get_instance();
        $this->user = $this->rcmail->user;
        $this->identity = $this->user->get_identity();
        
        // forward settings are shared by ftp,sshftp and setuid driver.
        $this->v->setDotForwardConfig($this->inicfg['driver'],$this->vcObject->getDotForwardCfg());
    }
    
    public function vacation_init() {
        $this->add_texts('localization/', array('vacation'));
        $rcmail = rcmail::get_instance();
        $rcmail->output->set_pagetitle($this->gettext('autoresponder'));
        //Load template
        $rcmail->output->send('vacation.vacation');
    }
    
    public function vacation_save() {
        $rcmail = rcmail::get_instance();

        // Initialize the driver
        $this->v->init();

        if ($this->v->save()) {
//          $this->v->getActionText() Dummy for now
            $rcmail->output->show_message($this->gettext("success_changed"), 'confirmation');
        } else {
            $rcmail->output->show_message($this->gettext("failed"), 'error');
        }
        $this->vacation_init();
    }

    // Parse config.ini and get configuration for current host
    private function readIniConfig() {
        $this->vcObject = new VacationConfig();
        $this->vcObject->setCurrentHost($_SESSION['imap_host']);
        $config = $this->vcObject->getCurrentConfig();

        if (false !== ($errorStr = $this->vcObject->hasError())) {
            rcube::raise_error(array('code' => 601, 'type' => 'php', 'file' => __FILE__,
                        'message' => sprintf("Vacation plugin: %s", $errorStr)), true, true);
        }
        $this->enableVacationTab = $this->vcObject->hasVacationEnabled();

        return $config;
    }
    
    public function vacation_form() {
        $rcmail = rcmail::get_instance();
        // Initialize the driver
        $defaults =  $this->v->init();
        $settings = $this->v->_get();

        // Load default body & subject if present.
     if (empty($settings['subject']) && $defaults ) {
	   $settings['subject'] = $defaults['autoresponsesubject'];
           $settings['body'] = $defaults['autoresponsemailbody'];
	   $settings['timeinterval'] = $defaults['mdcautoreplytimeinterval'];
           $settings['maxnoofreply'] = $defaults['mdcautoreplymaxnoofreply'];
           $settings['startdate'] = $defaults['mdcvacationreplystarttime'];
           $settings['enddate'] = $defaults['mdcvacationreplyendtime'];
	   $settings['bodyexternaluser'] = $defaults['autoresponsemailbodyexternaluser'];
	   $settings['subjectexternaluser'] = $defaults['autoresponsesubjectexternaluser'];
	   if($defaults['mdcautoreply'] == 'Enabled'){
		$settings['autoreplystatus'] = true;
	   }else{	
           	$settings['autoreplystatus'] = false;
	  }
	  if($defaults['mdcautoreplystatusexternaluser'] == 'Enabled'){
                $settings['autoreplyextstatus'] = true;
           }else{
                $settings['autoreplyextstatus'] = false;
          }

        }//var_dump($settings);

        $rcmail->output->set_env('product_name', $rcmail->config->get('product_name'));
        // return the complete edit form as table

        $out = '<style>.uibox{overflow-y:scroll;}</style>';
        $out .= '<fieldset><legend>' . $this->gettext('outofoffice') . ' ::: ' . $rcmail->user->data['username'] . '</legend>' . "\n";
        $out .= '<table class="propform"><tbody>';
        // show autoresponder properties

	// Auto-reply enabled/disabled 
        $field_id = 'vacation_autoreplystatus';
        $input_autoresponderautoreply = new html_checkbox(array('name' => '_vacation_autoreplystatus', 'id' => $field_id, 'value' => 1));
        $out .= sprintf("<tr><td class=\"title\"><label for=\"%s\">%s</label></td><td>%s</td></tr>\n",
                $field_id,
                rcube_utils::rep_specialchars_output($this->gettext('autoreplystatus')),
               $input_autoresponderautoreply->show($settings['autoreplystatus']));

	 // time interval
        $input_autorespondertimeinterval = new html_inputfield(array('name' => '_vacation_timeinterval', 'id' => $field_id, 'size' => 10, 'disabled' => 'disabled' ));
        $out .= sprintf("<tr><td class=\"title\"><label for=\"%s\">%s</label></td><td>%s</td></tr>\n",
                $field_id,
                rcube_utils::rep_specialchars_output($this->gettext('autoreplytimeinterval')),
                $input_autorespondertimeinterval->show($settings['timeinterval']));

        // max no of reply
        $field_id = 'vacation_maxnoofreply';
        $input_autorespondermaxnoofreply = new html_inputfield(array('name' => '_vacation_maxnoofreply', 'id' => $field_id, 'size' => 10, 'disabled' => 'disabled' ));
        $out .= sprintf("<tr><td class=\"title\"><label for=\"%s\">%s</label></td><td>%s</td></tr>\n",
                $field_id,
                rcube_utils::rep_specialchars_output($this->gettext('autoreplymaxnoofreply')),
                $input_autorespondermaxnoofreply->show($settings['maxnoofreply']));

	// vacation start date
        $field_id = 'vacation_startdate';
        $input_autoresponderstartdate = new html_inputfield(array('name' => '_vacation_startdate', 'id' => $field_id, 'size' => 10));
        $out .= sprintf("<tr><td class=\"title\"><label for=\"%s\">%s</label></td><td>%s</td></tr>\n",
               $field_id,
                rcube_utils::rep_specialchars_output($this->gettext('autoreplystartdate')),
                $input_autoresponderstartdate->show($settings['startdate']));

        // vacation end date
        $field_id = 'vacation_enddate';
        $input_autoresponderenddate = new html_inputfield(array('name' => '_vacation_enddate', 'id' => $field_id, 'size' => 10));
        $out .= sprintf("<tr><td class=\"title\"><label for=\"%s\">%s</label></td><td>%s</td></tr>\n",
                $field_id,
                rcube_utils::rep_specialchars_output($this->gettext('autoreplyenddate')),
                $input_autoresponderenddate->show($settings['enddate']));

	$field_id = '';
        $input_emptyBar = new html(array());
        $out .= sprintf("<tr><td class=\"title\"><td>%s</td></tr><tr></tr>\n\n\n\n",
                $field_id,
                rcube_utils::rep_specialchars_output($this->gettext('')),
               $input_emptyBar->show($settings['']));

        // Subject for local domain
        $field_id = 'vacation_subject';
        $input_autorespondersubject = new html_inputfield(array('name' => '_vacation_subject', 'id' => $field_id, 'size' => 62));
        $out .= sprintf("<tr><td class=\"title\"><label for=\"%s\">%s</label></td><td>%s</td></tr>\n",
                $field_id,
                rcube_utils::rep_specialchars_output($this->gettext('autoreplysubject')),
                $input_autorespondersubject->show($settings['subject']));

	//remove <br> from string and show body message
        $tempbody = str_replace("<br>", "", $settings['body']);
        $settings['body'] = $tempbody;

        $tempextrnalbody = str_replace("<br>", "", $settings['bodyexternaluser']);
        $settings['bodyexternaluser'] = $tempextrnalbody;


        // Out of office body for local domain
        $field_id = 'vacation_body';
        $input_autoresponderbody = new html_textarea(array('name' => '_vacation_body', 'id' => $field_id, 'cols' => 60, 'rows' => 10));
        $out .= sprintf("<tr><td class=\"title\"><label for=\"%s\">%s</label></td><td>%s</td></tr>\n",
                $field_id,
                rcube_utils::rep_specialchars_output($this->gettext('autoreplymessage')),
                $input_autoresponderbody->show($settings['body']));

	$field_id = '';
        $input_emptyBar = new html(array());
        $out .= sprintf("<tr><td class=\"title\"><td>%s</td></tr>\n",
                $field_id,
                rcube_utils::rep_specialchars_output($this->gettext('')),
               $input_emptyBar->show($settings['']));

	 // Auto-reply enabled/disabled 
        $field_id = 'vacation_autoreplyextstatus';
        $input_autoresponderautoreplyextstatus = new html_checkbox(array('name' => '_vacation_autoreplyextstatus', 'id' => $field_id, 'value' => 1));
        $out .= sprintf("<tr><td class=\"title\"><label for=\"%s\">%s</label></td><td>%s</td></tr>\n",
                $field_id,
                rcube_utils::rep_specialchars_output($this->gettext('autoreplyextstatus')),
               $input_autoresponderautoreplyextstatus->show($settings['autoreplyextstatus']));

	// Subject for except local domain
        $field_id = 'vacation_subject_externaluser';
       	$input_autorespondersubject_externaluser = new html_inputfield(array('name' => '_vacation_subject_externaluser', 'id' => $field_id, 'size' => 62));
        $out .= sprintf("<tr><td class=\"title\"><label for=\"%s\">%s</label></td><td>%s</td></tr>\n",
                $field_id,
                rcube_utils::rep_specialchars_output($this->gettext('autoreplysubject_externaluser')),
                $input_autorespondersubject_externaluser->show($settings['subjectexternaluser']));


	// Out of office body except local domain
     	$field_id = 'vacation_body_externaluser';
        $input_autoresponderbody_externaluser = new html_textarea(array('name' => '_vacation_body_externaluser', 'id' => $field_id, 'cols' => 60, 'rows' => 10));
        $out .= sprintf("<tr><td class=\"title\"><label for=\"%s\">%s</label></td><td>%s</td></tr>\n",
                $field_id,
                rcube_utils::rep_specialchars_output($this->gettext('autoreplymessage_externaluser')),
                $input_autoresponderbody_externaluser->show($settings['bodyexternaluser']));
	

        $out .= "</tbody></table></fieldset>\n";

        $rcmail->output->add_gui_object('vacationform', 'vacation-form');
        return $out;
    }
}

?>
