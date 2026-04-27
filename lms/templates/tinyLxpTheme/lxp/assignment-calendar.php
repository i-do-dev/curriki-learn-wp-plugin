<script type="text/javascript">
    jQuery(document).ready(function() {
        var calendarEl = document.getElementById('calendar');
        var calendar = new FullCalendar.Calendar(calendarEl, {
            selectable: false,
            initialView: 'timeGridWeek',
            events: host + "get/all/trek/events?user_id=" + <?php echo get_current_user_id() ?> ,
            select: function( selectionInfo ) {
                const start = selectionInfo.startStr;
                const end =  selectionInfo.endStr;
                const allDay = selectionInfo.allDay;
                console.log(" SELECT........... ");
            },
            eventClick: function(clickEventInfo) {
                const startTime = new Date(clickEventInfo.event.start).toLocaleTimeString();
                const endTime = new Date(clickEventInfo.event.end).toLocaleTimeString();
                jQuery("#eventStartTime").val(startTime);
                jQuery("#eventEndTime").val(endTime);
                jQuery("#eventId").val(clickEventInfo.event.id);
                jQuery("#trekTitle").val(clickEventInfo.event.extendedProps.trekTitle);
                jQuery("#trekSectionId").val(clickEventInfo.event.extendedProps.trekSectionId);
                jQuery("#trekSectionTitle").val(clickEventInfo.event.extendedProps.trekSectionTitle);
                
                jQuery("span#trekTitleText").text(jQuery("input#trekTitle").val());
                jQuery("span#trekSectionText").text(jQuery("input#trekSectionTitle").val());

                console.log(" EVENT CLICK............ ");
            }
        });

        calendar.render();
    });
</script>