<?php

class IPRange
{
    /**
    * Function to check ip is present in provided range.
    */
    function ipCheck ($CLIENT_IP, $ALLOWED_IP) {
        $LONG_CLIENT_IP = ip2long($CLIENT_IP);

        if ( strpos($ALLOWED_IP, '/') !== false)
        {
    	    list($net, $mask) = explode('/', $ALLOWED_IP);
            $ALLOWED_NET = ip2long ($net);
            $ALLOWED_MASK = ~((1 << (32 - $mask)) - 1);
	
	    $ALLOWED_IP_MASK = $ALLOWED_NET & $ALLOWED_MASK;	
    	    $CLIENT_IP_MASK = $LONG_CLIENT_IP & $ALLOWED_MASK;
        }
        else
        {
	    $ALLOWED_MASK="";
            $ALLOWED_IP_MASK = ip2long($ALLOWED_IP);
	    $CLIENT_IP_MASK = $LONG_CLIENT_IP;
        }

	rcube::write_log('access_control', "ALLOWED IP/RANGE: ".$ALLOWED_IP_MASK);    
	rcube::write_log('access_control', "CLIENT IP/RANGE: ".$CLIENT_IP_MASK."\n");    
        return ($CLIENT_IP_MASK == $ALLOWED_IP_MASK);
    }

    function convert_range_to_cidr($ipaddress)
    {
        $ip_classes_size=4;
        $cidr="";
	
	//Check for empty address.
        if(!$ipaddress)
        {
	    return $cidr;
        }

        //Associative array of ip class to mask, 
        //array($ip_classes_array_size => $mask);
        $net_mask = array(
		    1 => 8,
		    2 => 16,
		    3 => 24,
		    4 => 32,
	);

        //Associative array of empty classes to associative array merge.
        $empty_classes_merge = array(
		    0 => array(),
		    1 => array(3 => 0),
		    2 => array(2 => 0, 3 => 0),
		    3 => array(1 => 0, 2 => 0, 3 => 0)
	);
	
	//Array of classes
        $ip_classes_array = explode('.', $ipaddress);
	//Size of classes
        $ip_classes_array_size = sizeof($ip_classes_array);

        if ($ip_classes_array_size > $ip_classes_size )
        {
	    //Invalid range provided, return false
	    //empty cidr will block login, since it will return false on comparison with client ip
	    $cidr="";
        }
        else if($ip_classes_array_size == $ip_classes_size)
        {
	    //Provided ip address does not having range.
	    //Returning as it is
	    $cidr = $ipaddress;
        }
        else
        {
	    //No of empty classes
	    $empty_classes = $ip_classes_size - $ip_classes_array_size;

	    //Calculate mask based on size of classes in ip address.
	    $net_mask_value = $net_mask[$ip_classes_array_size];

	    //Merge empty classes filled with 0 with input ip
	    $ip_classes_array = array_merge($ip_classes_array,$empty_classes_merge[$empty_classes]);

	    //Form cidr with imploding ip classes array with '.' and appending net mask value
	    $cidr = implode(".", $ip_classes_array)."/".$net_mask_value;
        }

	return $cidr;
    }
}

/*Calls to ipCheck function
  echo ipCheck ("192.168.1.59", "192.168.0.0/16")."\n";
  echo ipCheck ("192.167.1.59", "192.168.1.0/16")."\n";
  echo ipCheck ("192.168.1.59", "192.168.0.0/16")."\n";
  echo ipCheck ("192.168.1.59", "192.168.0.58")."\n";
  echo ipCheck ("192.255.255.255", "192.0.0.0/8")."\n";
*/

/*Calls to convert_range_to_cidr 
convert_range_to_cidr('192');
convert_range_to_cidr('192.168');
convert_range_to_cidr('192.168.1');
convert_range_to_cidr('192.168.1.11');
*/

?>

