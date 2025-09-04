<?php

/**
* populate_caldav_calendars
*
* Populate caldav calendars into Roundcube
*
* @version 1.0
* @author
* @url http://roundcube.net/plugins/populate_caldav_calendars
*/

class populate_caldav_calendars extends rcube_plugin
{
    // populate_caldav_calendars will be activated in settings page
    public $task = 'calendar';
    private $rc;
    private $caldavserver;
    private $calendaruser;
    private $calendaruserpassword;
    private $caldavclient;
    private $db_calendars = 'caldav_calendars';
    // Crypt key for CalDAV auth
    private $crypt_key;
    static private $debug = null;

    public function init() {
        $this->rc = rcube::get_instance();
        $db = $this->rc->get_dbh();
        $this->db_calendars = $this->rc->config->get('db_table_caldav_calendars', $db->table_name($this->db_calendars));
        $this->crypt_key = $this->rc->config->get("calendar_crypt_key", "%E`c{2;<J2F^4_&._BxfQ<5Pf3qv!m{e");
        $this->caldavserver = $this->rc->config->get('mithi_calendar_server');
        $this->calendaruser = $this->rc->get_user_name();
        $this->calendaruserpassword = $this->rc->get_user_password();
        if(self::$debug === null) {
            self::$debug = $this->rc->config->get('calendar_caldav_debug', False);
        }

        if(!class_exists("populate_caldav_calendars_client")) {
            require_once(dirname(__FILE__).'/populate_caldav_calendars_client.php');
        }
        $this->caldavclient = new mithi\caldav\client\populate_caldav_calendars_client($this->caldavserver, $this->calendaruser, $this->calendaruserpassword);

        $this->add_hook('startup', array($this, 'startup'));
    }

    public function startup($args) {
        if ($args['task'] == 'calendar' && $args['action'] != 'save-pref') {
            $calendar_managed = $this->manage_caldav_server_calendars();
            self::debug_log("calendar_managed: " . print_r($calendar_managed, true));
        }
    }

    private function manage_caldav_server_calendars() {
        $calendars_managed = array();
        $calendars_created = array();
        $calendars_deleted = array();
        $server_calendars = $this->get_server_calendar_list();
        $rc_calendars = $this->get_rc_calendar_list();

        $calendars_created = $this->add_server_calendar_in_rc($server_calendars);
        $calendars_deleted = $this->delete_orphan_calendar_from_rc($server_calendars, $rc_calendars);

        $calendars_managed['created'] = $calendars_created;
        $calendars_managed['deleted'] = $calendars_deleted;

        return $calendars_managed;
    }

    private function get_server_calendar_list() {
        $calendars = array();
        $calendars = $this->caldavclient->getServerCalendars();
        return $calendars;
    }

    private function get_rc_calendar_list() {
        $calendars = array();
        $result = $this->rc->db->query("SELECT *, calendar_id AS id
                      FROM " . $this->db_calendars . "
                      WHERE user_id=?
                      ORDER BY name",
                      $this->rc->user->ID
                  );
        while ($result && ($arr = $this->rc->db->fetch_assoc($result))) {
            $calendars[$arr['calendar_id']] = $arr;
        }
        return $calendars;
    }

    private function delete_orphan_calendar_from_rc($server_calendars, $rc_calendars) {
        $calendars_deleted = array();
        $calendars_to_delete = array();
        $server_calendar_urls = array();

        foreach($server_calendars as $calendar) {
            $server_calendar_urls[] = $this->encode_url($calendar['location']['absolute_href']);
        }

        foreach($rc_calendars as $calendar) {
            if(!in_array($calendar['caldav_url'], $server_calendar_urls)) {
                $calendars_to_delete[] = $calendar;
            }
        }

        foreach($calendars_to_delete as $calendar) {
            self::debug_log("delete_orphan_calendar_from_rc: Deleting orphan calendar ".$calendar['caldav_url']);
            $deleted = $this->delete_calendar($calendar);
            if($deleted) {
                $calendars_deleted[] = $calendar;
            }
        }

        return $calendars_deleted;
    }

    /**
     * Delete the given calendar with all its contents
     *
     * @see calendar_driver::delete_calendar()
     */
    public function delete_calendar($prop) {
        // events and attachments will be deleted by foreign key cascade
        $query = $this->rc->db->query(
            "DELETE FROM " . $this->db_calendars . " WHERE calendar_id=?",
            $prop['id']
        );

        if ($this->rc->config->get('calendar_default_calendar') == $prop['id']) {
            $this->rc->user->save_prefs(array('calendar_default_calendar' => null));
        }

        return $this->rc->db->affected_rows($query);
    }

