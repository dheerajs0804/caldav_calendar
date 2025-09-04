alter table caldav_events add unique key `uk_caldav_event` (`calendar_id`, `recurrence_id`, `uid`, `caldav_tag`, `instance`);
