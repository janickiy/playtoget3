(function () {
    document.addEventListener('DOMContentLoaded', function () {
        const calendarEl = document.getElementById('calendar');

        if (!calendarEl || typeof FullCalendar === 'undefined') {
            return;
        }

        const eventDates = new Set([
            '2015-11-07',
            '2015-11-20',
            '2015-10-07',
        ]);

        const calendar = new FullCalendar.Calendar(calendarEl, {
            headerToolbar: {
                left: 'multiMonthYear,dayGridMonth prev',
                center: 'title',
                right: 'next today',
            },
            initialView: 'multiMonthYear',
            initialDate: new Date(),
            locale: 'en',
            firstDay: 1,
            selectable: true,
            editable: true,
            dayMaxEvents: true,
            multiMonthMaxColumns: 3,
            buttonText: {
                today: 'Today',
                year: '',
                month: '',
            },
            events: [
                {
                    title: 'Shooting competition',
                    start: '2015-11-07',
                },
                {
                    title: 'Igor \n Alekseev',
                    start: '2015-11-20',
                },
                {
                    title: 'Shooting competition',
                    start: '2015-10-07',
                },
            ],
            select: function (info) {
                if (info.view.type === 'multiMonthYear') {
                    calendar.changeView('dayGridMonth', info.start);
                    return;
                }

                const title = prompt('Enter event:');

                if (!title) {
                    return;
                }

                calendar.addEvent({
                    title: title,
                    start: info.start,
                    end: info.end,
                    allDay: info.allDay,
                });
                calendar.unselect();
            },
            dayCellClassNames: function (info) {
                return eventDates.has(toDateString(info.date)) ? ['color_th'] : [];
            },
            eventDidMount: function (info) {
                if (info.event.start) {
                    eventDates.add(toDateString(info.event.start));
                }
            },
            datesSet: function () {
                decorateCalendarTitle(calendarEl);
            },
        });

        calendar.render();
        decorateCalendarTitle(calendarEl);
    });

    function toDateString(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');

        return year + '-' + month + '-' + day;
    }

    function decorateCalendarTitle(calendarEl) {
        const title = calendarEl.querySelector('.fc-toolbar-title');

        if (!title || title.dataset.decorated === '1') {
            return;
        }

        title.dataset.decorated = '1';
        title.textContent = 'Calendar ' + title.textContent;
    }
}());
