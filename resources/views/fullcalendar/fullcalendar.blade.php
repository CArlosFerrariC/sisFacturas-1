@extends('adminlte::page')

@section('title', 'Pedidos - Bandeja de pedidos')

@push('css')
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@200;400;600;700&family=Work+Sans:wght@300;400&display=swap');
    </style>
    <link rel="stylesheet" href="{{asset('plugins/jquery-ui/jquery-ui.css')}}">
    <link rel="stylesheet" href="{{asset('css/toastr.min.css')}}">
    <link rel="stylesheet" href="{{asset('plugins/fullcalendar/main.css')}}">
    <style>
        /*.fc .fc-col-header-cell-cushion {
            display: inline-block;
            padding: 2px 4px;
        }*/
        /*#calendar {
            max-width: 900px;
            margin: 40px auto;
            background-color: #0c84ff !important;
        }*/
    </style>
@endpush

@section('content_header')
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Calendar</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="#">Home</a></li>
                    <li class="breadcrumb-item active">Calendar</li>
                </ol>
            </div>
        </div>
    </div><!-- /.container-fluid -->
@stop

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-3">
                <div class="sticky-top mb-3">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">Draggable Events</h4>
                        </div>
                        <div class="card-body">
                            <!-- the events -->
                            <div id="external-events">
                                <div class="external-event bg-success">Lunch</div>
                                <div class="external-event bg-warning">Go home</div>
                                <div class="external-event bg-info">Do homework</div>
                                <div class="external-event bg-primary">Work on UI design</div>
                                <div class="external-event bg-danger">Sleep tight</div>
                                <div class="checkbox">
                                    <label for="drop-remove">
                                        <input type="checkbox" id="drop-remove">
                                        remove after drop
                                    </label>
                                </div>
                            </div>
                        </div>
                        <!-- /.card-body -->
                    </div>
                    <!-- /.card -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Create Event</h3>
                        </div>
                        <div class="card-body">
                            <div class="btn-group" style="width: 100%; margin-bottom: 10px;">
                                <ul class="fc-color-picker" id="color-chooser">
                                    <li><a class="text-primary" href="#"><i class="fas fa-square"></i></a></li>
                                    <li><a class="text-warning" href="#"><i class="fas fa-square"></i></a></li>
                                    <li><a class="text-success" href="#"><i class="fas fa-square"></i></a></li>
                                    <li><a class="text-danger" href="#"><i class="fas fa-square"></i></a></li>
                                    <li><a class="text-muted" href="#"><i class="fas fa-square"></i></a></li>
                                </ul>
                            </div>
                            <!-- /btn-group -->
                            <div class="input-group">
                                <input id="new-event" type="text" class="form-control" placeholder="Event Title">

                                <div class="input-group-append">
                                    <button id="add-new-event" type="button" class="btn btn-primary">Add</button>
                                </div>
                                <!-- /btn-group -->
                            </div>
                            <!-- /input-group -->
                        </div>
                    </div>
                </div>
            </div>
            <!-- /.col -->
            <div class="col-md-9">
                <div class="card card-primary">
                    <div class="card-body p-0">
                        <!-- THE CALENDAR -->
                        <div id="calendar" style="width: 100%; display: inline-block;"></div>
                    </div>
                    <!-- /.card-body -->
                </div>
                <!-- /.card -->
            </div>
            <!-- /.col -->
        </div>
        <!-- /.row -->
    </div><!-- /.container-fluid -->
@stop

