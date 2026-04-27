// js/lxp-calendar.js

jQuery(document).ready(function($) {
    const calendarEl = document.getElementById('calendar');

    if (!calendarEl) return;

    const calendar = new FullCalendar.Calendar(calendarEl, {
        
        selectable: false,
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        events: function(fetchInfo, successCallback, failureCallback) {
            const url = LXP_Calendar_Data.api_url + '?user_id=' + LXP_Calendar_Data.user_id;

            fetch(url)
                .then(response => response.json())
                .then(data => {
                    successCallback(data);
                })
                .catch(err => {
                    console.error("Error loading events:", err);
                    failureCallback(err);
                });
        },
        dayHeaderContent: function(args) {
            const weekday_el = document.createElement('p');
            weekday_el.innerHTML = new Intl.DateTimeFormat("en-US", { weekday: "long" }).format(args.date);
            weekday_el.classList.add("month-text", "month-date-text");

            return { domNodes: [weekday_el] };
        },
        eventClassNames: function(arg) {
            return arg.event.extendedProps.segment ? "practice-a-event" : "segment-default-event";
        },
        eventContent: function(arg) {
            const trek_segment_el = document.createElement('p');
            trek_segment_el.innerHTML = arg.event.title;
            trek_segment_el.classList.add("lxp-event-title", "practice-a-segment-event-title");

            const trek_el = document.createElement('p');
            trek_el.innerHTML = arg.event.extendedProps.course || '';
            trek_el.classList.add("lxp-event-sub-title", "practice-a-segment-event-sub-title");

            return { domNodes: [trek_segment_el, trek_el] };
        },
        eventClick: function(eventClickInfo) {
            window.open(eventClickInfo.event.extendedProps.digital_journal_link, '_blank');
        },
        select: function(calendarSelectionInfo) {
            window.calendarSelectionInfo = calendarSelectionInfo;
            const tab = new bootstrap.Tab(document.querySelector('#step-2-tab'));
            tab.show();
        },
        viewDidMount: function(viewObject) {
            $('#month-date-text').text(viewObject.viewTitle);
            const month = new Intl.DateTimeFormat("en-US", { month: "long" }).format(viewObject.view.currentStart);
            $("#month-text").text(month);
        }
    });

    calendar.render();
    window.calendar = calendar;
    init_monthly_calendar();
});

function init_monthly_calendar() {
    webshim.setOptions('forms-ext', {
        replaceUI: 'auto',
        types: 'date',
        date: {
            startView: 2,
            inlinePicker: true,
            classes: 'hide-inputbtns'
        }
    });
    webshim.setOptions('forms', {
        lazyCustomMessages: true
    });
    //start polyfilling
    webshim.polyfill('forms forms-ext');
    const date = new Date();
    //YYYY-MM-DD format
    const dateString = date.toISOString().split("T")[0];
    jQuery("#calendar-monthly input[type='date']").val(dateString);

    jQuery("#calendar-monthly input[type='date']").on("change", function() {
    window.calendar.gotoDate(jQuery(this).val());
    });
}
