<?php

class quota extends rcube_plugin
{
    const ONE_KB = 1;
    const ONE_MB = 1024;
    const ONE_GB = 1048576;
    const ONE_TB = 1073741824;
    const ONE_PB = INF;

    /**
     * @var string
     */
    public $task = 'settings';

    private $rc;
    private $quotaUsedHR;
    private $quotaAllocatedHR;
    private $quotaUsedKb;
    private $quotaFreeKb;
    private $quotaPercent;
    private $messageLifetime;

    /**
     * Plugin Initialization
     */
    public function init()
    {
        //$this->loadPluginConfig();

	$this->rc = rcmail::get_instance();
	$this->load_config();


        $this->add_texts('localization/', true);
        $this->include_script('js/echarts-4.1.0.common.min.js');
        $this->include_script('js/draw.js');

	$this->add_hook('settings_actions', array($this, 'settings_actions'));

        $this->register_action('plugin.quota', array($this, 'quotaInit'));
	$this->register_action('plugin.update-quota', array($this, 'update_quota'));
    }

    /**
     * register as settings action
     */
    function settings_actions($args)
    {
        $args['actions'][] = array(
            'action' => 'plugin.quota',
            'label'  => 'quota.quota_plugin_title',
            'title'  => 'quota.quota_plugin_title',
        );

        return $args;
    }

    /**
     * Initialize quota
     */
    public function quotaInit()
    {
        $this->register_handler('plugin.body', array($this, 'quotaForm'));

        $this->rc->output->set_pagetitle($this->gettext('quota_plugin_title'));
        $this->rc->output->send('plugin');
    }

    /**
     * Function to update quota
     */
    public function update_quota()
    {
	$this->register_handler('plugin.body', array($this, 'quotaForm'));

        $this->rc->output->set_pagetitle($this->gettext('quota_plugin_title'));

	//Update quota using configured driver
        $response = $this->_updatequota();
        rcube::write_log('quota', "quota plugin: update quota response from configured driver:");
        rcube::write_log('quota', $response);
	
	if ( isset($response['Exit Code']) && $response['Exit Code'] == 0 )
	{ 
	    $this->rc->output->command('display_message', $response['Output'], 'confirmation');
	}
	//Check for null status in case of ws timeout
	else if( $response == NULL )
	{
	    $this->rc->output->command('display_message', $this->gettext('quota_update_failed'), 'error');
	}
	else
	{
	    $this->rc->output->command('display_message', $response['Output'], 'error');
	}


	$this->rc->overwrite_action('plugin.quota');
	$this->rc->output->send('plugin');
    }

    /**
     * Function for quota form
     */
    public function quotaForm()
    {
	$quota = array();

	//Get quota from configured driver if text or chart representation is on
	if ( $this->rc->config->get('enable_text_presentation') || $this->rc->config->get('enable_chart_presentation')  )
	{ 
            $quota = $this->_getquota();
            rcube::write_log('quota', "quota plugin: get quota response from configured driver:");
            rcube::write_log('quota', $quota);

	    //Prepare information required for quota form based on quota response.
	    $this->prepare_quota_form_info($quota);	
	}
	
	//Form button for update quota.
	$update_quota_button = $this->rc->output->button(array(
                'command' => 'plugin.update-quota',
                'type'    => 'input',
                'class'   => 'button mainaction',
                'label'   => 'quota.update_quota_label',
        ));


        $out = html::div(array('class' => 'box'),
                   html::div(array('id' => 'prefs-title', 'class' => 'boxtitle'),
                       $this->gettext('quota_plugin_title')
                   ).
                   html::div(array('class' => 'boxcontent'),
                       // Used Storage
                       (
                           $this->rc->config->get('enable_text_presentation') ?
                           html::p(null, '<b>'.$this->gettext('space_used'). ': </b>' . $this->quotaUsedHR) :
			   ''
                       ) .
		       // Allocated Storage
                       (
                           $this->rc->config->get('enable_text_presentation') ?
                           html::p(null, '<b>'.$this->gettext('space_allocated'). ': </b>' . $this->quotaAllocatedHR) :
                           ''
                       ) .
		       // Automail deletion
                       (
                           $this->rc->config->get('enable_text_presentation') ?
                           html::p(null, '<b>'.$this->gettext('automail_deletion'). ': </b>' . $this->messageLifetime) :
                           ''
                       ) .
                       // chart representation
                       (
                           $this->rc->config->get('enable_chart_presentation') ?
                           //html::p(array('id' => 'chartContainer', 'style' => 'height: 370px; width: 100%; max-width: 600px;')) :
                           html::p(array('id' => 'chartContainer', 'style' => 'height: 370px; width: 100%;')) :
			   ''
                       ) .
		       //Submit button
		       (
			   $this->rc->config->get('update_quota') ?
			   html::p(array('class' => 'formbuttons'), $update_quota_button) :
		           ''
		       ) 
                   )
               );

        $out .= $this->rc->config->get('enable_chart_presentation') ?
                '<script type="text/javascript">
                    var plugin_quota_chart_vars = {
                        charTitle: "' . addslashes($this->gettext('chart_title')) . '",
                        labelUsedSpace: "' . addslashes($this->gettext('space_used')) . '",
                        labelFreeSpace: "' . addslashes($this->gettext('space_free')) . '",
                        quotaUsedKb: ' . $this->quotaUsedKb . ',
                        quotaFreeKb: ' . $this->quotaFreeKb . '
                };

                drawDiskQuota();
                </script>' : '';


	$this->rc->output->add_gui_object('quotaform', 'quota-form');
	$this->include_script('js/quota.js');

	return $this->rc->output->form_tag(array(
            'id'     => 'quota-form',
	    'class'  => 'formcontent',
            'name'   => 'quota-form',
            'method' => 'post',
            'action' => './?_task=settings&_action=plugin.update-quota',
        ), $out);
    }

