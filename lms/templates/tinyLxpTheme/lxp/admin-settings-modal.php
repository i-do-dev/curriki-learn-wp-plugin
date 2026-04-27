<style type="text/css">
    #settingsModal .tab-content>.active {
        display: block !important;
    }
    .settings-min-height {
        min-height: 200px;
    }
</style>
<!-- Settings Modal -->
<div class="modal fade" id="settingsModal" tabindex="-1" aria-labelledby="settingsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="settingsModalLabel"><span id="settingsEntity"></span> Settings</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Bootstrap tabs -->
                <ul class="nav nav-tabs mb-3" id="settingsTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <a class="nav-link active" id="general-tab" data-bs-toggle="tab" href="#general" role="tab" aria-controls="general" aria-selected="true">General</a>
                    </li>
                </ul>
                <div class="tab-content settings-min-height" id="settingsTabContent">
                    <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">
                        <!-- Active switch -->
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="activeSwitch">
                                    <label class="form-check-label" for="activeSwitch">Active</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="btn_box mt-3">
                    <button class="btn" id="updateSettingsBtn"><span class="teacher-action">Update</span></button>
                    <button class="btn" type="button" data-bs-dismiss="modal" aria-label="Close">Cancel</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    jQuery(document).ready(function() {
        let apiUrl = window.location.hostname === 'localhost' ? window.location.origin + '<?php echo WORDPRESS_HOST; ?>' + '/wp-json/lms/v1/' : window.location.origin + '/wp-json/lms/v1/';

        var settingsModal = document.getElementById('settingsModal');
        settingsModalObj = new bootstrap.Modal(settingsModal);
        window.settingsModalObj = settingsModalObj;

        jQuery("#updateSettingsBtn").on('click', function() {
            jQuery("#updateSettingsBtn").attr("disabled", "disabled");
            let beforeText = jQuery("#updateSettingsBtn").text();
            jQuery("#updateSettingsBtn").html(`<i class="fa fa-spinner fa-spin"></i> ` + beforeText);
            
            let isActive = jQuery("#activeSwitch").prop('checked');
            let data = {
                entity_post_id: window.settingsEntityId,
                active: isActive
            };

            $.ajax({
                method: "POST",
                url: apiUrl + window.settingsEntity + "/settings/update",
                data
            }).done(function( response ) {
                // reload the page
                location.reload();
            }).fail(function (response) {
                console.error("Failed to update active status: ", response);
                jQuery("#updateSettingsBtn").text(beforeText);
                jQuery("#updateSettingsBtn").removeAttr("disabled");
            });
        });

        // reload page on modal close
        /* settingsModal.addEventListener('hidden.bs.modal', function (event) {
            location.reload();
        }); */
    });

    function onSettingsClick(settingsEntityId, settingsEntity) {
        let apiUrl = window.location.hostname === 'localhost' ? window.location.origin + '<?php echo WORDPRESS_HOST; ?>' + '/wp-json/lms/v1/' : window.location.origin + '/wp-json/lms/v1/';

        $.ajax({
            method: "GET",
            url: apiUrl + settingsEntity +"/settings" + "?entity_post_id=" + settingsEntityId 
        }).done(function( response ) {
            window.settingsEntity = settingsEntity;
            window.settingsEntityId = settingsEntityId;
            const settings = response.data;
            jQuery("#activeSwitch").prop('checked', settings.active);

            // set settingsEntity in modal title with first letter capital
            jQuery("#settingsEntity").text(settingsEntity.charAt(0).toUpperCase() + settingsEntity.slice(1));
            settingsModalObj.show();
        }).fail(function (response) {
            // console.error("Failed to load teacher settings");

            // set settingsEntity in modal title with first letter capital
            jQuery("#settingsEntity").text(settingsEntity.charAt(0).toUpperCase() + settingsEntity.slice(1));
            settingsModalObj.show();
        });
    }
</script>