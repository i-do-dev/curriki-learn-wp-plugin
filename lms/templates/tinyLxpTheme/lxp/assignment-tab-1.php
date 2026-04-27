<?php
global $treks_src, $trek_post;
    
$args = array(
    'posts_per_page'   => -1,
    'post_type'        => TL_COURSE_CPT,

    'order' => 'asc'
);
    $treks = get_posts($args);
    if ( isset($_GET['course']) && $_GET['course'] == 0 && isset($_GET['section']) && $_GET['section'] == 0 ) {
        $trek_post = $treks[0];
    }
    $select_trek_title = !boolval($trek_post) ? "Select a TREK" : $trek_post->post_title;    
    $trek_id = $trek_post ? $trek_post->ID : 0;
?>

<style type="text/css">
    #step-1-tab-pane .third-tab-date-time {        
        border-bottom: 0px !important;
    }
    #step-1-tab-pane .days-box {
        flex-direction: row;
        align-items: flex-start;
    }

    #step-1-tab-pane .select-assignment-section {
        min-height: initial !important;
    }

    #step-1-tab-pane .btns-container {
        justify-content: start !important;
    }


    #step-1-tab-pane .date-time input {
        color: #0b5d7a;
        outline: none;
        background: none;
        border: 0px !important;
    }

    #step-1-tab-pane .third-tab-date-time img {
        margin-top: 3px;
    }

</style>

<div class="tab-pane fade show active" id="step-1-tab-pane" role="tabpanel" aria-labelledby="step-1-tab" tabindex="0">

    <!-- New Assignment Calendar Section -->
    <section class="calendar-container select-assignment-section">

        <!-- New Assignment -->
        <div class="select-trek-box" id="new_assignment_data_0">
            <h3 class="new-assignment-heading">New Assignment</h3>
            <div class="select-calendar-box">
                <h4 class="new-assignment-heading select-calendar-heading">Calendar</h4>   
                <div class="calendar-time-date <?php echo boolval($trek_post) ? 'third-tab-date-time' : '' ?>">
                    <img src="<?php echo $treks_src; ?>/assets/img/clock-outline.svg" alt="logo" />                    
                    <div id="time-date-box" class="time-date-box days-box">
                        <div class="time-date-box">
                        <p class="date-time to-text">From</p>
                            <p class="date-time"><input id="start_date" type="date"></p>
                            <p class="date-time"><input id="start_time" type="time"></p>
                            <p class="date-time to-text">To</p>
                            <p class="date-time"><input id="end_date" type="date"></p>
                            <p class="date-time"><input id="end_time" type="time"></p>
                        </div>
                    </div>                    
                </div> 
            </div>
        </div>
    </section>

    <!-- Button Section -->
    <section class="calendar-container select-assignment-section btns-container">
        <div class="input_section">
            <div class="btn_box profile_buttons">
                <button class="btn profile_btn" onclick="go_to_step_2()">Continue</button>
            </div>
        </div>
    </section>
</div>

<script type="text/javascript">
    function go_to_step_2(params) {

        if (
            jQuery("#start_date").val() == '' ||
            jQuery("#start_time").val() == '' ||
            jQuery("#end_date").val() == '' ||
            jQuery("#end_time").val() == ''
        ) {
            alert('Please select start and end date and time');
            return;
        }
        const start = new Date(jQuery("#start_date").val() + ' ' + jQuery("#start_time").val()).toISOString();
        const end = new Date(jQuery("#end_date").val() + ' ' + jQuery("#end_time").val()).toISOString();
        
        if ( start > end ) {
            alert('Start date should be less than end date');
            return;
        }

        bootstrap.Tab.getOrCreateInstance(document.querySelector('#step-2-tab')).show();
    }
    jQuery(document).ready(function() {        
        //jQuery('#start_datetimepicker').datetimepicker();
        //jQuery('#end_datetimepicker').datetimepicker();
    });    
</script>