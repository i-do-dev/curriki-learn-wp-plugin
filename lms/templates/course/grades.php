<?php
/*
Template Name: grades-template
*/
$post   = get_post($_GET['course_id']);
?>
<!doctype html>
<html <?php language_attributes(); ?>>

<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <style>
        table,
        th,
        td {
            border: 1px solid black;
            border-collapse: collapse;
            border-color: #dee2e6;
        }

        body {
            background-color: white;
        }
    </style>
</head>

<body <?php body_class(); ?>>

    <div class="wp-site-blocks">
        <header class="wp-block-template-part site-header">
            <?php //block_header_area(); ?>
        </header>
        <div id="primary" class="content-area">
            <main id="main" class="site-main" role="main" style="overflow-y:auto;width:98%;">
                <?php echo "<h1>" . $post->post_title . " </h1>"; ?>
                <h3>Grades </h3>
                <?php
                // Start the loop.
                $args = array(
                    'posts_per_page'   => -1,
                    'post_type'        => TL_LESSON_CPT,
                    'meta_query' => array(
                        array(
                            'key'   => 'tl_course_id',
                            'value' =>  $post->ID
                        )
                    )
                );
                $lessons = get_posts($args);
                echo "<table  style='width:100%;'>";
                echo '<tr>';
                echo "<th>&nbsp;User&nbsp;name&nbsp;</th><th>&nbsp;User Email&nbsp;</th>";
                foreach ($lessons as $key => $lesson) {
                    echo '<th>&nbsp;<a href="' . get_permalink($lesson->ID) . '" target="blank">' . $lesson->post_title . '</a>&nbsp;</th>';
                    $lessonIds[] = $lesson->ID;
                }
                echo '</tr>';
                
                $students = get_course_participants($lessonIds);
                foreach ($students as $student) {
                    echo "<tr>";
                    echo "<td>";
                    echo $student->display_name;
                    echo "</td>";
                    echo "<td>";
                    echo $student->user_email;
                    echo "</td>";
                    foreach ($lessons as $key => $lesson) {
                        echo '<th>&nbsp;' . get_result($student->ID, $lesson->ID) . ' </th>';
                    }
                    echo "<tr>";
                }
                if(empty($students)){
                    $tableLength = count($lessonIds) + 2;
                    echo "<tr><td style='text-align:center' colspan=". $tableLength .">No record found</td></tr>";
                }
                echo "</table>";
                ?>
                <div>
                </div>
            </main><!-- .site-main -->
        </div><!-- .content-area -->

    </div>
</body>

<?php
function get_course_participants($lessonIds)
{
    global $wpdb;
    $lessonIds = join(",",$lessonIds);   
    $usersTable= $wpdb->prefix . "users";
    $gradesTable=  $wpdb->prefix . "tiny_lms_grades";
    return $wpdb->get_results("SELECT * FROM ".$usersTable." inner join ".$gradesTable." on ".$usersTable.".ID=".$gradesTable.".user_id where ".$gradesTable.".lesson_id In (".$lessonIds.") group by ".$usersTable.".ID");
}


function get_result($student_id, $lesson_id)
{
    global $wpdb;
    $respones = $wpdb->get_results("SELECT score FROM " . $wpdb->prefix . "tiny_lms_grades WHERE user_id = " . $student_id . "
   AND lesson_id= " . $lesson_id);
    if ($respones) {
        return (($respones[0]->score / 1) * 100) . '%' . ' &nbsp &nbsp <a href="'. site_url().'/wp-admin/admin.php?page=gradebook&lesson_id='.$lesson_id.'&student_id='.$student_id.'" title="" rel="permalink"><span class="dashicons dashicons-search"></span></a>';
    }
}
?>