#!/bin/bash
while [ 1 ]
do
        ###input file
        input_file=$1
        rewritefile=$2

        ###check if ics contains GMT time
        gmtcount=$(cat $input_file | grep "TZID:GMT Standard Time" | wc -l)

        if [ $gmtcount -eq "1" ]; then

                ###check if ics file contains STANDARD offset
                STDCOUNT=$(cat $input_file | grep "BEGIN:STANDARD" |wc -l)
                if [ $STDCOUNT -eq "1" ]; then
                        ###Standard offset exits
                        ###get TZOFFSETFROM value
                        offset=$(cat $input_file | sed -n '/BEGIN:STANDARD/,/END:STANDARD/p' | grep TZOFFSETFROM | cut -d ':' -f2)
                        ###get dtstart and dtend

                        dtstart=$(cat $input_file | sed -n '/BEGIN:VEVENT/,/END:VEVENT/p' | grep DTSTART | cut -d ':' -f2)
                        ###convert dtstart to utc
                        utcdtstart=$(php /var/www/html/roundcubemail/plugins/calendar/convert_gmt_to_utc.php $dtstart $offset)

                        dtend=$(cat $input_file | sed -n '/BEGIN:VEVENT/,/END:VEVENT/p' | grep DTEND | cut -d ':' -f2)
                        ###convert stend to utc
                        utcdtent=$(php /var/www/html/roundcubemail/plugins/calendar/convert_gmt_to_utc.php $dtend $offset)

                        ###rewrite ics file

                        ###get DTSTART Value from event and replace it
                        actualdtstart=$( cat $input_file |sed -n '/BEGIN:VEVENT/,/END:VEVENT/p' | grep DTSTART)
                        sed -i 's/'"$actualdtstart"'/'DTSTART:"$utcdtstart"'/' $input_file

                        ###get STEND value from event and replace it
                        actualdtenddate=$( cat $input_file |sed -n '/BEGIN:VEVENT/,/END:VEVENT/p' | grep DTEND)
                        sed -i 's/'"$actualdtenddate"'/'DTEND:"$utcdtent"'/' $input_file

                        ###remove ^M character which got added because of sed cmd
                        cat $input_file | tr -d '\r' > $rewritefile


                else
                        ###check if ics file contains daylight offset
                        daylightcount=$(cat $input_file | grep "BEGIN:DAYLIGHT" | wc -l)
                        if [ $daylightcount -eq "1" ]; then
                                ###daylight offset exits
                                ###GET TZOFFSETTO value
                                offset=$(cat $input_file | sed -n '/BEGIN:DAYLIGHT/,/END:DAYLIGHT/p' | grep TZOFFSETTO | cut -d ':' -f2)

                                dtstart=$(cat $input_file | sed -n '/BEGIN:VEVENT/,/END:VEVENT/p' | grep DTSTART | cut -d ':' -f2)
                                ###convert dtstart to utc
                                utcdtstart=$(php /var/www/html/roundcubemail/plugins/calendar/convert_gmt_to_utc.php $dtstart $offset)

                                dtend=$(cat $input_file | sed -n '/BEGIN:VEVENT/,/END:VEVENT/p' | grep DTEND | cut -d ':' -f2)
                                ###convert stend to utc
                                utcdtent=$(php /var/www/html/roundcubemail/plugins/calendar/convert_gmt_to_utc.php $dtend $offset)

                                ###rewrite ics file

                                ###get DTSTART Value from event and replace it
                                actualdtstart=$( cat $input_file |sed -n '/BEGIN:VEVENT/,/END:VEVENT/p' | grep DTSTART)
                                sed -i 's/'"$actualdtstart"'/'DTSTART:"$utcdtstart"'/' $input_file

                                ###get STEND value from event and replace it
                                actualdtenddate=$( cat $input_file |sed -n '/BEGIN:VEVENT/,/END:VEVENT/p' | grep DTEND)
                                sed -i 's/'"$actualdtenddate"'/'DTEND:"$utcdtent"'/' $input_file

                                ###remove ^M character which got added because of sed cmd
                                cat $input_file | tr -d '\r' > $rewritefile

                        else
                                ###Do Nothing
                                cat $input_file > $rewritefile
                        fi
                fi
        else
                ###Do Nothing
                cat $input_file > $rewritefile
        fi



        break
done
