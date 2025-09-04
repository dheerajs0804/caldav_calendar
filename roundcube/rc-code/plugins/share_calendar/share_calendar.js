function row_del(table_id, td_id, calendar_url, calendar_display_name, un_share_with) {
    if(table_id == "") {
        parent.rcmail.display_message(rcmail.gettext('tableidempty', 'share_calendar'), 'error');
    } else if(td_id == "") {
        parent.rcmail.display_message(rcmail.gettext('tdidempty', 'share_calendar'), 'error');
    } else if(calendar_url == "") {
        parent.rcmail.display_message(rcmail.gettext('urlempty', 'share_calendar'), 'error');
    } else if(calendar_display_name == "") {
        parent.rcmail.display_message(rcmail.gettext('calendardisplaynameempty', 'share_calendar'), 'error');
    } else if(un_share_with == "") {
        parent.rcmail.display_message(rcmail.gettext('unsharewithempty', 'share_calendar'), 'error');
    } else {
        rcmail.http_request('plugin.share_calendar.unshare', '_calendar_url=' + calendar_url + '&_calendar_display_name=' + calendar_display_name + '&_un_share_with=' + un_share_with, true);
        document.getElementById(table_id).deleteRow(document.getElementById(td_id).rowIndex);
        if(document.getElementById(table_id).getElementsByTagName("TBODY").item(0).rows.length == 0) {
            var tbody = document.getElementById(table_id).getElementsByTagName("TBODY").item(0);
            var row = document.createElement("TR");
            var cell = document.createElement("TD");
            var text = document.createTextNode(rcmail.gettext('noshare', 'share_calendar'));
            cell.setAttribute('colspan', '4');
            cell.appendChild(text);
            row.appendChild(cell);
            tbody.appendChild(row);
        }
        //parent.rcmail.display_message(rcmail.gettext('deleted', 'add_calendar'), 'confirmation');
    }
}

function shareWithAutoComplte() {
    var success = true;
    rcmail.init_address_input_events($('#sharewith'));
    rcmail.addEventListener('autocomplete_insert', function(e) {
        console.log(e);
    });
    return success;
}

if (window.rcmail) {
	rcmail.addEventListener('init', function(evt) {
        shareWithAutoComplte();
        rcmail.register_command('plugin.share_calendar.share', function() {
            var calendarname = rcube_find_object('_calendarname');
            if(calendarname.value == "") {
                parent.rcmail.display_message(rcmail.gettext('calendarnameempty', 'share_calendar'), 'error');
            }
            else {
                var shareDetails = calendarname.value.split('MITHI_CAL_VAL_SEPARATOR');
                var share_calendar_display_name = rcube_find_object('_share_calendar_display_name');
                share_calendar_display_name.value = shareDetails[0];
                var share_calendar = rcube_find_object('_share_calendar');
                share_calendar.value = shareDetails[1];
                document.forms.share_calendar_form.submit();
            }
        }, true);
    })
}
