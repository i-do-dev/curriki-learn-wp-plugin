<?php

get_header();

global $wp_query;
$post = $wp_query->post;
?>

<div id="main-content">
		<div class="container">
			<div id="content-area" class="clearfix">
				<div id="left-area">
				
					<?php
					// Start the loop.
					$courseId =  isset($_GET['courseid']) ? $_GET['courseid'] : get_post_meta($post->ID, 'tl_course_id', true);
					$args = array(
						'posts_per_page'   => -1,
						'post_type'        => TL_LESSON_CPT,
						'meta_query' => array(
							array(
								'key'   => 'tl_course_id',
								'value' =>  $courseId
							)
						)
					);
					$lessons = get_posts($args);
					
					?>
					
					
					
					<?php while (have_posts()) : the_post(); ?>
						<?php
						/**
						 * Fires before the title and post meta on single posts.
						 *
						 * @since 3.18.8
						 */
						do_action('et_before_post');
						?>
						<article id="post-<?php the_ID(); ?>" <?php post_class('et_pb_post'); ?>>
							
							<h1 class="entry-title"><?php the_title(); ?></h1>

							<p>
								<?php echo get_the_post_thumbnail( $post->ID ); ?>
							</p>
							<p>
								<?php echo $post->post_content; ?>
							</p>

							<?php
								global $wpdb;
								$trek_sections = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}trek_sections WHERE trek_id={$post->ID}");
							?>

							<p id="sections-navigation">
								<?php 
									if ( $trek_sections ) {
										foreach ( $trek_sections as $trek_section ) {
								?>
									<a href="#<?php echo implode('_', explode(' ', $trek_section->title));?>"><?php echo $trek_section->title;?></a> |
								<?php
										}
									}
								?>
							</p>
							
							<div>
						
								
							</div>
							<div>
								<?php 
									if ( $trek_sections ) {
										foreach ( $trek_sections as $trek_section ) {
								?>
										<div style="width: 100%;height: 125px;">
											<div style="float:left;width: 88%;">
												<h1 id="<?php echo implode('_', explode(' ', $trek_section->title));?>"><?php echo $trek_section->title;?></h1>
											</div>
											<div style="float:right;width: 12%;padding-top: 65px;">
												<a  style="float:right;margin-right:30px" href="#sections-navigation">&uarr; Top</a>
												 <div style="clear:both"> </div>
													<?php
											foreach($lessons as $lesson){
											  if(trim($trek_section->title) == trim($lesson->post_title) ){
											     echo '<p style="width:100%"> <a href="' . get_permalink($lesson->ID) . '">Digital Student Journal</a> </p>';
											  }
											}
											?>
											</div>
										</div>
										<div style="clear:both"> </div>
										<?php echo stripslashes($trek_section->content);?>
								<?php
										}
									}
								?>
							</div>
						</article>
						
					<?php endwhile; ?>
				</div>

				
			</div>
		</div>
</div>

<?php

get_footer();