    /**
    * Function to convert quota in human readable format
    */
    private function prepare_quota_form_info($quota)
    {
	if ($quota['status'] == 'success')
        {
            //Unlimited quota handling
            if( $quota['total'] == 0 )
            {
	   	$this->quotaUsedHR = $this->humanizeKbQuota($quota['used']);
		$this->quotaAllocatedHR = $this->gettext('unlimited');
                $this->quotaUsedKb = $quota['used'];
                $this->quotaFreeKb = static::ONE_PB;
		$this->quotaPercent = "NA";
            }
            else
            {
                $this->quotaUsedHR = $this->humanizeKbQuota($quota['used']);
                $this->quotaAllocatedHR = $this->humanizeKbQuota($quota['total']);	
                $this->quotaUsedKb = $quota['used'] < 0 ? $this->gettext('not_available') : $quota['used'];
                $this->quotaFreeKb = $quota['total'] - $quota['used'];
		$this->quotaPercent = sprintf('%.2f%%', $quota['percent']);
            }
        }
        else
        {
            $this->rc->output->command('display_message', 'Failed to get Quota. Please try again later.', 'error');
            $this->quotaText = $this->gettext('unknown');
            $this->quotaUsedKb = 0;
            $this->quotaFreeKb = static::ONE_PB;
        }

	//Get Mail Deletion policy (messagelifetime)
	$this->messageLifetime = $this->get_messagelifetime();

	//Format text for display
	$this->messageLifetime = empty($this->messageLifetime) ? $this->gettext('not_applicable') : $this->messageLifetime . " Days" ;
	$this->quotaUsedHR = empty($this->quotaUsedHR) ||  $this->quotaUsedHR < 0 ? $this->gettext('not_available') : $this->quotaUsedHR;
	$this->quotaAllocatedHR = empty($this->quotaAllocatedHR) || $this->quotaAllocatedHR < 0 ? $this->gettext('not_available') : $this->quotaAllocatedHR;
    }

    /**
     * Function to get messagelifetime using xf directory plugin.
     */
    private function get_messagelifetime()
    {
     	//Get xf instance
        include_once '/var/www/html/skyconnect/plugins/xf_directory/xf.php';
        $xf_instance = xf::get_instance();

        $user_properties = $xf_instance->get_xf_user_properties(array('messagelifetime'));

        rcube::write_log('quota', "Auto Mail Deletion (In days): " .$user_properties['messagelifetime']);
        return $user_properties['messagelifetime'];
    }


    /**
     * Function to convert quota in human readable format
     */
    protected function humanizeKbQuota($quota, $round = 2)
    {
	if (!is_numeric($quota) || $quota < 0)
	    return $quota;    

	$quota = (float) $quota;
        $units = [
            'PB' => static::ONE_PB,
            'TB' => static::ONE_TB,
            'GB' => static::ONE_GB,
            'MB' => static::ONE_MB,
            'KB' => static::ONE_KB,
        ];

        $partition = [static::ONE_KB, 'KB'];
        foreach ($units as $unit => $size) {
            if ($quota >= $size) {
                $partition = [$size, $unit];
                break;
            }
        }

        return round($quota / $partition[0], $round) . " {$partition[1]}";
    }

    /**
    * Function to get quota from configured driver.
    *
    */
    private function _getquota()
    {
	if (is_object($this->driver)) {
            $result = $this->driver->get();
        }
        elseif (!($result = $this->load_driver())){
            $result = $this->driver->get();
        }

        return $result;
    }

    /**
    * Function to update quota from configured driver.
    *
    */
    private function _updatequota()
    {
        if (is_object($this->driver)) {
            $result = $this->driver->update();
        }
        elseif (!($result = $this->load_driver())){
            $result = $this->driver->update();
        }

        return $result;
    }


    /**
     * Function to Load driver.
     *
     */
    private function load_driver()
    {
	//Default driver will be xf_dovecot_webservice
        $driver = $this->rc->config->get('quota_driver','xf_dovecot_webservice');
	$driver_class  = "rcube_{$driver}_quota";
        $file   = $this->home . "/drivers/$driver.php";

	if (!file_exists($file)) {
            rcube::raise_error(array(
                'code' => 600,
                'type' => 'php',
                'file' => __FILE__, 'line' => __LINE__,
                'message' => "Quota plugin: Unable to open driver file ($file)"
            ), true, false);
            rcube::write_log('quota', "quota plugin: Unable to open driver file ($file).");
            return $this->gettext('internalerror');
        }

        include_once $file;

        if (!class_exists($driver_class, false)) {
            rcube::raise_error(array(
                'code' => 600,
                'type' => 'php',
                'file' => __FILE__, 'line' => __LINE__,
                'message' => "Quota plugin: Broken driver $driver"
            ), true, false);
            rcube::write_log('quota', "quota plugin: Broken driver $driver.");
            return $this->gettext('internalerror');
        }

        $this->driver = new $driver_class();
    }

}
