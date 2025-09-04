/*******************************************************************************
 * Fetchmail Roundcube Plugin (Roundcube version 1.0-beta and above)
 * This software distributed under the terms of the GNU General Public License 
 * as published by the Free Software Foundation
 * Further details on the GPL license can be found at
 * http://www.gnu.org/licenses/gpl.html
 * By contributing authors release their contributed work under this license 
 * For more information see README.md file
 ******************************************************************************/

function imapsync_edit(id) {
	window.location.href = '?_task=settings&_action=plugin.imapsync&_id=' + id;
}
function row_del(id) {
	if (id == "") {
		parent.rcmail.display_message(rcmail.gettext('textempty', 'imapsync'),
				'error');
	} else {
		document.getElementById('fetch-table').deleteRow(
				document.getElementById('fetch_' + id).rowIndex);
		rcmail.http_request('plugin.imapsync.del', '_id=' + id, true);
		if (document.getElementById('fetch-table')
				.getElementsByTagName("TBODY").item(0).rows.length == 0) {
			var tbody = document.getElementById('fetch-table')
					.getElementsByTagName("TBODY").item(0);
			var row = document.createElement("TR");
			var cell = document.createElement("TD");
			var text = document.createTextNode(rcmail.gettext('nofetch',
					'imapsync'));
			cell.setAttribute('colspan', '4');
			cell.appendChild(text);
			row.appendChild(cell);
			tbody.appendChild(row);
		}
		$('#imapsync_items_number').text(
				$('#imapsync_items_number').text() - 1);
		parent.rcmail.display_message(rcmail.gettext('successfullydeleted',
				'imapsync'), 'confirmation');
	}
}
function row_edit(id, active) {
	if (id == "") {
		parent.rcmail.display_message(rcmail.gettext('textempty', 'imapsync'),
				'error');
	} else {
		if (active == 1) {
			var active = 0;
			document.getElementById('td_' + id).setAttribute('onclick',
					'row_edit(' + id + ',' + active + ');');
			document.getElementById('img_' + id).src = 'plugins/imapsync/skins/default/disabled.png';
			rcmail.http_request('plugin.imapsync.disable', '_id=' + id, true);
			parent.rcmail.display_message(rcmail.gettext(
					'successfullydisabled', 'imapsync'), 'confirmation');
		} else {
			var active = 1;
			document.getElementById('td_' + id).setAttribute('onclick',
					'row_edit(' + id + ',' + active + ');');
			document.getElementById('img_' + id).src = 'plugins/imapsync/skins/default/enabled.png';
			rcmail.http_request('plugin.imapsync.enable', '_id=' + id, true);
			parent.rcmail.display_message(rcmail.gettext('successfullyenabled',
					'imapsync'), 'confirmation');
		}
	}
}
function imapsync_toggle_folder() {
	switch ($('#imapsyncprotocol').val()) {
	case "IMAP":
		$("#imapsync_folder_display").show()
		break;
	default:
		$("#imapsync_folder_display").hide()
	}
}
if (window.rcmail) {
	rcmail.addEventListener('init', function(evt) {
		rcmail.register_command('plugin.imapsync.save', function() {
			var input_server = rcube_find_object('_imapsyncserver');
			var input_user = rcube_find_object('_imapsyncuser');
			var input_pass = rcube_find_object('_imapsyncpass');
			if (input_server.value == "" || input_user.value == ""
					|| input_pass.value == "") {
				parent.rcmail.display_message(rcmail.gettext('textempty',
						'imapsync'), 'error');
			} else {
				document.forms.imapsyncform.submit();
			}
		}, true);

	})
}
