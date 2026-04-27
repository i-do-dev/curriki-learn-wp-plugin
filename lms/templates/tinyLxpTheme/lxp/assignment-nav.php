<?php
    global $treks_src; global $trek_post;
    $args = array(
        'posts_per_page'   => -1,
        'post_type'        => TL_COURSE_CPT,
        'orderby'        => 'meta_value_num',
        'order' => 'asc'
    );
    $course = get_posts($args);
    if ( isset($_GET['course']) && $_GET['course'] == 0 && isset($_GET['section']) && $_GET['section'] == 0 ) {
        $trek_post = $course[0];
    }
    $previous_url = wp_get_referer();
?>
<section class="welcome-section assignment-section">
    <div id="back-btn">
        <?php if ($trek_post) { ?>
                <button class="back-btn" id="go_back" onclick="go_back('<?php echo esc_url($previous_url); ?>')">
                    <img src="<?php echo $treks_src; ?>/assets/img/back.svg" alt="logo" />
                    <p class="back-btn-text">Back</p>
                </button>
        <?php } ?>
    </div>
    <!-- Assignment Tabs -->
    <nav class="nav-section select-section">
        <ul class="treks_ul select-ul" id="myTab" role="tablist">
            <li>
                <button class="select-link active" id="step-1-tab" data-bs-toggle="tab"
                    data-bs-target="#step-1-tab-pane" type="button" role="tab" aria-controls="step-1-tab-pane"
                    aria-selected="true">
                    <span class="select-num">1</span>
                    Select a space in your calendar
                </button>
            </li>
            <li>
                <button class="select-link" id="step-2-tab" data-bs-toggle="tab" 
                    data-bs-target="#step-2-tab-pane" type="button" role="tab" aria-controls="step-2-tab-pane" aria-selected="true">
                    <span class="select-num">2</span>
                    Select Course & Lessons
                </button>
            </li>
            <li>
                <button class="select-link" id="step-3-tab" data-bs-toggle="tab" 
                    data-bs-target="#step-3-tab-pane" type="button" role="tab" aria-controls="step-3-tab-pane" aria-selected="true">

                    <span class="select-num third-select-num">3</span>
                    Select Class and Students
                </button>
            </li>
        </ul>
    </nav>
    <!-- End Assignment Tabs -->
</section>

<script type="text/javascript">
    jQuery(document).ready(function() {

        let allTabs = document.querySelectorAll('button[data-bs-toggle="tab"]').forEach(tabEl => {
            tabEl.addEventListener('shown.bs.tab', function (event) {
                console.log('event.target >>> ', jQuery(event.target).attr('id'));                
                switch (jQuery(event.target).attr('id')) {
                    case 'step-1-tab':
                        //window.calendar.render();
                        if ( jQuery('#set_date_time').val() == 1 ) {
                            jQuery('#set_date_time_alert').show();                            
                        }
                        break;
                    case 'step-2-tab':                        
                        set_assignment_date();
                        window.back_tab = bootstrap.Tab.getOrCreateInstance(document.querySelector('#step-1-tab'));
                        break;
                    case 'step-3-tab':
                        jQuery('#new_assignment_data_2').html(jQuery('#new_assignment_data_1').html());                      
                        window.back_tab = bootstrap.Tab.getOrCreateInstance(document.querySelector('#step-2-tab'));
                        break;
                    default:
                        break;
                }
                
                // event.target // newly activated tab
                // event.relatedTarget // previous active tab

            });
        });

    });

    function set_assignment_date() {
        // let assignment_date_start = new Date(window.calendarSelectionInfo.start);
        let assignment_date_start = new Date(jQuery("#start_date").val() + " " +  jQuery("#start_time").val());
        console.log("assignment_date_start >>> ", assignment_date_start);

        let day = new Intl.DateTimeFormat("en-US", { weekday: "long" }).format(assignment_date_start);
        let month = new Intl.DateTimeFormat("en-US", { month: "long" }).format(assignment_date_start);
        let date = assignment_date_start.getDate();
        // let time_start = assignment_date_start.toLocaleTimeString('en-US', {timeZone: 'UTC'});
        let time_start = assignment_date_start.toLocaleTimeString('en-US');
        
        let assignment_date_end = new Date(jQuery("#end_date").val() + " " + jQuery("#end_time").val());
        let day_end = new Intl.DateTimeFormat("en-US", { weekday: "long" }).format(assignment_date_end);
        let month_end = new Intl.DateTimeFormat("en-US", { month: "long" }).format(assignment_date_end);
        let date_end = assignment_date_end.getDate();

        console.log("assignment_date_end >>> ", assignment_date_end);

        // let time_end = assignment_date_end.toLocaleTimeString('en-US', {timeZone: 'UTC'});
        let time_end = assignment_date_end.toLocaleTimeString('en-US');
        jQuery("#assignment_day").text(day);
        jQuery("#assignment_month").text(month);
        jQuery("#assignment_date").text(date);
        jQuery("#assignment_time_start").text(time_start);
        
        jQuery("#assignment_day_end").text(day_end);
        jQuery("#assignment_month_end").text(month_end);
        jQuery("#assignment_date_end").text(date_end);
        jQuery("#assignment_time_end").text(time_end);
    }

    function go_back(url) {
        jQuery('#set_date_time').val(0);
        jQuery('#set_date_time_alert').hide();
        window.location = url;
    }

    function go_previous() {
        jQuery('#set_date_time').val(0);
        jQuery('#set_date_time_alert').hide();
        window.back_tab.show();
    }
</script>