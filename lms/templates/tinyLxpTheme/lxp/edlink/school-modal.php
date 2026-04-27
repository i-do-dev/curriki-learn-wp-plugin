<?php
    global $treks_src;
    $edlink_district_post = $args['edlink_district_post'];
    $edlink_access_token = get_post_meta($edlink_district_post->ID, 'lxp_edlink_provider_access_token', true);
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
<style>
    .edlink_school_input_box {
        padding: 0 0 30px !important;
    }
</style>

<!-- Modal -->
<div class="modal fade modal-lg" id="edlinkSchoolModal" tabindex="-1" aria-labelledby="edlinkSchoolModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">

            <div class="modal-header">
                <div class="modal-header-title">
                    <h2 class="modal-title" id="exampleModalLabel"><span class="edlink-school-action">New</span> School</h2>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body">
                <form class="row g-3" id="edlinkSchoolForm">
                    <!-- Check Logged User Role -->
                    <?php
                        if (isset($args['role']) && $args['role'] == 'client') {                            
                    ?>
                            <input type="hidden" name="edlink_district_admin_id" value="<?php get_current_user_id(); ?>">
                    <?php        
                        } else {
                    ?>
                            <input type="hidden" name="edlink_district_admin_id" value="<?php echo get_post_meta($edlink_district_post->ID, 'lxp_district_admin', true); ?>">
                    <?php        
                        }
                    ?>                    
                    <input type="hidden" name="edlink_school_district_id" value="<?php echo $edlink_district_post->ID; ?>">
                    <input type="hidden" name="edlink_school_post_id" id="edlink_school_post_id" value="0">
                    <input type="hidden" id="inputEdlinkSchoolId" name="edlink_school_id"/>
                    <input type="hidden" id="inputEdlinkProviderAccessToken" name="edlink_provider_access_token" value="<?php echo $edlink_access_token; ?>"/>
                    <div class="input_section">
                        <div class="label_box" id="edlink_loader" style="color: #0000ff"></div>
                        <div class="label_box" id="edlink_error" style="color: #dc3545"></div>
                        <br/>
                        <div class="input_box brief_input_box">
                            <div class="label_box brief_label_box id_label_box">
                                <label class="label">School name</label>
                                <div id="edlink_school_container">
                                    <select id="edlinkInputSchoolName" name="edlink_school_name" class="form-select" onChange="javascript:setEdlinkSchoolId();">
                                        <option value="0">Choose...</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="input_box brief_input_box">
                            <div class="label_box brief_label_box">
                                <label class="label">About</label>
                                <input class="brief_info form-control" type="text" id="edlinkInputAbout" name="edlink_school_about" placeholder="Enter a brief description here" />
                            </div>
                        </div>
                    </div>
                    <div class="horizontal_line"></div>
                    <div class="input_section" style="margin-bottom: 25px;">
                        <p class="personal-text">Administrator</p>
                    </div>
                
                    <div class="input_section edlink_school_input_box">
                        <div class="label_box" id="people_loader" style="color: #0000ff"></div>
                        <div class="input_box brief_input_box">
                            <div class="label_box brief_label_box id_label_box">
                                <label class="label">Edlink People</label>
                                <select id="edlinkInputPeopleName" name="edlink_user_email" class="form-select" onChange="javascript:setEdlinkPeopleData();">
                                    <option value="0">Choose...</option>
                                </select>
                                <input class="form-control" type="hidden" id="edlinkInputFirstName" name="edlink_first_name" />
                                <input class="form-control" type="hidden" id="edlinkInputLastName" name="edlink_last_name" />
                                <input class="form-control" type="hidden" id="inputEdlinkUserId" name="user_id" />
                            </div>
                        </div>
                    </div>
                </form>
                <div class="input_section">
                    <div class="btn_box">
                        <button class="btn" type="button" data-bs-dismiss="modal" aria-label="Close">Cancel</button>
                        <button class="btn" id="saveEdlinkSchoolBtn"><span class="edlink-school-action">Add </span></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    host = window.location.hostname === 'localhost' ? window.location.origin + '<?php echo WORDPRESS_HOST; ?>' : window.location.origin;
    apiUrl = host + '/wp-json/lms/v1/';

    jQuery(document).ready(function() {

        var edlinkSchoolModal = document.getElementById('edlinkSchoolModal');
        edlinkSchoolModalObj = new bootstrap.Modal(edlinkSchoolModal);
        window.edlinkSchoolModalObj = edlinkSchoolModalObj;

        jQuery("#addEdlinkSchoolModal").on('click', function() {
            getEdlinkSchools();
            edlinkSchoolModalObj.show();
        });

        jQuery("#saveEdlinkSchoolBtn").on('click', function() {
            jQuery("#edlinkSchoolForm").submit();
        });
        
        let edlinkSchoolForm = jQuery("#edlinkSchoolForm");
        jQuery(edlinkSchoolForm).on('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            
            $.ajax({
                method: "POST",
                enctype: 'multipart/form-data',
                url: apiUrl + "edlink/schools/save",
                data: formData,
                processData: false,
                contentType: false,
                cache: false,
            }).done(function( response ) {
                jQuery('#edlinkSchoolForm .form-control').removeClass('is-invalid');
                edlinkSchoolModalObj.hide();
                window.location.reload();
            }).fail(function (response) {
                jQuery('#edlinkSchoolForm .form-control').removeClass('is-invalid');
                if (response.responseJSON !== undefined) {
                    Object.keys(response.responseJSON.data.params).forEach(element => {
                        jQuery('input[name="' + element + '"]').addClass('is-invalid');
                        jQuery('textarea[name="' + element + '"]').addClass('is-invalid');
                        jQuery('select[name="' + element + '"]').addClass('is-invalid');
                    });
                }
            });
        
        });

        edlinkSchoolModal.addEventListener('hide.bs.modal', function (event) {
            jQuery('#edlinkSchoolForm').removeClass('is-invalid');
            jQuery('#edlinkInputPeopleName').removeClass('is-invalid');
            jQuery("#saveEdlinkSchoolBtn").attr("disabled", false);
            jQuery("#inputEdlinkSchoolId").val("");
            jQuery("#edlink_school_post_id").val(0);
            jQuery("#edlinkInputSchoolName").html('<option value="0"> Choose...</option>');
            jQuery('#edlinkInputAbout').val("");
            jQuery("#edlinkInputPeopleName").html('<option value="0"> Choose...</option>');
            jQuery("#inputEdlinkUserId").val('');
            jQuery("#edlinkInputFirstName").val('');
            jQuery("#edlinkInputLastName").val('');
            jQuery("#edlink_loader").html('');
            jQuery("#edlink_error").html('');
            jQuery('.edlink-school-action').text("Add");
            var html = '<select id="edlinkInputSchoolName" name="edlink_school_name" class="form-select" onChange="javascript:setEdlinkSchoolId();"><option value="0">Choose...</option></select>';
            jQuery('#edlink_school_container').html(html);            
        });
    });
    // fas fa-spinner-third fa-fw fa-2x fa-spin
    function getEdlinkSchools() {
        jQuery("#saveEdlinkSchoolBtn").attr("disabled", true);
        jQuery("#edlink_loader").html('<i class="fa fa-spinner fa-spin" style="font-size:25px"></i> Loading ...');
        // jQuery("#people_loader").html('<i class="fa fa-spinner fa-spin" style="font-size:25px"></i> Loading ...');
        var access_token = '<?php echo $edlink_access_token; ?>';
        $.ajax({
            method: "POST",            
            url: apiUrl + "edlink/provider/people",
            data: {access_token, "api_require" : "schools"}
        }).done(function( response ) {            
            // Set School Data
            if (typeof response.schools === 'object' && response.schools !== null && !response['schools']['error']) {
                var html = '';
                html += '<option value="0"> Choose...</option>';
                Object.entries(response.schools).forEach(([key, school]) => {
                    html += '<option value="'+school["name"]+'" id="'+school["id"]+'">'+school["name"]+'</option>';
                });
                jQuery("#edlinkInputSchoolName").html(html);
                jQuery("#edlink_error").html('');
                jQuery("#saveEdlinkSchoolBtn").attr("disabled", false);
            } else if (response['schools']['error'] != '') {
                jQuery("#saveEdlinkSchoolBtn").attr("disabled", true);                
                jQuery("#edlink_error").html(response['schools']['error']);
                jQuery("#edlinkInputSchoolName").html('<option value="0"> Choose...</option>');
                jQuery("#inputEdlinkSchoolId").val("");
            }
            // Set People Data
            if (typeof response.people === 'object' && response.people !== null && !response['people']['error']) {
                var html = '';
                html += '<option value="0"> Choose...</option>';
                Object.entries(response.people).forEach(([key, person]) => {
                    html += '<option value="'+person["email"]+'" id="'+person["id"]+'" firstName="'+person["first_name"]+'" lastName="'+person["last_name"]+'">'+person["first_name"]+' '+person["last_name"]+' ('+person["email"]+')</option>';                    
                });
                jQuery("#edlinkInputPeopleName").html(html);
                jQuery("#saveEdlinkSchoolBtn").attr("disabled", false);
            } else {
                jQuery("#saveEdlinkSchoolBtn").attr("disabled", true);                
                jQuery("#edlink_error").html(response['people']['error']);
                jQuery("#edlinkInputPeopleName").html('<option value="0"> Choose...</option>');
                jQuery("#inputEdlinkUserId").val('');
                jQuery("#edlinkInputFirstName").val('');
                jQuery("#edlinkInputLastName").val('');
            }
            jQuery("#edlink_loader").html('');
            jQuery("#people_loader").html('');
        });
    }

    function setEdlinkSchoolId() {
        var id = jQuery("#edlinkInputSchoolName option:selected").attr('id');
        jQuery("#inputEdlinkSchoolId").val(id);
    }

    function setEdlinkPeopleData() {
        var id = jQuery("#edlinkInputPeopleName option:selected").attr('id');
        var first_name = jQuery("#edlinkInputPeopleName option:selected").attr('firstName');
        var last_name = jQuery("#edlinkInputPeopleName option:selected").attr('lastName');
        first_name = (first_name != '') ? first_name : ' ';
        last_name = (last_name != '') ? last_name : ' ';
        jQuery("#inputEdlinkUserId").val(id);
        jQuery("#edlinkInputFirstName").val(first_name);
        jQuery("#edlinkInputLastName").val(last_name);
    }
    
</script>