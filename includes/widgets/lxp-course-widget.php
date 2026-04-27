<?php

namespace Edudeme\Elementor;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Background;
use Elementor\Widget_Base; // Important!
use Elementor\Group_Control_Box_Shadow;
use Elementor\Icons_Manager;
use LP_Course_Filter;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
/**
 * Elementor oEmbed Widget.
 *
 * Elementor widget that inserts embeddable content into the page from any given URL.
 *
 * @since 1.0.0
 */
class LXP_Course_Widget extends Widget_Base {

    public function get_categories() {
        return array('edudeme-addons');
    }

    /**
     * Get widget name.
     *
     * Retrieve tabs widget name.
     *
     * @return string Widget name.
     * @since  1.0.0
     * @access public
     *
     */
    public function get_name() {
        return 'lp-courses';
    }

    /**
     * Get widget title.
     *
     * Retrieve tabs widget title.
     *
     * @return string Widget title.
     * @since  1.0.0
     * @access public
     *
     */
    public function get_title() {
        return 'LXP Courses';
    }

    /**
     * Get widget icon.
     *
     * Retrieve tabs widget icon.
     *
     * @return string Widget icon.
     * @since  1.0.0
     * @access public
     *
     */
    public function get_icon() {
        return 'eicon-archive-posts';
    }

    public function get_script_depends() {
        return ['edudeme-elementor-courses'];
    }

    public function get_style_depends() {
        return ['magnific-popup'];
    }

