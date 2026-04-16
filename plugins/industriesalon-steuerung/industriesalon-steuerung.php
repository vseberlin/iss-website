<?php
/**
 * Plugin Name: Industriesalon Steuerung
 * Description: Zentrale Seiteneinstellungen für Industriesalon: Öffnungszeiten, Bürozeiten, Kontakt, Adresse, Mission Statement, Barrierefreiheit, FAQ und Preise mit wiederverwendbarer Ausgabe per PHP, Shortcodes und Gutenberg-Blöcke.
 * Version: 0.2.4
 * Author: OpenAI
 * Text Domain: industriesalon-steuerung
 */

if (! defined('ABSPATH')) {
    exit;
}

final class Industriesalon_Steuerung {
    private const VERSION = '0.2.4';
    private const CAPABILITY = 'manage_iss_controls';

    private const OPTION_GENERAL = 'iss_control_general';
    private const OPTION_CONTACT = 'iss_control_contact';
    private const OPTION_MAPS = 'iss_control_maps';
    private const OPTION_HOURS = 'iss_control_hours';
    private const OPTION_ACCESSIBILITY = 'iss_control_accessibility';
    private const OPTION_PRICES = 'iss_control_prices';
    private const OPTION_FAQ = 'iss_control_faq';
    private const OPTION_MISSION_STATEMENT = 'iss_control_mission_statement';

    private static ?Industriesalon_Steuerung $instance = null;

