<?php

/*******************************************************************************
 * RCimapSync Roundcube Plugin (Roundcube version 1.0-beta and above)
 * This software distributed under the terms of the GNU General Public License 
 * as published by the Free Software Foundation
 * Further details on the GPL license can be found at
 * http://www.gnu.org/licenses/gpl.html
 * By contributing authors release their contributed work under this license 
 * For more information see README.md file
 ******************************************************************************/

 class imapsync extends rcube_plugin {
	public $task = 'settings';
	private $rc;
	private $show_folder;
	function init() {
		$this->rc = rcube::get_instance ();
		$this->load_config ( 'config.inc.php.dist' );
		$this->load_config ();
		$this->add_texts ( 'localization/', true );
		$this->show_folder = $this->rc->config->get ( 'imapsync_folder' );
		
		$this->add_hook ( 'settings_actions', array (
				$this,
				'settings_actions' 
		) );
		
		$this->register_action ( 'plugin.imapsync', array (
				$this,
				'init_html' 
		) );
		$this->register_action ( 'plugin.imapsync.save', array (
				$this,
				'save' 
		) );
		$this->register_action ( 'plugin.imapsync.del', array (
				$this,
				'del' 
		) );
		$this->register_action ( 'plugin.imapsync.enable', array (
				$this,
				'enable' 
		) );
		$this->register_action ( 'plugin.imapsync.disable', array (
				$this,
				'disable' 
		) );
		if (strpos ( $this->rc->action, 'plugin.imapsync' ) === 0) {
			$this->include_script ( 'imapsync.js' );
		}
	}
	function init_html() {
		$this->register_handler ( 'plugin.imapsync_form', array (
				$this,
				'gen_form' 
		) );
		$this->register_handler ( 'plugin.imapsync_table', array (
				$this,
				'gen_table' 
		) );
		$this->rc->output->set_pagetitle ( $this->gettext ( 'imapsync' ) );
		$this->rc->output->send ( 'imapsync.imapsync' );
	}
	function disable() {
		$id = rcube_utils::get_input_value ( '_id', rcube_utils::INPUT_GET );
		if ($id != 0 || $id != '') {
			$sql = "UPDATE imapsync SET active = '0' WHERE id = '$id'";
			$update = $this->rc->db->query ( $sql );
		}
	}
	function enable() {
		$id = rcube_utils::get_input_value ( '_id', rcube_utils::INPUT_GET );
		if ($id != 0 || $id != '') {
			$sql = "UPDATE imapsync SET active = '1' WHERE id = '$id'";
			$update = $this->rc->db->query ( $sql );
		}
	}
	function del() {
		$id = rcube_utils::get_input_value ( '_id', rcube_utils::INPUT_GET );
		if ($id != 0 || $id != '') {
			$sql = "DELETE FROM imapsync WHERE id = '$id'";
			$delete = $this->rc->db->query ( $sql );
		}
	}
	function save() {
		$id = rcube_utils::get_input_value ( '_id', rcube_utils::INPUT_POST );
		$mailbox = $this->rc->user->data ['username'];
		$protocol = rcube_utils::get_input_value ( '_imapsyncprotocol', rcube_utils::INPUT_POST );
		$protocol = strtoupper ( $protocol ); // Remark: we only support imap now
		$server = rcube_utils::get_input_value ( '_imapsyncserver', rcube_utils::INPUT_POST );
		$user = rcube_utils::get_input_value ( '_imapsyncuser', rcube_utils::INPUT_POST );
		$pass = base64_encode ( rcube_utils::get_input_value ( '_imapsyncpass', rcube_utils::INPUT_POST ) );
		$dest_pass = base64_encode ( rcube_utils::get_input_value ( '_imapsyncdestpass', rcube_utils::INPUT_POST ) );
		$folder = rcube_utils::get_input_value ( '_imapsyncfolder', rcube_utils::INPUT_POST );
		$pollinterval = rcube_utils::get_input_value ( '_imapsyncpollinterval', rcube_utils::INPUT_POST );
		$keep = rcube_utils::get_input_value ( '_imapsynckeep', rcube_utils::INPUT_POST );
		$usessl = rcube_utils::get_input_value ( '_imapsyncusessl', rcube_utils::INPUT_POST );
		$fetchall = rcube_utils::get_input_value ( '_imapsyncfetchall', rcube_utils::INPUT_POST );
		$enabled = rcube_utils::get_input_value ( '_imapsyncenabled', rcube_utils::INPUT_POST );
                $subscribeallenabled = rcube_utils::get_input_value ( '_imapsyncsubscribeall', rcube_utils::INPUT_POST );
                $skipemptyfoldersenabled = rcube_utils::get_input_value ( '_imapsyncskipemptyfolders', rcube_utils::INPUT_POST );
                $maxage = rcube_utils::get_input_value ( '_imapsyncmaxage', rcube_utils::INPUT_POST );
		$newentry = rcube_utils::get_input_value ( '_imapsyncnewentry', rcube_utils::INPUT_POST );
		if (! $keep) {
			$keep = 0;
		} else {
			$keep = 1;
		}
		if (! $enabled) {
			$enabled = 0;
		} else {
			$enabled = 1;
		}
		if (! $usessl) {
			$usessl = 0;
		} else {
			$usessl = 1;
		}
		if (! $fetchall) {
			$fetchall = 0;
		} else {
			$fetchall = 1;
		}
                if (! $skipemptyfoldersenabled) {
                        $skipemptyfoldersenabled = 0;
                } else {
                        $skipemptyfoldersenabled = 1;
                }
                if (! $subscribeallenabled) {
                        $subscribeallenabled = 0;
                } else {
                        $subscribeallenabled = 1;
                }
//		$mda = $this->rc->config->get ( 'imapsync_mda' );
		if ($newentry or $id == '') {
			$sql = "SELECT * FROM imapsync WHERE mailbox='" . $mailbox . "'";
			$result = $this->rc->db->query ( $sql );
			$limit = $this->rc->config->get ( 'imapsync_limit' );
			$num_rows = $this->rc->db->num_rows ( $result );
			if ($num_rows < $limit) {
				$sql = "INSERT INTO imapsync (mailbox, active, src_server, src_user, src_password, dest_password, src_folder, poll_time, fetchall, keep, protocol, subscribeall, skipempty, maxage, usessl, src_auth, mda) VALUES ('$mailbox', '$enabled', '$server', '$user', '$pass', '$dest_pass', '$folder', '$pollinterval', '$fetchall', '$keep', '$protocol', '$subscribeallenabled', '$skipemptyfoldersenabled', '$maxage', '$usessl', 'password', '' )";
				$insert = $this->rc->db->query ( $sql );
				$this->rc->output->command ( 'display_message', $this->gettext ( 'successfullysaved' ), 'confirmation' );
			} else {
				$this->rc->output->command ( 'display_message', 'Error: ' . $this->gettext ( 'imapsynclimitreached' ), 'error' );
			}
		} else {
			$sql = "UPDATE imapsync SET mailbox = '$mailbox', active = '$enabled', keep = '$keep', protocol = '$protocol', subscribeall = '$subscribeallenabled', skipempty = '$skipemptyfoldersenabled', maxage = '$maxage', src_server = '$server', src_user = '$user', src_password = '$pass', src_folder = '$folder', poll_time = '$pollinterval', fetchall = '$fetchall', usessl = '$usessl', src_auth = 'password', mda = '', dest_password = '$dest_pass' WHERE id = '$id'";
			$update = $this->rc->db->query ( $sql );
			$this->rc->output->command ( 'display_message', $this->gettext ( 'successfullysaved' ), 'confirmation' );
		}
		$this->init_html ();
	}
	function gen_form() {
		$id = rcube_utils::get_input_value ( '_id', rcube_utils::INPUT_GET );
		$mailbox = $this->rc->user->data ['username'];
		
		// reasonable(?) defaults
		$pollinterval = '10';
		$usessl = 1;
		$fetchall = 0;
		$keep = 1;
		$enabled = 1;
		$protocol = 'IMAP';
		$subscribeallenabled = 1;
		$skipemptyfoldersenabled = 1;
		$maxage = '365';
		
		// auslesen start
		if ($id != '' || $id != 0) {
			$sql = "SELECT * FROM imapsync WHERE mailbox='" . $mailbox . "' AND id='" . $id . "'";
			$result = $this->rc->db->query ( $sql );
			while ( $row = $this->rc->db->fetch_assoc ( $result ) ) {
				$enabled = $row ['active'];
				$keep = $row ['keep'];
				$mailget_id = $row ['id'];
				$protocol = $row ['protocol'];
				$server = $row ['src_server'];
				$user = $row ['src_user'];
				$pass = base64_decode ( $row ['src_password'] );
				$dest_pass = base64_decode ( $row ['dest_password'] );
				$folder = $row ['src_folder'];
				$pollinterval = $row ['poll_time'];
				$fetchall = $row ['fetchall'];
				$usessl = $row ['usessl'];
                                $subscribeallenabled = $row ['subscribeall'];
                                $skipemptyfoldersenabled = $row ['skipempty'];
                                $maxage = $row ['maxage'];
			}
		}
		$newentry = 0;
		$out .= '<fieldset><legend>' . $this->gettext ( 'imapsync_to' ) . ' ' . $mailbox . '</legend>' . "\n";
		$out .= '<br />' . "\n";
		$out .= '<table' . $attrib_str . ">\n\n";
		$hidden_id = new html_hiddenfield ( array (
				'name' => '_id',
				'value' => $mailget_id 
		) );
		$out .= $hidden_id->show ();
		
		$field_id = 'imapsyncprotocol';
		$input_imapsyncprotocol = new html_select ( array (
				'name' => '_imapsyncprotocol',
				'id' => $field_id,
				'onchange' => 'imapsync_toggle_folder();' 
		) );
		$input_imapsyncprotocol->add ( array (
				'IMAP',
		), array (
				'IMAP',
		) );
		$out .= sprintf ( "<tr><td class=\"title\"><label for=\"%s\">%s</label>:</td><td>%s</td></tr>\n", $field_id, rcube_utils::rep_specialchars_output ( $this->gettext ( 'imapsyncprotocol' ) ), $input_imapsyncprotocol->show ( $protocol ) );
		
		$field_id = 'imapsyncserver';
		$input_imapsyncserver = new html_inputfield ( array (
				'name' => '_imapsyncserver',
				'id' => $field_id,
				'maxlength' => 320,
				'size' => 40 
		) );
		$out .= sprintf ( "<tr><td class=\"title\"><label for=\"%s\">%s</label>:</td><td>%s</td></tr>\n", $field_id, rcube_utils::rep_specialchars_output ( $this->gettext ( 'imapsyncserver' ) ), $input_imapsyncserver->show ( $server ) );
		
		$field_id = 'imapsyncuser';
		$input_imapsyncuser = new html_inputfield ( array (
				'name' => '_imapsyncuser',
				'id' => $field_id,
				'maxlength' => 320,
				'size' => 40 
		) );
		$out .= sprintf ( "<tr><td class=\"title\"><label for=\"%s\">%s</label>:</td><td>%s</td></tr>\n", $field_id, rcube_utils::rep_specialchars_output ( $this->gettext ( 'username' ) ), $input_imapsyncuser->show ( $user ) );
		
		$field_id = 'imapsyncpass';
		$input_imapsyncpass = new html_passwordfield ( array (
				'name' => '_imapsyncpass',
				'id' => $field_id,
				'maxlength' => 320,
				'size' => 40,
				'autocomplete' => 'off' 
		) );
		$out .= sprintf ( "<tr><td class=\"title\"><label for=\"%s\">%s</label>:</td><td>%s</td></tr>\n", $field_id, rcube_utils::rep_specialchars_output ( $this->gettext ( 'password' ) ), $input_imapsyncpass->show ( $pass ) );
		
		$field_id = 'imapsyncdestpass';
		$input_imapsyncdestpass = new html_passwordfield ( array (
				'name' => '_imapsyncdestpass',
				'id' => $field_id,
				'maxlength' => 320,
				'size' => 40,
				'autocomplete' => 'off' 
		) );
		$out .= sprintf ( "<tr><td class=\"title\"><label for=\"%s\">%s</label>:</td><td>%s</td></tr>\n", $field_id, rcube_utils::rep_specialchars_output ( $this->gettext ( 'dest_password' ) ), $input_imapsyncdestpass->show ( $dest_pass ) );
		
		if ($this->show_folder) {
			$field_id = 'imapsyncfolder';
			$input_imapsyncfolder = new html_inputfield ( array (
					'name' => '_imapsyncfolder',
					'id' => $field_id,
					'maxlength' => 320,
					'size' => 40 
			) );
			$out .= sprintf ( "<tr id=\"imapsync_folder_display\"" . (($protocol != "IMAP") ? ("style=\"display: none;\"") : ("")) . "><td class=\"title\"><label for=\"%s\">%s</label>:</td><td>%s</td></tr>\n", $field_id, rcube_utils::rep_specialchars_output ( $this->gettext ( 'imapsyncfolder' ) ), $input_imapsyncfolder->show ( $folder ) );
		}
		
		$field_id = 'imapsyncpollinterval';
		$input_imapsyncpollinterval = new html_select ( array (
				'name' => '_imapsyncpollinterval',
				'id' => $field_id 
		) );
		$input_imapsyncpollinterval->add ( array (
				'5',
				'10',
				'15',
				'20',
				'25',
				'30',
				'60' 
		), array (
				'5',
				'10',
				'15',
				'20',
				'25',
				'30',
				'60' 
		) );
		$out .= sprintf ( "<tr><td class=\"title\"><label for=\"%s\">%s</label>:</td><td>%s</td></tr>\n", $field_id, rcube_utils::rep_specialchars_output ( $this->gettext ( 'imapsyncpollinterval' ) ), $input_imapsyncpollinterval->show ( "$pollinterval" ) );
		
                $field_id = 'imapsyncenabled';
                $input_imapsyncenabled = new html_checkbox ( array (
                                'name' => '_imapsyncenabled',
                                'id' => $field_id,
                                'value' => '1'
                ) );
                $out .= sprintf ( "<tr><td class=\"title\"><label for=\"%s\">%s</label>:</td><td>%s</td></tr>\n", $field_id, rcube_utils::rep_specialchars_output ( $this->gettext ( 'imapsyncenabled' ) ), $input_imapsyncenabled->show ( $enabled ) );

		$field_id = 'imapsynckeep';
		$input_imapsynckeep = new html_checkbox ( array (
				'name' => '_imapsynckeep',
				'id' => $field_id,
				'value' => '1' 
		) );
		$out .= sprintf ( "<tr><td class=\"title\"><label for=\"%s\">%s</label>:</td><td>%s</td></tr>\n", $field_id, rcube_utils::rep_specialchars_output ( $this->gettext ( 'imapsynckeep' ) ), $input_imapsynckeep->show ( $keep ) );
		
/*		$field_id = 'imapsyncfetchall';
		$input_imapsyncfetchall = new html_checkbox ( array (
				'name' => '_imapsyncfetchall',
				'id' => $field_id,
				'value' => '1' 
		) );
		$out .= sprintf ( "<tr><td class=\"title\"><label for=\"%s\">%s</label>:</td><td>%s</td></tr>\n", $field_id, rcube_utils::rep_specialchars_output ( $this->gettext ( 'imapsyncfetchall' ) ), $input_imapsyncfetchall->show ( $fetchall ) );
		
		$field_id = 'imapsyncusessl';
		$input_imapsyncusessl = new html_checkbox ( array (
				'name' => '_imapsyncusessl',
				'id' => $field_id,
				'value' => '1' 
		) );
		$out .= sprintf ( "<tr><td class=\"title\"><label for=\"%s\">%s</label>:</td><td>%s</td></tr>\n", $field_id, rcube_utils::rep_specialchars_output ( $this->gettext ( 'imapsyncusessl' ) ), $input_imapsyncusessl->show ( $usessl ) );
*/		
		$field_id = 'imapsyncskipemptyfolders';
		$input_imapsyncskipemptyfolders = new html_checkbox ( array (
				'name' => '_imapsyncskipemptyfolders',
				'id' => $field_id,
				'value' => '1' 
		) );
		$out .= sprintf ( "<tr><td class=\"title\"><label for=\"%s\">%s</label>:</td><td>%s</td></tr>\n", $field_id, rcube_utils::rep_specialchars_output ( $this->gettext ( 'imapsyncskipemptyfolders' ) ), $input_imapsyncskipemptyfolders->show ( $skipemptyfoldersenabled ) );
		
                $field_id = 'imapsyncsubscribeall';
                $input_imapsyncsubscribeall = new html_checkbox ( array (
                                'name' => '_imapsyncsubscribeall',
                                'id' => $field_id,
                                'value' => '1'
                ) );
                $out .= sprintf ( "<tr><td class=\"title\"><label for=\"%s\">%s</label>:</td><td>%s</td></tr>\n", $field_id, rcube_utils::rep_specialchars_output ( $this->gettext ( 'imapsyncsubscribeall' ) ), $input_imapsyncsubscribeall->show ( $subscribeallenabled ) );

                $field_id = 'imapsyncmaxage';
                $input_imapsyncmaxage = new html_inputfield ( array (
                                'name' => '_imapsyncmaxage',
                                'id' => $field_id,
                                'maxlength' => 320,
                                'size' => 10
                ) );
                $out .= sprintf ( "<tr><td class=\"title\"><label for=\"%s\">%s</label>:</td><td>%s</td></tr>\n", $field_id, rcube_utils::rep_specialchars_output ( $this->gettext ( 'imapsyncmaxage' ) ), $input_imapsyncmaxage->show ( $maxage ) );

		if ($id != '' || $id != 0) {
			$field_id = 'imapsyncnewentry';
			$input_imapsyncnewentry = new html_checkbox ( array (
					'name' => '_imapsyncnewentry',
					'id' => $field_id,
					'value' => '1' 
			) );
			$out .= sprintf ( "<tr><td class=\"title\"><label for=\"%s\">%s</label>:</td><td>%s</td></tr>\n", $field_id, rcube_utils::rep_specialchars_output ( $this->gettext ( 'imapsyncnewentry' ) ), $input_imapsyncnewentry->show ( $newentry ) );
		}
		
		$out .= "\n</table>";
		$out .= '<br />' . "\n";
		$out .= "</fieldset>\n";
		$this->rc->output->add_gui_object ( 'imapsyncform', 'imapsync-form' );
		return $out;
	}
	function gen_table($attrib) {
		$mailbox = $this->rc->user->data ['username'];
		$sql = "SELECT * FROM imapsync WHERE mailbox='$mailbox'";
		$result = $this->rc->db->query ( $sql );
		$num_rows = $this->rc->db->num_rows ( $result );
		$limit = $this->rc->config->get ( 'imapsync_limit' );
		$out = '<fieldset><legend>' . $this->gettext ( 'imapsync_entries' ) . " (<span id=\"imapsync_items_number\">$num_rows</span>/$limit)" . '</legend>' . "\n";
		$out .= '<br />' . "\n";
		$fetch_table = new html_table ( array (
				'id' => 'fetch-table',
				'class' => 'records-table',
				'cellspacing' => '0',
				'cols' => ($this->show_folder) ? 5 : 4 
		) );
		$fetch_table->add_header ( array (
				'width' => ($this->show_folder) ? ('100px') : ('184px') 
		), $this->gettext ( 'imapsyncserver' ) );
		$fetch_table->add_header ( array (
				'width' => ($this->show_folder) ? ('100px') : ('184px') 
		), $this->gettext ( 'username' ) );
		if ($this->show_folder) {
			$fetch_table->add_header ( array (
					'width' => '100px' 
			), $this->gettext ( 'imapsyncfolder' ) );
		}
		$fetch_table->add_header ( array (
				'width' => '26px' 
		), '' );
		$fetch_table->add_header ( array (
				'width' => '26px' 
		), '' );
		
		while ( $row = $this->rc->db->fetch_assoc ( $result ) ) {
			$class = ($class == 'odd' ? 'even' : 'odd');
			if ($row ['id'] == rcube_utils::get_input_value ( '_id', rcube_utils::INPUT_GET )) {
				$class = 'selected';
			}
			$fetch_table->set_row_attribs ( array (
					'class' => $class,
					'id' => 'fetch_' . $row ['id'] 
			) );
			$this->_fetch_row ( $fetch_table, $row ['src_server'], $row ['src_user'], $row ['src_folder'], $row ['active'], $row ['id'], $attrib );
		}
		if ($num_rows == 0) {
			$fetch_table->add ( array (
					'colspan' => '4' 
			), rcube_utils::rep_specialchars_output ( $this->gettext ( 'nofetch' ) ) );
			$fetch_table->set_row_attribs ( array (
					'class' => 'odd' 
			) );
			$fetch_table->add_row ();
		}
		$out .= "<div id=\"fetch-cont\">" . $fetch_table->show () . "</div>\n";
		$out .= '<br />' . "\n";
		$out .= "</fieldset>\n";
		return $out;
	}
	private function _fetch_row($fetch_table, $col_remoteserver, $col_remoteuser, $col_folder, $active, $id, $attrib) {
		$fetch_table->add ( array (
				'onclick' => 'imapsync_edit(' . $id . ');' 
		), $col_remoteserver );
		$fetch_table->add ( array (
				'onclick' => 'imapsync_edit(' . $id . ');' 
		), $col_remoteuser );
		if ($this->show_folder) {
			$fetch_table->add ( array (
					'onclick' => 'imapsync_edit(' . $id . ');' 
			), $col_folder );
		}
		$disable_button = html::img ( array (
				'src' => $attrib ['enableicon'],
				'alt' => $this->gettext ( 'enabled' ),
				'border' => 0,
				'id' => 'img_' . $id 
		) );
		$enable_button = html::img ( array (
				'src' => $attrib ['disableicon'],
				'alt' => $this->gettext ( 'disabled' ),
				'border' => 0,
				'id' => 'img_' . $id 
		) );
		$del_button = html::img ( array (
				'src' => $attrib ['deleteicon'],
				'alt' => $this->gettext ( 'delete' ),
				'border' => 0 
		) );
		if ($active == 1) {
			$status_button = $disable_button;
		} else {
			$status_button = $enable_button;
		}
		$fetch_table->add ( array (
				'id' => 'td_' . $id,
				'onclick' => 'row_edit(' . $id . ',' . $active . ');' 
		), $status_button );
		$fetch_table->add ( array (
				'id' => 'td_' . $id,
				'onclick' => 'row_del(' . $id . ');' 
		), $del_button );
		return $fetch_table;
	}
	function settings_actions($args) {
		$args ['actions'] [] = array (
				'action' => 'plugin.imapsync',
				'label' => 'imapsync.imapsync',
				'title' => 'imapsync.imapsync' 
		);
		return $args;
	}
}

?>
