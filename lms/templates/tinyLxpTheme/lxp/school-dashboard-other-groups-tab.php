<?php
global $treks_src;
$other_groups = $args["other_groups"];
?>
<div id="other-group-tab-content" class="tab-pane fade" role="tabpanel">
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
                            Group
                            <img src="<?php echo $treks_src; ?>/assets/img/showing.svg" alt="logo" />
                        </div>
                    </th>
                    <th>
                        <div class="th1 th2">
                            Schedule
                            <img src="<?php echo $treks_src; ?>/assets/img/showing.svg" alt="logo" />
                        </div>
                    </th>
                    <th>
                        <div class="th1 th3">
                            Assignments
                            <img src="<?php echo $treks_src; ?>/assets/img/showing.svg" alt="logo" />
                        </div>
                    </th>
                    <th>
                        <div class="th1 th4">
                            Grades
                            <img src="<?php echo $treks_src; ?>/assets/img/showing.svg" alt="logo" />
                        </div>
                    </th>
                    <th>
                        <div class="th1 th5">
                            Groups
                            <img src="<?php echo $treks_src; ?>/assets/img/showing.svg" alt="logo" />
                        </div>
                    </th>
                </tr>
            </thead>
            <tbody>
                <?php 
                    foreach ($other_groups as $other_group) {
                ?>
                    <tr>
                        <td class="user-box">
                            <div class="table-user">
                                <img src="<?php echo $treks_src; ?>/assets/img/profile-icon.png" alt="student" />
                                <div class="user-about">
                                    <h5><?php echo $other_group->post_title?></h5>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="table-status grade">
                                <?php 
                                    $schedule = (array)json_decode(get_post_meta($other_group->ID, 'schedule', true));
                                    foreach (array_keys($schedule) as $day) {
                                        $start = date('h:i a', strtotime($schedule[$day]->start));
                                        $end = date('h:i a', strtotime($schedule[$day]->end));
                                    ?>
                                        <span><?php echo ucwords($day) ?> / <?php echo $start; ?> - <?php echo $end; ?></span>
                                    <?php } ?>
                            </div>
                        </td>
                        <td><?php echo count(lxp_get_class_assignments($other_group->ID)); ?></td>
                        <td class="grade">
                            <span><?php echo get_post_meta($other_group->ID, 'grade', true); ?></span>
                        </td>
                        <td>
                            <?php
                                echo count(lxp_get_class_group($other_group->ID)); 
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