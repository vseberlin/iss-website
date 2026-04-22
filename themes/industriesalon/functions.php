<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('after_setup_theme', function () {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('editor-styles');
    add_editor_style('style.css');
});

/**
 * Register local block patterns from theme files.
 */
function industriesalon_register_block_patterns(): void
{
    if (!function_exists('register_block_pattern')) {
        return;
    }

    if (function_exists('register_block_pattern_category')) {
        register_block_pattern_category(
            'industriesalon',
            array(
                'label' => 'Industriesalon',
            )
        );
    }

    $theme_dir = get_stylesheet_directory();
    $registry = class_exists('WP_Block_Patterns_Registry')
        ? WP_Block_Patterns_Registry::get_instance()
        : null;

    $patterns = array(
        array(
            'name' => 'industriesalon/info-panel-anmeldung',
            'title' => 'Info Panel – Anmeldung',
            'description' => 'Kontakt- und Anmeldeblock für Führungen',
            'categories' => array('industriesalon', 'text'),
            'file' => '/patterns/pattern-info-panel-anmeldung.html',
        ),
        array(
            'name' => 'industriesalon/info-panel-besuch',
            'title' => 'Info Panel – Besuch planen',
            'description' => 'Öffnungszeiten und Besuchsinformationen',
            'categories' => array('industriesalon', 'text'),
            'file' => '/patterns/pattern-info-panel-besuch.html',
        ),
        array(
            'name' => 'industriesalon/info-panel-vermietung',
            'title' => 'Info Panel – Vermietung',
            'description' => 'Kontakt für Raumvermietung und Anfragen',
            'categories' => array('industriesalon', 'text'),
            'file' => '/patterns/pattern-info-panel-vermietung.html',
        ),
        array(
            'name' => 'industriesalon/feature-split',
            'title' => 'ISS Feature Split',
            'description' => 'Linear feature section with text left and image right. Used to break card grids.',
            'categories' => array('industriesalon', 'text', 'media'),
            'file' => '/patterns/iss-section-feature-split.html',
        ),
        array(
            'name' => 'industriesalon/1to4-grid',
            'title' => 'ISS 1to4 Grid',
            'description' => 'Lead card with a compact query grid.',
            'categories' => array('industriesalon', 'text', 'media'),
            'file' => '/patterns/iss-1to4-grid.html',
        ),
        array(
            'name' => 'industriesalon/50-50-media-text',
            'title' => 'ISS 50/50 Media Text',
            'description' => 'Two-column section with text on one side and image on the other.',
            'categories' => array('industriesalon', 'text', 'media'),
            'file' => '/patterns/iss-50-50-media-text.html',
        ),
        array(
            'name' => 'industriesalon/asymmetric-feature',
            'title' => 'ISS Asymmetric Feature',
            'description' => 'Asymmetric content and image section with offset visual rhythm.',
            'categories' => array('industriesalon', 'text', 'media'),
            'file' => '/patterns/iss-asymmetric-feature.html',
        ),
        array(
            'name' => 'industriesalon/4-card-row',
            'title' => 'ISS 4 Card Row',
            'description' => 'Section heading above a row of four compact cards with image and title.',
            'categories' => array('industriesalon', 'media', 'cards'),
            'file' => '/patterns/iss-4-card-row.html',
        ),
        array(
            'name' => 'industriesalon/3-card-row',
            'title' => 'ISS 3 Card Row',
            'description' => 'Section heading above a row of three compact info cards.',
            'categories' => array('industriesalon', 'media', 'cards'),
            'file' => '/patterns/iss-3-card-row.html',
        ),
        array(
            'name' => 'industriesalon/newsletter-funders',
            'title' => 'ISS Newsletter + Förderer',
            'description' => 'Newsletter signup section with sponsor/funder panel.',
            'categories' => array('industriesalon', 'text', 'media'),
            'file' => '/patterns/iss-newsletter-funders.html',
        ),
        array(
            'name' => 'industriesalon/archive-landing',
            'title' => 'ISS Archive Landing',
            'description' => 'Archive and media landing page layout.',
            'categories' => array('industriesalon', 'text', 'media'),
            'file' => '/patterns/archive-landing.html',
        ),
        array(
            'name' => 'industriesalon/mission-support-strip',
            'title' => 'ISS Mission Support Strip',
            'description' => 'Mission statement with three supporting fact modules.',
            'categories' => array('industriesalon', 'text'),
            'file' => '/patterns/iss-section-mission-support-strip.html',
        ),
    );

    foreach ($patterns as $pattern) {
        if ($registry && $registry->is_registered($pattern['name'])) {
            continue;
        }

        $file_path = $theme_dir . $pattern['file'];
        if (!file_exists($file_path)) {
            continue;
        }

        $content = file_get_contents($file_path);
        if ($content === false) {
            continue;
        }

        $content = preg_replace('/^<!--[\s\S]*?-->\s*/', '', $content, 1);

        register_block_pattern(
            $pattern['name'],
            array(
                'title' => $pattern['title'],
                'description' => $pattern['description'],
                'categories' => $pattern['categories'],
                'inserter' => true,
                'content' => $content,
            )
        );
    }
}
add_action('init', 'industriesalon_register_block_patterns');

