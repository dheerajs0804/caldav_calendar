function row_del(reply, uid, organizer, hosturl, summary) {
    if (reply == "") {
        parent.rcmail.display_message(rcmail.gettext('responseempty', 'subscribe_calendar'), 'error');
    } else if (uid == "") {
        parent.rcmail.display_message(rcmail.gettext('uidempty', 'subscribe_calendar'), 'error');        
    } else if (organizer == "") {
        parent.rcmail.display_message(rcmail.gettext('organizerempty', 'subscribe_calendar'), 'error');        
    } else if (hosturl == "") {
        parent.rcmail.display_message(rcmail.gettext('hosturlempty', 'subscribe_calendar'), 'error');
    } else if (summary == "") {
        parent.rcmail.display_message(rcmail.gettext('summaryempty', 'subscribe_calendar'), 'error');
    } else {
        rcmail.http_request('plugin.subscribe_calendar.subscribe', '_reply=' + reply + '&_uid=' + uid + '&_organizer=' + organizer + '&_hosturl=' + hosturl + '&_summary=' + summary, true);
    }
}