@section('js')
    <script src="{{asset('./plugins/jquery/jquery.min.js')}}"></script>
    <script src="{{asset('plugins/jquery-ui/jquery-ui.min.js')}}"></script>
    <script src=" {{asset('plugins/moment/moment.min.js')}}"></script>
    <script src=" {{asset('./plugins/fullcalendar/main.js')}}"></script>
    <script src=" {{asset('./plugins/fullcalendar/locales/es.js')}}"></script>
    <script src=" {{asset('./js/toastr.min.js')}}"></script>
    <script>
        $(document).ready(function () {
        //$(function () {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            toastr.success('Hola', 'Event');

            function ini_events(ele) {
                ele.each(function () {

                    // create an Event Object (https://fullcalendar.io/docs/event-object)
                    // it doesn't need to have a start or end
                    var eventObject = {
                        title: $.trim($(this).text()) // use the element's text as the event title
                    }

                    // store the Event Object in the DOM element so we can get to it later
                    $(this).data('eventObject', eventObject)

                    // make the event draggable using jQuery UI
                    $(this).draggable({
                        zIndex        : 1070,
                        revert        : true, // will cause the event to go back to its
                        revertDuration: 0  //  original position after the drag
                    })

                })
            }

            ini_events($('#external-events div.external-event'));

            var date = new Date()
            var d    = date.getDate(),
                m    = date.getMonth(),
                y    = date.getFullYear()

            var Calendar = FullCalendar.Calendar;
            var Draggable = FullCalendar.Draggable;

            var containerEl = document.getElementById('external-events');
            var checkbox = document.getElementById('drop-remove');
            var calendarEl = document.getElementById('calendar');

            new Draggable(containerEl, {
                itemSelector: '.external-event',
                eventData: function(eventEl) {
                    return {
                        title: eventEl.innerText,
                        backgroundColor: window.getComputedStyle( eventEl ,null).getPropertyValue('background-color'),
                        borderColor: window.getComputedStyle( eventEl ,null).getPropertyValue('background-color'),
                        textColor: window.getComputedStyle( eventEl ,null).getPropertyValue('color'),
                    };
                }
            });

            var calendar = new Calendar(calendarEl, {
                dayMaxEventRows: true,
                views: {
                    timeGrid: {
                        dayMaxEventRows: 6 // adjust to 6 only for timeGridWeek/timeGridDay
                    }
                },
                initialDate: '2023-03-02',
                initialView: 'timeGridWeek',
                selectable: true,
                headerToolbar: {
                    left  : 'prev,next today',
                    center: 'title',
                    right : 'dayGridMonth,timeGridWeek,timeGridDay,listMonth'
                },
                weekNumbers:true,
                dateClick: function(info) {
                    //alert('clicked ' + info.dateStr);
                },
                select: function(info) {
                    //alert('selected ' + info.startStr + ' to ' + info.endStr);
                    var event_name = prompt('Event Name:');
                    if(event_name)
                    {
                        var event_start = $.fullCalendar.formatDate(info.startStr, "Y-MM-DD HH:mm:ss");
                        var event_end = $.fullCalendar.formatDate(info.endStr, "Y-MM-DD HH:mm:ss");

                        $.ajax({
                            url: "{{route('fullcalendarAjax')}}",
                            data: {
                                event_name: event_name,
                                event_start: event_start,
                                event_end: event_end,
                                type: 'create'
                            },
                            type: "POST",
                            success: function (data) {
                                displayMessage("Event created.");
                                calendar.fullCalendar('renderEvent', {
                                    id: data.id,
                                    title: event_name,
                                    start: event_start,
                                    end: event_end,
                                    allDay: allDay
                                }, true);
                                calendar.fullCalendar('unselect');
                            }
                        });

                    }
                },
                eventDrop:function(info){
                    //alert(info.event.title + " was dropped on " + info.event.start.toISOString());

                    /*if (!confirm("Are you sure about this change?")) {
                        info.revert();
                    }*/

                    var event_start = $.fullCalendar.formatDate(info.event.startStr, "Y-MM-DD");
                    var event_end = $.fullCalendar.formatDate(info.endStr, "Y-MM-DD");

                    $.ajax({
                        url: "{{route('fullcalendarAjax')}}",
                        data: {
                            title: info.name,
                            start: event_start,
                            end: event_end,
                            id: info.id,
                            type: 'edit',
                        },
                        type: "POST",
                        success: function (response) {
                            displayMessage("Event updated");
                        }
                    });

                },
                eventClick: function (event) {
                    var eventDelete = confirm("Are you sure?");
                    if (eventDelete) {
                        $.ajax({
                            type: "POST",
                            url: "{{route('fullcalendarAjax')}}",
                            data: {
                                id: event.id,
                                type: 'delete',
                            },
                            success: function (response) {
                                calendar.fullCalendar('removeEvents', event.id);
                                displayMessage("Event removed");
                            }
                        });
                    }
                },
                locale: 'es',
                themeSystem: 'bootstrap',
                //themeSystem: 'jquery-ui',
                //Random default events
                //events: @json($eventss),
                events: [
                    {
                        title          : 'All Day Event',
                        start          : new Date(y, m, 1),
                        backgroundColor: '#f56954', //red
                        borderColor    : '#f56954', //red
                        allDay         : true,
                        overlap: true,
                        //display: 'inverse-background',
                        display: 'background',
                    },
                    {
                        title          : 'Long Event',
                        start          : new Date(y, m, d - 5),
                        end            : new Date(y, m, d - 2),
                        backgroundColor: '#f39c12', //orange
                        borderColor    : '#f39c12' //yellow
                    },
                    {
                        title          : 'Meeting',
                        start          : new Date(y, m, d, 10, 30),
                        allDay         : false,
                        backgroundColor: '#0073b7', //Blue
                        borderColor    : '#0073b7' //Blue
                    },
                    {
                        title          : 'Lunch',
                        start          : new Date(y, m, d, 12, 0),
                        end            : new Date(y, m, d, 14, 0),
                        allDay         : false,
                        backgroundColor: '#00c0ef', //Info (aqua)
                        borderColor    : '#00c0ef' //Info (aqua)
                    },
                    {
                        title          : 'Birthday Party',
                        start          : new Date(y, m, d + 1, 19, 0),
                        end            : new Date(y, m, d + 1, 22, 30),
                        allDay         : false,
                        backgroundColor: '#00a65a', //Success (green)
                        borderColor    : '#00a65a' //Success (green)
                    },
                    {
                        title          : 'Click for Google',
                        start          : new Date(y, m, 28),
                        end            : new Date(y, m, 29),
                        url            : 'https://www.google.com/',
                        backgroundColor: '#3c8dbc', //Primary (light-blue)
                        borderColor    : '#3c8dbc' //Primary (light-blue)
                    }
                ],
                editable  : true,
                droppable : true, // this allows things to be dropped onto the calendar !!!
                //drop      : function(info) {
                    // is the "remove after drop" checkbox checked?
                    //if (checkbox.checked) {
                        // if so, remove the element from the "Draggable Events" list
                        //info.draggedEl.parentNode.removeChild(info.draggedEl);
                    //}
                //}
            });

            function displayMessage(message) {
                //toastr.success(message, 'Event');
            }

            toastr.success('Hola', 'Event');

            calendar.render();

            var currColor = '#3c8dbc'
            $('#color-chooser > li > a').click(function (e) {
                e.preventDefault()
                // Save color
                currColor = $(this).css('color')
                // Add color effect to button
                $('#add-new-event').css({
                    'background-color': currColor,
                    'border-color'    : currColor
                })
            })

            $('#add-new-event').click(function (e) {
                e.preventDefault()
                // Get value and make sure it is not null
                var val = $('#new-event').val()
                if (val.length == 0) {
                    return
                }

                // Create events
                var event = $('<div />')
                event.css({
                    'background-color': currColor,
                    'border-color'    : currColor,
                    'color'           : '#fff'
                }).addClass('external-event')
                event.text(val)
                $('#external-events').prepend(event)

                // Add draggable funtionality
                ini_events(event)

                // Remove event from text input
                $('#new-event').val('')
            })

            /*$('#calendar').fullCalendar({
                themeSystem: 'jquery-ui',
                header: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'month,agendaWeek,agendaDay,listMonth'
                },
                locale: 'es',
                weekNumbers: true,
                eventLimit: true,
                events: @json($eventss),
                eventRender: function (event, element, view) {
                    if (event.allDay === 'true') {
                        event.allDay = true;
                    } else {
                        event.allDay = false;
                    }
                },
            });*/



        })
    </script>
@endsection

