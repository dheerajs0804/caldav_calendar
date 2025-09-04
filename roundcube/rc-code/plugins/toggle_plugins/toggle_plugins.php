<?php
/**
 * Enable/Disable plugins
 * This plugin allows to have host wise control over enabling or disabling plugins
 * @version 1.0.0
*/

class toggle_plugins extends rcube_plugin {

    public $task = 'settings';
    private $rc;
    private $rcmail;
    private $path = '/var/www/html/roundcubemail/config/';
    private $plugins;
    private $pluginList;
    private $pluginLabel;
    private $enabledPlugins = array();
    private $disabledPlugins = array();
    private $currentlyEnabledPlugins = array();
    private $formEnabledPlugins = array();
    private $hostPlugins = array();
    private $hostConfigFile = '';
    private $userrole;

    function init() {
        $this->rc = rcube::get_instance();
        $this->rcmail = rcmail::get_instance();
        $this->add_texts('localization/', true);
	$this->userrole = $this->get_userrole();
	//$this->load_config('config.inc.php.dist');
        $this->load_config();
        $this->get_plugin_list();
	if(strpos($this->userrole, 'admin')) {
           $this->add_hook('settings_actions', array($this, 'settings_actions'));
        }
        $this->register_action('plugin.toggle_plugins', array($this, 'init_html_toggle_plugins'));

        $this->get_host();
	$this->get_plugins();
	$this->get_currently_enabled_plugins();
        $this->register_handler("plugin.plugin_checkboxes", array($this, "plugin_checkboxes_handler"));
	$this->register_action("plugin.save_plugins", array($this, "save_plugins_handler"));

        if (strpos ( $this->rc->action, 'plugin.toggle_plugins' ) === 0) {
            $this->include_script('toggle_plugins.js');
        }
    }
    
    private function get_userrole()
    {
      //rcube::console('Inside get userrole');
      //Get xf instance
      include_once '/var/www/html/roundcubemail/plugins/xf_directory/xf.php';
      $xf_instance = xf2::get_instance();
      $user_properties = $xf_instance->get_xf_user_properties(array('userrole'));
      return $user_properties['userrole'];
    }

    function get_plugin_list() {
      $this->pluginList = $this->rc->config->get('pluginList');
      $this->pluginLabel = $this->rc->config->get('pluginLabel');
    }

    function settings_actions($args) {
        $args ['actions'] [] = array(
            'action' => 'plugin.toggle_plugins',
            'label' => 'toggle_plugins.toggle_plugins',
            'title' => 'toggle_plugins.toggle_plugins'
        );
        return $args;
    }

    function init_html_toggle_plugins() {
        $this->rc->output->set_pagetitle($this->gettext('toggle_plugins'));
        $this->rc->output->send('toggle_plugins.toggle_plugins');
    }

    function get_host() {
      //rcube::console("Inside get host");
      list($host) = explode(':', $_SERVER['HTTP_HOST']);
      $this->hostConfigFile = $host . '.inc.php';
    }

    function get_plugins() {
      //rcube::console('Inside get plugins');
      $this->plugins = $this->rc->config->get('plugins');
    }

    function get_currently_enabled_plugins() {
      //rcube::console('Inside get currently enabled plugins');
      $currEnabled = array();
      foreach ($this->pluginList as $plugin) {
        if (in_array($plugin, $this->plugins)) {
	  array_push($currEnabled, $plugin);
        }
      }
      $this->currentlyEnabledPlugins = array_values($currEnabled);
    }

    function plugin_checkboxes_handler() {
        //rcube::console('Inside plugin checkboxes handler');
	$template = '';
	$len = count($this->pluginList);
        for ($i = 0; $i < $len; $i++) {
            $template .= '<div class="custom-control custom-switch">';
            $template .=  '<input type="checkbox" class="custom-control-input" id="' . $this->pluginList[$i] . '" name="' . $this->pluginList[$i] . '"';
            if (in_array($this->pluginList[$i], $this->plugins)) {
	      $template .= 'checked="checked" />';
	    } else {
              $template .= ' />';
            }
            $template .= '<label class="custom-control-label" for="' . $this->pluginList[$i] . '">' . $this->pluginLabel[$i] . '</label>';
            $template .= '</div>';
	    $template .= '<br>';
        }
        return $template;
    }

    function create_host_plugins_config() {
      //rcube::console('Inside create host plugins config');
      $fileStr = '<?php' . PHP_EOL;
      $fileStr .= '$config["plugins"] = array(';
      foreach($this->hostPlugins as $plugin) {
        $fileStr .= '"' . $plugin . '",' . PHP_EOL;
      }
      $fileStr .= ');' . PHP_EOL;
      return $fileStr;
    }

    function get_newly_enabled_plugins() {
      //rcube::console('Inside get newly enabled plugins');
      $this->formEnabledPlugins = array_values(array_diff($this->enabledPlugins, $this->currentlyEnabledPlugins));
    }

    function get_disabled_plugins() {
      //rcube::console('Inside get disabled plugins');
      $this->disabledPlugins = array_values(array_diff($this->pluginList, $this->enabledPlugins));
    }

    function get_host_plugins() {
      //rcube::console('Inside get host plugins');
      $this->hostPlugins = array_values(array_diff($this->plugins, $this->disabledPlugins));
      $this->hostPlugins = array_values(array_merge($this->hostPlugins, $this->formEnabledPlugins));
    }
    
    function save_plugins_handler() {
      //rcube::console('Inside save plugins handler');
      $this->enabledPlugins = explode(',', $_POST['data']);
      $this->get_disabled_plugins();
      $this->get_newly_enabled_plugins();
      $this->get_host_plugins();
      $fileStr = $this->create_host_plugins_config();
      $ret = file_put_contents($this->path . $this->hostConfigFile, $fileStr);
      if ($ret !== false) {
        $this->rcmail->output->show_message($this->gettext("saved"), 'confirmation');
      } else {
        $this->rcmail->output->show_message($this->gettext("failed"), 'error');
      }
    }
}
?>
