<?php


class ideolve_tokencache_db
{

    protected $cache;
    protected $rc;

    // A prefix for the cache key used in the session and in the key field of the cache table
    const PREFIX = 'IDEOLVE_TOKEN';


    /**
     * Initialize and return cache object
     */
    protected function get_cache()
    {
        if (!$this->cache) {

            $rcmail = rcube::get_instance();
            $ttl    = 12 * 60 * 60; // default: 12 hours
            $ttl    = $rcmail->config->get('ideolve_token_cache_ttl', $ttl);
            $type   = $rcmail->config->get('ideolve_token_cache', 'db');
            $prefix = self::PREFIX;

            // Add session identifier to the prefix to prevent from removing attachments
            // in other sessions of the same user (#1490542)
            if ($id = session_id()) {
                $prefix .= $id;
            }

            // Init SQL cache (disable cache data serialization)
            $this->cache = $rcmail->get_cache($prefix, $type, $ttl, false);
        }

        return $this->cache;
    }
    function isInitialised()
    {
	return true; // since RC is managing the cache db table, we do not have to initialise the table
    }
    function get(&$token)
    {	
	$RESULT=false;
	while( true )
	{
        	$cache = $this->get_cache();
		if( $cache )
		{
       	 		$key   = $this->_key();
			$data  = $cache->read( $key );
			if ($data !== null && $data !== false) 
        		{
				$token = base64_decode( $data );
				$RESULT=true;
			}
		}
		break;
	}
	return $RESULT;
    }
    /**
     * Save an attachment from a non-upload source (draft or forward)
     */
    function save($args)
    {
        $args['status'] = false;

        $cache = $this->get_cache();
        $key   = $this->_key();

        if ($args['token']) {
            $args['data'] = $args['token'];

            if ($args['data'] === false) {
                return $args;
            }

            $args['token'] = null;
        }

        $data   = base64_encode($args['data']);
        $status = $cache->write($key, $data);

        if ($status) {
            $args['id'] = $key;
            $args['status'] = true;
        }

        return $args;
    }

    /**
     * Remove an attachment from storage
     * This is triggered by the remove attachment button on the compose screen
     */
    function remove($args)
    {
        $cache  = $this->get_cache();
        $status = $cache->remove($args['id']);

        $args['status'] = true;

        return $args;
    }

    /**
     * Delete all temp ideolve tokens associated with this user
     */
    function cleanup($args)
    {
        // check if cache object exist, it may be empty on session_destroy (#1489726)
        if ($cache = $this->get_cache()) {
            $cache->remove($args['group'], true);
        }
    }

    /**
     * Helper method to generate a unique key for the given ideolve user token
     */
    protected function _key()
    {
        return md5(time() . $_SESSION['user_id']);
    }
}
?>
