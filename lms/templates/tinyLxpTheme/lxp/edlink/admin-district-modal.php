<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
<style>
    .edlink_district_input_box {
        padding: 0 0 30px !important;
    }
</style>
<!-- Edlink District Modal -->
<div class="modal fade modal-lg" id="edlinkDistrictModal" tabindex="-1" aria-labelledby="edlinkDistrictModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-header-title">
                    <h2 class="modal-title" id="exampleModalLabel"><span class="edlink-district-action">New</span> District</h2>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form class="row g-3" id="edlinkDistrictForm">
                    <input type="hidden" name="edlink_site_admin_id" id="edlink_site_admin_id" value="<?php echo get_current_user_id(); ?>">
                    <input type="hidden" name="edlink_district_post_id" id="edlink_district_post_id" value="0">
                    <div class="input_section">
                        <div class="personal_box">
                            <p class="personal-text">District</p>
                        </div>
                        <div class="input_box">
                            <?php
                                $district_type = (isset($_GET['district_type']) && $_GET['district_type'] == 'edlink') ? $_GET['district_type'] : 'tinylxp'; 
                            ?>
                            <input type="hidden" id="inputDistrictType" name="district_type" value="<?php echo $district_type; ?>" />
                            <div class="input_box brief_input_box" id="edlink_provider"></div>
                            <input type="hidden" id="inputEdlinkProviderId" name="edlink_provider_id"/>
                            <input type="hidden" id="inputEdlinkProviderAccessToken" name="edlink_provider_access_token"/>
                            <input type="hidden" id="inputEdlinkProviderName" name="edlink_provider_name"/>
                            <input type="hidden" id="inputEdlinkDistrictId" name="edlink_district_id"/>
                        </div>
                        <div class="label_box" id="edlink_loader" style="color: #0000aa"></div>
                        <div class="label_box" id="edlink_error" style="color: #dc3545"></div>
                        <br/>
                        <div class="input_box brief_input_box">
                            <div class="label_box brief_label_box id_label_box">
                                <label class="label">District name</label>
                                <div id="edlink_district_container">
                                    <select id="edlinkInputDistrictName" name="edlink_district_name" class="form-select" onChange="javascript:setEdlinkDistrictId();">
                                        <option value="0">Choose...</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="input_box brief_input_box">
                            <div class="label_box brief_label_box">
                                <label class="label">About</label>
                                <input class="brief_info form-control" type="text" id="edlinkInputAbout" name="edlink_district_about" placeholder="Enter a brief description here" />
                            </div>
                        </div>
                    </div>
                    <div class="horizontal_line"></div>
                    <div class="input_section" style="margin-bottom: 25px;">
                        <p class="personal-text">Administrator</p>
                    </div>
                    
                    <div class="input_section edlink_district_input_box">
                        <span class="label_box" id="people_loader" style="color: #0000aa"></span>
                        <div class="input_box brief_input_box">
                            <div class="label_box brief_label_box id_label_box">
                                <label class="label">Edlink People </label>
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
            </div>
            <div class="input_section">
                <div class="btn_box">
                    <button class="btn" type="button" data-bs-dismiss="modal" aria-label="Close">Cancel</button>
                    <button class="btn" id="saveEdlinkDistrictBtn"><span class="edlink-district-action">Add</span></button>
                </div>
            </div>
        </div>    
    </div>
</div>