/**
 * Force zero margin in editor canvas to match frontend gap-less layout.
 */
add_action('admin_head', function() {
    echo '<style>
        .interface-interface-skeleton__content { background-color: #fff; }
        .is-root-container.block-editor-block-list__block { margin-top: 0 !important; padding-top: 0 !important; }
    </style>';
});

/**
 * Enqueue theme assets.
 */
function industriesalon_enqueue_assets(): void
{
    $theme = wp_get_theme();
    $version = $theme->get('Version');
    $theme_dir = get_stylesheet_directory();
    $theme_uri = get_stylesheet_directory_uri();

    // 1. Base / style.css (contains global variables)
    wp_enqueue_style(
        'industriesalon-base',
        get_stylesheet_uri(),
        array(),
        $version
    );

    // 2. Media card utilities (must load immediately after base)
    $media_card_utilities_rel = '/assets/css/iss-media-card-utilities.css';
    $media_card_utilities_abs = $theme_dir . $media_card_utilities_rel;
    if (file_exists($media_card_utilities_abs)) {
        wp_enqueue_style(
            'industriesalon-media-card-utilities',
            $theme_uri . $media_card_utilities_rel,
            array('industriesalon-base'),
            (string) filemtime($media_card_utilities_abs)
        );
    }

    // 3. Header styles
    $header_rel = '/assets/css/header.css';
    $header_abs = $theme_dir . $header_rel;
    if (file_exists($header_abs)) {
        wp_enqueue_style(
            'industriesalon-header',
            $theme_uri . $header_rel,
            array('industriesalon-base'),
            (string) filemtime($header_abs)
        );
    }

    // 3b. Footer styles
    $footer_rel = '/assets/css/footer.css';
    $footer_abs = $theme_dir . $footer_rel;
    if (file_exists($footer_abs)) {
        wp_enqueue_style(
            'industriesalon-footer',
            $theme_uri . $footer_rel,
            array('industriesalon-base'),
            (string) filemtime($footer_abs)
        );
    }

    // 4. Front page styles (conditional)
    $front_page_rel = '/assets/css/front-page.css';
    $front_page_abs = $theme_dir . $front_page_rel;
    if ((is_front_page() || is_home() || is_page_template('templates/front-page.html')) && file_exists($front_page_abs)) {
        wp_enqueue_style(
            'industriesalon-front-page',
            $theme_uri . $front_page_rel,
            array('industriesalon-base'),
            (string) filemtime($front_page_abs)
        );
    }

    // 5. Default page template styles (exclude front page)
    $page_rel = '/assets/css/page.css';
    $page_abs = $theme_dir . $page_rel;
    $current_template_id = isset($GLOBALS['_wp_current_template_id']) && is_string($GLOBALS['_wp_current_template_id'])
        ? $GLOBALS['_wp_current_template_id']
        : '';
    $default_page_template_id = get_stylesheet() . '//page';
    if (!is_front_page() && !is_home() && is_page() && $current_template_id === $default_page_template_id && file_exists($page_abs)) {
        wp_enqueue_style(
            'industriesalon-page',
            $theme_uri . $page_rel,
            array('industriesalon-base'),
            (string) filemtime($page_abs)
        );
    }

    // 6. Timeline theme tokens/classes (plugin keeps structural timeline layout)
    $timeline_theme_rel = '/assets/css/timeline-theme.css';
    $timeline_theme_abs = $theme_dir . $timeline_theme_rel;
    if (file_exists($timeline_theme_abs)) {
        wp_enqueue_style(
            'industriesalon-timeline-theme',
            $theme_uri . $timeline_theme_rel,
            array('industriesalon-base'),
            (string) filemtime($timeline_theme_abs)
        );
    }

    // 7. Visit info theme tokens/classes (plugin keeps structural visit info layout)
    $visit_info_rel = '/assets/css/visit-info.css';
    $visit_info_abs = $theme_dir . $visit_info_rel;
    if (file_exists($visit_info_abs)) {
        wp_enqueue_style(
            'industriesalon-visit-info',
            $theme_uri . $visit_info_rel,
            array('industriesalon-base'),
            (string) filemtime($visit_info_abs)
        );
    }

    // 8. Tour styles
    $tour_rel = '/assets/css/tour.css';
    $tour_abs = $theme_dir . $tour_rel;
    if (file_exists($tour_abs)) {
        wp_enqueue_style(
            'industriesalon-tour',
            $theme_uri . $tour_rel,
            array('industriesalon-base'),
            (string) filemtime($tour_abs)
        );
    }

    // 9. Tours page styles
    $tours_page_rel = '/assets/css/tours-page.css';
    $tours_page_abs = $theme_dir . $tours_page_rel;
    if (file_exists($tours_page_abs)) {
        wp_enqueue_style(
            'industriesalon-tours-page',
            $theme_uri . $tours_page_rel,
            array('industriesalon-base'),
            (string) filemtime($tours_page_abs)
        );
    }

    // 10. Info panel styles
    $info_panel_rel = '/assets/css/info-panel.css';
    $info_panel_abs = $theme_dir . $info_panel_rel;
    if (file_exists($info_panel_abs)) {
        wp_enqueue_style(
            'industriesalon-info-panel',
            $theme_uri . $info_panel_rel,
            array('industriesalon-base'),
            (string) filemtime($info_panel_abs)
        );
    }

    // 11. Feature split styles
    $feature_split_rel = '/assets/css/feature-split.css';
    $feature_split_abs = $theme_dir . $feature_split_rel;
    if (file_exists($feature_split_abs)) {
        wp_enqueue_style(
            'industriesalon-feature-split',
            $theme_uri . $feature_split_rel,
            array('industriesalon-base'),
            (string) filemtime($feature_split_abs)
        );
    }

    // 12. 1to4 grid pattern styles
    $one_to_four_grid_rel = '/assets/css/1to4-grid.css';
    $one_to_four_grid_abs = $theme_dir . $one_to_four_grid_rel;
    if (file_exists($one_to_four_grid_abs)) {
        wp_enqueue_style(
            'industriesalon-1to4-grid',
            $theme_uri . $one_to_four_grid_rel,
            array('industriesalon-base'),
            (string) filemtime($one_to_four_grid_abs)
        );
    }

    // 13. 50-50 media text pattern styles
    $fifty_fifty_rel = '/assets/css/50-50-media-text.css';
    $fifty_fifty_abs = $theme_dir . $fifty_fifty_rel;
    if (file_exists($fifty_fifty_abs)) {
        wp_enqueue_style(
            'industriesalon-50-50-media-text',
            $theme_uri . $fifty_fifty_rel,
            array('industriesalon-base'),
            (string) filemtime($fifty_fifty_abs)
        );
    }

    // 14. Asymmetric feature pattern styles
    $asymmetric_feature_rel = '/assets/css/asymmetric-feature.css';
    $asymmetric_feature_abs = $theme_dir . $asymmetric_feature_rel;
    if (file_exists($asymmetric_feature_abs)) {
        wp_enqueue_style(
            'industriesalon-asymmetric-feature',
            $theme_uri . $asymmetric_feature_rel,
            array('industriesalon-base'),
            (string) filemtime($asymmetric_feature_abs)
        );
    }

    // 15. 4 card row pattern styles
    $four_card_row_rel = '/assets/css/4-card-row.css';
    $four_card_row_abs = $theme_dir . $four_card_row_rel;
    if (file_exists($four_card_row_abs)) {
        wp_enqueue_style(
            'industriesalon-4-card-row',
            $theme_uri . $four_card_row_rel,
            array('industriesalon-base'),
            (string) filemtime($four_card_row_abs)
        );
    }

    // 16. 3 card row pattern styles
    $three_card_row_rel = '/assets/css/3-card-row.css';
    $three_card_row_abs = $theme_dir . $three_card_row_rel;
    if (file_exists($three_card_row_abs)) {
        wp_enqueue_style(
            'industriesalon-3-card-row',
            $theme_uri . $three_card_row_rel,
            array('industriesalon-base'),
            (string) filemtime($three_card_row_abs)
        );
    }

    // 17. Newsletter + funders pattern styles
    $newsletter_funders_rel = '/assets/css/iss-newsletter-funders.css';
    $newsletter_funders_abs = $theme_dir . $newsletter_funders_rel;
    if (file_exists($newsletter_funders_abs)) {
        wp_enqueue_style(
            'industriesalon-newsletter-funders',
            $theme_uri . $newsletter_funders_rel,
            array('industriesalon-base'),
            (string) filemtime($newsletter_funders_abs)
        );
    }

    // 18. Archive landing pattern styles
    $archive_landing_rel = '/assets/css/archive-landing.css';
    $archive_landing_abs = $theme_dir . $archive_landing_rel;
    if (file_exists($archive_landing_abs)) {
        wp_enqueue_style(
            'industriesalon-archive-landing',
            $theme_uri . $archive_landing_rel,
            array('industriesalon-base'),
            (string) filemtime($archive_landing_abs)
        );
    }

    // 19. Mission support strip pattern styles
    $mission_support_strip_rel = '/assets/css/mission-support-strip.css';
    $mission_support_strip_abs = $theme_dir . $mission_support_strip_rel;
    if (file_exists($mission_support_strip_abs)) {
        wp_enqueue_style(
            'industriesalon-mission-support-strip',
            $theme_uri . $mission_support_strip_rel,
            array('industriesalon-base'),
            (string) filemtime($mission_support_strip_abs)
        );
    }

    // Header JS
    $script_rel_path = '/assets/js/header.js';
    $script_abs_path = get_stylesheet_directory() . $script_rel_path;
    if (file_exists($script_abs_path)) {
        wp_enqueue_script(
            'industriesalon-header',
            get_stylesheet_directory_uri() . $script_rel_path,
            array(),
            $version,
            true
        );
    }
}
add_action('wp_enqueue_scripts', 'industriesalon_enqueue_assets');
