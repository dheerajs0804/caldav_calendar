<?php

/**
 * settings_disable_sections plugin
 */
class settings_disable_sections extends rcube_plugin
{

    public $task = 'settings';

    /**
     * initialize plugin
     */
    public function init()
    {
        $this->add_hook('preferences_sections_list', array($this, 'disable_sections'));
        $this->add_hook('preferences_list', array($this, 'disable_section_options'));
        $this->add_hook('settings_actions', array($this, 'disable_settings'));
    }

    public function disable_settings($args){
        $rcmail = rcmail::get_instance();
        $this->load_config();

        //Get settings to disable
        $disable_settings = $rcmail->config->get('disable_settings', array());

        //var_dump ($args);
        foreach ($disable_settings as $setting){
            foreach ($args['actions'] as $key => $val) {
                if ($val['label'] === $setting) {
                    unset($args['actions'][$key]);
                }
            }
        }
        return ($args);
    }


   /**
     * Function to disable options from preferences pane
     * @param $args
     * @return mixed
     */
    public function disable_section_options($args)
    {
        $rcmail = rcmail::get_instance();
        $this->load_config();

        //Get section and its options to disable from config
        $disable_section_options = $rcmail->config->get('disable_section_options', array());

        //var_dump($args);      
        //Go through all provided sections and its options, and disable options as provided by config
        foreach ($disable_section_options as $section => $options) {
            if (in_array($section, $args)) {
                //Get options of section from config file
                foreach($options as $option){
                    //Disable option
                    unset($args['blocks'][$option]);
                }
            }
        }
        return ($args);
    }


   /**
     * @param $args
     * @return mixed
     */
     public function disable_sections($args)
     {
         $rcmail = rcmail::get_instance();
         $this->load_config();

         //var_dump($args);
         // get sections to disable from config
         $disable_sections = $rcmail->config->get('settings_disable_sections', array());

         // go trough all sections provided by the hook and remove the ones that should be disabled according to the config
         foreach ($args["list"] as $section => $value) {
             if (in_array($section, $disable_sections)) {
                 unset($args['list'][$section]);
             }
         }

         return($args);
     }
	
}
