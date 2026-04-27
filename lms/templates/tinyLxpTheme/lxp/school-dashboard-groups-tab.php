<?php
global $treks_src;
$groups = $args["groups"];
?>
<div id="group-tab-content" class="tab-pane fade" role="tabpanel">
    <div class="add-teacher-box">
        <div class="search-filter-box">
            <input type="text" name="text" placeholder="Search..." />
            <div class="filter-box">
                <img src="<?php echo $treks_src; ?>/assets/img/filter-alt.svg" alt="filter logo" />
                <p class="filter-heading">Filter</p>
            </div>
        </div>
    </div>
    <div class="students-table">
        <table class="table">
            <thead>
                <tr>
                    <th class="">
                        <div class="th1">
                            Groups
                            <img src="<?php echo $treks_src; ?>/assets/img/showing.svg" alt="logo" />
                        </div>
                    </th>
                    <th>
                        <div class="th1 th2">
                            Class/Group
                            <img src="<?php echo $treks_src; ?>/assets/img/showing.svg" alt="logo" />
                        </div>
                    </th>
                    <th>
                        <div class="th1 th3">
                            Students
                            <img src="<?php echo $treks_src; ?>/assets/img/showing.svg" alt="logo" />
                        </div>
                    </th>
                </tr>
            </thead>
            <tbody>
                <?php 
                    foreach ($groups as $group) {
                ?>
                    <tr>
                        <td class="user-box">
                            <div class="table-user">
                                <img src="<?php echo $treks_src; ?>/assets/img/profile-icon.png" alt="student" />
                                <div class="user-about">
                                    <h5><?php echo $group->post_title?></h5>
                                </div>
                            </div>
                        </td>
                        <td>
                            <?php 
                                echo get_post_meta($group->ID, 'lxp_classe_group_name', true); 
                            ?>
                        </td>
                        <td>
                            <?php 
                                echo count(get_post_meta($group->ID, 'lxp_group_student_ids'));
                            ?>
                        </td>
                        <td>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>