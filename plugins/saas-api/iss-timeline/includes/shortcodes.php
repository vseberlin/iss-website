<?php
if (!defined('ABSPATH')) exit;

add_action('init', function () {
    if (!function_exists('add_shortcode')) return;

    add_shortcode('iss_timeline', function ($atts = []) {
        $atts = is_array($atts) ? $atts : [];
        $attrs = shortcode_atts([
            'title' => '',
            'kicker' => '',
            'showtitle' => '1',
            'showkicker' => '0',
            'intro' => '',
            'limit' => 50,
            'group' => '',
            'yeargrouping' => '1',
            'showdetailsbutton' => '1',
            'showrecommendbutton' => '1',
            'showticketsbutton' => '1',
            'detailsbuttontext' => '',
            'recommendbuttontext' => '',
            'ticketsbuttontext' => '',
            'detailsbuttonurl' => '',
            'recommendbuttonurl' => '',
            'ticketsbuttonurl' => '',
            'showbottombutton' => '0',
            'bottombuttontext' => '',
            'bottombuttonurl' => '',
        ], $atts, 'iss_timeline');

        return function_exists('iss_timeline_render')
            ? iss_timeline_render([
                'title' => (string) $attrs['title'],
                'kicker' => (string) $attrs['kicker'],
                'showTitle' => (string) $attrs['showtitle'] !== '0',
                'showKicker' => (string) $attrs['showkicker'] !== '0',
                'intro' => (string) $attrs['intro'],
                'limit' => (int) $attrs['limit'],
                'group' => (string) $attrs['group'],
                'yearGrouping' => (string) $attrs['yeargrouping'] !== '0',
                'showDetailsButton' => (string) $attrs['showdetailsbutton'] !== '0',
                'showRecommendButton' => (string) $attrs['showrecommendbutton'] !== '0',
                'showTicketsButton' => (string) $attrs['showticketsbutton'] !== '0',
                'detailsButtonText' => (string) $attrs['detailsbuttontext'],
                'recommendButtonText' => (string) $attrs['recommendbuttontext'],
                'ticketsButtonText' => (string) $attrs['ticketsbuttontext'],
                'detailsButtonUrl' => (string) $attrs['detailsbuttonurl'],
                'recommendButtonUrl' => (string) $attrs['recommendbuttonurl'],
                'ticketsButtonUrl' => (string) $attrs['ticketsbuttonurl'],
                'showBottomButton' => (string) $attrs['showbottombutton'] !== '0',
                'bottomButtonText' => (string) $attrs['bottombuttontext'],
                'bottomButtonUrl' => (string) $attrs['bottombuttonurl'],
            ], '')
            : '';
    });

    add_shortcode('iss_timeline_sections', function ($atts = []) {
        $atts = is_array($atts) ? $atts : [];
        $attrs = shortcode_atts([
            'title' => '',
            'intro' => '',
            'group' => '',
            'nextTitle' => 'Was kommt als Nächstes',
            'nextLimit' => 4,
            'monthlyTitle' => 'Monatsübersicht',
            'monthlyLimit' => 80,
            'archiveTitle' => 'Archiv',
            'archiveLimit' => 250,
        ], $atts, 'iss_timeline_sections');

        return function_exists('iss_timeline_render_sections')
            ? iss_timeline_render_sections([
                'title' => (string) $attrs['title'],
                'intro' => (string) $attrs['intro'],
                'group' => (string) $attrs['group'],
                'nextTitle' => (string) $attrs['nextTitle'],
                'nextLimit' => (int) $attrs['nextLimit'],
                'monthlyTitle' => (string) $attrs['monthlyTitle'],
                'monthlyLimit' => (int) $attrs['monthlyLimit'],
                'archiveTitle' => (string) $attrs['archiveTitle'],
                'archiveLimit' => (int) $attrs['archiveLimit'],
            ], '')
            : '';
    });

    add_shortcode('iss_timeline_latest', function ($atts = []) {
        $atts = is_array($atts) ? $atts : [];
        $attrs = shortcode_atts([
            'title' => '',
            'kicker' => '',
            'showtitle' => '1',
            'showkicker' => '0',
            'intro' => '',
            'group' => '',
            'showdetailsbutton' => '1',
            'showrecommendbutton' => '1',
            'showticketsbutton' => '1',
            'detailsbuttontext' => '',
            'recommendbuttontext' => '',
            'ticketsbuttontext' => '',
            'detailsbuttonurl' => '',
            'recommendbuttonurl' => '',
            'ticketsbuttonurl' => '',
            'showbottombutton' => '0',
            'bottombuttontext' => '',
            'bottombuttonurl' => '',
        ], $atts, 'iss_timeline_latest');

        return function_exists('iss_timeline_render_latest')
            ? iss_timeline_render_latest([
                'title' => (string) $attrs['title'],
                'kicker' => (string) $attrs['kicker'],
                'showTitle' => (string) $attrs['showtitle'] !== '0',
                'showKicker' => (string) $attrs['showkicker'] !== '0',
                'intro' => (string) $attrs['intro'],
                'group' => (string) $attrs['group'],
                'showDetailsButton' => (string) $attrs['showdetailsbutton'] !== '0',
                'showRecommendButton' => (string) $attrs['showrecommendbutton'] !== '0',
                'showTicketsButton' => (string) $attrs['showticketsbutton'] !== '0',
                'detailsButtonText' => (string) $attrs['detailsbuttontext'],
                'recommendButtonText' => (string) $attrs['recommendbuttontext'],
                'ticketsButtonText' => (string) $attrs['ticketsbuttontext'],
                'detailsButtonUrl' => (string) $attrs['detailsbuttonurl'],
                'recommendButtonUrl' => (string) $attrs['recommendbuttonurl'],
                'ticketsButtonUrl' => (string) $attrs['ticketsbuttonurl'],
                'showBottomButton' => (string) $attrs['showbottombutton'] !== '0',
                'bottomButtonText' => (string) $attrs['bottombuttontext'],
                'bottomButtonUrl' => (string) $attrs['bottombuttonurl'],
            ], '')
            : '';
    });
});
