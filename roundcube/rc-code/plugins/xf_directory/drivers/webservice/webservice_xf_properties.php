<?php

class webservice_xf_properties
{
    
    public function get_xf_properties()
    {
        $rcmail = rcmail::get_instance();
	$xf_properties =array();

        //Get array of xf directory entity, to fetch properties for
        $xf_directory_entities = $rcmail->config->get('xf_directory_entities', array());


	foreach ($xf_directory_entities as $req_entity){
            if (!($this->load_entity($req_entity))){
		$entity_properties = $this->entity->get();
                $xf_properties[$req_entity.'properties'] =  $entity_properties;	
            }	
        }
        return $xf_properties;
    }

    public function set_xf_properties($req_entity, $properties)
    {
	if (!($this->load_entity($req_entity))){
                $status = $this->entity->set($properties);
	}

	return $status;
    }

    private function load_entity($req_entity)
    {
        $entity_class  = "{$req_entity}_properties";
	$file = dirname(__FILE__) . '/'.$entity_class.'.php';
        if (!file_exists($file)) {
	    rcube::write_log('errors', "xf_properties plugin: Unable to open entity file ($file).");
	    exit("xf_properties plugin: Unable to open entity file ($file).");            
        }

        include_once $file;
        if (!class_exists($entity_class, false)) {
	    rcube::write_log('errors', "xf_properties plugin: Broken entity: $req_entity.");
	    exit("xf_properties plugin: Broken entity: $req_entity.");
        }
        $this->entity = new $entity_class();
    }
}
?>
