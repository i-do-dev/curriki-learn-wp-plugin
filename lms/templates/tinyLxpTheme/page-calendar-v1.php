<?php
$treks_src = content_url().'/plugins/TinyLxp-wp-plugin/lms/templates/tinyLxpTheme/treks-src/';
// Start the loop.
$courseId =  isset($_GET['courseid']) ? $_GET['courseid'] : get_post_meta($post->ID, 'tl_course_id', true);
$args = array(
    'posts_per_page'   => -1,
    'post_type'        => 'tl_trek',
    'order' => 'asc'
);
$treks = get_posts($args);
while (have_posts()) : the_post();
?>

    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title><?php the_title(); ?></title>
        <link href="<?php echo $treks_src; ?>/style/main.css" rel="stylesheet" />
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous" />

        <style type="text/css">
            .treks-card {
                width: 300px !important;
            }

            .fc-content {
                padding: 7px;
            }

            .eventCloseBtn {
                padding: 3px;
                font-size: 18px;
            }

            .treks-card-link {
                text-decoration: none !important;
            }
        </style>
    </head>

    <body>
        <nav class="navbar navbar-expand-lg bg-light">
            <div class="container-fluid">
                <?php include $livePath.'/trek/header-logo.php'; ?>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <div class="navbar-nav me-auto mb-2 mb-lg-0">
                        <div class="header-logo-search">

                            <!-- searching input -->
                            <div class="header-search">
                                <img src="<?php echo $treks_src; ?>/assets/img/header_search.svg" alt="svg" />
                                <input placeholder="Search" />
                            </div>
                        </div>
                    </div>
                    <div class="d-flex" role="search">
                        <div class="header-notification-user">
                            <?php include $livePath.'/trek/user-profile-block.php'; ?>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Basic Container -->
        <section class="main-container">
            <!-- Nav Section -->
            <nav class="nav-section">
                <?php include $livePath.'/trek/navigation.php'; ?>
            </nav>

            <!-- Reminders: section-->
            <section class="reminder-section">
                <div class="reminder-section-div">
                    <!-- reminder title -->
                    <div class="reminder-title reminder-detail">
                        <img src="<?php echo $treks_src; ?>/assets/img/rm_calendar.svg" />
                        <span>Reminders:</span>
                    </div>
                    <!-- Physical Properties -->
                    <div class="reminder-detail reminder-vli">
                        <span>Physical Properties Thu 9:00 AM</span>
                    </div>
                    <!-- Forces & Experimental Design  -->
                    <div class="reminder-detail reminder-vli">
                        <span>Forces & Experimental Design Fri 10:00 AM</span>
                    </div>
                    <!-- Physics  -->
                    <div class="reminder-detail">
                        <span>Physics Fri 1:00 PM</span>
                    </div>
                    <!-- Mathematics Mon  -->
                    <div class="reminder-detail">
                        <span>Mathematics Mon 11:00 AM</span>
                    </div>
                    <!-- Arrow down
        <div class="reminder-arrow">
          <img src="<?php echo $treks_src; ?>/assets/img/rm_arrow down.svg" />
        </div> -->
                </div>
            </section>

            <!-- Recent TREKs -->
            <section class="recent-treks-section">
                <div class="recent-treks-section-div">
                    <!--  TREKs header-->
                    <div class="recent-treks-header section-div-header">
                        <h2>Calendar</h2>
                    </div>
                    <!-- TREKs cards -->
                    <div class="recent-treks-cards-list">
                        <!-- each cards  -->
                        <div id='calendar'></div>
                    </div>
                </div>
            </section>



        </section>

        <script src="https://code.jquery.com/jquery-3.6.3.js" integrity="sha256-nQLuAZGRRcILA+6dMBOvcRh5Pe310sBpanc6+QBmyVM=" crossorigin="anonymous"></script>
        <script src="<?php echo $treks_src; ?>/js/Animated-Circular-Progress-Bar-with-jQuery-Canvas-Circle-Progress/dist/circle-progress.js"></script>
        <script src="<?php echo $treks_src; ?>/js/custom.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>


        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.4.0/fullcalendar.css" />
        <!-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.0.0-alpha.6/css/bootstrap.css" /> -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.18.1/moment.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.4.0/fullcalendar.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


         <script>
        $(document).ready(function() {


            var date = new Date();
            var d = date.getDate();
            var m = date.getMonth();
            var y = date.getFullYear();
            var trekOptions = "";
            var trekSectionOptions = "";
            var trekPostId;
            var selectedTrekAndSection;
            var closeAction;
            var host = window.location.origin + '/wp-json/lms/v1/';

            function getSelectedEvent($id) {
                $.ajax({
                    url: host + "get/trek/event",
                    type: "get",
                    data: "&id=" + $id,
                    async: false,
                    success: function(response) {
                        window.selectedTrekAndSection = response;
                    }
                });
            }

            function getTrekSections() {
                console.log(window.selectedTrekAndSection);
                $.ajax({
                    url: host + "get/all/trek/sections",
                    type: "get",
                    data: "&trek_post_id=" + window.selectedTrekAndSection['trek_id'],
                    success: function(response) {
                        window.trekSectionOptions = '';
                        var selectedSection = '';
                        for (var j = 0, len = response.length; j < len; ++j) {
                            if (window.selectedTrekAndSection['trek_section_id'] == response[j]['id']) {
                                selectedSection = 'selected';
                            }
                            window.trekSectionOptions += '<option ' + selectedSection + ' value="' + response[j]['id'] + '">' + response[j]['title'] + '</option>';
                            selectedSection = '';
                        }
                        trek_section = $('#edit_trek_section').html(window.trekSectionOptions);
                    }
                });
            }
            $('body').on('change', '#trek_post', function() {
                window.trekPostId = $(this).val();
                $.ajax({
                    url: host + "get/all/trek/sections",
                    type: "get",
                    data: "&trek_post_id=" + window.trekPostId,
                    success: function(response) {
                        window.trekSectionOptions = '';
                        for (var j = 0, len = response.length; j < len; ++j) {
                            window.trekSectionOptions += '<option value="' + response[j]['id'] + '">' + response[j]['title'] + '</option>';
                        }
                        trek_section = $('#trek_section').html(window.trekSectionOptions);
                    }
                });
            });

            $('body').on('change', '#edit_trek_post', function() {
                window.trekPostId = $(this).val();
                $.ajax({
                    url: host + "get/all/trek/sections",
                    type: "get",
                    data: "&trek_post_id=" + window.trekPostId,
                    success: function(response) {
                        window.trekSectionOptions = '';
                        for (var j = 0, len = response.length; j < len; ++j) {
                            window.trekSectionOptions += '<option value="' + response[j]['id'] + '">' + response[j]['title'] + '</option>';
                            trek_section = $('#edit_trek_section').html(window.trekSectionOptions);
                        }
                    }
                });
            });


            var calendar = $('#calendar').fullCalendar({
                editable: true,
                displayEventTime: false,
                header: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'month,agendaWeek,agendaDay'
                },
                events: {
                    url: host + "get/all/trek/events?user_id=" + <?php echo get_current_user_id() ?> ,
                    method: 'GET',
                    failure: function() {
                        alert('there was an error while fetching events!');
                    },
                },
                eventRender: function(event, element, view) {
                    if (event.allDay === 'true') {
                        event.allDay = true;
                    } else {
                        event.allDay = false;
                    }
                    element
                        .find(".fc-content")
                        .prepend("<span class='eventCloseBtn material-icons'>&times</span>");
                    element.find(".eventCloseBtn").on("click", function() {
                        window.closeAction = true;
                        var decision = confirm("Are you sure you want to remove this event?");
                        if (decision) {
                            $.ajax({
                                type: "POST",
                                url: host + 'delete/trek/event',
                                data: "&id=" + event.id,
                                success: function(json) {
                                    $('#calendar').fullCalendar('removeEvents', event.id);
                                }
                            });
                        }
                    });
                },
                selectable: true,
                selectHelper: true,
                select: function(start, end, allDay) {
                    $.ajax({
                        url: host + "get/all/treks",
                        type: "get",
                        async: false,
                        success: function(response) {
                            window.trekOptions = '<option>---Select Trek---</option>';
                            for (var j = 0, len = response.length; j < len; ++j) {
                                window.trekOptions += '<option value="' + response[j]['ID'] + '">' + response[j]['post_title'] + '</option>';
                            }
                        }
                    });
                    Swal.fire({
                        html: "<div class='fw-bold mt-2'>Select Trek<select id='trek_post' class='form-control' name='event_name' />" + window.trekOptions + "</select> <br> Trek Section</div><select id='trek_section' class='form-control' name='trek_section' ><option value='0'>---Select Trek Frist---</option> </select>",
                        showCancelButton: true,
                        buttonsStyling: false,
                        confirmButtonText: "Add to calendar",
                        cancelButtonText: "Cancel",
                        customClass: {
                            confirmButton: "btn btn-primary",
                            cancelButton: "btn btn-active-light"
                        }
                    }).then(function(result) {
                        if (result.value) {
                            var title = "";
                            var trekSectionId = document.querySelector("#trek_section").value;
                            if (trekSectionId == 0) {
                                alert("Please select trek first");
                                return;
                            }
                            if (trekSectionId) {
                                $.ajax({
                                    url: host + "store/trek/event",
                                    data: 'trek_section_id=' + trekSectionId + '&start=' + start + '&end=' + end + '&user_id=' + <?php echo get_current_user_id() ?>,
                                    type: "POST",
                                    async: false,
                                    success: function(response) {
                                        // title = response['title'];
                                        eventData = {
                                            title: response['title'],
                                            start: start,
                                            end: end,
                                            allDay: true,
                                            color: response['color'],
                                            textColor: response['textColor'],
                                            id: response['id']
                                        };
                                    }
                                });
                                $("#calendar").fullCalendar("renderEvent", eventData, true);
                            }
                            $("#calendar").fullCalendar("unselect");
                        } else if (result.dismiss === "cancel") {
                            Swal.fire({
                                text: "Event creation was declined!.",
                                icon: "error",
                                buttonsStyling: false,
                                confirmButtonText: "Ok, got it!",
                                customClass: {
                                    confirmButton: "btn btn-primary",
                                }
                            });
                        }
                    });
                },
                editable: true,
                eventDrop: function(event, delta) {
                    $.ajax({
                        url: host + 'update/trek/event',
                        data: '&start=' + event.start + '&end=' + event.end + '&id=' + event.id,
                        type: "POST",
                        async: false,
                        success: function(response) {}
                    });
                },
                eventClick: function(calEvent) {
                    if (window.closeAction == true) {
                        return;
                    }
                    window.closeAction = false;
                    $.ajax({
                        url: host + "get/all/treks",
                        type: "get",
                        async: false,
                        success: function(response) {
                            getSelectedEvent(calEvent.id);
                            window.trekOptions = '<option>---Select Trek---</option>';
                            var selectedTrekOption = '';
                            for (var j = 0, len = response.length; j < len; ++j) {
                                if (window.selectedTrekAndSection['trek_id'] == response[j]['ID']) {
                                    selectedTrekOption = 'selected';
                                    getTrekSections();
                                }
                                window.trekOptions += '<option ' + selectedTrekOption + ' value="' + response[j]['ID'] + '">' + response[j]['post_title'] + '</option>';
                                selectedTrekOption = '';
                            }
                        }
                    });

                    Swal.fire({
                        html: "<div class='fw-bold mt-2'>Select Trek<select id='edit_trek_post' class='form-control' name='event_name' />" + window.trekOptions + "</select> <br> Trek Section</div><select id='edit_trek_section' class='form-control' name='trek_section' > </select>",
                        showCancelButton: true,
                        buttonsStyling: false,
                        confirmButtonText: "Update",
                        cancelButtonText: "Cancel",
                        customClass: {
                            confirmButton: "btn btn-primary",
                            cancelButton: "btn btn-active-light"
                        }
                    }).then(function(result) {
                        if (result.value) {
                            var trekSectionId = document.querySelector("#edit_trek_section").value;
                            if (trekSectionId) {
                                calEvent.title = trekSectionId;
                                $.ajax({
                                    url: host + 'update/trek/event',
                                    data: 'trek_section_id=' + trekSectionId + '&start=' + calEvent.start + '&end=' + calEvent.end + '&id=' + calEvent.id,
                                    type: "POST",
                                    async: false,
                                    success: function(response) {
                                        calEvent.title = response['title'];
                                        calEvent.color = response['color'];
                                        calEvent.textColor = response['textColor'];
                                    }
                                });
                                $("#calendar").fullCalendar("updateEvent", calEvent);
                            }
                            $("#calendar").fullCalendar("unselect");
                        } else if (result.dismiss === "cancel") {
                            Swal.fire({
                                text: "Event Update was declined!.",
                                icon: "error",
                                buttonsStyling: false,
                                confirmButtonText: "Ok, got it!",
                                customClass: {
                                    confirmButton: "btn btn-primary",
                                }
                            });
                        }
                    });
                },
                eventResize: function(event) {
                    $.ajax({
                        url: host + 'update/trek/event',
                        data: '&start=' + event.start + '&end=' + event.end + '&id=' + event.id,
                        type: "POST",
                        async: false,
                        success: function(response) {}
                    });
                }
            });

        });
    </script>
    </body>

    </html>
<?php endwhile; ?>