<script type="text/javascript">
    host = window.location.hostname === 'localhost' ? window.location.origin + '<?php echo WORDPRESS_HOST; ?>' : window.location.origin;
    apiUrl = host + '/wp-json/lms/v1/';

    jQuery(document).ready(function() {
        var edlinkDistrictModal = document.getElementById('edlinkDistrictModal');
        edlinkDistrictModalObj = new bootstrap.Modal(edlinkDistrictModal);
        window.edlinkDistrictModalObj = edlinkDistrictModalObj;

        jQuery("#addEdlinkDistrictModal").on('click', function() {
            getEdlinkProviders();
            edlinkDistrictModalObj.show();
        });

        jQuery("#saveEdlinkDistrictBtn").on('click', function() {
            jQuery("#edlinkDistrictForm").submit();
        });
        
        let edlinkDistrictForm = jQuery("#edlinkDistrictForm");
        jQuery(edlinkDistrictForm).on('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            
            $.ajax({
                method: "POST",
                enctype: 'multipart/form-data',
                url: apiUrl + "edlink/district/save",
                data: formData,
                processData: false,
                contentType: false,
                cache: false,
            }).done(function( response ) {
                jQuery('#edlinkDistrictForm .form-control').removeClass('is-invalid');
                edlinkDistrictModalObj.hide();
                window.location.reload();
            }).fail(function (response) {
                jQuery('#edlinkDistrictForm .form-control').removeClass('is-invalid');
                if (response.responseJSON !== undefined) {
                    Object.keys(response.responseJSON.data.params).forEach(element => {
                        jQuery('input[name="' + element + '"]').addClass('is-invalid');
                        jQuery('textarea[name="' + element + '"]').addClass('is-invalid');
                        jQuery('select[name="' + element + '"]').addClass('is-invalid');
                    });
                }
            });
        
        });

        edlinkDistrictModal.addEventListener('hide.bs.modal', function (event) {
            jQuery('#edlinkDistrictForm .form-control').removeClass('is-invalid');
            jQuery('#edlinkInputPeopleName').removeClass('is-invalid');
            jQuery("#saveEdlinkDistrictBtn").attr("disabled", false);
            jQuery("#edlink_provider_select").val(0);
            jQuery("#edlink_district_post_id").val(0);
            jQuery("#inputEdlinkDistrictId").val("");
            jQuery("#edlinkInputDistrictName").html('<option value="0"> Choose...</option>');
            jQuery('#edlinkInputAbout').val("");
            jQuery("#edlinkInputPeopleName").html('<option value="0"> Choose...</option>');
            jQuery("#inputEdlinkUserId").val('');
            jQuery("#edlinkInputFirstName").val('');
            jQuery("#edlinkInputLastName").val('');
            jQuery("#inputEdlinkProviderId").val('');
            jQuery("#inputEdlinkProviderAccessToken").val('');
            jQuery("#inputEdlinkProviderName").val('');
            jQuery("#edlink_loader").html('');
            jQuery("#edlink_error").html("");
            jQuery('.edlink-district-action').text("Add");
            var html = '<select id="edlinkInputDistrictName" name="edlink_district_name" class="form-select" onChange="javascript:setEdlinkDistrictId();"><option value="0">Choose...</option></select>';
            jQuery('#edlink_district_container').html(html);
        });
    });

    function getEdlinkProviders() {
        var cuurent_val = jQuery("#district_type option:selected").val();        
        jQuery("#edlink_error").html("");
        jQuery("#saveEdlinkDistrictBtn").attr("disabled", true);
        jQuery("#edlink_loader").html('<i class="fa fa-spinner fa-spin" style="font-size:25px"></i> Loading ...');
        if (cuurent_val == "edlink") {
            //jQuery("#district_type").attr("disabled", true);
            $.ajax({
                method: "GET",
                url: apiUrl + "edlink/provider"
            }).done(function( response ) {
                if (response['data']['error'] == '') {
                    var html = '<div class="label_box brief_label_box id_label_box"><label class="label">Edlink Providers</label>';
                    html += '<select name="edlink_providers" id="edlink_provider_select" class="form-select" onChange="javascript:getEdlinkDistricts()"><option value="0"> Choose...</option>';
                    response['data']['providers']['$data'].forEach(function(result) {
                        html += '<option value="'+result["provider"]["id"]+'" id="'+result["access_token"]+'">'+result["provider"]["name"]+'</option>';
                    });
                    html += '</select></div>';
                    jQuery("#saveEdlinkDistrictBtn").attr("disabled", false);
                    jQuery("#edlink_provider").html(html);
                } else if (response['data']['error'] != '') {
                    jQuery("#edlink_provider").html("");
                    jQuery("#edlink_error").html(response['data']['error']);
                }
                jQuery("#edlink_loader").html('');
            });
        } else if (cuurent_val == "tinylxp") {
            jQuery("#edlink_provider").html("");
            jQuery("#edlinkInputDistrictName").val("");
            jQuery("#saveEdlinkDistrictBtn").attr("disabled", false);
        }
    }

    function getEdlinkDistricts() {
        jQuery("#saveEdlinkDistrictBtn").attr("disabled", true);
        jQuery("#people_loader").html('<i class="fa fa-spinner fa-spin" style="font-size:25px"></i> Loading ...');
        var access_token = jQuery("#edlink_provider_select option:selected").attr('id');
        jQuery("#inputEdlinkProviderId").val(jQuery("#edlink_provider_select option:selected").val());
        jQuery("#inputEdlinkProviderAccessToken").val(access_token);
        jQuery("#inputEdlinkProviderName").val(jQuery("#edlink_provider_select option:selected").text());
        jQuery("#edlink_error").html("");
        jQuery("#edlinkInputDistrictName").html('<option value="0"> Choose...</option>');
        jQuery("#edlinkInputPeopleName").html('<option value="0"> Choose...</option>');
        jQuery("#inputEdlinkUserId").val('');
        jQuery("#edlinkInputFirstName").val('');
        jQuery("#edlinkInputLastName").val('');
        $.ajax({
            method: "POST",            
            url: apiUrl + "edlink/provider/people",
            data: {access_token, "api_require" : "districts", "provider_name" : jQuery("#edlink_provider_select option:selected").text()}
        }).done(function( response ) {
            // Set District Data
            if (typeof response.districts === 'object' && response.districts !== null && !response['districts']['error']) {
                var html = '';
                html += '<option value="0"> Choose...</option>';
                Object.entries(response.districts).forEach(([key, district]) => {
                    html += '<option value="'+district["name"]+'" id="'+district["id"]+'">'+district["name"]+'</option>';
                });
                jQuery("#edlinkInputDistrictName").html(html);
                jQuery("#saveEdlinkDistrictBtn").attr("disabled", false);
            } else if (response['districts']['error'] != '') {
                jQuery("#saveEdlinkDistrictBtn").attr("disabled", true);
                jQuery("#edlink_provider_select").val(0);
                jQuery("#edlink_error").html(response['districts']['error']);
                jQuery("#edlinkInputDistrictName").html('<option value="0"> Choose...</option>');
                jQuery("#inputEdlinkDistrictId").val("");
            }

            // Set People Data
            if (typeof response.people === 'object' && response.people !== null && !response['people']['error']) {
                var html = '';
                html += '<option value="0"> Choose...</option>';
                Object.entries(response.people).forEach(([key, person]) => {
                    html += '<option value="'+person["email"]+'" id="'+person["id"]+'" firstName="'+person["first_name"]+'" lastName="'+person["last_name"]+'">'+person["first_name"]+' '+person["last_name"]+' ('+person["email"]+')</option>';
                });
                jQuery("#edlinkInputPeopleName").html(html);
                jQuery("#saveEdlinkDistrictBtn").attr("disabled", false);
            } else {
                jQuery("#saveEdlinkDistrictBtn").attr("disabled", true);
                jQuery("#edlink_provider_select").val(0);
                jQuery("#edlink_error").html(response['people']['error']);
                jQuery("#edlinkInputPeopleName").html('<option value="0"> Choose...</option>');
                jQuery("#inputEdlinkUserId").val('');
                jQuery("#edlinkInputFirstName").val('');
                jQuery("#edlinkInputLastName").val('');
            }
            jQuery("#people_loader").html('');
        });
    }

    function setEdlinkDistrictId() {
        var id = jQuery("#edlinkInputDistrictName option:selected").attr('id');
        jQuery("#inputEdlinkDistrictId").val(id);
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