<?php
// calendar.php
require_once 'config/db.php';
require_once 'includes/auth.php';
checkAuth();

include 'includes/header.php';
?>

<div style="margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: flex-end;">
    <div>
        <h2 style="font-size: 1.5rem; font-weight: 800; color: var(--text-main); letter-spacing: -0.03em;">Activity Calendar</h2>
        <p style="color: var(--text-muted); font-size: 0.875rem;">Visual overview of your leads and scheduled follow-ups.</p>
    </div>
    <div style="display: flex; gap: 1rem;">
        <div style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.75rem; font-weight: 600;">
            <span style="width: 12px; height: 12px; border-radius: 3px; background: #3b82f6;"></span> Interested
        </div>
        <div style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.75rem; font-weight: 600;">
            <span style="width: 12px; height: 12px; border-radius: 3px; background: #10b981;"></span> Converted
        </div>
        <div style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.75rem; font-weight: 600;">
            <span style="width: 12px; height: 12px; border-radius: 3px; background: #f59e0b;"></span> Pending
        </div>
        <div style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.75rem; font-weight: 600;">
            <span style="width: 12px; height: 12px; border-radius: 3px; background: #ef4444;"></span> Lost
        </div>
    </div>
</div>

<div class="card" style="padding: 1.5rem; min-height: 800px;">
    <!-- FullCalendar Container -->
    <div id="calendar"></div>
</div>

<!-- FullCalendar Library -->
<link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css' rel='stylesheet' />
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js'></script>

<style>
    :root {
        --fc-border-color: #f1f5f9;
        --fc-daygrid-event-dot-width: 8px;
        --fc-button-bg-color: var(--primary);
        --fc-button-border-color: var(--primary);
        --fc-button-hover-bg-color: var(--primary-dark);
        --fc-button-hover-border-color: var(--primary-dark);
        --fc-button-active-bg-color: var(--primary-dark);
        --fc-button-active-border-color: var(--primary-dark);
        --fc-today-bg-color: #f8fafc;
    }

    #calendar {
        font-family: 'Inter', sans-serif;
    }

    .fc .fc-toolbar-title {
        font-size: 1.125rem;
        font-weight: 800;
        color: var(--text-main);
    }

    .fc .fc-button {
        font-weight: 700;
        font-size: 0.8125rem;
        text-transform: capitalize;
        border-radius: 8px;
        padding: 0.5rem 1rem;
    }

    .fc .fc-button-primary:not(:disabled).fc-button-active, 
    .fc .fc-button-primary:not(:disabled):active {
        background-color: var(--primary-dark);
        border-color: var(--primary-dark);
    }

    .fc-event {
        cursor: pointer;
        padding: 2px 4px;
        border-radius: 4px;
        font-size: 0.75rem;
        font-weight: 600;
        border: none !important;
        margin-bottom: 2px;
    }

    .fc-daygrid-day-number {
        font-weight: 700;
        font-size: 0.8125rem;
        color: var(--text-muted);
        text-decoration: none !important;
        padding: 8px !important;
    }

    .fc-col-header-cell-cushion {
        font-weight: 700;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--text-muted);
        padding: 12px 0 !important;
        text-decoration: none !important;
    }

    .fc-theme-standard td, .fc-theme-standard th {
        border-color: #f1f5f9;
    }

    .fc-day-today .fc-daygrid-day-number {
        color: var(--primary);
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');
        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,listMonth'
            },
            events: 'api/calendar_events.php',
            eventClick: function(info) {
                if (info.event.url) {
                    window.location.href = info.event.url;
                    info.jsEvent.preventDefault();
                }
            },
            eventDidMount: function(info) {
                // Add tooltip or descriptive title on hover
                if (info.event.extendedProps.description) {
                    info.el.title = info.event.extendedProps.description;
                }
            },
            height: 'auto',
            firstDay: 1, // Start week on Monday
            dayMaxEvents: true // allow "more" link when too many events
        });
        calendar.render();
    });
</script>

<?php include 'includes/footer.php'; ?>