    public static function instance(): Industriesalon_Steuerung {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public static function activate(): void {
        foreach (['administrator', 'editor'] as $role_name) {
            $role = get_role($role_name);
            if ($role instanceof WP_Role) {
                $role->add_cap(self::CAPABILITY);
            }
        }
    }

    public static function deactivate(): void {
        foreach (['administrator', 'editor'] as $role_name) {
            $role = get_role($role_name);
            if ($role instanceof WP_Role) {
                $role->remove_cap(self::CAPABILITY);
            }
        }
    }

    private function __construct() {
        add_action('plugins_loaded', [$this, 'load_textdomain']);
        add_action('admin_menu', [$this, 'register_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('admin_post_iss_control_export', [$this, 'handle_export']);
        add_action('admin_post_iss_control_import', [$this, 'handle_import']);

        add_action('init', [$this, 'register_shortcodes']);
        add_action('init', [$this, 'register_blocks']);
    }

    public function load_textdomain(): void {
        load_plugin_textdomain('industriesalon-steuerung', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function capability(): string {
        return (string) apply_filters('iss_control_capability', self::CAPABILITY);
    }

    public function get_option_keys(): array {
        return [
            'general'       => self::OPTION_GENERAL,
            'contact'       => self::OPTION_CONTACT,
            'maps'          => self::OPTION_MAPS,
            'hours'         => self::OPTION_HOURS,
            'accessibility' => self::OPTION_ACCESSIBILITY,
            'prices'        => self::OPTION_PRICES,
            'faq'           => self::OPTION_FAQ,
            'mission_statement' => self::OPTION_MISSION_STATEMENT,
        ];
    }

    public function register_admin_menu(): void {
        $hook = add_menu_page(
            __('Industriesalon Steuerung', 'industriesalon-steuerung'),
            __('Industriesalon Steuerung', 'industriesalon-steuerung'),
            $this->capability(),
            'industriesalon-steuerung',
            [$this, 'render_admin_page'],
            'dashicons-admin-generic',
            58
        );

        if (is_string($hook) && $hook !== '') {
            add_action('load-' . $hook, [$this, 'register_help_tabs']);
        }
    }

    public function register_help_tabs(): void {
        $screen = get_current_screen();
        if (! ($screen instanceof WP_Screen)) {
            return;
        }

        if ($screen->id !== 'toplevel_page_industriesalon-steuerung') {
            return;
        }

        $screen->add_help_tab([
            'id'      => 'iss-help-quick',
            'title'   => __('Kurzhilfe', 'industriesalon-steuerung'),
            'content' =>
                '<p><strong>' . esc_html__('Alles hier wirkt websiteweit.', 'industriesalon-steuerung') . '</strong></p>' .
                '<p>' . esc_html__('Änderungen nach dem Speichern werden automatisch in allen verbundenen Bereichen angezeigt.', 'industriesalon-steuerung') . '</p>' .
                '<ul>' .
                '<li>' . esc_html__('Nach jeder Bearbeitung auf „Änderung speichern“ klicken.', 'industriesalon-steuerung') . '</li>' .
                '<li>' . esc_html__('Vor größeren Änderungen zuerst JSON exportieren.', 'industriesalon-steuerung') . '</li>' .
                '<li>' . esc_html__('Bei Öffnungszeiten Ausnahmen mit Datum prüfen.', 'industriesalon-steuerung') . '</li>' .
                '</ul>',
        ]);

        $screen->add_help_tab([
            'id'      => 'iss-help-shortcodes',
            'title'   => __('Shortcodes & PHP', 'industriesalon-steuerung'),
            'content' =>
                '<p>' . esc_html__('Beispiele für technische Einbindung in Seiten/Theme:', 'industriesalon-steuerung') . '</p>' .
                '<p><code>[iss_field key="contact.phone"]</code></p>' .
                '<p><code>[iss_hours type="public"]</code></p>' .
                '<p><code>[iss_mission_statement title="Mission Statement" heading="Unsere Haltung"]</code></p>' .
                '<p><code>&lt;?php echo Industriesalon_Steuerung::instance()->render_contact(); ?&gt;</code></p>',
        ]);

        $screen->set_help_sidebar(
            '<p><strong>' . esc_html__('Hinweis', 'industriesalon-steuerung') . '</strong></p>' .
            '<p>' . esc_html__('Für Redaktion: nur Inhalte pflegen und speichern. Für Entwickler: Shortcodes/PHP nutzen.', 'industriesalon-steuerung') . '</p>'
        );
    }

    public function enqueue_admin_assets(string $hook): void {
        if ($hook !== 'toplevel_page_industriesalon-steuerung') {
            return;
        }

        wp_enqueue_style(
            'iss-control-admin',
            plugins_url('assets/admin.css', __FILE__),
            [],
            self::VERSION
        );

        wp_enqueue_script(
            'iss-control-admin',
            plugins_url('assets/admin.js', __FILE__),
            [],
            self::VERSION,
            true
        );
    }

    public function register_settings(): void {
        register_setting('iss_control_group', self::OPTION_GENERAL, [
            'type'              => 'array',
            'sanitize_callback' => [$this, 'sanitize_general'],
            'default'           => $this->default_general(),
        ]);

        register_setting('iss_control_group', self::OPTION_CONTACT, [
            'type'              => 'array',
            'sanitize_callback' => [$this, 'sanitize_contact'],
            'default'           => $this->default_contact(),
        ]);

        register_setting('iss_control_group', self::OPTION_MAPS, [
            'type'              => 'array',
            'sanitize_callback' => [$this, 'sanitize_maps'],
            'default'           => $this->default_maps(),
        ]);

        register_setting('iss_control_group', self::OPTION_HOURS, [
            'type'              => 'array',
            'sanitize_callback' => [$this, 'sanitize_hours'],
            'default'           => $this->default_hours(),
        ]);

        register_setting('iss_control_group', self::OPTION_ACCESSIBILITY, [
            'type'              => 'array',
            'sanitize_callback' => [$this, 'sanitize_accessibility'],
            'default'           => $this->default_accessibility(),
        ]);

        register_setting('iss_control_group', self::OPTION_PRICES, [
            'type'              => 'array',
            'sanitize_callback' => [$this, 'sanitize_prices'],
            'default'           => $this->default_prices(),
        ]);

        register_setting('iss_control_group', self::OPTION_FAQ, [
            'type'              => 'array',
            'sanitize_callback' => [$this, 'sanitize_faq'],
            'default'           => $this->default_faq(),
        ]);

        register_setting('iss_control_group', self::OPTION_MISSION_STATEMENT, [
            'type'              => 'array',
            'sanitize_callback' => [$this, 'sanitize_mission_statement'],
            'default'           => $this->default_mission_statement(),
        ]);
    }

    public function render_admin_page(): void {
        if (! current_user_can($this->capability())) {
            return;
        }

        $general = get_option(self::OPTION_GENERAL, $this->default_general());
        $contact = get_option(self::OPTION_CONTACT, $this->default_contact());
        $maps = get_option(self::OPTION_MAPS, $this->default_maps());
        $hours = get_option(self::OPTION_HOURS, $this->default_hours());
        $accessibility = get_option(self::OPTION_ACCESSIBILITY, $this->default_accessibility());
        $prices = get_option(self::OPTION_PRICES, $this->default_prices());
        $faq = get_option(self::OPTION_FAQ, $this->default_faq());
        $mission_statement = get_option(self::OPTION_MISSION_STATEMENT, $this->default_mission_statement());
        $days = $this->days();
        ?>
        <div class="wrap iss-admin">
            <h1><?php esc_html_e('Industriesalon Steuerung', 'industriesalon-steuerung'); ?></h1>
            <p class="description"><?php esc_html_e('Zentrale Angaben für wiederverwendbare Inhalte im Frontend.', 'industriesalon-steuerung'); ?></p>

            <?php $this->render_admin_notice(); ?>

            <nav class="nav-tab-wrapper iss-tabs" aria-label="<?php esc_attr_e('Bereiche', 'industriesalon-steuerung'); ?>">
                <a href="#iss-general" class="nav-tab nav-tab-active"><?php esc_html_e('Standort & Adresse', 'industriesalon-steuerung'); ?></a>
                <a href="#iss-contact" class="nav-tab"><?php esc_html_e('Kontakt', 'industriesalon-steuerung'); ?></a>
                <a href="#iss-maps" class="nav-tab"><?php esc_html_e('Google Maps / Anfahrt', 'industriesalon-steuerung'); ?></a>
                <a href="#iss-hours" class="nav-tab"><?php esc_html_e('Öffnungszeiten', 'industriesalon-steuerung'); ?></a>
                <a href="#iss-prices" class="nav-tab"><?php esc_html_e('Preise', 'industriesalon-steuerung'); ?></a>
                <a href="#iss-accessibility" class="nav-tab"><?php esc_html_e('Barrierefreiheit', 'industriesalon-steuerung'); ?></a>
                <a href="#iss-faq" class="nav-tab"><?php esc_html_e('Häufige Fragen', 'industriesalon-steuerung'); ?></a>
                <a href="#iss-mission-statement" class="nav-tab"><?php esc_html_e('Mission Statement', 'industriesalon-steuerung'); ?></a>
                <a href="#iss-tools" class="nav-tab"><?php esc_html_e('Werkzeuge', 'industriesalon-steuerung'); ?></a>
            </nav>

            <section class="iss-help-panel" aria-label="<?php esc_attr_e('Schnellhilfe', 'industriesalon-steuerung'); ?>">
                <h2><?php esc_html_e('Schnellhilfe', 'industriesalon-steuerung'); ?></h2>
                <ul>
                    <li><?php esc_html_e('Alles, was Sie hier speichern, erscheint automatisch auf der gesamten Website.', 'industriesalon-steuerung'); ?></li>
                    <li><?php esc_html_e('Nutzen Sie den Button „Änderung speichern“ oben oder unten im Formular.', 'industriesalon-steuerung'); ?></li>
                    <li><?php esc_html_e('Vor größeren Änderungen bitte zuerst über „Werkzeuge“ eine JSON-Sicherung erstellen.', 'industriesalon-steuerung'); ?></li>
                </ul>
                <p class="description"><?php esc_html_e('Mehr Hilfe finden Sie oben rechts unter „Hilfe einblenden“.', 'industriesalon-steuerung'); ?></p>
            </section>

            <form method="post" action="options.php">
                <?php settings_fields('iss_control_group'); ?>
                <p><?php submit_button(__('Änderung speichern', 'industriesalon-steuerung'), 'primary', 'submit', false); ?></p>

                <section id="iss-general" class="iss-panel">
                    <h2><?php esc_html_e('Standort & Adresse', 'industriesalon-steuerung'); ?></h2>
                    <p class="iss-panel__hint"><?php esc_html_e('Grundangaben zum Ort und zur Anfahrt.', 'industriesalon-steuerung'); ?></p>
                    <div class="iss-grid iss-grid--2">
                        <?php $this->text_field(self::OPTION_GENERAL, 'site_name', __('Name des Ortes', 'industriesalon-steuerung'), $general['site_name'] ?? '', true, 'Industriesalon Schöneweide', __('So erscheint der Name in Kontakt- und Infobereichen.', 'industriesalon-steuerung')); ?>
                        <?php $this->text_field(self::OPTION_GENERAL, 'tagline', __('Kurzbeschreibung', 'industriesalon-steuerung'), $general['tagline'] ?? '', true, 'Museum und Besucherzentrum zur Industriegeschichte in Berlin-Schöneweide.', __('Kurzer Satz für Hinweise oder Infobereiche.', 'industriesalon-steuerung')); ?>
                        <?php $this->text_field(self::OPTION_GENERAL, 'street', __('Straße und Hausnummer', 'industriesalon-steuerung'), $general['street'] ?? '', true, 'Reinbeckstraße 9', __('Bitte so eingeben, wie es öffentlich erscheinen soll.', 'industriesalon-steuerung')); ?>
                        <?php $this->text_field(self::OPTION_GENERAL, 'postal_code', __('PLZ', 'industriesalon-steuerung'), $general['postal_code'] ?? '', true, '12459'); ?>
                        <?php $this->text_field(self::OPTION_GENERAL, 'city', __('Ort', 'industriesalon-steuerung'), $general['city'] ?? '', true, 'Berlin'); ?>
                        <?php $this->text_field(self::OPTION_GENERAL, 'district', __('Stadtteil', 'industriesalon-steuerung'), $general['district'] ?? '', true, 'Schöneweide', __('Optional.', 'industriesalon-steuerung')); ?>
                    </div>
                    <div class="iss-grid iss-grid--1">
                        <?php $this->textarea_field(self::OPTION_GENERAL, 'arrival', __('Anfahrt', 'industriesalon-steuerung'), $general['arrival'] ?? '', 4, true, 'Erreichbar mit S-Bahn, Tram oder Bus …', __('Kurzer Hinweis zur Anreise.', 'industriesalon-steuerung')); ?>
                        <?php $this->textarea_field(self::OPTION_GENERAL, 'visit_note', __('Besuchshinweis', 'industriesalon-steuerung'), $general['visit_note'] ?? '', 4, true, 'Der Eingang befindet sich …', __('Optional. Für wichtige Hinweise vor dem Besuch.', 'industriesalon-steuerung')); ?>
                    </div>
                </section>

                <section id="iss-contact" class="iss-panel" hidden>
                    <h2><?php esc_html_e('Kontakt', 'industriesalon-steuerung'); ?></h2>
                    <p class="iss-panel__hint"><?php esc_html_e('Kontaktangaben für Besucherinnen und Besucher.', 'industriesalon-steuerung'); ?></p>
                    <div class="iss-grid iss-grid--2">
                        <?php $this->text_field(self::OPTION_CONTACT, 'phone', __('Telefon', 'industriesalon-steuerung'), $contact['phone'] ?? '', true, '030 12345678', __('Kann mit Leerzeichen eingegeben werden.', 'industriesalon-steuerung')); ?>
                        <?php $this->email_field(self::OPTION_CONTACT, 'email', __('E-Mail', 'industriesalon-steuerung'), $contact['email'] ?? '', 'info@industriesalon.de'); ?>
                        <?php $this->email_field(self::OPTION_CONTACT, 'booking_email', __('E-Mail für Buchungen', 'industriesalon-steuerung'), $contact['booking_email'] ?? '', 'buchung@industriesalon.de', __('Optional. Nur wenn Buchungen über eine eigene Adresse laufen.', 'industriesalon-steuerung')); ?>
                        <?php $this->text_field(self::OPTION_CONTACT, 'contact_person', __('Ansprechperson', 'industriesalon-steuerung'), $contact['contact_person'] ?? '', true, 'Max Mustermann', __('Optional.', 'industriesalon-steuerung')); ?>
                        <?php $this->url_field(self::OPTION_CONTACT, 'website', __('Website', 'industriesalon-steuerung'), $contact['website'] ?? '', 'https://www.industriesalon.de'); ?>
                        <?php $this->url_field(self::OPTION_CONTACT, 'booking_url', __('Buchungslink', 'industriesalon-steuerung'), $contact['booking_url'] ?? '', 'https://…', __('Zum Beispiel SuperSaaS, Formular oder externe Buchungsseite.', 'industriesalon-steuerung')); ?>
                        <?php $this->url_field(self::OPTION_CONTACT, 'instagram', __('Instagram', 'industriesalon-steuerung'), $contact['instagram'] ?? '', 'https://instagram.com/...', __('Optional.', 'industriesalon-steuerung')); ?>
                        <?php $this->url_field(self::OPTION_CONTACT, 'facebook', __('Facebook', 'industriesalon-steuerung'), $contact['facebook'] ?? '', 'https://facebook.com/...', __('Optional.', 'industriesalon-steuerung')); ?>
                    </div>
                </section>

                <section id="iss-maps" class="iss-panel" hidden>
                    <h2><?php esc_html_e('Google Maps / Anfahrt', 'industriesalon-steuerung'); ?></h2>
                    <p class="iss-panel__hint"><?php esc_html_e('Links zur Karte und optional zur Einbettung.', 'industriesalon-steuerung'); ?></p>
                    <div class="iss-grid iss-grid--1">
                        <?php $this->url_field(self::OPTION_MAPS, 'google_maps_url', __('Google Maps URL', 'industriesalon-steuerung'), $maps['google_maps_url'] ?? '', 'https://maps.google.com/...'); ?>
                        <?php $this->textarea_field(self::OPTION_MAPS, 'google_maps_embed', __('Embed URL oder iFrame-Code', 'industriesalon-steuerung'), $maps['google_maps_embed'] ?? '', 5, true, '', __('Optional.', 'industriesalon-steuerung')); ?>
                    </div>
                </section>

                <section id="iss-hours" class="iss-panel" hidden>
                    <h2><?php esc_html_e('Öffnungszeiten', 'industriesalon-steuerung'); ?></h2>
                    <p class="iss-panel__hint"><?php esc_html_e('Öffnungszeiten und Bürozeiten werden getrennt gepflegt.', 'industriesalon-steuerung'); ?></p>

                    <div class="iss-hours-group">
                        <h3><?php esc_html_e('Öffnungszeiten', 'industriesalon-steuerung'); ?></h3>
                        <?php $this->hours_table('public', $hours['public'] ?? [], $days); ?>
                    </div>

                    <div class="iss-hours-group">
                        <h3><?php esc_html_e('Bürozeiten', 'industriesalon-steuerung'); ?></h3>
                        <?php $this->hours_table('office', $hours['office'] ?? [], $days); ?>
                    </div>

                    <div class="iss-hours-group">
                        <h3><?php esc_html_e('Ausnahmen / Feiertage / Sonderöffnungen', 'industriesalon-steuerung'); ?></h3>
                        <p class="iss-panel__hint"><?php esc_html_e('Diese Einträge überschreiben die normalen Zeiten für das gewählte Datum.', 'industriesalon-steuerung'); ?></p>
                        <div class="iss-special-list" data-iss-special-list>
                            <?php
                            $special = isset($hours['exceptions']) && is_array($hours['exceptions']) ? $hours['exceptions'] : [];
                            if (empty($special)) {
                                $special = [['date' => '', 'type' => 'public', 'closed' => 0, 'open' => '', 'close' => '', 'note' => '']];
                            }
                            foreach ($special as $index => $item) :
                                $this->special_date_fields((int) $index, $item);
                            endforeach;
                            ?>
                        </div>
                        <template id="iss-special-template">
                            <?php $this->special_date_fields('__INDEX__', ['date' => '', 'type' => 'public', 'closed' => 0, 'open' => '', 'close' => '', 'note' => '']); ?>
                        </template>
                        <p><button type="button" class="button" data-iss-add-special><?php esc_html_e('Sondertermin hinzufügen', 'industriesalon-steuerung'); ?></button></p>
                    </div>
                </section>

                <section id="iss-accessibility" class="iss-panel" hidden>
                    <h2><?php esc_html_e('Barrierefreiheit', 'industriesalon-steuerung'); ?></h2>
                    <p class="iss-panel__hint"><?php esc_html_e('Kurze, klare Angaben zur Zugänglichkeit.', 'industriesalon-steuerung'); ?></p>
                    <div class="iss-grid iss-grid--2">
                        <?php $this->checkbox_field(self::OPTION_ACCESSIBILITY, 'wheelchair', __('Stufenloser Zugang', 'industriesalon-steuerung'), ! empty($accessibility['wheelchair'])); ?>
                        <?php $this->checkbox_field(self::OPTION_ACCESSIBILITY, 'accessible_toilet', __('Barrierefreies WC', 'industriesalon-steuerung'), ! empty($accessibility['accessible_toilet'])); ?>
                        <?php $this->checkbox_field(self::OPTION_ACCESSIBILITY, 'elevator', __('Aufzug vorhanden', 'industriesalon-steuerung'), ! empty($accessibility['elevator'])); ?>
                        <?php $this->checkbox_field(self::OPTION_ACCESSIBILITY, 'parking', __('Parken in der Nähe', 'industriesalon-steuerung'), ! empty($accessibility['parking'])); ?>
                        <?php $this->checkbox_field(self::OPTION_ACCESSIBILITY, 'companion', __('Begleitperson möglich', 'industriesalon-steuerung'), ! empty($accessibility['companion'])); ?>
                    </div>
                    <div class="iss-grid iss-grid--1">
                        <?php $this->textarea_field(self::OPTION_ACCESSIBILITY, 'note', __('Zusätzlicher Hinweis', 'industriesalon-steuerung'), $accessibility['note'] ?? '', 5, true, 'Ein Teil der Ausstellung ist nur eingeschränkt zugänglich.', __('Nur das eintragen, was Besucher wirklich wissen müssen.', 'industriesalon-steuerung')); ?>
                    </div>
                </section>

                <section id="iss-prices" class="iss-panel" hidden>
                    <h2><?php esc_html_e('Preise', 'industriesalon-steuerung'); ?></h2>
                    <p class="iss-panel__hint"><?php esc_html_e('Preise und Hinweise für Besucher.', 'industriesalon-steuerung'); ?></p>
                    <div class="iss-grid iss-grid--2">
                        <?php $this->text_field(self::OPTION_PRICES, 'adult', __('Regulär', 'industriesalon-steuerung'), $prices['adult'] ?? '', true, '5 €'); ?>
                        <?php $this->text_field(self::OPTION_PRICES, 'reduced', __('Ermäßigt', 'industriesalon-steuerung'), $prices['reduced'] ?? ''); ?>
                        <?php $this->text_field(self::OPTION_PRICES, 'group', __('Gruppen', 'industriesalon-steuerung'), $prices['group'] ?? '', true, 'auf Anfrage', __('Optional.', 'industriesalon-steuerung')); ?>
                        <?php $this->text_field(self::OPTION_PRICES, 'school', __('Schulklassen', 'industriesalon-steuerung'), $prices['school'] ?? '', true, 'nach Vereinbarung', __('Optional.', 'industriesalon-steuerung')); ?>
                        <?php $this->text_field(self::OPTION_PRICES, 'tour', __('Hinweis zu Führungen oder Buchungen', 'industriesalon-steuerung'), $prices['tour'] ?? '', true, 'Führungen nur nach Voranmeldung.'); ?>
                        <?php $this->text_field(self::OPTION_PRICES, 'rental', __('Freier Eintritt / Hinweise', 'industriesalon-steuerung'), $prices['rental'] ?? '', true, 'Kinder bis … frei', __('Optional.', 'industriesalon-steuerung')); ?>
                    </div>
                    <div class="iss-grid iss-grid--1">
                        <?php $this->textarea_field(self::OPTION_PRICES, 'note', __('Hinweis', 'industriesalon-steuerung'), $prices['note'] ?? '', 4); ?>
                    </div>
                </section>

                <section id="iss-faq" class="iss-panel" hidden>
                    <h2><?php esc_html_e('Häufige Fragen', 'industriesalon-steuerung'); ?></h2>
                    <p class="iss-panel__hint"><?php esc_html_e('Kurze Fragen und kurze Antworten.', 'industriesalon-steuerung'); ?></p>

                    <div class="iss-faq-list" data-iss-faq-list>
                        <?php
                        $items = $faq['items'] ?? [];
                        if (empty($items)) {
                            $items = [['question' => '', 'answer' => '']];
                        }
                        foreach ($items as $index => $item) :
                            $this->faq_item_fields((int) $index, $item);
                        endforeach;
                        ?>
                    </div>

                    <template id="iss-faq-template">
                        <?php $this->faq_item_fields('__INDEX__', ['question' => '', 'answer' => '']); ?>
                    </template>

                    <p>
                        <button type="button" class="button" data-iss-add-faq><?php esc_html_e('FAQ-Eintrag hinzufügen', 'industriesalon-steuerung'); ?></button>
                    </p>
                </section>

                <section id="iss-mission-statement" class="iss-panel" hidden>
                    <h2><?php esc_html_e('Mission Statement', 'industriesalon-steuerung'); ?></h2>
                    <p class="iss-panel__hint"><?php esc_html_e('Kurztext für die redaktionelle Selbstbeschreibung auf Seiten und in Inhaltsblöcken.', 'industriesalon-steuerung'); ?></p>
                    <div class="iss-grid iss-grid--1">
                        <?php $this->textarea_field(self::OPTION_MISSION_STATEMENT, 'content', __('Mission Statement', 'industriesalon-steuerung'), $mission_statement['content'] ?? '', 8, true, '', __('Kurz und prägnant formulieren.', 'industriesalon-steuerung')); ?>
                    </div>
                </section>

                <?php submit_button(__('Änderung speichern', 'industriesalon-steuerung')); ?>
            </form>

                <section id="iss-tools" class="iss-panel" hidden>
                <h2><?php esc_html_e('Werkzeuge', 'industriesalon-steuerung'); ?></h2>
                <p class="iss-panel__hint"><?php esc_html_e('Für Sicherung und Wiederherstellung der Daten.', 'industriesalon-steuerung'); ?></p>

                <div class="iss-tools-grid">
                    <div class="iss-tool-card">
                        <h3><?php esc_html_e('Daten sichern', 'industriesalon-steuerung'); ?></h3>
                        <p><?php esc_html_e('Lädt eine Sicherungsdatei herunter.', 'industriesalon-steuerung'); ?></p>
                        <p>
                            <a class="button button-secondary" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=iss_control_export'), 'iss_control_export')); ?>">
                                <?php esc_html_e('JSON exportieren', 'industriesalon-steuerung'); ?>
                            </a>
                        </p>
                    </div>

                    <div class="iss-tool-card">
                        <h3><?php esc_html_e('Daten wiederherstellen', 'industriesalon-steuerung'); ?></h3>
                        <p><?php esc_html_e('Spielt eine zuvor gespeicherte Sicherungsdatei ein. Bestehende Werte werden überschrieben.', 'industriesalon-steuerung'); ?></p>
                        <p class="iss-panel__hint"><?php esc_html_e('Nur benutzen, wenn Sie eine Sicherungsdatei haben. Nicht für den täglichen Gebrauch.', 'industriesalon-steuerung'); ?></p>
                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data">
                            <?php wp_nonce_field('iss_control_import'); ?>
                            <input type="hidden" name="action" value="iss_control_import">
                            <input type="file" name="iss_import_file" accept="application/json,.json" required>
                            <?php submit_button(__('JSON importieren', 'industriesalon-steuerung'), 'secondary', 'submit', false); ?>
                        </form>
                    </div>
                </div>
            </section>
        </div>
        <?php
    }

    private function render_admin_notice(): void {
        if (! isset($_GET['iss_notice'])) {
            return;
        }

        $notice = sanitize_key((string) wp_unslash($_GET['iss_notice']));
        $messages = [
            'import_success' => ['updated', __('Import erfolgreich.', 'industriesalon-steuerung')],
            'import_error'   => ['error', __('Import fehlgeschlagen. Datei prüfen und erneut versuchen.', 'industriesalon-steuerung')],
        ];

        if (! isset($messages[$notice])) {
            return;
        }

        [$class, $message] = $messages[$notice];

        printf(
            '<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>',
            esc_attr($class),
            esc_html($message)
        );
    }

    public function handle_export(): void {
        if (! current_user_can($this->capability())) {
            wp_die(esc_html__('Keine Berechtigung.', 'industriesalon-steuerung'));
        }

        check_admin_referer('iss_control_export');

        $payload = [
            'plugin'       => 'Industriesalon Steuerung',
            'version'      => self::VERSION,
            'generated_at' => current_time('mysql'),
            'site_url'     => home_url('/'),
            'options'      => $this->get_all_data(),
        ];

        nocache_headers();
        header('Content-Type: application/json; charset=' . get_option('blog_charset'));
        header('Content-Disposition: attachment; filename="industriesalon-steuerung-export-' . gmdate('Y-m-d-His') . '.json"');

        echo wp_json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function handle_import(): void {
        if (! current_user_can($this->capability())) {
            wp_die(esc_html__('Keine Berechtigung.', 'industriesalon-steuerung'));
        }

        check_admin_referer('iss_control_import');

        if (
            empty($_FILES['iss_import_file']['tmp_name']) ||
            ! is_uploaded_file($_FILES['iss_import_file']['tmp_name'])
        ) {
            $this->redirect_with_notice('import_error');
        }

        $raw = file_get_contents($_FILES['iss_import_file']['tmp_name']);
        if (! is_string($raw) || $raw === '') {
            $this->redirect_with_notice('import_error');
        }

        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            $this->redirect_with_notice('import_error');
        }

        $options = isset($decoded['options']) && is_array($decoded['options']) ? $decoded['options'] : $decoded;
        $map = $this->get_option_keys();

        foreach ($map as $section => $option_name) {
            $payload = [];

            if (isset($options[$section]) && is_array($options[$section])) {
                $payload = $options[$section];
            } elseif (isset($options[$option_name]) && is_array($options[$option_name])) {
                $payload = $options[$option_name];
            }

            switch ($section) {
                case 'general':
                    update_option($option_name, $this->sanitize_general($payload));
                    break;
                case 'contact':
                    update_option($option_name, $this->sanitize_contact($payload));
                    break;
                case 'maps':
                    update_option($option_name, $this->sanitize_maps($payload));
                    break;
                case 'hours':
                    update_option($option_name, $this->sanitize_hours($payload));
                    break;
                case 'accessibility':
                    update_option($option_name, $this->sanitize_accessibility($payload));
                    break;
                case 'prices':
                    update_option($option_name, $this->sanitize_prices($payload));
                    break;
                case 'faq':
                    update_option($option_name, $this->sanitize_faq($payload));
                    break;
                case 'mission_statement':
                    update_option($option_name, $this->sanitize_mission_statement($payload));
                    break;
            }
        }

        $this->redirect_with_notice('import_success');
    }

    private function redirect_with_notice(string $notice): void {
        wp_safe_redirect(add_query_arg(['page' => 'industriesalon-steuerung', 'iss_notice' => $notice], admin_url('admin.php')));
        exit;
    }

    public function register_shortcodes(): void {
        add_shortcode('iss_field', [$this, 'shortcode_field']);
        add_shortcode('iss_hours', [$this, 'shortcode_hours']);
        add_shortcode('iss_contact', [$this, 'shortcode_contact']);
        add_shortcode('iss_prices', [$this, 'shortcode_prices']);
        add_shortcode('iss_faq', [$this, 'shortcode_faq']);
        add_shortcode('iss_mission_statement', [$this, 'shortcode_mission_statement']);
    }

    public function register_blocks(): void {
        if (! function_exists('register_block_type')) {
            return;
        }

        wp_register_script(
            'iss-control-blocks',
            plugins_url('assets/blocks.js', __FILE__),
            ['wp-blocks', 'wp-element', 'wp-components', 'wp-block-editor', 'wp-server-side-render'],
            self::VERSION,
            true
        );

        register_block_type('industriesalon/field', [
            'api_version'     => 2,
            'editor_script'   => 'iss-control-blocks',
            'render_callback' => [$this, 'render_field_block'],
            'attributes'      => [
                'key'      => ['type' => 'string', 'default' => 'contact.phone'],
                'tagName'  => ['type' => 'string', 'default' => 'div'],
                'linkMode' => ['type' => 'string', 'default' => 'auto'],
                'label'    => ['type' => 'string', 'default' => ''],
            ],
        ]);

        register_block_type('industriesalon/hours', [
            'api_version'     => 2,
            'editor_script'   => 'iss-control-blocks',
            'render_callback' => [$this, 'render_hours_block'],
            'attributes'      => [
                'type'  => ['type' => 'string', 'default' => 'public'],
                'title' => ['type' => 'string', 'default' => ''],
            ],
        ]);

        register_block_type('industriesalon/contact', [
            'api_version'     => 2,
            'editor_script'   => 'iss-control-blocks',
            'render_callback' => [$this, 'render_contact_block'],
            'attributes'      => [
                'title' => ['type' => 'string', 'default' => ''],
            ],
        ]);

        register_block_type('industriesalon/prices', [
            'api_version'     => 2,
            'editor_script'   => 'iss-control-blocks',
            'render_callback' => [$this, 'render_prices_block'],
            'attributes'      => [
                'title' => ['type' => 'string', 'default' => ''],
            ],
        ]);

        register_block_type('industriesalon/faq', [
            'api_version'     => 2,
            'editor_script'   => 'iss-control-blocks',
            'render_callback' => [$this, 'render_faq_block'],
            'attributes'      => [
                'title' => ['type' => 'string', 'default' => ''],
            ],
        ]);

        register_block_type('industriesalon/mission-statement', [
            'api_version'     => 2,
            'editor_script'   => 'iss-control-blocks',
            'render_callback' => [$this, 'render_mission_statement_block'],
            'attributes'      => [
                'title' => ['type' => 'string', 'default' => ''],
            ],
        ]);
    }

    public function render_field_block(array $attributes = []): string {
        return $this->render_field([
            'key'   => $attributes['key'] ?? 'contact.phone',
            'tag'   => $attributes['tagName'] ?? 'div',
            'link'  => $attributes['linkMode'] ?? 'auto',
            'label' => $attributes['label'] ?? '',
        ]);
    }

    public function render_hours_block(array $attributes = []): string {
        return $this->render_hours((string) ($attributes['type'] ?? 'public'), (string) ($attributes['title'] ?? ''));
    }

    public function render_contact_block(array $attributes = []): string {
        return $this->render_contact((string) ($attributes['title'] ?? ''));
    }

    public function render_prices_block(array $attributes = []): string {
        return $this->render_prices((string) ($attributes['title'] ?? ''));
    }

    public function render_faq_block(array $attributes = []): string {
        return $this->render_faq((string) ($attributes['title'] ?? ''));
    }

    public function render_mission_statement_block(array $attributes = []): string {
        return $this->render_mission_statement(
            (string) ($attributes['title'] ?? ''),
            (string) ($attributes['heading'] ?? '')
        );
    }

    public function shortcode_field(array $atts): string {
        $atts = shortcode_atts([
            'key'   => '',
            'label' => '',
            'tag'   => 'div',
            'link'  => 'auto',
        ], $atts, 'iss_field');

        return $this->render_field($atts);
    }

    public function shortcode_hours(array $atts): string {
        $atts = shortcode_atts([
            'type'  => 'public',
            'title' => '',
        ], $atts, 'iss_hours');

        return $this->render_hours((string) $atts['type'], (string) $atts['title']);
    }

    public function shortcode_contact(array $atts): string {
        $atts = shortcode_atts(['title' => ''], $atts, 'iss_contact');
        return $this->render_contact((string) $atts['title']);
    }

    public function shortcode_prices(array $atts): string {
        $atts = shortcode_atts(['title' => ''], $atts, 'iss_prices');
        return $this->render_prices((string) $atts['title']);
    }

    public function shortcode_faq(array $atts): string {
        $atts = shortcode_atts(['title' => ''], $atts, 'iss_faq');
        return $this->render_faq((string) $atts['title']);
    }

    public function shortcode_mission_statement(array $atts): string {
        $atts = shortcode_atts([
            'title'   => '',
            'heading' => '',
        ], $atts, 'iss_mission_statement');
        return $this->render_mission_statement((string) $atts['title'], (string) $atts['heading']);
    }

    public function render_field(array $args): string {
        $key = isset($args['key']) ? (string) $args['key'] : '';
        if ($key === '') {
            return '';
        }

        $tag = isset($args['tag']) ? strtolower((string) $args['tag']) : 'div';
        $allowed_tags = ['div', 'p', 'span', 'strong', 'h1', 'h2', 'h3'];
        if (! in_array($tag, $allowed_tags, true)) {
            $tag = 'div';
        }

        $link_mode = isset($args['link']) ? (string) $args['link'] : 'auto';
        $label = isset($args['label']) ? (string) $args['label'] : '';
        $value = iss_control_get($key);

        if ($value === '' || $value === null) {
            return '';
        }

        $label_html = $label !== '' ? '<span class="iss-control-field__label">' . esc_html($label) . '</span> ' : '';
        $content = esc_html((string) $value);
        $href = '';

        if ($link_mode === 'auto') {
            $href = $this->auto_link_for_key($key, (string) $value);
        } elseif ($link_mode === 'tel') {
            $href = 'tel:' . $this->normalize_phone_href((string) $value);
        } elseif ($link_mode === 'email') {
            $href = 'mailto:' . antispambot((string) $value);
        } elseif ($link_mode === 'url') {
            $href = esc_url((string) $value);
        }

        if ($href !== '' && $link_mode !== 'none') {
            $content = '<a href="' . esc_url($href) . '">' . $content . '</a>';
        }

        $class = 'iss-control-field iss-control-field--' . sanitize_html_class(str_replace('.', '-', $key));

        return sprintf('<%1$s class="%2$s">%3$s%4$s</%1$s>', esc_html($tag), esc_attr($class), $label_html, $content);
    }

    public function render_hours(string $type = 'public', string $title = ''): string {
        $hours = get_option(self::OPTION_HOURS, $this->default_hours());
        $type = $type === 'office' ? 'office' : 'public';
        $group = $hours[$type] ?? [];
        $days = $this->days();
        $exceptions = isset($hours['exceptions']) && is_array($hours['exceptions']) ? $hours['exceptions'] : [];
        $exceptions = array_values(array_filter($exceptions, function ($item) use ($type) {
            if (! is_array($item)) {
                return false;
            }
            $item_type = isset($item['type']) ? (string) $item['type'] : 'public';
            $date = isset($item['date']) ? (string) $item['date'] : '';
            if ($date === '') {
                return false;
            }
            return $item_type === 'both' || $item_type === $type;
        }));
        usort($exceptions, static function ($a, $b) {
            return strcmp((string) ($a['date'] ?? ''), (string) ($b['date'] ?? ''));
        });

        ob_start();
        ?>
        <div class="iss-hours iss-hours--<?php echo esc_attr($type); ?>">
            <?php if ($title !== '') : ?>
                <h3 class="iss-hours__title"><?php echo esc_html($title); ?></h3>
            <?php endif; ?>
            <ul class="iss-hours__list">
                <?php foreach ($days as $slug => $label) : ?>
                    <?php $row = $group['days'][$slug] ?? []; ?>
                    <li class="iss-hours__item">
                        <span class="iss-hours__day"><?php echo esc_html($label); ?></span>
                        <span class="iss-hours__time"><?php echo esc_html($this->format_hours_row($row)); ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
            <?php if (! empty($group['note'])) : ?>
                <p class="iss-hours__note"><?php echo nl2br(esc_html((string) $group['note'])); ?></p>
            <?php endif; ?>
            <?php if (! empty($exceptions)) : ?>
                <div class="iss-hours__special">
                    <h4 class="iss-hours__special-title"><?php esc_html_e('Ausnahmen / Feiertage', 'industriesalon-steuerung'); ?></h4>
                    <ul class="iss-hours__special-list">
                        <?php foreach ($exceptions as $item) : ?>
                            <li class="iss-hours__special-item">
                                <span class="iss-hours__special-date"><?php echo esc_html($this->format_date_label((string) ($item['date'] ?? ''))); ?></span>
                                <span class="iss-hours__special-time"><?php echo esc_html($this->format_hours_row($item)); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return (string) ob_get_clean();
    }

    public function render_contact(string $title = ''): string {
        $contact = get_option(self::OPTION_CONTACT, $this->default_contact());
        $general = get_option(self::OPTION_GENERAL, $this->default_general());
        $maps = get_option(self::OPTION_MAPS, $this->default_maps());

        $address_lines = array_filter([
            $general['site_name'] ?? '',
            $this->compose_address_line(),
            trim(($general['postal_code'] ?? '') . ' ' . ($general['city'] ?? '')),
        ]);

        ob_start();
        ?>
        <div class="iss-contact-card">
            <?php if ($title !== '') : ?>
                <h3 class="iss-contact-card__title"><?php echo esc_html($title); ?></h3>
            <?php endif; ?>

            <?php if (! empty($address_lines)) : ?>
                <div class="iss-contact-card__address">
                    <?php foreach ($address_lines as $line) : ?>
                        <div><?php echo esc_html($line); ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <ul class="iss-contact-card__list">
                <?php if (! empty($contact['phone'])) : ?>
                    <li><a href="<?php echo esc_url('tel:' . $this->normalize_phone_href((string) $contact['phone'])); ?>"><?php echo esc_html((string) $contact['phone']); ?></a></li>
                <?php endif; ?>
                <?php if (! empty($contact['email'])) : ?>
                    <li><a href="<?php echo esc_url('mailto:' . antispambot((string) $contact['email'])); ?>"><?php echo esc_html((string) $contact['email']); ?></a></li>
                <?php endif; ?>
                <?php if (! empty($contact['booking_email'])) : ?>
                    <li><a href="<?php echo esc_url('mailto:' . antispambot((string) $contact['booking_email'])); ?>"><?php echo esc_html((string) $contact['booking_email']); ?></a></li>
                <?php endif; ?>
                <?php if (! empty($contact['website'])) : ?>
                    <li><a href="<?php echo esc_url((string) $contact['website']); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html((string) $contact['website']); ?></a></li>
                <?php endif; ?>
                <?php if (! empty($maps['google_maps_url'])) : ?>
                    <li><a href="<?php echo esc_url((string) $maps['google_maps_url']); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Google Maps', 'industriesalon-steuerung'); ?></a></li>
                <?php endif; ?>
            </ul>
        </div>
        <?php
        return (string) ob_get_clean();
    }

    public function render_prices(string $title = ''): string {
        $prices = get_option(self::OPTION_PRICES, $this->default_prices());
        $items = [
            'adult'   => __('Erwachsene', 'industriesalon-steuerung'),
            'reduced' => __('Ermäßigt', 'industriesalon-steuerung'),
            'group'   => __('Gruppen', 'industriesalon-steuerung'),
            'school'  => __('Schulklassen', 'industriesalon-steuerung'),
            'tour'    => __('Führung', 'industriesalon-steuerung'),
            'rental'  => __('Raumvermietung', 'industriesalon-steuerung'),
        ];

        ob_start();
        ?>
        <div class="iss-prices">
            <?php if ($title !== '') : ?>
                <h3 class="iss-prices__title"><?php echo esc_html($title); ?></h3>
            <?php endif; ?>
            <ul class="iss-prices__list">
                <?php foreach ($items as $key => $label) : ?>
                    <?php if (! empty($prices[$key])) : ?>
                        <li class="iss-prices__item">
                            <span class="iss-prices__label"><?php echo esc_html($label); ?></span>
                            <span class="iss-prices__value"><?php echo esc_html((string) $prices[$key]); ?></span>
                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>
            <?php if (! empty($prices['note'])) : ?>
                <p class="iss-prices__note"><?php echo nl2br(esc_html((string) $prices['note'])); ?></p>
            <?php endif; ?>
        </div>
        <?php
        return (string) ob_get_clean();
    }

    public function render_faq(string $title = ''): string {
        $faq = get_option(self::OPTION_FAQ, $this->default_faq());
        $items = $faq['items'] ?? [];
        if (empty($items)) {
            return '';
        }

        ob_start();
        ?>
        <div class="iss-faq-list">
            <?php if ($title !== '') : ?>
                <h3 class="iss-faq-list__title"><?php echo esc_html($title); ?></h3>
            <?php endif; ?>
            <?php foreach ($items as $item) : ?>
                <?php if (empty($item['question']) && empty($item['answer'])) { continue; } ?>
                <details class="iss-faq-item">
                    <summary class="iss-faq-item__question"><?php echo esc_html((string) ($item['question'] ?? '')); ?></summary>
                    <div class="iss-faq-item__answer"><?php echo wpautop(wp_kses_post((string) ($item['answer'] ?? ''))); ?></div>
                </details>
            <?php endforeach; ?>
        </div>
        <?php
        return (string) ob_get_clean();
    }

    public function render_mission_statement(string $title = '', string $heading = ''): string {
        $mission_statement = get_option(self::OPTION_MISSION_STATEMENT, $this->default_mission_statement());
        $content = trim((string) ($mission_statement['content'] ?? ''));
        if ($content === '') {
            return '';
        }

        ob_start();
        ?>
        <div class="iss-heading iss-mission-statement">
            <?php if ($title !== '') : ?>
                <p class="iss-kicker iss-mission-statement__title"><?php echo esc_html($title); ?></p>
            <?php endif; ?>
            <?php if ($heading !== '') : ?>
                <h2 class="iss-heading__title iss-mission-statement__heading"><?php echo esc_html($heading); ?></h2>
            <?php endif; ?>
            <p class="iss-heading__text iss-mission-statement__text"><?php echo nl2br(esc_html($content)); ?></p>
        </div>
        <?php
        return (string) ob_get_clean();
    }

    private function hours_table(string $type, array $group, array $days): void {
        $group = wp_parse_args($group, ['note' => '', 'days' => []]);
        ?>
        <table class="widefat striped iss-hours-table">
            <thead>
                <tr>
                    <th><?php esc_html_e('Tag', 'industriesalon-steuerung'); ?></th>
                    <th><?php esc_html_e('Geschlossen', 'industriesalon-steuerung'); ?></th>
                    <th><?php esc_html_e('Von', 'industriesalon-steuerung'); ?></th>
                    <th><?php esc_html_e('Bis', 'industriesalon-steuerung'); ?></th>
                    <th><?php esc_html_e('Hinweis', 'industriesalon-steuerung'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($days as $slug => $label) : ?>
                    <?php $row = wp_parse_args($group['days'][$slug] ?? [], ['closed' => 0, 'open' => '', 'close' => '', 'note' => '']); ?>
                    <tr>
                        <th scope="row"><?php echo esc_html($label); ?></th>
                        <td><input type="checkbox" name="<?php echo esc_attr(self::OPTION_HOURS . '[' . $type . '][days][' . $slug . '][closed]'); ?>" value="1" <?php checked(! empty($row['closed'])); ?>></td>
                        <td><input type="time" class="regular-text iss-time" step="300" name="<?php echo esc_attr(self::OPTION_HOURS . '[' . $type . '][days][' . $slug . '][open]'); ?>" value="<?php echo esc_attr((string) $row['open']); ?>" placeholder="10:00"></td>
                        <td><input type="time" class="regular-text iss-time" step="300" name="<?php echo esc_attr(self::OPTION_HOURS . '[' . $type . '][days][' . $slug . '][close]'); ?>" value="<?php echo esc_attr((string) $row['close']); ?>" placeholder="18:00"></td>
                        <td><input type="text" class="regular-text" name="<?php echo esc_attr(self::OPTION_HOURS . '[' . $type . '][days][' . $slug . '][note]'); ?>" value="<?php echo esc_attr((string) $row['note']); ?>"></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
        $this->textarea_field(self::OPTION_HOURS . '[' . $type . ']', 'note', __('Globaler Hinweis', 'industriesalon-steuerung'), $group['note'] ?? '', 3, false);
    }

    private function special_date_fields($index, array $item): void {
        $item = wp_parse_args($item, ['date' => '', 'type' => 'public', 'closed' => 0, 'open' => '', 'close' => '', 'note' => '']);
        ?>
        <div class="iss-special-item" data-iss-special-item>
            <div class="iss-grid iss-grid--2">
                <div class="iss-field">
                    <label><?php esc_html_e('Datum', 'industriesalon-steuerung'); ?></label>
                    <input type="date" class="regular-text" name="<?php echo esc_attr(self::OPTION_HOURS . '[exceptions][' . $index . '][date]'); ?>" value="<?php echo esc_attr((string) $item['date']); ?>">
                </div>
                <div class="iss-field">
                    <label><?php esc_html_e('Art', 'industriesalon-steuerung'); ?></label>
                    <select class="regular-text" name="<?php echo esc_attr(self::OPTION_HOURS . '[exceptions][' . $index . '][type]'); ?>">
                        <option value="public" <?php selected((string) $item['type'], 'public'); ?>><?php esc_html_e('Öffnungszeiten', 'industriesalon-steuerung'); ?></option>
                        <option value="office" <?php selected((string) $item['type'], 'office'); ?>><?php esc_html_e('Bürozeiten', 'industriesalon-steuerung'); ?></option>
                        <option value="both" <?php selected((string) $item['type'], 'both'); ?>><?php esc_html_e('Beides', 'industriesalon-steuerung'); ?></option>
                    </select>
                </div>
                <label class="iss-checkbox">
                    <input type="checkbox" name="<?php echo esc_attr(self::OPTION_HOURS . '[exceptions][' . $index . '][closed]'); ?>" value="1" <?php checked(! empty($item['closed'])); ?>>
                    <span><?php esc_html_e('Geschlossen', 'industriesalon-steuerung'); ?></span>
                </label>
                <div class="iss-field">
                    <label><?php esc_html_e('Von', 'industriesalon-steuerung'); ?></label>
                    <input type="time" class="regular-text iss-time" step="300" name="<?php echo esc_attr(self::OPTION_HOURS . '[exceptions][' . $index . '][open]'); ?>" value="<?php echo esc_attr((string) $item['open']); ?>">
                </div>
                <div class="iss-field">
                    <label><?php esc_html_e('Bis', 'industriesalon-steuerung'); ?></label>
                    <input type="time" class="regular-text iss-time" step="300" name="<?php echo esc_attr(self::OPTION_HOURS . '[exceptions][' . $index . '][close]'); ?>" value="<?php echo esc_attr((string) $item['close']); ?>">
                </div>
                <div class="iss-field">
                    <label><?php esc_html_e('Hinweis', 'industriesalon-steuerung'); ?></label>
                    <input type="text" class="regular-text" name="<?php echo esc_attr(self::OPTION_HOURS . '[exceptions][' . $index . '][note]'); ?>" value="<?php echo esc_attr((string) $item['note']); ?>">
                </div>
            </div>
            <p><button type="button" class="button-link-delete" data-iss-remove-special><?php esc_html_e('Sondertermin entfernen', 'industriesalon-steuerung'); ?></button></p>
        </div>
        <?php
    }

    private function faq_item_fields($index, array $item): void {
        $question = isset($item['question']) ? (string) $item['question'] : '';
        $answer = isset($item['answer']) ? (string) $item['answer'] : '';
        ?>
        <div class="iss-faq-item-editor" data-iss-faq-item>
            <div class="iss-grid iss-grid--1">
                <?php $this->text_field(self::OPTION_FAQ . '[items][' . $index . ']', 'question', __('Frage', 'industriesalon-steuerung'), $question, false, 'Ist der Industriesalon barrierefrei?'); ?>
                <?php $this->textarea_field(self::OPTION_FAQ . '[items][' . $index . ']', 'answer', __('Antwort', 'industriesalon-steuerung'), $answer, 4, false, 'Der Eingangsbereich ist stufenlos erreichbar. Weitere Hinweise finden Sie unter Barrierefreiheit.'); ?>
            </div>
            <p><button type="button" class="button-link-delete" data-iss-remove-faq><?php esc_html_e('Eintrag entfernen', 'industriesalon-steuerung'); ?></button></p>
        </div>
        <?php
    }

    private function text_field(string $base, string $key, string $label, string $value, bool $wrap = true, string $placeholder = '', string $help = ''): void {
        $name = $base . '[' . $key . ']';
        $field = '<div class="iss-field"><label>' . esc_html($label) . '</label><input type="text" class="regular-text" name="' . esc_attr($name) . '" value="' . esc_attr($value) . '" placeholder="' . esc_attr($placeholder) . '">';
        if ($help !== '') {
            $field .= '<p class="description iss-field-help">' . esc_html($help) . '</p>';
        }
        $field .= '</div>';
        echo $field; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    private function email_field(string $base, string $key, string $label, string $value, string $placeholder = '', string $help = ''): void {
        $name = $base . '[' . $key . ']';
        $field = '<div class="iss-field"><label>' . esc_html($label) . '</label><input type="email" class="regular-text" name="' . esc_attr($name) . '" value="' . esc_attr($value) . '" placeholder="' . esc_attr($placeholder) . '">';
        if ($help !== '') {
            $field .= '<p class="description iss-field-help">' . esc_html($help) . '</p>';
        }
        $field .= '</div>';
        echo $field; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    private function url_field(string $base, string $key, string $label, string $value, string $placeholder = '', string $help = ''): void {
        $name = $base . '[' . $key . ']';
        $field = '<div class="iss-field"><label>' . esc_html($label) . '</label><input type="url" class="regular-text code" name="' . esc_attr($name) . '" value="' . esc_attr($value) . '" placeholder="' . esc_attr($placeholder) . '">';
        if ($help !== '') {
            $field .= '<p class="description iss-field-help">' . esc_html($help) . '</p>';
        }
        $field .= '</div>';
        echo $field; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    private function textarea_field(string $base, string $key, string $label, string $value, int $rows = 4, bool $wrap = true, string $placeholder = '', string $help = ''): void {
        $name = $base . '[' . $key . ']';
        $field = '<div class="iss-field"><label>' . esc_html($label) . '</label><textarea class="large-text" rows="' . esc_attr((string) $rows) . '" name="' . esc_attr($name) . '" placeholder="' . esc_attr($placeholder) . '">' . esc_textarea($value) . '</textarea>';
        if ($help !== '') {
            $field .= '<p class="description iss-field-help">' . esc_html($help) . '</p>';
        }
        $field .= '</div>';
        echo $field; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    private function checkbox_field(string $base, string $key, string $label, bool $checked): void {
        $name = $base . '[' . $key . ']';
        echo '<label class="iss-checkbox"><input type="checkbox" name="' . esc_attr($name) . '" value="1" ' . checked($checked, true, false) . '> <span>' . esc_html($label) . '</span></label>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    public function sanitize_general($input): array {
        $input = is_array($input) ? $input : [];
        return [
            'site_name'   => sanitize_text_field($input['site_name'] ?? ''),
            'tagline'     => sanitize_text_field($input['tagline'] ?? ''),
            'street'      => sanitize_text_field($input['street'] ?? ''),
            'postal_code' => sanitize_text_field($input['postal_code'] ?? ''),
            'city'        => sanitize_text_field($input['city'] ?? ''),
            'district'    => sanitize_text_field($input['district'] ?? ''),
            'arrival'     => sanitize_textarea_field($input['arrival'] ?? ''),
            'visit_note'  => sanitize_textarea_field($input['visit_note'] ?? ''),
        ];
    }

    public function sanitize_contact($input): array {
        $input = is_array($input) ? $input : [];
        return [
            'phone'          => sanitize_text_field($input['phone'] ?? ''),
            'email'          => sanitize_email($input['email'] ?? ''),
            'booking_email'  => sanitize_email($input['booking_email'] ?? ''),
            'contact_person' => sanitize_text_field($input['contact_person'] ?? ''),
            'website'        => esc_url_raw($input['website'] ?? ''),
            'booking_url'    => esc_url_raw($input['booking_url'] ?? ''),
            'instagram'      => esc_url_raw($input['instagram'] ?? ''),
            'facebook'       => esc_url_raw($input['facebook'] ?? ''),
        ];
    }

    public function sanitize_maps($input): array {
        $input = is_array($input) ? $input : [];
        $embed = $input['google_maps_embed'] ?? '';

        if (is_string($embed) && strpos($embed, '<iframe') !== false) {
            $embed = wp_kses($embed, [
                'iframe' => [
                    'src'             => true,
                    'width'           => true,
                    'height'          => true,
                    'style'           => true,
                    'loading'         => true,
                    'allowfullscreen' => true,
                    'referrerpolicy'  => true,
                ],
            ]);
        } else {
            $embed = esc_url_raw((string) $embed);
        }

        return [
            'google_maps_url'   => esc_url_raw($input['google_maps_url'] ?? ''),
            'google_maps_embed' => $embed,
        ];
    }

    public function sanitize_hours($input): array {
        $input = is_array($input) ? $input : [];
        $days = $this->days();
        $output = $this->default_hours();

        foreach (['public', 'office'] as $type) {
            $group = isset($input[$type]) && is_array($input[$type]) ? $input[$type] : [];
            $output[$type]['note'] = sanitize_textarea_field($group['note'] ?? '');

            foreach ($days as $slug => $label) {
                $row = isset($group['days'][$slug]) && is_array($group['days'][$slug]) ? $group['days'][$slug] : [];
                $open = $this->sanitize_time_value($row['open'] ?? '');
                $close = $this->sanitize_time_value($row['close'] ?? '');
                if ($open !== '' && $close !== '' && strcmp($open, $close) > 0) {
                    $close = '';
                }
                $output[$type]['days'][$slug] = [
                    'closed' => ! empty($row['closed']) ? 1 : 0,
                    'open'   => $open,
                    'close'  => $close,
                    'note'   => sanitize_text_field($row['note'] ?? ''),
                ];
            }
        }

        $exceptions = isset($input['exceptions']) && is_array($input['exceptions']) ? array_values($input['exceptions']) : [];
        $exceptions = array_slice($exceptions, 0, 20);
        $clean_exceptions = [];
        foreach ($exceptions as $item) {
            if (! is_array($item)) {
                continue;
            }
            $date = $this->sanitize_date_value($item['date'] ?? '');
            $type = isset($item['type']) ? (string) $item['type'] : 'public';
            if (! in_array($type, ['public', 'office', 'both'], true)) {
                $type = 'public';
            }
            $open = $this->sanitize_time_value($item['open'] ?? '');
            $close = $this->sanitize_time_value($item['close'] ?? '');
            if ($open !== '' && $close !== '' && strcmp($open, $close) > 0) {
                $close = '';
            }

            $row = [
                'date' => $date,
                'type' => $type,
                'closed' => ! empty($item['closed']) ? 1 : 0,
                'open' => $open,
                'close' => $close,
                'note' => sanitize_text_field($item['note'] ?? ''),
            ];
            if ($row['date'] === '') {
                continue;
            }
            $clean_exceptions[] = $row;
        }
        $output['exceptions'] = $clean_exceptions;

        return $output;
    }

    public function sanitize_accessibility($input): array {
        $input = is_array($input) ? $input : [];
        return [
            'wheelchair'        => ! empty($input['wheelchair']) ? 1 : 0,
            'accessible_toilet' => ! empty($input['accessible_toilet']) ? 1 : 0,
            'elevator'          => ! empty($input['elevator']) ? 1 : 0,
            'parking'           => ! empty($input['parking']) ? 1 : 0,
            'companion'         => ! empty($input['companion']) ? 1 : 0,
            'note'              => sanitize_textarea_field($input['note'] ?? ''),
        ];
    }

    public function sanitize_prices($input): array {
        $input = is_array($input) ? $input : [];
        return [
            'adult'   => sanitize_text_field($input['adult'] ?? ''),
            'reduced' => sanitize_text_field($input['reduced'] ?? ''),
            'group'   => sanitize_text_field($input['group'] ?? ''),
            'school'  => sanitize_text_field($input['school'] ?? ''),
            'tour'    => sanitize_text_field($input['tour'] ?? ''),
            'rental'  => sanitize_text_field($input['rental'] ?? ''),
            'note'    => sanitize_textarea_field($input['note'] ?? ''),
        ];
    }

    public function sanitize_faq($input): array {
        $input = is_array($input) ? $input : [];
        $items = isset($input['items']) && is_array($input['items']) ? array_values($input['items']) : [];
        $items = array_slice($items, 0, 10);

        $clean = [];
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }
            $question = sanitize_text_field($item['question'] ?? '');
            $answer = wp_kses_post($item['answer'] ?? '');
            if ($question === '' && trim(wp_strip_all_tags($answer)) === '') {
                continue;
            }
            $clean[] = ['question' => $question, 'answer' => $answer];
        }

        return ['items' => $clean];
    }

    public function sanitize_mission_statement($input): array {
        $input = is_array($input) ? $input : [];
        return [
            'content' => sanitize_textarea_field($input['content'] ?? ''),
        ];
    }

    private function sanitize_time_value($value): string {
        $value = sanitize_text_field((string) $value);
        if (preg_match('/^([0-1]?[0-9]|2[0-3]):([0-5][0-9])$/', $value, $m)) {
            return sprintf('%02d:%02d', (int) $m[1], (int) $m[2]);
        }
        return '';
    }

    private function sanitize_date_value($value): string {
        $value = sanitize_text_field((string) $value);
        if (! preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m)) {
            return '';
        }
        $year = (int) $m[1];
        $month = (int) $m[2];
        $day = (int) $m[3];
        if (! checkdate($month, $day, $year)) {
            return '';
        }
        return sprintf('%04d-%02d-%02d', $year, $month, $day);
    }

    private function format_date_label(string $value): string {
        $timestamp = strtotime($value);
        if ($timestamp === false) {
            return $value;
        }
        return wp_date('d.m.Y', $timestamp);
    }

    public function get_all_data(): array {
        return [
            'general'       => get_option(self::OPTION_GENERAL, $this->default_general()),
            'contact'       => get_option(self::OPTION_CONTACT, $this->default_contact()),
            'maps'          => get_option(self::OPTION_MAPS, $this->default_maps()),
            'hours'         => get_option(self::OPTION_HOURS, $this->default_hours()),
            'accessibility' => get_option(self::OPTION_ACCESSIBILITY, $this->default_accessibility()),
            'prices'        => get_option(self::OPTION_PRICES, $this->default_prices()),
            'faq'           => get_option(self::OPTION_FAQ, $this->default_faq()),
            'mission_statement' => get_option(self::OPTION_MISSION_STATEMENT, $this->default_mission_statement()),
        ];
    }

    public function get_field_value(string $key, $default = '') {
        $key = trim($key);
        if ($key === '') {
            return $default;
        }

        $aliases = [
            'address.street'      => 'general.street',
            'address.postal_code' => 'general.postal_code',
            'address.city'        => 'general.city',
            'address.district'    => 'general.district',
        ];

        if (isset($aliases[$key])) {
            $key = $aliases[$key];
        }

        if ($key === 'address.full' || $key === 'general.address_full') {
            $full = $this->compose_full_address();
            return $full !== '' ? $full : $default;
        }

        if (strpos($key, '.') === false) {
            return $default;
        }

        [$section, $field] = explode('.', $key, 2);
        $data = $this->get_all_data();
        if (! isset($data[$section]) || ! is_array($data[$section])) {
            return $default;
        }

        return $data[$section][$field] ?? $default;
    }

    public function get_section(string $section): array {
        $data = $this->get_all_data();
        return isset($data[$section]) && is_array($data[$section]) ? $data[$section] : [];
    }

    private function auto_link_for_key(string $key, string $value): string {
        if (str_contains($key, 'email')) {
            return 'mailto:' . antispambot($value);
        }
        if (str_contains($key, 'phone')) {
            return 'tel:' . $this->normalize_phone_href($value);
        }
        if (str_contains($key, 'url') || str_contains($key, 'website') || str_contains($key, 'instagram') || str_contains($key, 'facebook') || str_contains($key, 'maps')) {
            return $value;
        }
        return '';
    }

    private function normalize_phone_href(string $value): string {
        return preg_replace('/[^0-9\+]/', '', $value) ?: '';
    }

    private function compose_address_line(): string {
        $general = get_option(self::OPTION_GENERAL, $this->default_general());
        return trim(implode(', ', array_filter([$general['street'] ?? '', $general['district'] ?? ''])));
    }

    private function compose_full_address(): string {
        $general = get_option(self::OPTION_GENERAL, $this->default_general());
        $parts = array_filter([$general['street'] ?? '', trim(($general['postal_code'] ?? '') . ' ' . ($general['city'] ?? '')), $general['district'] ?? '']);
        return implode(', ', $parts);
    }

    private function format_hours_row(array $row): string {
        $row = wp_parse_args($row, ['closed' => 0, 'open' => '', 'close' => '', 'note' => '']);
        if (! empty($row['closed'])) {
            return ! empty($row['note']) ? (string) $row['note'] : __('geschlossen', 'industriesalon-steuerung');
        }

        $time = trim((string) $row['open']);
        if ($time !== '' && ! empty($row['close'])) {
            $time .= '–' . (string) $row['close'];
        } elseif (! empty($row['close'])) {
            $time = (string) $row['close'];
        }

        if ($time === '' && ! empty($row['note'])) {
            return (string) $row['note'];
        }
        if ($time !== '' && ! empty($row['note'])) {
            return $time . ' · ' . (string) $row['note'];
        }
        return $time !== '' ? $time : '—';
    }

    private function days(): array {
        return [
            'monday'    => __('Montag', 'industriesalon-steuerung'),
            'tuesday'   => __('Dienstag', 'industriesalon-steuerung'),
            'wednesday' => __('Mittwoch', 'industriesalon-steuerung'),
            'thursday'  => __('Donnerstag', 'industriesalon-steuerung'),
            'friday'    => __('Freitag', 'industriesalon-steuerung'),
            'saturday'  => __('Samstag', 'industriesalon-steuerung'),
            'sunday'    => __('Sonntag', 'industriesalon-steuerung'),
        ];
    }

    private function default_general(): array {
        return ['site_name' => '', 'tagline' => '', 'street' => '', 'postal_code' => '', 'city' => '', 'district' => '', 'arrival' => '', 'visit_note' => ''];
    }

    private function default_contact(): array {
        return ['phone' => '', 'email' => '', 'booking_email' => '', 'contact_person' => '', 'website' => '', 'booking_url' => '', 'instagram' => '', 'facebook' => ''];
    }

    private function default_maps(): array {
        return ['google_maps_url' => '', 'google_maps_embed' => ''];
    }

    private function default_hours(): array {
        $days = [];
        foreach (array_keys($this->days()) as $slug) {
            $days[$slug] = ['closed' => 0, 'open' => '', 'close' => '', 'note' => ''];
        }
        return ['public' => ['note' => '', 'days' => $days], 'office' => ['note' => '', 'days' => $days], 'exceptions' => []];
    }

    private function default_accessibility(): array {
        return ['wheelchair' => 0, 'accessible_toilet' => 0, 'elevator' => 0, 'parking' => 0, 'companion' => 0, 'note' => ''];
    }

    private function default_prices(): array {
        return ['adult' => '', 'reduced' => '', 'group' => '', 'school' => '', 'tour' => '', 'rental' => '', 'note' => ''];
    }

    private function default_faq(): array {
        return ['items' => []];
    }

    private function default_mission_statement(): array {
        return ['content' => ''];
    }
}

register_activation_hook(__FILE__, ['Industriesalon_Steuerung', 'activate']);
register_deactivation_hook(__FILE__, ['Industriesalon_Steuerung', 'deactivate']);
Industriesalon_Steuerung::instance();

function iss_control_get(string $key, $default = '') {
    return Industriesalon_Steuerung::instance()->get_field_value($key, $default);
}

function iss_control_get_section(string $section): array {
    return Industriesalon_Steuerung::instance()->get_section($section);
}
