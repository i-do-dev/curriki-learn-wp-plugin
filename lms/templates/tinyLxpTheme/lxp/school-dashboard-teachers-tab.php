<?php
    global $treks_src;
    $teachers = $args["teachers"];
    $district_type = $args["district_type"];
?>
<div id="teacher-tab-content" class="tab-pane fade" role="tabpanel">
    <div class="add-teacher-box" style="width: 43%">
        <!-- <div class="search-filter-box">
            <input type="text" name="text" placeholder="Search..." />
            <div class="filter-box">
                <img src="<?php echo $treks_src; ?>/assets/img/filter-alt.svg" alt="filter logo" />
                <p class="filter-heading">Filter</p>
            </div>
        </div> -->
        <?php 
            if (isset($district_type) && $district_type == 'edlink') {
                $model_id = 'edlinkTeacherModal';
                $add_btn = 'addEdlinkTeachers';
            } else {
                $model_id = 'teacherModal';
                $add_btn = 'addTeachers';
            }
        ?>
        <button id="<?php echo $add_btn; ?>" class="add-heading" type="button" type="button" data-bs-toggle="modal" data-bs-target="#<?php echo $model_id; ?>" class="primary-btn">
            Add New Teacher
        </button>
        <?php
            if (empty($district_type) || $district_type != 'edlink') {
        ?>
                <label for="import-teacher" class="primary-btn add-heading">
                    Import Teachers (CSV)
                </label >
                <input type="file" id="import-teacher" hidden />
        <?php        
            }
        ?>
    </div>
    <div class="students-table">
        <table class="table">
            <thead>
                <tr>
                    <th class="">
                        <div class="th1">
                            Teacher
                            <img src="<?php echo $treks_src; ?>/assets/img/showing.svg" alt="logo" />
                        </div>
                    </th>
                    <th>
                        <div class="th1 th2">
                            Email
                            <img src="<?php echo $treks_src; ?>/assets/img/showing.svg" alt="logo" />
                        </div>
                    </th>
                    <th>
                        <div class="th1 th3">
                            District
                            <img src="<?php echo $treks_src; ?>/assets/img/showing.svg" alt="logo" />
                        </div>
                    </th>
                    <th>
                        <div class="th1 th3">
                            School
                            <img src="<?php echo $treks_src; ?>/assets/img/showing.svg" alt="logo" />
                        </div>
                    </th>
                    <th>
                        <div class="th1 th3">
                            Students
                            <img src="<?php echo $treks_src; ?>/assets/img/showing.svg" alt="logo" />
                        </div>
                    </th>
                    <th>
                        <div class="th1 th5">
                            ID
                            <img src="<?php echo $treks_src; ?>/assets/img/showing.svg" alt="logo" />
                        </div>
                    </th>
                </tr>
            </thead>
            <tbody>
                <?php 
                    foreach ($teachers as $teacher) {
                        $teacher_admin = get_userdata(get_post_meta($teacher->ID, 'lxp_teacher_admin_id', true));
                        $teacher_school = null;
                        $teacher_district = null;
                        $teacher_school_id = get_post_meta($teacher->ID, 'lxp_teacher_school_id', true);
                        if ($teacher_school_id) {
                            $teacher_school = get_post($teacher_school_id);
                            $teacher_district_id = get_post_meta($teacher_school->ID, 'lxp_school_district_id', true);
                            if ($teacher_district_id) {
                                $teacher_district = get_post($teacher_district_id);
                            }
                        }
                        $teacher_students = array();
                        if ($teacher_district && $teacher_school) {
                            $teacher_students = lxp_get_school_teacher_students($teacher->ID);
                        }
                ?>
                    <tr>
                        <td class="user-box">
                            <div class="table-user">
                                <img src="<?php echo $treks_src; ?>/assets/img/profile-icon.png" alt="teacher" />
                                <div class="user-about">
                                    <h5><?php echo $teacher->post_title; ?></h5>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="table-status"><?php echo $teacher_admin->user_email?></div>
                        </td>
                        <td><?php echo $teacher_district ? $teacher_district->post_title : '---'; ?></td>
                        <td><?php echo $teacher_school ? $teacher_school->post_title : '---' ?></td>
                        <td><?php echo $teacher_students && is_array($teacher_students) ? count($teacher_students) : 0 ?></td>
                        <!-- 
                        <td class="grade">
                            <?php 
                                /* $teacher_grades = json_decode(get_post_meta($teacher->ID, 'grades', true));
                                $teacher_grades = $teacher_grades ? $teacher_grades : array();
                                foreach ($teacher_grades as $grade) { */
                            ?>
                                <span><?php //echo $grade; ?></span>
                            <?php        
                                // }
                            ?>
                        </td>
                         -->
                        <td><?php echo $teacher->ID ?></td>
                        <td>
                            <div class="dropdown">
                                <button class="dropdown_btn" type="button" id="dropdownMenu2"
                                    data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <img src="<?php echo $treks_src; ?>/assets/img/dots.svg" alt="logo" />
                                </button>
                                <div class="dropdown-menu" aria-labelledby="dropdownMenu2">
                                <?php
                                    if (isset($district_type) && $district_type == 'edlink') {
                                ?>
                                        <button class="dropdown-item" type="button" onclick="onEdlinkTeacherEdit(<?php echo $teacher->ID; ?>)">
                                        <img src="<?php echo $treks_src; ?>/assets/img/edit.svg" alt="logo" />
                                        Edit</button>
                                <?php
                                    } else {
                                ?>
                                        <button class="dropdown-item" type="button" onclick="onTeacherEdit(<?php echo $teacher->ID; ?>)">
                                        <img src="<?php echo $treks_src; ?>/assets/img/edit.svg" alt="logo" />
                                        Edit</button>
                                <?php            
                                    }
                                ?>
                                    <!-- <button class="dropdown-item" type="button">
                                        <img src="<?php // echo $treks_src; ?>/assets/img/delete.svg" alt="logo" />
                                        Delete</button> -->
                                </div>
                            </div>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>