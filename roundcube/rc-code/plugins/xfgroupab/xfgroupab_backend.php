<?php

class xfgroupab_backend extends rcube_ldap
{
    public $xf_base_dn;
    private $ldap_debug;

    function __construct($p, $debug=false, $mail_domain=null )
    {
        parent::__construct($p, $debug, $mail_domain);

        $this->ldap_debug = rcube::get_instance()->config->get('ldap_debug');
    }

    /**
    * @Override
    * Overrided Function to get list of group members.
    **/
    function list_group_members($dn, $count = false, $entries = null)
    {
        $group_members = array();
        $rcube = rcube::get_instance();

	self::ldap_debug_log("In xfgroupab_backend:list_group_members dn: ".$dn);

        // fetch group object
        if ($entries == null || empty($entries)) {
            $attribs = array('dn','objectClass','cn','mail','displayName');
            $ldap_data = $this->ldap->search($dn, '(objectClass=*)','base', $attribs);
	    $entries = array();
	    
	    foreach ($ldap_data as $entry) {
                if (!$entry['dn'])
                    $entry['dn'] = $ldap_data->get_dn();
   
       	  	$group_name = $entry['cn'];
		self::ldap_debug_log("In xfgroupab_backend: ".  $ldap_data->count());
		$entries[] = $entry;
            }

            if ($entries === false) {
		self::ldap_debug_log("In xfgroupab_backend: read_entries returned false ".$dn);
                return $group_members;
            }
        }
         
	self::ldap_debug_log("In xfgroupab_backend: entries:");
	self::ldap_debug_log($entries);

        for ($i=0; $i < sizeof($entries);$i++) {
            $entry = $entries[$i];
            $attrs = array();

		self::ldap_debug_log("In xfgroupab_backend: getting members for group entry ");
		self::ldap_debug_log($entry['cn']);
		self::ldap_debug_log($entry['mail']);
                    $members       = $this->_list_group_members_xf($dn, $entry, $member_attr, $count);
                    $group_members = array_merge($group_members, $members);
                    $attrs[]       = $member_attr;
                if ($this->prop['sizelimit'] && count($group_members) > $this->prop['sizelimit']) {
                    break ;
                }
        }

	self::ldap_debug_log("In xfgroupab_backend: returning group members ".count($group_members));
        return array_filter($group_members);
    }

   /**
    * Function to get list of group members from xf directory.
    **/
    private function _list_group_members_xf($dn, $entry, $attr, $count)
    {
        // read these attributes for all members
        $attrib =  array('dn','cn','mail','displayName');

	//Filter to get member of group cn
        $filter = '(mithiMemberOf='.$entry['cn'][0].')';

	//Used ldap search instead of ldap read
	//Since, xf group are part of users so, to traverse through all users.
	$members = $this->search_entries($this->xf_base_dn, $filter, $attrib); 

        if ($members == false) {
	    self::ldap_debug_log("In xfgroupab_backend:_list_group_members_xf read_entries returned empty dn: ".$this->xf_base_dn);
            $members = array();
        }

        // for nested groups, call recursively
        //$nested_group_members = $this->list_group_members($entry[$attr][$i], $count, $members);
	//In xf group case nested groups are not there currently
	$nested_group_members = array();

        unset($members['count']);
        $group_members = array_merge(array_filter($members), $nested_group_members);

	self::ldap_debug_log("In _list_group_members_xf:: returning group members");
	self::ldap_debug_log($group_members);

        return $group_members;
    }

    /**
     * Wrapper for ldap_search() + ldap_get_entries()
     *
     * @see ldap_search()
     * @see ldap_get_entries()
     */
    private function search_entries($dn, $filter, $attributes = null)
    {
	self::ldap_debug_log("C: Search $dn [{$filter}]");

        if ($this->ldap->conn && $dn) {
            $result = @ldap_search($this->ldap->conn, $dn, $filter, $attributes, 0, (int)$this->config['sizelimit'], (int)$this->config['timelimit']);
            if ($result === false) {
		self::ldap_debug_log("ldap_search() failed with " . ldap_error($this->ldap->conn));
                return false;
            }

	    self::ldap_debug_log("S: OK");
            return ldap_get_entries($this->ldap->conn, $result);
        }

        return false;
    }

    /**
    *
    * Function to print ldap debug log if ldap_debug is on.
    *
    **/
    private function ldap_debug_log($message) {
        if($this->ldap_debug === true) {
            rcube::write_log('ldap', $message);
        }
    }
}
