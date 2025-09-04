<?php

class textsense extends rcube_plugin
{

    // Enable plugin for task mail
    public $task = 'mail';

    // Roundcube instance
    private $rc;

    // Plugin init function
    // Plugin API call this function to instantiate plugin object
    function init()
    {
        $this->rc = rcube::get_instance();
        // Import CSS
        $this->include_stylesheet($this->local_skin_path() . '/textsense.css');

        // Import lables, messages
        $this->add_texts('localization/');

        // Import java scripts
        $this->include_script('textsense.js');

        // Register plugin actions
        $this->register_action('plugin.textsense.named_entity_recognition', array(
            $this,
            'named_entity_recognition'
        ));
        $this->register_action('plugin.textsense.sentiment_analysis', array(
            $this,
            'sentiment_analysis'
        ));

        $this->register_action('plugin.textsense.sensitivity', array(
            $this,
            'sensitivity'
        ));

        // Add textsense button in mail toolbar menu
        $this->add_textsense_button();

        // Add textsense menu
        if (! $this->rc->action) {
            $this->add_textsense_menu();
        }
    }

    function add_textsense_button()
    {
        $this->add_button(array(
            'type' => 'link-menuitem',
            'class' => 'icon',
            'classact' => 'icon active',
            'innerclass' => 'icon list',
            'label' => 'textsense.textsense',
            'command' => 'plugin.textsense.do_textsense',
            'onclick' => "rcmail.command('menu-open', 'textsensemenu', event.target, event)"
        ), 'messagemenu');
    }

    public function add_textsense_menu()
    {
        $menu = array();
        $ul_attr = array(
            'role' => 'menu',
            'aria-labelledby' => 'aria-label-textsense-menu'
        );
        if ($this->rc->config->get('skin') != 'classic') {
            $ul_attr['class'] = 'toolbarmenu';
        }

        $textsense_options = array(
            'named_entity_recognition',
            'sentiment_analysis',
            'sensitivity'
        );

        foreach ($textsense_options as $option) {
            $menu[] = html::tag('li', null, $this->rc->output->button(array(
                'command' => "plugin.textsense.do_$option",
                'label' => "textsense.$option",
                'class' => 'text',
                'classact' => 'text active'
            )));
        }

        $this->rc->output->add_footer(html::div(array(
            'id' => 'textsensemenu',
            'class' => 'popupmenu',
            'aria-hidden' => 'true'
        ), html::tag('h2', array(
            'class' => 'voice',
            'id' => 'aria-label-textsense-menu'
        ), "Textsense Options Menu") . html::tag('ul', $ul_attr, implode('', $menu))));
    }

    function named_entity_recognition()
    {
        $uid = rcube_utils::get_input_value('_uid', rcube_utils::INPUT_POST);
        $mbox = rcube_utils::get_input_value('_mbox', rcube_utils::INPUT_POST);
        $message = new rcube_message($uid, $mbox);
        $subject = $message->get_header('subject');
        $text_body = "";

        // This will only fetch the text from first part
        if ($message->has_text_part()) {
            $text_body = $message->first_text_part();
        }

        $url = 'http://textsense.ai:8011/ner/ner';
        $data = array(
            'text' => $text_body,
            'source' => 'roundcube',
            'subject' => $subject
        );
        $options = array(
            'http' => array(
                'header' => array(
                    "Content-Type: application/json",
                    // FIXME: Move this API key to config file
                    "X-Api-Key:DpyXAYwPjuKJCUvkRKYIPZcoXDeMQihaMPZ9yIce"
                ),
                'method' => 'POST',
                'content' => json_encode($data)
            )
        );
        $context = stream_context_create($options);
        $mail_text = '';
        try {
            $result = file_get_contents($url, false, $context);
            $mail_text = json_decode($result, true)["ner"];
        } catch (Exception $exception) {
            rcmail::console(__CLASS__ . " Error: " . $exception->__toString());
            $mail_text = $exception->getMessage();
        }
        $this->rc->output->command('plugin.textsense.show_named_entity_recognition_result', '<div style="font-size:14px;padding-right:40px;padding-left:40px;padding-bottom:50px;line-height:1.5;">' . $mail_text . '</div>');
    }

    function sentiment_analysis()
    {
        $uid = rcube_utils::get_input_value('_uid', rcube_utils::INPUT_POST);
        $mbox = rcube_utils::get_input_value('_mbox', rcube_utils::INPUT_POST);
        $message = new rcube_message($uid, $mbox);
        $subject = $message->get_header('subject');
        $text_body = "";

        // This will only fetch the text from first part
        if ($message->has_text_part()) {
            $text_body = $message->first_text_part();
        }

        $url = 'http://textsense.ai:8011/sentiment_analysis/sentiment_analysis';
        $data = array(
            'text' => $text_body,
            'source' => 'roundcube',
            'subject' => $subject
        );
        $options = array(
            'http' => array(
                'header' => array(
                    "Content-Type: application/json",
                    // FIXME: Move this API key to config file
                    "X-Api-Key:DpyXAYwPjuKJCUvkRKYIPZcoXDeMQihaMPZ9yIce"
                ),
                'method' => 'POST',
                'content' => json_encode($data)
            )
        );
        $context = stream_context_create($options);

        $mail_text = '';
        try {
            $result = file_get_contents($url, false, $context);
            $mail_text = json_decode($result, true)["text"];
        } catch (Exception $exception) {
            rcmail::console(__CLASS__ . " Error: " . $exception->__toString());
            $mail_text = $exception->getMessage();
        }
        $this->rc->output->command('plugin.textsense.show_sentiment_analysis_result', '<div style="font-size:14px;padding-right:40px;padding-left:40px;padding-bottom:50px;line-height:1.5;">' . $mail_text . '</div>');
    }

    function sensitivity()
    {
        $uid = rcube_utils::get_input_value('_uid', rcube_utils::INPUT_POST);
        $mbox = rcube_utils::get_input_value('_mbox', rcube_utils::INPUT_POST);
        $message = new rcube_message($uid, $mbox);
        $subject = $message->get_header('subject');
        $text_body = "";

        // This will only fetch the text from first part
        if ($message->has_text_part()) {
            $text_body = $message->first_text_part();
        }

        $url = 'http://textsense.ai:8011/sensitivity/sensitivity';
        $data = array(
            'text' => $text_body,
            'source' => 'roundcube',
            'subject' => $subject
        );
        $options = array(
            'http' => array(
                'header' => array(
                    "Content-Type: application/json",
                    "X-Api-Key:DpyXAYwPjuKJCUvkRKYIPZcoXDeMQihaMPZ9yIce"
                ),
                'method' => 'POST',
                'content' => json_encode($data)
            )
        );
        $context = stream_context_create($options);

        try {
            $result = file_get_contents($url, false, $context);

            rcmail::console(__CLASS__ . " Response data: " . $result . "\n");
        } catch (Exception $e) {
            rcmail::console(__CLASS__ . " Response data: Error" . json_encode($result) . " \n");
        }
        // //$result = '<div>'.$subject.'</div';
        $mail_text = json_decode($result, true)["text"];
        $risk = json_decode($result, true)["risk"];
        $risk_score = json_decode($result, true)["risk_score"];

        $this->rc->output->command('plugin.textsense.show_sensitivity_result', '<div style="font-size:14px;padding-right:40px;padding-left:40px;padding-bottom:50px;line-height:1.5;"><h3>Risk: ' . $risk . '<br>Risk Score: ' . $risk_score . '</h3><br>' . $mail_text . '</div>');
    }
}
?>
