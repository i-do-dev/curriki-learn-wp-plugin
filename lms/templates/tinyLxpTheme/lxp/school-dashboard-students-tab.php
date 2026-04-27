<?php
    global $treks_src;
    $students = $args["students"];
    $teachers = $args["teachers"];
    $district_type = $args["district_type"];
    $is_teacher_assignment_needed = $args["is_teacher_assignment_needed"];
    $teacher_id = isset($_GET['teacher_id']) ? $_GET['teacher_id'] : 0;
    $teacher = array_values(array_filter($teachers, function($teacher) use ($teacher_id) {
        return $teacher->ID == $teacher_id;
    }));
    $teacher_selected = is_array($teacher) && count($teacher) > 0 ? $teacher[0] : null;
?>
<div id="student-tab-content" class="tab-pane fade" role="tabpanel">
    <div class="add-teacher-box">
        
        <!--   <div class="search-filter-box">
            <input type="text" name="text" placeholder="Search..." />
            <div class="filter-box">
                <img src="<?php // echo $treks_src; ?>/assets/img/filter-alt.svg" alt="filter logo" />
                <p class="filter-heading">Filter</p>
            </div>
        </div> -->

        <div class="search-filter-box">
            <div class="row">
                <div class="col-md-12">
                    <form class="row">
                        <div class="col-md-12">
                            <label for="district-drop-down" class="form-label">Teacher</label>
                            <select class="form-select" id="teacher-drop-down" name="teacher_id">
                                <option value="0">Choose...</option>
                                <?php foreach ($teachers as $teacher) { ?>
                                    <option value="<?php echo $teacher->ID; ?>"<?php echo isset($_GET['teacher_id']) && $_GET['teacher_id'] == $teacher->ID ? ' selected=selected' : '' ?>><?php echo $teacher->post_title; ?></option>
                                <?php } ?>
                            </select>
                        </div>
                    </form>
                </div>
                
            </div>
        </div>
        
        <?php if (!is_null($teacher_selected)) { ?>
            <div>
            <?php 
                if (isset($district_type) && $district_type == 'edlink') {
                    $model_id = 'edlinkStudentModal';
                    $add_btn = 'edlinkStudentModalBtn';
                } else {
                    $model_id = 'studentModal';
                    $add_btn = 'studentModalBtn';

                    echo '<button id="takenStudentModalBtn" class="add-heading" type="button" data-bs-toggle="modal" data-bs-target="#takenStudentModal" class="primary-btn"> Add Lxp Students </button>';
                }
            ?>
                <button id="<?php echo $add_btn; ?>" class="add-heading" type="button" data-bs-toggle="modal" data-bs-target="#<?php echo $model_id; ?>" class="primary-btn">
                    Add New Student
                </button>
                <?php
                    if (empty($district_type) || $district_type != 'edlink') {
                ?>
                        <label for="import-student" class="primary-btn add-heading">
                            Import Students (CSV)
                        </label >
                        <input type="file" id="import-student" hidden />
                <?php        
                    }
                ?>
            </div>
        <?php } else { ?>
            <!-- guide alter which says 'Select `Teacher` to `Add` or `Import` the `Students`-->
            <div class="alert alert-info" role="alert" style="margin-top: 10px;">
                Please select a `Teacher` to add or import `Students`.
            </div>
        <?php } ?>
    </div>

    <div class="students-table">
        <!-- notice to show message 'Select Teaher to list Students' -->
        <?php if (is_null($teacher_selected) & $is_teacher_assignment_needed) { ?>
            <div class="alert alert-danger" role="alert">
                Please assign a teacher to each student listed below.
            </div>
        <?php } ?>
        
        
        <table class="table">
            <thead>
                <tr>
                    <th class="">
                        <div class="th1">
                            Student
                            <img src="<?php echo $treks_src; ?>/assets/img/showing.svg" alt="logo" />
                        </div>
                    </th>
                    <th>
                        <div class="th1 th2">
                            Username
                            <img src="<?php echo $treks_src; ?>/assets/img/showing.svg" alt="logo" />
                        </div>
                    </th>
                    <th>
                        <div class="th1 th3">
                            Classes
                            <img src="<?php echo $treks_src; ?>/assets/img/showing.svg" alt="logo" />
                        </div>
                    </th>
                    <th>
                        <div class="th1 th3">
                            Group
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
                            ID
                            <img src="<?php echo $treks_src; ?>/assets/img/showing.svg" alt="logo" />
                        </div>
                    </th>
                </tr>
            </thead>
            <tbody>
                <?php 
                    foreach ($students as $student) {
                        $student_admin = get_userdata(get_post_meta($student->ID, 'lxp_student_admin_id', true));
                        $student_id = get_post_meta($student->ID, 'student_id', true);
                ?>
                    <tr>
                        <td class="user-box">
                            <div class="table-user">
                                <img src="<?php echo $treks_src; ?>/assets/img/profile-icon.png" alt="student" />
                                <div class="user-about">
                                    <h5><?php echo $student->post_title?></h5>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="table-status"><?php echo $student_admin->user_login?></div>
                        </td>
                        <td>
                            <?php 
                                echo count(lxp_get_student_class_group_by_type($student->ID, 'classes'));
                            ?>
                        </td>
                        <td>
                            <?php 
                                echo count(lxp_get_student_class_group_by_type($student->ID, 'other_group'));
                            ?>
                        </td>
                        <td class="grade">
                            <?php 
                                $student_grades = json_decode(get_post_meta($student->ID, 'grades', true));
                                $student_grades = $student_grades ? $student_grades : array();
                                foreach ($student_grades as $grade) {
                            ?>
                                <span><?php echo $grade; ?></span>
                            <?php        
                                }
                            ?>
                        </td>
                        <td><?php echo $student_id ? $student_id : '--'; ?></td>
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
                                            <button class="dropdown-item" type="button" onclick="onEdlinkStudentEdit(<?php echo $student->ID; ?>)">
                                            <img src="<?php echo $treks_src; ?>/assets/img/edit.svg" alt="logo" />
                                            Edit</button>
                                    <?php
                                        } else {
                                    ?>
                                            <button class="dropdown-item" type="button" onclick="onStudentEdit(<?php echo $student->ID; ?>)">
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