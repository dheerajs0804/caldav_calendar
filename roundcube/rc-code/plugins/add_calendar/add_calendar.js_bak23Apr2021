function delete_calendar(tdid, url, displayname) {
	if(confirm(rcmail.gettext('confirmcalendardelete', 'add_calendar') + ' ' + displayname)) {
        	rcmail.http_request('plugin.add_calendar.delete', '_url=' + url + '&_displayname=' + displayname, true);
	        document.getElementById('user_calendars').deleteRow(document.getElementById(tdid).rowIndex);
        	if(document.getElementById('user_calendars').getElementsByTagName("TBODY").item(0).rows.length == 0) {
	            var tbody = document.getElementById('user_calendars').getElementsByTagName("TBODY").item(0);
        	    var row = document.createElement("TR");
	            var cell = document.createElement("TD");
        	    var text = document.createTextNode(rcmail.gettext('nofetch', 'add_calendar'));
	            cell.setAttribute('colspan', '3');
        	    cell.appendChild(text);
	            row.appendChild(cell);
        	    tbody.appendChild(row);
		}
	}
}

function row_del(tdid, url, displayname) {
    if (tdid == "") {
        parent.rcmail.display_message(rcmail.gettext('tdidempty', 'add_calendar'), 'error');
    } else if (url == "") {
        parent.rcmail.display_message(rcmail.gettext('urlempty', 'add_calendar'), 'error');
    } else {
	delete_calendar(tdid, url, displayname);
    }
}

if (window.rcmail) {
	rcmail.addEventListener('init', function(evt) {
		rcmail.register_command('plugin.add_calendar.save', function() {
            var calendarname = rcube_find_object('_calendarname');
            if(calendarname.value == "") {
                parent.rcmail.display_message(rcmail.gettext('calendarnameempty', 'add_calendar'), 'error');
            }
            else {
                document.forms.add_calendar_form.submit();
            }
		}, true);
	})
}