    protected function register_controls() {

        $this->start_controls_section(
            'section_setting',
            [
                'label' => esc_html__('Settings', 'edudeme'),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'limit',
            [
                'label'   => esc_html__('Posts Per Page', 'edudeme'),
                'type'    => Controls_Manager::NUMBER,
                'default' => 6,
            ]
        );


        $this->add_control(
            'advanced',
            [
                'label' => esc_html__('Advanced', 'edudeme'),
                'type'  => Controls_Manager::HEADING,
            ]
        );

        $this->add_control(
            'orderby',
            [
                'label'   => esc_html__('Order By', 'edudeme'),
                'type'    => Controls_Manager::SELECT,
                'default' => 'post_date',
                'options' => [
                    'post_date'       => esc_html__('Newest', 'edudeme'),
                    'post_title'      => esc_html__('Title a-z', 'edudeme'),
                    'post_title_desc' => esc_html__('Title z-a', 'edudeme'),
                    'price'           => esc_html__('Price High to Low', 'edudeme'),
                    'price_low'       => esc_html__('Price Low to High', 'edudeme'),
                    'popular'         => esc_html__('Popular', 'edudeme'),
                ],
            ]
        );

        $this->add_control(
            'enable_dynamic',
            [
                'label' => 'Dynamic Data',
                'type'  => Controls_Manager::SWITCHER,
                'frontend_available' => true,
                'label_on' => __('Yes', 'textdomain'),
                'label_off' => __('No', 'textdomain'),
            ]
        );

        $this->add_control(
            'categories',
            [
                'label'       => esc_html__('Categories', 'edudeme'),
                'type'        => Controls_Manager::SELECT2,
                'options'     => $this->get_course_categories(),
                'label_block' => true,
                'multiple'    => true,
            ]
        );

        $this->add_control(
            'course_type',
            [
                'label'   => esc_html__('Course Type', 'edudeme'),
                'type'    => Controls_Manager::SELECT,
                'default' => 'all',
                'options' => [
                    'all'        => esc_html__('All Courses', 'edudeme'),
                    'on_sale'    => esc_html__('On Sale Courses', 'edudeme'),
                    'on_free'    => esc_html__('Free Courses', 'edudeme'),
                    'on_paid'    => esc_html__('Paid Courses', 'edudeme'),
                    'on_feature' => esc_html__('Featured Courses', 'edudeme'),
                ],

            ]
        );

        $this->add_control(
            'course_style',
            [
                'label'   => esc_html__('Course Style', 'edudeme'),
                'type'    => Controls_Manager::SELECT,
                'default' => '1',
                'options' => [
                    '1' => esc_html__('Style 1', 'edudeme'),
                    '2' => esc_html__('Style 2', 'edudeme'),
                    '3' => esc_html__('Style 3', 'edudeme'),
                    '4' => esc_html__('Style 4', 'edudeme'),
                ],
            ]
        );

        $this->end_controls_section();

        // Style
        $this->start_controls_section(
            'section_courses_style',
            [
                'label' => esc_html__('Wrapper', 'edudeme'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            'course_padding',
            [
                'label'      => esc_html__('Padding', 'edudeme'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors'  => [
                    '{{WRAPPER}} .learn-press-courses[data-layout] li.course .course-item' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'course_margin',
            [
                'label'      => esc_html__('Margin', 'edudeme'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors'  => [
                    '{{WRAPPER}} .learn-press-courses[data-layout] li.course .course-item' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name'        => 'wrapper_border',
                'placeholder' => '1px',
                'default'     => '1px',
                'selector'    => '{{WRAPPER}} .learn-press-courses[data-layout] li.course .course-item',
                'separator'   => 'before',
            ]
        );

        $this->add_control(
            'wrapper_border_radius',
            [
                'label'      => esc_html__('Border Radius', 'edudeme'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors'  => [
                    '{{WRAPPER}} .learn-press-courses[data-layout] li.course .course-item' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'wrapper_background',
            [
                'label'     => esc_html__('Background item', 'edudeme'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '',
                'selectors' => [
                    '{{WRAPPER}} .learn-press-courses[data-layout] li.course .course-item' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_courses_image_style',
            [
                'label' => esc_html__('Image', 'edudeme'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            'image_width_style',
            [
                'label'          => esc_html__('Width', 'edudeme'),
                'type'           => Controls_Manager::SLIDER,
                'default'        => [
                    'unit' => '%',
                ],
                'tablet_default' => [
                    'unit' => '%',
                ],
                'mobile_default' => [
                    'unit' => '%',
                ],
                'size_units'     => ['%', 'px', 'vw'],
                'range'          => [
                    '%'  => [
                        'min' => 1,
                        'max' => 100,
                    ],
                    'px' => [
                        'min' => 1,
                        'max' => 1000,
                    ],
                    'vw' => [
                        'min' => 1,
                        'max' => 100,
                    ],
                ],
                'selectors'      => [
                    '{{WRAPPER}} .learn-press-courses[data-layout] li.course .course-wrap-thumbnail'     => 'width: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .learn-press-courses[data-layout] li.course .course-wrap-thumbnail img' => 'width: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'image_height_style',
            [
                'label'          => esc_html__('Height', 'edudeme'),
                'type'           => Controls_Manager::SLIDER,
                'default'        => [
                    'unit' => 'px',
                ],
                'tablet_default' => [
                    'unit' => 'px',
                ],
                'mobile_default' => [
                    'unit' => 'px',
                ],
                'size_units'     => ['px', 'vh'],
                'range'          => [
                    'px' => [
                        'min' => 1,
                        'max' => 500,
                    ],
                    'vh' => [
                        'min' => 1,
                        'max' => 100,
                    ],
                ],
                'selectors'      => [
                    '{{WRAPPER}} .learn-press-courses[data-layout] li.course .course-wrap-thumbnail img' => 'height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'image_border_radius',
            [
                'label'      => esc_html__('Border Radius', 'edudeme'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors'  => [
                    '{{WRAPPER}} .learn-press-courses[data-layout] li.course .course-wrap-thumbnail' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_courses_content_style',
            [
                'label' => esc_html__('Content', 'edudeme'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'course_content_heading',
            [
                'label' => esc_html__('Wrapper', 'edudeme'),
                'type'  => Controls_Manager::HEADING,
            ]
        );

        $this->add_responsive_control(
            'course_content_padding',
            [
                'label'      => esc_html__('Padding', 'edudeme'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors'  => [
                    '{{WRAPPER}} .learn-press-courses[data-layout] li.course .course-content' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'course_content_margin',
            [
                'label'      => esc_html__('Margin', 'edudeme'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors'  => [
                    '{{WRAPPER}} .learn-press-courses[data-layout] li.course .course-content' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        // Title
        $this->add_control(
            'course_title_heading',
            [
                'label'     => esc_html__('Title', 'edudeme'),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'typography',
                'selector' => '{{WRAPPER}} .learn-press-courses[data-layout] li.course .course-content .course-title',
            ]
        );

        $this->add_responsive_control(
            'course_title_margin',
            [
                'label'      => esc_html__('Margin', 'edudeme'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors'  => [
                    '{{WRAPPER}} .learn-press-courses[data-layout] li.course .course-content .course-permalink' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        // Description
        $this->add_control(
            'course_desc_heading',
            [
                'label'     => esc_html__('Description', 'edudeme'),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'course_desc_typo',
                'selector' => '{{WRAPPER}} .learn-press-courses[data-layout] li.course .course-content .course-short-description',
            ]
        );

        $this->add_responsive_control(
            'course_desc_margin',
            [
                'label'      => esc_html__('Margin', 'edudeme'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors'  => [
                    '{{WRAPPER}} .learn-press-courses[data-layout] li.course .course-content .course-short-description' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        // Price
        $this->add_control(
            'course_price_heading',
            [
                'label'     => esc_html__('Price', 'edudeme'),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'free_color_style',
            [
                'label'     => esc_html__('Free Color', 'edudeme'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .learn-press-courses[data-layout] li.course .course-content .course-price .course-item-price .free' => 'color: {{VALUE}}',
                ],
            ]
        );

        $this->add_control(
            'free_background_style',
            [
                'label'     => esc_html__('Free background', 'edudeme'),
                'type'      => Controls_Manager::COLOR,
                'condition' => [
                    'course_style' => '4',
                ],
                'selectors' => [
                    '{{WRAPPER}} .learn-press-courses[data-layout] li.course .course-content .course-price .course-item-price .free' => 'background: {{VALUE}}',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'course_free_typo',
                'selector' => '{{WRAPPER}} .learn-press-courses[data-layout] li.course .course-content .course-price .course-item-price .free',
            ]
        );

        $this->add_control(
            'origin_color_style',
            [
                'label'     => esc_html__('Origin Price Color', 'edudeme'),
                'type'      => Controls_Manager::COLOR,
                'separator' => 'before',
                'condition' => [
                    'course_style!' => '4',
                ],
                'selectors' => [
                    '{{WRAPPER}} .learn-press-courses[data-layout] li.course .course-content .course-price .course-item-price .origin-price' => 'color: {{VALUE}}',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'      => 'course_origin_typo',
                'condition' => [
                    'course_style!' => '4',
                ],
                'selector'  => '{{WRAPPER}} .learn-press-courses[data-layout] li.course .course-content .course-price .course-item-price .origin-price',
            ]
        );

        $this->add_control(
            'price_color_style',
            [
                'label'     => esc_html__('Price Color', 'edudeme'),
                'type'      => Controls_Manager::COLOR,
                'separator' => 'before',
                'selectors' => [
                    '{{WRAPPER}} .learn-press-courses[data-layout] li.course .course-content .course-price .course-item-price .price' => 'color: {{VALUE}}',
                ],
            ]
        );

        $this->add_control(
            'price_background_style',
            [
                'label'     => esc_html__('Price background', 'edudeme'),
                'type'      => Controls_Manager::COLOR,
                'condition' => [
                    'course_style' => '4',
                ],
                'selectors' => [
                    '{{WRAPPER}} .learn-press-courses[data-layout] li.course .course-content .course-price .course-item-price .price' => 'background: {{VALUE}}',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'course_price_typo',
                'selector' => '{{WRAPPER}} .learn-press-courses[data-layout] li.course .course-content .course-price .course-item-price .price',
            ]
        );


        $this->end_controls_section();

        $this->get_controls_column();
        // Carousel options
        $this->get_control_carousel();
    }

    protected function get_course_categories() {
        $categories = get_terms(array(
                'taxonomy'   => 'course_category',
                'hide_empty' => false,
            )
        );
        $results    = array();
        if (!is_wp_error($categories)) {
            foreach ($categories as $category) {
                $results[$category->term_id] = $category->name;
            }
        }
        return $results;
    }

    /**
     * Render tabs widget output on the frontend.
     *
     * Written in PHP and used to generate the final HTML.
     *
     * @since  1.0.0
     * @access protected
     */
    protected function render() {
        $settings = $this->get_settings_for_display();

        $this->add_render_attribute('wrapper', 'class', ['edudeme-courses-grid']);
        $this->add_render_attribute('inner', 'class', ['learn-press-courses', 'course-style-' . $settings['course_style'], 'lp-list-courses-no-css', 'grid']);
        // Item
        $this->add_render_attribute('item', 'class', 'elementor-course-item');
        $this->get_data_elementor_columns();

        $filter = new LP_Course_Filter();
        $dynamic_content    = $settings['enable_dynamic'];

        $student_post = lxp_get_student_post(get_current_user_id());
        $assignments = lxp_get_student_assignments($student_post->ID);
        $assignment_courses = [];
        if ($dynamic_content ) {
            if (count($assignments) > 0) {
                $assignment_courses = lxp_get_assignments_courses($assignments);
                $filter->post_ids = wp_list_pluck($assignment_courses, 'ID');
            } else {
                $filter->post_ids    = ['1'];
            }
        } else {
            $filter->order_by = $settings['orderby'];
            $filter->sort_by  = $settings['course_type'];
            $filter->limit    = $settings['limit'];
        }

        $total_rows = 0;
        $courses    = \LearnPress\Models\Courses::get_courses($filter, $total_rows);

        $this->add_render_attribute('container', 'data-count', count($courses));

        if (empty($courses)) {
            echo '<p class="learn-press-message success">No assignment(s)</p>';
        } else {
            ?>
            <div <?php $this->print_render_attribute_string('wrapper'); ?>>
                <div <?php $this->print_render_attribute_string('container'); ?>>
                    <ul <?php $this->print_render_attribute_string('inner'); ?> data-layout="grid">
                        <?php
                        foreach ($courses as $courseObj) {
                            $course = learn_press_get_course($courseObj->ID);
                            ?>

                            <?php echo \LearnPress\TemplateHooks\Course\ListCoursesTemplate::render_course($course, $settings); ?>

                            <?php
                        }
                        ?>
                    </ul>
                </div>
                <?php $this->get_swiper_navigation(count($courses)); ?>
            </div>
            <?php
        }
    }

    /**
     * Register column widget controls.
     *
     * Adds different input fields to allow the user to change and customize the widget settings.
     *
     * @since  1.0.0
     * @access protected
     */
    protected function get_controls_column($condition = array()) {
        $column = range(1, 10);
        $column = array_combine($column, $column);
        $this->start_controls_section(
            'section_column_options',
            [
                'label'     => esc_html__('Column Options', 'edudeme'),
                'condition' => $condition,
            ]
        );

        $this->add_responsive_control(
            'column',
            [
                'label'              => esc_html__('Columns', 'edudeme'),
                'type'               => Controls_Manager::SELECT,
                'default'            => 4,
                'options'            => [
                                            '' => esc_html__('Default', 'edudeme'),
                                        ] + $column,
                'frontend_available' => true,
                'render_type'        => 'template',
                'prefix_class'       => 'elementor-grid%s-',
                'selectors'          => [
                    '{{WRAPPER}}'                               => '--e-global-column-to-show: {{VALUE}}',
                    //                    '(widescreen){{WRAPPER}} .grid__item'     => 'width: calc((100% - {{column_spacing_widescreen.SIZE}}{{column_spacing_widescreen.UNIT}}*({{column_widescreen.VALUE}} - 1)) / {{column_widescreen.VALUE}})',
                    '{{WRAPPER}} .elementor-item'               => 'width: calc((100% - {{column_spacing.SIZE}}{{column_spacing.UNIT}}*({{column.VALUE}} - 1)) / {{column.VALUE}});',
                    '(laptop){{WRAPPER}} .elementor-item'       => 'width: calc((100% - {{column_spacing.SIZE}}{{column_spacing.UNIT}}*({{column_laptop.VALUE}} - 1)) / {{column_laptop.VALUE}});',
                    '(tablet_extra){{WRAPPER}} .elementor-item' => 'width: calc((100% - {{column_spacing.SIZE}}{{column_spacing.UNIT}}*({{column_tablet_extra.VALUE}} - 1)) / {{column_tablet_extra.VALUE}});',
                    '(tablet){{WRAPPER}} .elementor-item'       => 'width: calc((100% - {{column_spacing.SIZE}}{{column_spacing.UNIT}}*({{column_tablet.VALUE}} - 1)) / {{column_tablet.VALUE}});',
                    '(mobile_extra){{WRAPPER}} .elementor-item' => 'width: calc((100% - {{column_spacing.SIZE}}{{column_spacing.UNIT}}*({{column_mobile_extra.VALUE}} - 1)) / {{column_mobile_extra.VALUE}});',
                    '(mobile){{WRAPPER}} .elementor-item'       => 'width: calc((100% - {{column_spacing.SIZE}}{{column_spacing.UNIT}}*({{column_mobile.VALUE}} - 1)) / {{column_mobile.VALUE}});',
                ],
            ]
        );
        $this->add_control(
            'column_spacing',
            [
                'label'              => esc_html__('Column Spacing', 'edudeme'),
                'type'               => Controls_Manager::SLIDER,
                'range'              => [
                    'px' => [
                        'max' => 100,
                    ],
                ],
                'default'            => [
                    'size' => 30,
                ],
                'frontend_available' => true,
                'render_type'        => 'none',
                'separator'          => 'after',
                'selectors'          => [
                    '{{WRAPPER}}' => '--grid-column-gap: {{SIZE}}{{UNIT}}; --grid-row-gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register style column widget controls.
     *
     * Adds different input fields to allow the user to change and customize the widget settings.
     *
     * @since  1.0.0
     * @access protected
     */
    protected function get_control_style_column($atts = array()) {
        $selectors = isset($atts['selectors']) ? $atts['selectors'] : '.item-inner';
        $prefix    = isset($atts['name']) ? $atts['name'] : 'item';
        $this->start_controls_section(
            'section_' . $prefix . '_style',
            [
                'label' => ucfirst($prefix),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            $prefix . '_padding',
            [
                'label'      => esc_html__('Padding', 'edudeme'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors'  => [
                    '{{WRAPPER}} ' . $selectors => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
                ],
            ]
        );

        $this->add_responsive_control(
            $prefix . '_margin',
            [
                'label'      => esc_html__('Margin', 'edudeme'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors'  => [
                    '{{WRAPPER}} ' . $selectors => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name'     => $prefix . '_background',
                'selector' => '{{WRAPPER}} ' . $selectors,
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name'      => $prefix . '_border',
                'selector'  => '{{WRAPPER}} ' . $selectors,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            $prefix . '_border_radius',
            [
                'label'      => esc_html__('Border Radius', 'edudeme'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors'  => [
                    '{{WRAPPER}} ' . $selectors => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name'     => $prefix . '_box_shadow',
                'selector' => '{{WRAPPER}} ' . $selectors,
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register Carousel widget controls.
     *
     * Adds different input fields to allow the user to change and customize the widget settings.
     *
     * @since  1.0.0
     * @access protected
     */
    protected function get_control_carousel($condition = array()) {

        $this->get_control_carousel_options($condition);
        $this->get_control_carousel_additional($condition);
        $this->get_control_carousel_style_navigation($condition);
    }

    /**
     * Register Control Carousel options.
     *
     * Adds different input fields to allow the user to change and customize the widget settings.
     *
     * @since  1.0.0
     * @access protected
     */
    protected function get_control_carousel_options($condition) {
        $this->start_controls_section(
            'section_swiperjs_options',
            [
                'label'     => esc_html__('Caroseul Options', 'edudeme'),
                'condition' => $condition,
            ]
        );
        $this->add_control(
            'enable_carousel',
            [
                'label' => esc_html__('Enable Carousel', 'edudeme'),
                'type'  => Controls_Manager::SWITCHER,
            ]
        );

        $this->add_control('center',
            [
                'label'              => esc_html__('Center', 'edudeme'),
                'type'               => Controls_Manager::SWITCHER,
                'frontend_available' => true,
                'condition'          => [
                    'enable_carousel' => 'yes'
                ],
            ]
        );

        $this->add_control(
            'swiper_overflow',
            [
                'label'              => esc_html__('Overflow', 'edudeme'),
                'type'               => Controls_Manager::SELECT,
                'default'            => 'none',
                'options'            => [
                    'none'    => esc_html__('None', 'edudeme'),
                    'visible' => esc_html__('Visible', 'edudeme'),
                    'left'    => esc_html__('Overflow to the left', 'edudeme'),
                    'right'   => esc_html__('Overflow to the right', 'edudeme'),
                ],
                'frontend_available' => true,
                'prefix_class'       => 'overflow-to-',
                'condition'          => [
                    'enable_carousel' => 'yes'
                ],
            ]
        );

        $this->add_control(
            'navigation',
            [
                'label'              => esc_html__('Navigation', 'edudeme'),
                'type'               => Controls_Manager::SELECT,
                'default'            => 'both',
                'options'            => [
                    'both'         => esc_html__('Arrows and Dots', 'edudeme'),
                    'bars'         => esc_html__('Arrows and Progressbars', 'edudeme'),
                    'arrows'       => esc_html__('Arrows', 'edudeme'),
                    'dots'         => esc_html__('Dots', 'edudeme'),
                    'progressbars' => esc_html__('Progressbars', 'edudeme'),
                    'none'         => esc_html__('None', 'edudeme'),
                ],
                'frontend_available' => true,
                'condition'          => [
                    'enable_carousel' => 'yes'
                ],

            ]
        );
        $this->add_control('custom_navigation',
            [
                'label'              => esc_html__('Custom Navigation', 'edudeme'),
                'type'               => Controls_Manager::SWITCHER,
                'frontend_available' => true,
                'conditions'         => [
                    'relation' => 'and',
                    'terms'    => [
                        [
                            'name'     => 'enable_carousel',
                            'operator' => '=',
                            'value'    => 'yes',
                        ],
                        [
                            'relation' => 'or',
                            'terms'    => [
                                [
                                    'name'     => 'navigation',
                                    'operator' => '=',
                                    'value'    => 'both',
                                ],
                                [
                                    'name'     => 'navigation',
                                    'operator' => '=',
                                    'value'    => 'bars',
                                ],
                                [
                                    'name'     => 'navigation',
                                    'operator' => '=',
                                    'value'    => 'arrows',
                                ],
                            ],
                        ]
                    ],

                ],
            ]
        );
        $this->add_control(
            'custom_navigation_previous',
            [
                'label'              => esc_html__('Class Navigation Previous', 'edudeme'),
                'type'               => Controls_Manager::TEXT,
                'frontend_available' => true,
                'conditions'         => [
                    'relation' => 'and',
                    'terms'    => [
                        [
                            'name'     => 'enable_carousel',
                            'operator' => '=',
                            'value'    => 'yes',
                        ],
                        [
                            'name'     => 'custom_navigation',
                            'operator' => '=',
                            'value'    => 'yes',
                        ],
                        [
                            'relation' => 'or',
                            'terms'    => [
                                [
                                    'name'     => 'navigation',
                                    'operator' => '=',
                                    'value'    => 'both',
                                ],
                                [
                                    'name'     => 'navigation',
                                    'operator' => '=',
                                    'value'    => 'bars',
                                ],
                                [
                                    'name'     => 'navigation',
                                    'operator' => '=',
                                    'value'    => 'arrows',
                                ],
                            ],
                        ]
                    ],

                ],
            ]
        );
        $this->add_control(
            'custom_navigation_next',
            [
                'label'              => esc_html__('Class Navigation Next', 'edudeme'),
                'type'               => Controls_Manager::TEXT,
                'frontend_available' => true,
                'conditions'         => [
                    'relation' => 'and',
                    'terms'    => [
                        [
                            'name'     => 'enable_carousel',
                            'operator' => '=',
                            'value'    => 'yes',
                        ],
                        [
                            'name'     => 'custom_navigation',
                            'operator' => '=',
                            'value'    => 'yes',
                        ],
                        [
                            'relation' => 'or',
                            'terms'    => [
                                [
                                    'name'     => 'navigation',
                                    'operator' => '=',
                                    'value'    => 'both',
                                ],
                                [
                                    'name'     => 'navigation',
                                    'operator' => '=',
                                    'value'    => 'bars',
                                ],
                                [
                                    'name'     => 'navigation',
                                    'operator' => '=',
                                    'value'    => 'arrows',
                                ],
                            ],
                        ]
                    ],

                ],
            ]
        );

        $this->add_control(
            'navigation_previous_icon',
            [
                'label'            => esc_html__('Previous Arrow Icon', 'edudeme'),
                'type'             => Controls_Manager::ICONS,
                'fa4compatibility' => 'icon',
                'skin'             => 'inline',
                'label_block'      => false,
                'skin_settings'    => [
                    'inline' => [
                        'none' => [
                            'label' => 'Default',
                            'icon'  => is_rtl() ? 'eicon-angle-right' : 'eicon-angle-left',
                        ],
                        'icon' => [
                            'icon' => 'eicon-star',
                        ],
                    ],
                ],
                'recommended'      => [
                    'fa-regular' => [
                        'arrow-alt-circle-left',
                        'caret-square-left',
                    ],
                    'fa-solid'   => [
                        'angle-double-left',
                        'angle-left',
                        'arrow-alt-circle-left',
                        'arrow-circle-left',
                        'arrow-left',
                        'caret-left',
                        'caret-square-left',
                        'angle-circle-left',
                        'angle-left',
                        'long-arrow-alt-left',
                    ],
                ],
                'conditions'       => [
                    'relation' => 'and',
                    'terms'    => [
                        [
                            'name'     => 'enable_carousel',
                            'operator' => '=',
                            'value'    => 'yes',
                        ],
                        [
                            'relation' => 'or',
                            'terms'    => [
                                [
                                    'name'     => 'navigation',
                                    'operator' => '=',
                                    'value'    => 'both',
                                ],
                                [
                                    'name'     => 'navigation',
                                    'operator' => '=',
                                    'value'    => 'bars',
                                ],
                                [
                                    'name'     => 'navigation',
                                    'operator' => '=',
                                    'value'    => 'arrows',
                                ],
                            ],
                        ]
                    ],

                ],
            ]
        );

        $this->add_control(
            'navigation_next_icon',
            [
                'label'            => esc_html__('Next Arrow Icon', 'edudeme'),
                'type'             => Controls_Manager::ICONS,
                'fa4compatibility' => 'icon',
                'skin'             => 'inline',
                'label_block'      => false,
                'skin_settings'    => [
                    'inline' => [
                        'none' => [
                            'label' => 'Default',
                            'icon'  => is_rtl() ? 'eicon-angle-left' : 'eicon-angle-right',
                        ],
                        'icon' => [
                            'icon' => 'eicon-star',
                        ],
                    ],
                ],
                'recommended'      => [
                    'fa-regular' => [
                        'arrow-alt-circle-right',
                        'caret-square-right',
                    ],
                    'fa-solid'   => [
                        'angle-double-right',
                        'angle-right',
                        'arrow-alt-circle-right',
                        'arrow-circle-right',
                        'arrow-right',
                        'caret-right',
                        'caret-square-right',
                        'angle-circle-right',
                        'angle-right',
                        'long-arrow-alt-right',
                    ],
                ],
                'conditions'       => [
                    'relation' => 'and',
                    'terms'    => [
                        [
                            'name'     => 'enable_carousel',
                            'operator' => '=',
                            'value'    => 'yes',
                        ],
                        [
                            'relation' => 'or',
                            'terms'    => [
                                [
                                    'name'     => 'navigation',
                                    'operator' => '=',
                                    'value'    => 'both',
                                ], [
                                    'name'     => 'navigation',
                                    'operator' => '=',
                                    'value'    => 'bars',
                                ],
                                [
                                    'name'     => 'navigation',
                                    'operator' => '=',
                                    'value'    => 'arrows',
                                ],
                            ],
                        ],
                    ],
                ],
            ]
        );
        $this->end_controls_section();
    }

    /**
     * Register Control Carousel Additional.
     *
     * Adds different input fields to allow the user to change and customize the widget settings.
     *
     * @since  1.0.0
     * @access protected
     */
    protected function get_control_carousel_additional($condition) {


        $this->start_controls_section(
            'section_additional_options',
            [
                'label'     => esc_html__('Additional Options', 'edudeme'),
                'condition' => [
                                   'enable_carousel' => 'yes'
                               ] + $condition,
            ]
        );


        $this->add_control(
            'lazyload',
            [
                'label'              => esc_html__('Lazyload', 'edudeme'),
                'type'               => Controls_Manager::SWITCHER,
                'frontend_available' => true,
            ]
        );

        $this->add_control(
            'autoplay',
            [
                'label'              => esc_html__('Autoplay', 'edudeme'),
                'type'               => Controls_Manager::SELECT,
                'default'            => 'yes',
                'options'            => [
                    'yes' => esc_html__('Yes', 'edudeme'),
                    'no'  => esc_html__('No', 'edudeme'),
                ],
                'frontend_available' => true,
            ]
        );

        $this->add_control(
            'pause_on_hover',
            [
                'label'              => esc_html__('Pause on Hover', 'edudeme'),
                'type'               => Controls_Manager::SELECT,
                'default'            => 'yes',
                'options'            => [
                    'yes' => esc_html__('Yes', 'edudeme'),
                    'no'  => esc_html__('No', 'edudeme'),
                ],
                'condition'          => [
                    'autoplay' => 'yes',
                ],
                'render_type'        => 'none',
                'frontend_available' => true,
            ]
        );

        $this->add_control(
            'pause_on_interaction',
            [
                'label'              => esc_html__('Pause on Interaction', 'edudeme'),
                'type'               => Controls_Manager::SELECT,
                'default'            => 'yes',
                'options'            => [
                    'yes' => esc_html__('Yes', 'edudeme'),
                    'no'  => esc_html__('No', 'edudeme'),
                ],
                'condition'          => [
                    'autoplay' => 'yes',
                ],
                'frontend_available' => true,
            ]
        );

        $this->add_control(
            'autoplay_speed',
            [
                'label'              => esc_html__('Autoplay Speed', 'edudeme'),
                'type'               => Controls_Manager::NUMBER,
                'default'            => 5000,
                'condition'          => [
                    'autoplay' => 'yes',
                ],
                'render_type'        => 'none',
                'frontend_available' => true,
            ]
        );

        // Loop requires a re-render so no 'render_type = none'
        $this->add_control(
            'infinite',
            [
                'label'              => esc_html__('Infinite Loop', 'edudeme'),
                'type'               => Controls_Manager::SELECT,
                'default'            => 'yes',
                'options'            => [
                    'yes' => esc_html__('Yes', 'edudeme'),
                    'no'  => esc_html__('No', 'edudeme'),
                ],
                'frontend_available' => true,
            ]
        );

        $this->add_control(
            'effect',
            [
                'label'              => esc_html__('Effect', 'edudeme'),
                'type'               => Controls_Manager::SELECT,
                'default'            => 'slide',
                'options'            => [
                    'slide' => esc_html__('Slide', 'edudeme'),
                    'fade'  => esc_html__('Fade', 'edudeme'),
                ],
                'condition'          => [
                    'slides_to_show' => '1',
                ],
                'frontend_available' => true,
            ]
        );

        $this->add_control(
            'speed',
            [
                'label'              => esc_html__('Animation Speed', 'edudeme'),
                'type'               => Controls_Manager::NUMBER,
                'default'            => 500,
                'render_type'        => 'none',
                'frontend_available' => true,
            ]
        );
        $this->add_control(
            'direction',
            [
                'label'              => esc_html__('Direction', 'edudeme'),
                'type'               => Controls_Manager::SELECT,
                'default'            => 'horizontal',
                'frontend_available' => true,
                'options'            => [
                    'horizontal' => esc_html__('Horizontal', 'edudeme'),
                    'vertical'   => esc_html__('Vertical', 'edudeme'),
                ],
            ]
        );
        $this->add_control(
            'rtl',
            [
                'label'   => esc_html__('Direction Right/Left', 'edudeme'),
                'type'    => Controls_Manager::SELECT,
                'default' => 'ltr',
                'options' => [
                    'ltr' => esc_html__('Left', 'edudeme'),
                    'rtl' => esc_html__('Right', 'edudeme'),
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register Control Carousel Style Navigation.
     *
     * Adds different input fields to allow the user to change and customize the widget settings.
     *
     * @since  1.0.0
     * @access protected
     */

    protected function get_control_carousel_style_navigation($condition) {
        $this->start_controls_section(
            'section_style_navigation',
            [
                'label'     => esc_html__('Navigation', 'edudeme'),
                'tab'       => Controls_Manager::TAB_STYLE,
                'condition' => [
                                   'navigation'      => ['arrows', 'dots', 'both', 'bars'],
                                   'enable_carousel' => 'yes',
                               ] + $condition,
            ]
        );

        $this->add_control(
            'heading_style_arrows',
            [
                'label'     => esc_html__('Arrows', 'edudeme'),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
                'condition' => [
                    'navigation' => ['arrows', 'both', 'bars'],
                ],
            ]
        );

        $this->add_responsive_control(
            'arrows_size',
            [
                'label'     => esc_html__('Size', 'edudeme'),
                'type'      => Controls_Manager::SLIDER,
                'range'     => [
                    'px' => [
                        'min' => 10,
                        'max' => 100,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .elementor-swiper-button.elementor-swiper-button-prev i, 
                    {{WRAPPER}} .elementor-swiper-button.elementor-swiper-button-next i' => 'font-size: {{SIZE}}{{UNIT}};',
                ],
                'condition' => [
                    'navigation' => ['arrows', 'both', 'bars'],
                ],
            ]
        );

        $this->add_responsive_control(
            'arrows_width',
            [
                'label'      => esc_html__('Width', 'edudeme'),
                'type'       => Controls_Manager::SLIDER,
                'default'    => [
                    'unit' => 'px',
                ],
                'size_units' => ['%', 'px', 'vw'],
                'range'      => [
                    '%'  => [
                        'min' => 1,
                        'max' => 100,
                    ],
                    'px' => [
                        'min' => 1,
                        'max' => 200,
                    ],
                    'vw' => [
                        'min' => 1,
                        'max' => 100,
                    ],
                ],
                'selectors'  => [
                    '{{WRAPPER}} .elementor-swiper-button.elementor-swiper-button-prev, {{WRAPPER}} .elementor-swiper-button.elementor-swiper-button-next' => 'width: {{SIZE}}{{UNIT}};',
                ],
                'condition'  => [
                    'navigation' => ['arrows', 'both', 'bars'],
                ],
            ]
        );

        $this->add_responsive_control(
            'arrows_height',
            [
                'label'      => esc_html__('Height', 'edudeme'),
                'type'       => Controls_Manager::SLIDER,
                'default'    => [
                    'unit' => 'px',
                ],
                'size_units' => ['%', 'px', 'vw'],
                'range'      => [
                    '%'  => [
                        'min' => 1,
                        'max' => 100,
                    ],
                    'px' => [
                        'min' => 1,
                        'max' => 200,
                    ],
                    'vw' => [
                        'min' => 1,
                        'max' => 100,
                    ],
                ],
                'selectors'  => [
                    '{{WRAPPER}} .elementor-swiper-button.elementor-swiper-button-prev, {{WRAPPER}} .elementor-swiper-button.elementor-swiper-button-next' => 'height: {{SIZE}}{{UNIT}};',
                ],
                'condition'  => [
                    'navigation' => ['arrows', 'both', 'bars'],
                ],
            ]
        );

        $this->add_group_control(

            Group_Control_Border::get_type(),
            [
                'name'      => 'arrows_border',
                'selector'  => '{{WRAPPER}} .elementor-swiper-button.elementor-swiper-button-prev, {{WRAPPER}} .elementor-swiper-button.elementor-swiper-button-next',
                'separator' => 'before',
                'condition' => [
                    'navigation' => ['arrows', 'both', 'bars'],
                ],
            ]
        );

        $this->add_control(
            'arrows_radius',
            [
                'label'      => esc_html__('Border Radius', 'edudeme'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors'  => [
                    '{{WRAPPER}} .elementor-swiper-button.elementor-swiper-button-prev, {{WRAPPER}} .elementor-swiper-button.elementor-swiper-button-next' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
                'condition'  => [
                    'navigation' => ['arrows', 'both', 'bars'],
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name'      => 'arrows_box_shadow',
                'condition' => [
                    'navigation' => ['arrows', 'both', 'bars'],
                ],
                'selector'  => '{{WRAPPER}} .elementor-swiper-button.elementor-swiper-button-prev, {{WRAPPER}} .elementor-swiper-button.elementor-swiper-button-next',
            ]
        );

        $this->start_controls_tabs('arrows_tabs');

        $this->start_controls_tab('arrows_normal',
            [
                'label'     => esc_html__('Normal', 'edudeme'),
                'condition' => [
                    'navigation' => ['arrows', 'both', 'bars'],
                ],
            ]
        );

        $this->add_control(
            'arrows_color',
            [
                'label'     => esc_html__('Color', 'edudeme'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .elementor-swiper-button.elementor-swiper-button-prev, {{WRAPPER}} .elementor-swiper-button.elementor-swiper-button-next'         => 'color: {{VALUE}};',
                    '{{WRAPPER}} .elementor-swiper-button.elementor-swiper-button-prev svg, {{WRAPPER}} .elementor-swiper-button.elementor-swiper-button-next svg' => 'fill: {{VALUE}};',
                ],
                'condition' => [
                    'navigation' => ['arrows', 'both', 'bars'],
                ],
            ]
        );

        $this->add_control(
            'arrows_background_color',
            [
                'label'     => esc_html__('Background Color', 'edudeme'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .elementor-swiper-button.elementor-swiper-button-prev, {{WRAPPER}} .elementor-swiper-button.elementor-swiper-button-next' => 'background-color: {{VALUE}};',
                ],
                'condition' => [
                    'navigation' => ['arrows', 'both', 'bars'],
                ],
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab('arrows_hover',
            [
                'label'     => esc_html__('Hover', 'edudeme'),
                'condition' => [
                    'navigation' => ['arrows', 'both', 'bars'],
                ],
            ]
        );

        $this->add_control(
            'arrows_color_hover',
            [
                'label'     => esc_html__('Color', 'edudeme'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .elementor-swiper-button.elementor-swiper-button-prev:hover, {{WRAPPER}} .elementor-swiper-button.elementor-swiper-button-next:hover'         => 'color: {{VALUE}};',
                    '{{WRAPPER}} .elementor-swiper-button.elementor-swiper-button-prev:hover svg, {{WRAPPER}} .elementor-swiper-button.elementor-swiper-button-next:hover svg' => 'fill: {{VALUE}};',
                ],
                'condition' => [
                    'navigation' => ['arrows', 'both', 'bars'],
                ],
            ]
        );

        $this->add_control(
            'arrows_background_color_hover',
            [
                'label'     => esc_html__('Background Color', 'edudeme'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .elementor-swiper-button.elementor-swiper-button-prev:hover, {{WRAPPER}} .elementor-swiper-button.elementor-swiper-button-next:hover' => 'background-color: {{VALUE}};',
                ],
                'condition' => [
                    'navigation' => ['arrows', 'both', 'bars'],
                ],
            ]
        );

        $this->add_control(
            'arrows_border_color_hover',
            [
                'label'     => esc_html__('Border Color', 'edudeme'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .elementor-swiper-button.elementor-swiper-button-prev:hover, {{WRAPPER}} .elementor-swiper-button.elementor-swiper-button-next:hover' => 'border-color: {{VALUE}};',
                ],
                'condition' => [
                    'navigation' => ['arrows', 'both', 'bars'],
                ],
            ]
        );
        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_control(
            'arrows_next_heading',
            [
                'label'     => esc_html__('Next button', 'edudeme'),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
                'condition' => [
                    'navigation' => ['arrows', 'both', 'bars'],
                ],
            ]
        );

        $this->add_control(
            'arrows_next_vertical',
            [
                'label'        => esc_html__('Next Vertical', 'edudeme'),
                'type'         => Controls_Manager::CHOOSE,
                'label_block'  => false,
                'options'      => [
                    'top'    => [
                        'title' => esc_html__('Top', 'edudeme'),
                        'icon'  => 'eicon-v-align-top',
                    ],
                    'bottom' => [
                        'title' => esc_html__('Bottom', 'edudeme'),
                        'icon'  => 'eicon-v-align-bottom',
                    ],
                ],
                'prefix_class' => 'elementor-swiper-button-next-vertical-',
                'condition'    => [
                    'navigation' => ['arrows', 'both', 'bars'],
                ],
            ]
        );

        $this->add_responsive_control(
            'arrows_next_vertical_value',
            [
                'type'       => Controls_Manager::SLIDER,
                'show_label' => false,
                'size_units' => ['px', '%'],
                'range'      => [
                    'px' => [
                        'min'  => -1000,
                        'max'  => 1000,
                        'step' => 1,
                    ],
                    '%'  => [
                        'min' => -100,
                        'max' => 100,
                    ],
                ],
                'default'    => [
                    'unit' => '%',
                    'size' => 50,
                ],
                'selectors'  => [
                    '{{WRAPPER}} .elementor-swiper-button.elementor-swiper-button-next' => 'top: unset; bottom: unset; {{arrows_next_vertical.value}}: {{SIZE}}{{UNIT}};',
                ],
                'condition'  => [
                    'navigation'           => ['arrows', 'both', 'bars'],
                    'arrows_next_vertical' => ['top', 'bottom'],
                ],
            ]
        );

        $this->add_control(
            'arrows_next_horizontal',
            [
                'label'        => esc_html__('Next Horizontal', 'edudeme'),
                'type'         => Controls_Manager::CHOOSE,
                'label_block'  => false,
                'options'      => [
                    'left'  => [
                        'title' => esc_html__('Left', 'edudeme'),
                        'icon'  => 'eicon-h-align-left',
                    ],
                    'right' => [
                        'title' => esc_html__('Right', 'edudeme'),
                        'icon'  => 'eicon-h-align-right',
                    ],
                ],
                'prefix_class' => 'elementor-swiper-button-next-horizontal-',
                'condition'    => [
                    'navigation' => ['arrows', 'both', 'bars'],
                ],
            ]
        );
        $this->add_responsive_control(
            'next_horizontal_value',
            [
                'type'       => Controls_Manager::SLIDER,
                'show_label' => false,
                'size_units' => ['px', 'em', '%'],
                'range'      => [
                    'px' => [
                        'min'  => -1000,
                        'max'  => 1000,
                        'step' => 1,
                    ],
                    '%'  => [
                        'min' => -100,
                        'max' => 100,
                    ],
                ],
                'default'    => [
                    'unit' => 'px',
                    'size' => -45,
                ],
                'selectors'  => [
                    '{{WRAPPER}} .elementor-swiper-button.elementor-swiper-button-next' => 'left: unset; right: unset;{{arrows_next_horizontal.value}}: {{SIZE}}{{UNIT}};',
                ],
                'condition'  => [
                    'navigation'             => ['arrows', 'both', 'bars'],
                    'arrows_next_horizontal' => ['left', 'right'],
                ],
            ]
        );

        $this->add_control(
            'arrows_prev_heading',
            [
                'label'     => esc_html__('Prev button', 'edudeme'),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
                'condition' => [
                    'navigation' => ['arrows', 'both', 'bars'],
                ],
            ]
        );

        $this->add_control(
            'arrows_prev_vertical',
            [
                'label'        => esc_html__('Prev Vertical', 'edudeme'),
                'type'         => Controls_Manager::CHOOSE,
                'label_block'  => false,
                'render_type'  => 'ui',
                'options'      => [
                    'top'    => [
                        'title' => esc_html__('Top', 'edudeme'),
                        'icon'  => 'eicon-v-align-top',
                    ],
                    'bottom' => [
                        'title' => esc_html__('Bottom', 'edudeme'),
                        'icon'  => 'eicon-v-align-bottom',
                    ],
                ],
                'prefix_class' => 'elementor-swiper-button-prev-vertical-',
                'condition'    => [
                    'navigation' => ['arrows', 'both', 'bars'],
                ],
            ]
        );

        $this->add_responsive_control(
            'arrows_prev_vertical_value',
            [
                'type'       => Controls_Manager::SLIDER,
                'show_label' => false,
                'size_units' => ['px', '%'],
                'range'      => [
                    'px' => [
                        'min'  => -1000,
                        'max'  => 1000,
                        'step' => 1,
                    ],
                    '%'  => [
                        'min' => -100,
                        'max' => 100,
                    ],
                ],
                'default'    => [
                    'unit' => '%',
                    'size' => 50,
                ],
                'selectors'  => [
                    '{{WRAPPER}} .elementor-swiper-button.elementor-swiper-button-prev' => 'top: unset; bottom: unset; {{arrows_prev_vertical.value}}: {{SIZE}}{{UNIT}};',
                ],

                'condition' => [
                    'navigation'           => ['arrows', 'both', 'bars'],
                    'arrows_prev_vertical' => ['top', 'bottom'],
                ],
            ]
        );

        $this->add_control(
            'arrows_prev_horizontal',
            [
                'label'        => esc_html__('Prev Horizontal', 'edudeme'),
                'type'         => Controls_Manager::CHOOSE,
                'label_block'  => false,
                'options'      => [
                    'left'  => [
                        'title' => esc_html__('Left', 'edudeme'),
                        'icon'  => 'eicon-h-align-left',
                    ],
                    'right' => [
                        'title' => esc_html__('Right', 'edudeme'),
                        'icon'  => 'eicon-h-align-right',
                    ],
                ],
                'prefix_class' => 'elementor-swiper-button-prev-horizontal-',
                'condition'    => [
                    'navigation' => ['arrows', 'both', 'bars'],
                ],
            ]
        );
        $this->add_responsive_control(
            'arrows_prev_horizontal_value',
            [
                'type'       => Controls_Manager::SLIDER,
                'show_label' => false,
                'size_units' => ['px', 'em', '%'],
                'range'      => [
                    'px' => [
                        'min'  => -1000,
                        'max'  => 1000,
                        'step' => 1,
                    ],
                    '%'  => [
                        'min' => -100,
                        'max' => 100,
                    ],
                ],
                'default'    => [
                    'unit' => 'px',
                    'size' => 0,
                ],
                'selectors'  => [
                    '{{WRAPPER}} .elementor-swiper-button.elementor-swiper-button-prev' => 'left: unset; right: unset; {{arrows_prev_horizontal.value}}: {{SIZE}}{{UNIT}};',
                ],

                'condition' => [
                    'navigation'             => ['arrows', 'both', 'bars'],
                    'arrows_prev_horizontal' => ['left', 'right'],
                ],
            ]
        );

        $this->add_control(
            'heading_style_dots',
            [
                'label'     => esc_html__('Pagination', 'edudeme'),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
                'condition' => [
                    'navigation' => ['dots', 'both', 'bars', 'progressbars'],
                ],
            ]
        );

        $this->add_control(
            'dots_position',
            [
                'label'        => esc_html__('Position', 'edudeme'),
                'type'         => Controls_Manager::SELECT,
                'default'      => 'outside',
                'options'      => [
                    'outside' => esc_html__('Outside', 'edudeme'),
                    'inside'  => esc_html__('Inside', 'edudeme'),
                ],
                'prefix_class' => 'elementor-pagination-position-',
                'condition'    => [
                    'navigation' => ['dots', 'both'],
                ],
            ]
        );

        $this->add_control(
            'dots_size',
            [
                'label'     => esc_html__('Size', 'edudeme'),
                'type'      => Controls_Manager::SLIDER,
                'range'     => [
                    'px' => [
                        'min' => 10,
                        'max' => 20,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .swiper-pagination-bullet' => '--size-pagination-bullet: {{SIZE}}{{UNIT}};',
                ],
                'condition' => [
                    'navigation' => ['dots', 'both'],
                ],
            ]
        );

        $this->end_controls_section();
        $this->start_controls_section(
            'carousel_dots',
            [
                'label'      => esc_html__('Carousel Dots & Progressbar', 'edudeme'),
                'conditions' => [
                    'relation' => 'and',
                    'terms'    => [
                        [
                            'name'     => 'enable_carousel',
                            'operator' => '==',
                            'value'    => 'yes',
                        ],
                        [
                            'name'     => 'navigation',
                            'operator' => '!==',
                            'value'    => 'none',
                        ],
                        [
                            'name'     => 'navigation',
                            'operator' => '!==',
                            'value'    => 'arrows',
                        ],
                    ],
                ],
            ]
        );


        $this->add_control(
            'style_dot',
            [
                'label'        => esc_html__('Style Dot', 'edudeme'),
                'type'         => Controls_Manager::SELECT,
                'options'      => [
                    'style-1' => esc_html__('Style 1', 'edudeme'),
                    'style-2' => esc_html__('Style 2', 'edudeme'),
                ],
                'default'      => 'style-1',
                'prefix_class' => 'elementor-pagination-',
                'condition'    => [
                    'navigation' => ['dots', 'both'],
                ],
            ]
        );

        $this->start_controls_tabs('tabs_carousel_dots_style');

        $this->start_controls_tab(
            'tab_carousel_dots_normal',
            [
                'label' => esc_html__('Normal', 'edudeme'),
            ]
        );

        $this->add_control(
            'carousel_dots_color',
            [
                'label'     => esc_html__('Color', 'edudeme'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '',
                'selectors' => [
                    '{{WRAPPER}} .swiper-pagination-bullet'      => 'background-color: {{VALUE}}!important; color: {{VALUE}}!important;',
                    '{{WRAPPER}} .swiper-pagination-progressbar' => 'background-color: {{VALUE}}!important; color: {{VALUE}}!important;',
                ],
            ]
        );

        $this->add_control(
            'carousel_dots_opacity',
            [
                'label'     => esc_html__('Opacity', 'edudeme'),
                'type'      => Controls_Manager::SLIDER,
                'range'     => [
                    'px' => [
                        'max'  => 1,
                        'min'  => 0.10,
                        'step' => 0.01,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .swiper-pagination-bullet'      => 'opacity: {{SIZE}};',
                    '{{WRAPPER}} .swiper-pagination-progressbar' => 'opacity: {{SIZE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'tab_carousel_dots_hover',
            [
                'label' => esc_html__('Hover', 'edudeme'),
            ]
        );

        $this->add_control(
            'carousel_dots_color_hover',
            [
                'label'     => esc_html__('Color Hover', 'edudeme'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '',
                'selectors' => [
                    '{{WRAPPER}} .swiper-pagination-bullet:hover'      => 'background-color: {{VALUE}} !important; color: {{VALUE}} !important;',
                    '{{WRAPPER}} .swiper-pagination-bullet:focus'      => 'background-color: {{VALUE}} !important; color: {{VALUE}} !important;',
                    '{{WRAPPER}} .swiper-pagination-progressbar:hover' => 'background-color: {{VALUE}} !important; color: {{VALUE}} !important;',
                    '{{WRAPPER}} .swiper-pagination-progressbar:focus' => 'background-color: {{VALUE}} !important; color: {{VALUE}} !important;',
                ],
            ]
        );

        $this->add_control(
            'carousel_dots_opacity_hover',
            [
                'label'     => esc_html__('Opacity', 'edudeme'),
                'type'      => Controls_Manager::SLIDER,
                'range'     => [
                    'px' => [
                        'max'  => 1,
                        'min'  => 0.10,
                        'step' => 0.01,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .swiper-pagination-bullet:hover'      => 'opacity: {{SIZE}};',
                    '{{WRAPPER}} .swiper-pagination-bullet:focus'      => 'opacity: {{SIZE}};',
                    '{{WRAPPER}} .swiper-pagination-progressbar:hover' => 'opacity: {{SIZE}};',
                    '{{WRAPPER}} .swiper-pagination-progressbar:focus' => 'opacity: {{SIZE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'tab_carousel_dots_activate',
            [
                'label' => esc_html__('Activate', 'edudeme'),
            ]
        );

        $this->add_control(
            'carousel_dots_color_activate',
            [
                'label'     => esc_html__('Color', 'edudeme'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '',
                'selectors' => [
                    '{{WRAPPER}} .swiper-pagination-bullet-active'    => 'background-color: {{VALUE}} !important; color: {{VALUE}} !important;',
                    '{{WRAPPER}} .swiper-pagination-progressbar-fill' => 'background-color: {{VALUE}} !important; color: {{VALUE}} !important;',
                ],
            ]
        );

        $this->add_control(
            'carousel_dots_opacity_activate',
            [
                'label'     => esc_html__('Opacity', 'edudeme'),
                'type'      => Controls_Manager::SLIDER,
                'range'     => [
                    'px' => [
                        'max'  => 1,
                        'min'  => 0.10,
                        'step' => 0.01,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .swiper-pagination-bullet'      => 'opacity: {{SIZE}};',
                    '{{WRAPPER}} .swiper-pagination-progressbar' => 'opacity: {{SIZE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_responsive_control(
            'dots_vertical_value',
            [
                'label'      => esc_html__('Spacing', 'edudeme'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range'      => [
                    'px' => [
                        'min'  => 0,
                        'max'  => 100,
                        'step' => 1,
                    ],
                    '%'  => [
                        'min' => 0,
                        'max' => 100,
                    ],
                ],
                'default'    => [
                    'unit' => '%',
                    'size' => '',
                ],
                'selectors'  => [
                    '{{WRAPPER}}.elementor-pagination-position-outside .swiper-pagination'             => 'bottom: -{{SIZE}}{{UNIT}};',
                    '{{WRAPPER}}.elementor-pagination-position-inside .swiper-pagination'              => 'bottom: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}}.elementor-pagination-position-outside .swiper-pagination-progressbar' => 'bottom: -{{SIZE}}{{UNIT}};',
                    '{{WRAPPER}}.elementor-pagination-position-inside .swiper-pagination-progressbar'  => 'bottom: {{SIZE}}{{UNIT}};',
                ]
            ]
        );

        $this->add_responsive_control(
            'progressbar_height',
            [
                'label'      => esc_html__('Height', 'edudeme'),
                'type'       => Controls_Manager::SLIDER,
                'default'    => [
                    'unit' => 'px',
                ],
                'size_units' => ['%', 'px', 'vw'],
                'range'      => [
                    '%'  => [
                        'min' => 1,
                        'max' => 100,
                    ],
                    'px' => [
                        'min' => 1,
                        'max' => 200,
                    ],
                    'vw' => [
                        'min' => 1,
                        'max' => 100,
                    ],
                ],
                'selectors'  => [
                    '{{WRAPPER}} .swiper-pagination-progressbar.swiper-pagination-horizontal' => 'height: {{SIZE}}{{UNIT}};',
                ],
                'condition'  => [
                    'navigation' => ['progressbars', 'bars'],
                ],
            ]
        );

        $this->add_responsive_control(
            'Alignment_text',
            [
                'label'     => esc_html__('Alignment text', 'edudeme'),
                'type'      => Controls_Manager::CHOOSE,
                'options'   => [
                    'left'   => [
                        'title' => esc_html__('Left', 'edudeme'),
                        'icon'  => 'eicon-text-align-left',
                    ],
                    'center' => [
                        'title' => esc_html__('Center', 'edudeme'),
                        'icon'  => 'eicon-text-align-center',
                    ],
                    'right'  => [
                        'title' => esc_html__('Right', 'edudeme'),
                        'icon'  => 'eicon-text-align-right',
                    ],
                ],
                'default'   => 'center',
                'selectors' => [
                    '{{WRAPPER}} .swiper-pagination' => 'text-align: {{VALUE}};',
                ],
                'condition' => [
                    'navigation' => ['both', 'dots'],
                    'style_dot'  => ['style-1', 'style-2']
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function get_swiper_navigation($slides_count = 0) {
        $settings = $this->get_settings_for_display();
        if ($settings['enable_carousel'] != 'yes') {
            return;
        }
        $show_dots         = (in_array($settings['navigation'], ['dots', 'both']));
        $show_arrows       = (in_array($settings['navigation'], ['arrows', 'both', 'bars']));
        $show_progressbars = (in_array($settings['navigation'], ['progressbars', 'bars']));
        ?>
        <?php if (isset($slides_count) && $slides_count > 1) : ?>
            <?php if ($show_dots) : ?>
                <div class="swiper-pagination swiper-pagination-<?php echo esc_attr($this->get_id()) ?>"></div>
            <?php endif; ?>

            <?php if ($show_progressbars) : ?>
                <div class="swiper-pagination swiper-pagination-<?php echo esc_attr($this->get_id()) ?>"></div>
            <?php endif; ?>
            <?php if ($show_arrows && $settings['custom_navigation'] != 'yes') : ?>
                <div class="elementor-swiper-button elementor-swiper-button-prev elementor-swiper-button-prev-<?php echo esc_attr($this->get_id()) ?>" role="button" tabindex="0">
                    <?php $this->render_swiper_button('previous'); ?>
                    <span class="elementor-screen-only"><?php echo esc_html__('Previous', 'edudeme'); ?></span>
                </div>
                <div class="elementor-swiper-button elementor-swiper-button-next elementor-swiper-button-next-<?php echo esc_attr($this->get_id()) ?>" role="button" tabindex="0">
                    <?php $this->render_swiper_button('next'); ?>
                    <span class="elementor-screen-only"><?php echo esc_html__('Next', 'edudeme'); ?></span>
                </div>
            <?php endif; ?>
        <?php endif; ?>
        <?php
    }

    protected function render_swiper_button($type, $html = false) {
        $direction     = 'next' === $type ? 'right' : 'left';
        $icon_settings = $this->get_settings_for_display('navigation_' . $type . '_icon');

        if (empty($icon_settings['value'])) {
            $icon_settings = [
                'library' => 'eicons',
                'value'   => 'eicon-angle-' . $direction,
            ];
        }

        if ($html === true) {
            return Icons_Manager::try_get_icon_html($icon_settings, ['aria-hidden' => 'true']);
        }
        Icons_Manager::render_icon($icon_settings, ['aria-hidden' => 'true']);
    }

    protected function get_swiper_navigation_for_product() {
        $settings = $this->get_settings_for_display();
        if ($settings['enable_carousel'] != 'yes') {
            return;
        }
        $settings_navigation = '';
        $show_dots           = (in_array($settings['navigation'], ['dots', 'both']));
        $show_arrows         = (in_array($settings['navigation'], ['arrows', 'both', 'bars']));
        $show_progressbars   = (in_array($settings['navigation'], ['progressbars', 'bars']));


        if ($show_dots) {
            $settings_navigation .= '<div class="swiper-pagination swiper-pagination-' . $this->get_id() . '"></div>';
        }
        if ($show_progressbars) {
            $settings_navigation .= '<div class="swiper-pagination swiper-pagination-' . $this->get_id() . '"></div>';
        }
        if ($show_arrows && $settings['custom_navigation'] != 'yes') {
            $settings_navigation .= '<div class="elementor-swiper-button elementor-swiper-button-prev elementor-swiper-button-prev-' . $this->get_id() . '" role="button" tabindex="0">';
            $settings_navigation .= $this->render_swiper_button('previous', true);
            $settings_navigation .= '<span class="elementor-screen-only">' . esc_html__('Previous', 'edudeme') . '</span>';
            $settings_navigation .= '</div>';
            $settings_navigation .= '<div class="elementor-swiper-button elementor-swiper-button-next elementor-swiper-button-next-' . $this->get_id() . '" role="button" tabindex="0">';
            $settings_navigation .= $this->render_swiper_button('next', true);
            $settings_navigation .= '<span class="elementor-screen-only">' . esc_html__('Next', 'edudeme') . '</span>';
            $settings_navigation .= '</div>';
        }
        return $settings_navigation;
    }

    /**
     * Get data elementor columns
     *
     * Adds different input fields to allow the user to change and customize the widget settings.
     *
     * @since  1.0.0
     * @access protected
     */
    protected function get_data_elementor_columns() {

        $settings = $this->get_settings_for_display();
        //item
        $this->add_render_attribute('wrapper', 'class', 'edudeme-wrapper');
        $this->add_render_attribute('container', 'class', 'edudeme-con');
        $this->add_render_attribute('inner', 'class', 'edudeme-con-inner');
        $class = $settings['column'] == 'auto' ? 'swiper-autowidth' : '';
        if ($settings['enable_carousel'] === 'yes') {
            $swiper_class = 'swiper';

            $has_autoplay_enabled = 'yes' === $this->get_settings_for_display('autoplay');
            $this->add_render_attribute('wrapper', 'class', 'edudeme-swiper-wrapper');
            $this->add_render_attribute('container', [
                'class'       => [$swiper_class, 'edudeme-swiper'],
                'data-center' => $settings['center'] ? 'true' : 'false',
            ]);

            $this->add_render_attribute('inner', [
                'class'     => 'swiper-wrapper',
                'aria-live' => $has_autoplay_enabled ? 'off' : 'polite',
            ]);

            $this->add_render_attribute('item', 'class', 'swiper-slide');
            $this->add_render_attribute('item', 'class', 'elementor-item');

        } else {
            $this->add_render_attribute('inner', 'class', 'elementor-grid');
        }
    }


    protected function get_control_pagination() {
        $this->start_controls_section(
            'section_pagination',
            [
                'label' => esc_html__('Pagination', 'edudeme'),
            ]
        );

        $this->add_control(
            'pagination_type',
            [
                'label'   => esc_html__('Pagination', 'edudeme'),
                'type'    => Controls_Manager::SELECT,
                'default' => '',
                'options' => [
                    ''                      => esc_html__('None', 'edudeme'),
                    'numbers'               => esc_html__('Numbers', 'edudeme'),
                    'prev_next'             => esc_html__('Previous/Next', 'edudeme'),
                    'numbers_and_prev_next' => esc_html__('Numbers', 'edudeme') . ' + ' . esc_html__('Previous/Next', 'edudeme'),
                ],
            ]
        );

        $this->add_control(
            'pagination_page_limit',
            [
                'label'     => esc_html__('Page Limit', 'edudeme'),
                'default'   => '5',
                'condition' => [
                    'pagination_type!' => '',
                ],
            ]
        );

        $this->add_control(
            'pagination_numbers_shorten',
            [
                'label'     => esc_html__('Shorten', 'edudeme'),
                'type'      => Controls_Manager::SWITCHER,
                'default'   => '',
                'condition' => [
                    'pagination_type' => [
                        'numbers',
                        'numbers_and_prev_next',
                    ],
                ],
            ]
        );

        $this->add_control(
            'pagination_prev_label',
            [
                'label'     => esc_html__('Previous Label', 'edudeme'),
                'default'   => esc_html__('&laquo; Previous', 'edudeme'),
                'condition' => [
                    'pagination_type' => [
                        'prev_next',
                        'numbers_and_prev_next',
                    ],
                ],
            ]
        );

        $this->add_control(
            'pagination_next_label',
            [
                'label'     => esc_html__('Next Label', 'edudeme'),
                'default'   => esc_html__('Next &raquo;', 'edudeme'),
                'condition' => [
                    'pagination_type' => [
                        'prev_next',
                        'numbers_and_prev_next',
                    ],
                ],
            ]
        );

        $this->add_control(
            'pagination_align',
            [
                'label'     => esc_html__('Alignment', 'edudeme'),
                'type'      => Controls_Manager::CHOOSE,
                'options'   => [
                    'left'   => [
                        'title' => esc_html__('Left', 'edudeme'),
                        'icon'  => 'eicon-text-align-left',
                    ],
                    'center' => [
                        'title' => esc_html__('Center', 'edudeme'),
                        'icon'  => 'eicon-text-align-center',
                    ],
                    'right'  => [
                        'title' => esc_html__('Right', 'edudeme'),
                        'icon'  => 'eicon-text-align-right',
                    ],
                ],
                'default'   => 'center',
                'selectors' => [
                    '{{WRAPPER}} .elementor-pagination' => 'text-align: {{VALUE}}; justify-content: {{VALUE}}',
                ],
                'condition' => [
                    'pagination_type!' => '',
                ],
            ]
        );
        $this->end_controls_section();
    }

    public function get_current_page() {
        if ('' === $this->get_settings('pagination_type')) {
            return 1;
        }

        return max(1, get_query_var('paged'), get_query_var('page'));
    }

    public function get_posts_nav_link($page_limit = null) {
        if (!$page_limit) {
            $page_limit = $this->query_posts()->max_num_pages;
        }

        $return = [];

        $paged = $this->get_current_page();

        $link_template     = '<a class="page-numbers %s" href="%s">%s</a>';
        $disabled_template = '<span class="page-numbers %s">%s</span>';

        if ($paged > 1) {
            $next_page = intval($paged) - 1;
            if ($next_page < 1) {
                $next_page = 1;
            }

            $return['prev'] = sprintf($link_template, 'prev', get_pagenum_link($next_page), $this->get_settings('pagination_prev_label'));
        } else {
            $return['prev'] = sprintf($disabled_template, 'prev', $this->get_settings('pagination_prev_label'));
        }

        $next_page = intval($paged) + 1;

        if ($next_page <= $page_limit) {
            $return['next'] = sprintf($link_template, 'next', get_pagenum_link($next_page), $this->get_settings('pagination_next_label'));
        } else {
            $return['next'] = sprintf($disabled_template, 'next', $this->get_settings('pagination_next_label'));
        }

        return $return;
    }

    protected function render_loop_footer() {
        $settings = $this->get_settings_for_display();
        if (!$settings['pagination_type'] || empty($settings['pagination_type'])) {
            return;
        }
        $parent_settings = $this->get_settings();
        if ('' === $parent_settings['pagination_type']) {
            return;
        }

        $page_limit = $this->query_posts()->max_num_pages;
        if ('' !== $parent_settings['pagination_page_limit']) {
            $page_limit = min($parent_settings['pagination_page_limit'], $page_limit);
        }

        if (2 > $page_limit) {
            return;
        }

        $this->add_render_attribute('pagination', 'class', 'elementor-pagination');

        $has_numbers   = in_array($parent_settings['pagination_type'], ['numbers', 'numbers_and_prev_next']);
        $has_prev_next = in_array($parent_settings['pagination_type'], ['prev_next', 'numbers_and_prev_next']);

        $links = [];

        if ($has_numbers) {
            $links = paginate_links([
                'type'               => 'array',
                'current'            => $this->get_current_page(),
                'total'              => $page_limit,
                'prev_next'          => false,
                'show_all'           => 'yes' !== $parent_settings['pagination_numbers_shorten'],
                'before_page_number' => '<span class="elementor-screen-only">' . esc_html__('Page', 'edudeme') . '</span>',
            ]);
        }

        if ($has_prev_next) {
            $prev_next = $this->get_posts_nav_link($page_limit);
            array_unshift($links, $prev_next['prev']);
            $links[] = $prev_next['next'];
        }

        ?>
        <div class="pagination">
            <nav class="elementor-pagination" aria-label="<?php esc_attr_e('Pagination', 'edudeme'); ?>">
                <?php echo implode(PHP_EOL, $links); ?>
            </nav>
        </div>
        <?php
    }

}