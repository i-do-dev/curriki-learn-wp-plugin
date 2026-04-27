<?php
global $treks_src;
$classes = $args["classes"];
?>
<div id="class-tab-content" class="tab-pane fade" role="tabpanel">
    <div class="add-teacher-box">
        <div class="search-filter-box">
            <input type="text" name="text" placeholder="Search..." />
            <div class="filter-box">
                <img src="<?php echo $treks_src; ?>/assets/img/filter-alt.svg" alt="filter logo" />
                <p class="filter-heading">Filter</p>
            </div>
        </div>
        <!-- <button class="add-heading" type="button" type="button" data-bs-toggle="modal"
            data-bs-target="#classModal" class="primary-btn">
            Add New Class
        </button> -->
    </div>
    <div class="students-table">
        <table class="table">
            <thead>
                <tr>
                    <th class="">
                        <div class="th1">
                            Class
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
                    foreach ($classes as $class) {
                ?>
                    <tr>
                        <td class="user-box">
                            <div class="table-user">
                                <img src="<?php echo $treks_src; ?>/assets/img/profile-icon.png" alt="student" />
                                <div class="user-about">
                                    <h5><?php echo $class->post_title?></h5>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="table-status grade">
                                <?php 
                                    $schedule = (array)json_decode(get_post_meta($class->ID, 'schedule', true));
                                    foreach (array_keys($schedule) as $day) {
                                        $start = date('h:i a', strtotime($schedule[$day]->start));
                                        $end = date('h:i a', strtotime($schedule[$day]->end));
                                    ?>
                                        <span><?php echo ucwords($day) ?> / <?php echo $start; ?> - <?php echo $end; ?></span>
                                    <?php } ?>
                            </div>
                        </td>
                        <td><?php echo count(lxp_get_class_assignments($class->ID)); ?></td>
                        <td class="grade">
                            <span><?php echo get_post_meta($class->ID, 'grade', true); ?></span>
                        </td>
                        <td>
                            <?php
                                echo count(lxp_get_class_group($class->ID)); 
                            ?>
                        </td>
                        <td>
                            <!-- <div class="dropdown">
                                <button class="dropdown_btn" type="button" id="dropdownMenu2"
                                    data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <img src="<?php // echo $treks_src; ?>/assets/img/dots.svg" alt="logo" />
                                </button>
                                <div class="dropdown-menu" aria-labelledby="dropdownMenu2">
                                    <button class="dropdown-item" type="button" onclick="onClassEdit(<?php // echo $class->ID; ?>)">
                                        <img src="<?php // echo $treks_src; ?>/assets/img/edit.svg" alt="logo" />
                                        Edit</button>
                                    <button class="dropdown-item" type="button">
                                        <img src="./assets/img/delete.svg" alt="logo" />
                                        Delete</button>
                                </div>
                            </div> -->
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>