    private function add_server_calendar_in_rc($server_calendars) {
        $calendars_created = array();
        foreach($server_calendars as $calendar) {
            // Do not add tasks
            if(strcmp("tasks", basename($calendar['location']['absolute_href'])) == 0) {
                continue;
            }
            $calendar_details = array();
            $calendar_details['caldav_url'] = self::encode_url($calendar['location']['absolute_href']);
            $calendar_details['caldav_user'] = $this->calendaruser;
            $calendar_details['caldav_pass'] = $this->calendaruserpassword;
            if($calendar['color'] && $calendar['color']['code']) {
                $calendar_details['color'] = preg_replace('/^#/', '', strval($calendar['color']['code']));
            }
            if(empty($calendar['color'])) {
                $calendar_details['color'] = 'cc0000';
            }
            $calendar_details['name'] = $calendar['name'];
            if(empty($calendar_details['name'])) {
                $calendar_details['name'] = basename($calendar_details['caldav_url']);
            }
            $calendar_details['showalarms'] = 1;

            if($this->is_calendar_exist($calendar_details)) {
                self::debug_log("add_server_calendar_in_rc: Calendar already exist. ".print_r($calendar, true));
                continue;
            }

            $created = $this->create_calendar($calendar_details);
            if($created) {
                self::debug_log("add_server_calendar_in_rc: Calendar created ".print_r($calendar, true));
                $calendars_created[] = $calendar;
            } else {
                self::debug_log("add_server_calendar_in_rc: Failed to create calendar ".print_r($calendar, true));
            }
        }
        return $calendars_created;
    }

    private function is_calendar_exist($cal) {
        $exist = false;
        $result = $this->rc->db->query("SELECT * FROM ".$this->db_calendars." WHERE caldav_url LIKE ? AND user_id = ?", $cal['caldav_url'], $this->rc->user->ID);
        if($this->rc->db->affected_rows($result)) {
            $exist = true;
        }
        return $exist;
    }

    /**
     * Create a new calendar assigned to the current user
     *
     * @param array Hash array with calendar properties
     *                   name: Calendar name
     *                  color: The color of the calendar
     *             caldav_url: CalDAV calendar URL
     *             caldav_tag: CalDAV calendar ctag
     *            caldav_user: CalDAV authentication user
     *            caldav_pass: CalDAV authentication password
     * caldav_oauth_provider: Unique config ID for OAuth2 provider, see config.inc.php
     *
     * @return mixed ID of the calendar on success, False on error
     */
     private function create_calendar($prop) {
         $result = $this->rc->db->query(
             "INSERT INTO " . $this->db_calendars . "
             (user_id, name, color, showalarms, readonly, caldav_url, caldav_tag, caldav_user, caldav_pass, caldav_oauth_provider)
             VALUES (?,?, ?, ?, ?, ?, ?, ?, ?, ?)",
             $this->rc->user->ID,
             $prop['name'],
             $prop['color'],
             $prop['showalarms']?1:0,
             $prop['readonly']?1:0,
             $prop['caldav_url'],
             isset($prop["caldav_tag"]) && $prop["caldav_tag"] ? $prop["caldav_tag"] : null,
             isset($prop["caldav_user"]) && $prop["caldav_user"] ? $prop["caldav_user"] : null,
             isset($prop["caldav_pass"]) && $prop["caldav_pass"] ? $this->encrypt_pass($prop["caldav_pass"]) : null,
             isset($prop["caldav_oauth_provider"]) && $prop["caldav_oauth_provider"] ? $prop["caldav_oauth_provider"] : null
          );

         if ($result)
             return $this->rc->db->insert_id($this->db_calendars);
         return false;
     }

     private function encrypt_pass($pass) {
         $e = new Encryption(MCRYPT_BlOWFISH, MCRYPT_MODE_CBC);
         $p = $e->encrypt($pass, $this->crypt_key);
         return base64_encode($p);
     }

     /**
      * Encodes directory- and filenames using rawurlencode().
      *
      * @see http://stackoverflow.com/questions/7973790/urlencode-only-the-directory-and-file-names-of-a-url
      * @param string Unencoded URL to be encoded.
      * @return Encoded URL.
      */
      private static function encode_url($url) {
          // Don't encode if "%" is already used.
          if(strstr($url, "%") === false) {
              return preg_replace_callback('#://([^/]+)/([^?]+)#', function ($matches) {
                  return '://' . $matches[1] . '/' . join('/', array_map('rawurlencode', explode('/', $matches[2])));
              }, $url);
          }
          else return $url;
      }

      static private function debug_log($message) {
          if(self::$debug === true) {
              rcmail::console(__CLASS__.': '.$message);
          }
      }
}

?>
