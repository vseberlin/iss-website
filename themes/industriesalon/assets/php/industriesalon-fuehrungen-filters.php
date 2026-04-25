<?php

if (!defined('ABSPATH')) {
    exit;
}

function iss_fuehrungen_filter_tab_definitions(): array
{
    return [
        [
            'key' => 'gruppen',
            'label' => 'Gruppen',
            'aliases' => ['gruppen', 'gruppe'],
        ],
        [
            'key' => 'individuell',
            'label' => 'Individuell',
            'aliases' => ['individuell'],
        ],
        [
            'key' => 'kinder-familie',
            'label' => 'Kinder/Familie',
            'aliases' => ['kinder-familie', 'kinderfamilie', 'kinder_familie'],
        ],
        [
            'key' => 'besonders',
            'label' => 'Besonders',
            'aliases' => ['besonders'],
        ],
        [
            'key' => 'bus',
            'label' => 'Bus',
            'aliases' => ['bus'],
        ],
        [
            'key' => 'regular',
            'label' => 'Regular',
            'aliases' => ['regular'],
        ],
    ];
}

function iss_fuehrungen_filter_tabs(): array
{
    $tabs = [
        [
            'slug' => 'all',
            'label' => 'Alle',
        ],
    ];

    $terms_by_slug = [];
    if (taxonomy_exists('fuehrung_typ')) {
        $all_terms = get_terms([
            'taxonomy'   => 'fuehrung_typ',
            'hide_empty' => false,
        ]);
        if (!is_wp_error($all_terms)) {
            foreach ($all_terms as $term) {
                if ($term instanceof WP_Term) {
                    $terms_by_slug[(string) $term->slug] = $term;
                }
            }
        }
    }

    $seen_slugs = [];
    foreach (iss_fuehrungen_filter_tab_definitions() as $definition) {
        $resolved_slug = '';
        foreach ($definition['aliases'] as $alias) {
            $alias = sanitize_title((string) $alias);
            if ($alias !== '' && isset($terms_by_slug[$alias])) {
                $resolved_slug = (string) $terms_by_slug[$alias]->slug;
                break;
            }
        }

        if ($resolved_slug === '') {
            $resolved_slug = sanitize_title((string) $definition['key']);
        }

        if ($resolved_slug === '' || isset($seen_slugs[$resolved_slug])) {
            continue;
        }

        $tabs[] = [
            'slug' => $resolved_slug,
            'label' => (string) $definition['label'],
        ];
        $seen_slugs[$resolved_slug] = true;
    }

    return $tabs;
}

function iss_fuehrungen_query_block_class_name(array $block, $instance): string
{
    $attrs = isset($block['attrs']) && is_array($block['attrs']) ? $block['attrs'] : [];
    if ($instance instanceof WP_Block && isset($instance->attributes) && is_array($instance->attributes)) {
        $attrs = array_merge($attrs, $instance->attributes);
    }

    return isset($attrs['className']) ? (string) $attrs['className'] : '';
}

function iss_fuehrungen_render_filter_tabs_markup(): string
{
    $tabs = iss_fuehrungen_filter_tabs();
    if (count($tabs) <= 1) {
        return '';
    }

    $markup = '<div class="iss-fuehrungen-filters" data-iss-fuehrungen-tabs role="tablist" aria-label="' . esc_attr__('Führungen filtern', 'industriesalon') . '">';
    foreach ($tabs as $index => $tab) {
        $is_active = $index === 0;
        $markup .= sprintf(
            '<button type="button" class="iss-fuehrungen-filters__link%s" data-iss-filter="%s" role="tab" aria-selected="%s">%s</button>',
            $is_active ? ' is-active' : '',
            esc_attr((string) $tab['slug']),
            $is_active ? 'true' : 'false',
            esc_html((string) $tab['label'])
        );
    }
    $markup .= '</div>';

    return $markup;
}

add_filter('render_block', function (string $block_content, array $block, $instance): string {
    if (is_admin()) {
        return $block_content;
    }

    if (($block['blockName'] ?? '') !== 'core/query') {
        return $block_content;
    }

    $class_name = iss_fuehrungen_query_block_class_name($block, $instance);
    if (strpos($class_name, 'iss-fuehrungen-query') === false) {
        return $block_content;
    }

    if (strpos($block_content, 'data-iss-fuehrungen-tabs') !== false) {
        return $block_content;
    }

    $tabs_markup = iss_fuehrungen_render_filter_tabs_markup();
    if ($tabs_markup === '') {
        return $block_content;
    }

    return $tabs_markup . $block_content;
}, 10, 3);

add_filter('render_block', function (string $block_content, array $block, $instance): string {
    if (is_admin()) {
        return $block_content;
    }

    if (($block['blockName'] ?? '') !== 'core/group') {
        return $block_content;
    }

    $class_name = isset($block['attrs']['className']) ? (string) $block['attrs']['className'] : '';
    if (strpos($class_name, 'iss-fuehrung-card') === false) {
        return $block_content;
    }

    if (strpos($block_content, 'data-iss-tour-types=') !== false) {
        return $block_content;
    }

    $post_id = 0;
    if ($instance instanceof WP_Block && isset($instance->context['postId'])) {
        $post_id = (int) $instance->context['postId'];
    }
    if ($post_id <= 0 && isset($GLOBALS['post']) && $GLOBALS['post'] instanceof WP_Post) {
        $post_id = (int) $GLOBALS['post']->ID;
    }
    if ($post_id <= 0) {
        return $block_content;
    }

    $term_slugs = wp_get_post_terms($post_id, 'fuehrung_typ', ['fields' => 'slugs']);
    if (is_wp_error($term_slugs)) {
        $term_slugs = [];
    }
    $term_slugs = array_values(array_filter(array_map(static function ($slug) {
        return sanitize_title((string) $slug);
    }, is_array($term_slugs) ? $term_slugs : [])));

    $term_attr = esc_attr(implode(' ', array_unique($term_slugs)));
    $updated = preg_replace('/^<div\\b/', '<div data-iss-tour-types="' . $term_attr . '"', $block_content, 1);

    return is_string($updated) ? $updated : $block_content;
}, 11, 3);

add_action('wp_enqueue_scripts', function (): void {
    if (is_admin()) {
        return;
    }

    $style = <<<'CSS'
.iss-fuehrungen-filters {
  display: flex;
  flex-wrap: wrap;
  gap: 0.65rem;
  margin: 0 0 1.25rem;
}
.iss-fuehrungen-filters__link {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: 2.25rem;
  padding: 0.45rem 0.95rem;
  border: 1px solid var(--iss-border, rgba(30, 30, 30, 0.2));
  border-radius: 999px;
  background: transparent;
  color: var(--iss-black, #1e1e1e);
  font: inherit;
  font-size: 0.85rem;
  font-weight: 600;
  text-decoration: none;
  cursor: pointer;
  transition: background-color .2s ease, border-color .2s ease, color .2s ease;
}
.iss-fuehrungen-filters__link:hover,
.iss-fuehrungen-filters__link:focus-visible,
.iss-fuehrungen-filters__link.is-active {
  border-color: var(--iss-red, #e81d25);
  background: var(--iss-red, #e81d25);
  color: #fff;
}
.iss-fuehrung-item--hidden {
  display: none !important;
}
.iss-fuehrungen-query__empty--js {
  margin-top: 1rem;
}
CSS;

    wp_register_style('industriesalon-fuehrungen-filters-helper', false, [], null);
    wp_enqueue_style('industriesalon-fuehrungen-filters-helper');
    wp_add_inline_style('industriesalon-fuehrungen-filters-helper', $style);

    $script = <<<'JS'
document.addEventListener('DOMContentLoaded', function () {
  var tabGroups = document.querySelectorAll('[data-iss-fuehrungen-tabs]');
  if (!tabGroups.length) {
    return;
  }

  tabGroups.forEach(function (tabGroup) {
    var query = tabGroup.nextElementSibling;
    if (!query || !query.classList || !query.classList.contains('iss-fuehrungen-query')) {
      query = tabGroup.parentElement ? tabGroup.parentElement.querySelector('.iss-fuehrungen-query') : null;
    }
    if (!query) {
      return;
    }

    var cards = Array.prototype.slice.call(query.querySelectorAll('.iss-fuehrung-card'));
    if (!cards.length) {
      return;
    }
    var items = cards.map(function (card) {
      var item = card.closest('li.wp-block-post');
      return {
        card: card,
        item: item || card
      };
    });

    var buttons = Array.prototype.slice.call(tabGroup.querySelectorAll('[data-iss-filter]'));
    if (!buttons.length) {
      return;
    }

    var empty = query.querySelector('[data-iss-filter-empty]');
    if (!empty) {
      empty = document.createElement('p');
      empty.className = 'iss-fuehrungen-query__empty iss-fuehrungen-query__empty--js';
      empty.setAttribute('data-iss-filter-empty', '1');
      empty.textContent = 'Für diesen Filter sind aktuell keine Führungen eingetragen.';
      empty.style.display = 'none';
      query.appendChild(empty);
    }

    function setActiveButton(activeSlug) {
      buttons.forEach(function (button) {
        var isActive = button.getAttribute('data-iss-filter') === activeSlug;
        button.classList.toggle('is-active', isActive);
        button.setAttribute('aria-selected', isActive ? 'true' : 'false');
      });
    }

    function normalizeTerm(term) {
      var raw = String(term || '').toLowerCase().trim().replace(/_/g, '-').replace(/\s+/g, '-');
      if (!raw) {
        return '';
      }
      var map = {
        'group': 'gruppen',
        'gruppe': 'gruppen',
        'gruppen': 'gruppen',
        'individual': 'individuell',
        'individuell': 'individuell',
        'special': 'besonders',
        'besonders': 'besonders',
        'kinderfamilie': 'kinder-familie',
        'kinder-familie': 'kinder-familie',
        'kinder/familie': 'kinder-familie',
        'family': 'kinder-familie',
        'familie': 'kinder-familie',
        'bus': 'bus',
        'regular': 'regular'
      };
      return map[raw] || raw;
    }

    function parseCardTerms(card) {
      var terms = [];
      var attr = card.getAttribute('data-iss-tour-types') || '';
      if (attr.trim() !== '') {
        terms = terms.concat(attr.split(/\s+/).filter(Boolean));
      }

      var links = card.querySelectorAll('.iss-fuehrung-card__terms a');
      links.forEach(function (link) {
        var href = String(link.getAttribute('href') || '');
        if (href) {
          var path = href.replace(/^https?:\/\/[^/]+/i, '').replace(/\/+$/, '');
          var slug = path.substring(path.lastIndexOf('/') + 1);
          if (slug) {
            terms.push(slug);
          }
        }
        var text = String(link.textContent || '').trim();
        if (text) {
          terms.push(text);
        }
      });

      return terms.map(normalizeTerm).filter(Boolean);
    }

    function applyFilter(slug) {
      var visibleCount = 0;

      items.forEach(function (entry) {
        var terms = parseCardTerms(entry.card);
        var visible = (slug === 'all') || terms.indexOf(slug) !== -1;
        entry.item.classList.toggle('iss-fuehrung-item--hidden', !visible);
        if (visible) {
          visibleCount += 1;
        }
      });

      empty.style.display = visibleCount === 0 ? '' : 'none';
      setActiveButton(slug);
    }

    buttons.forEach(function (button) {
      button.addEventListener('click', function () {
        var slug = button.getAttribute('data-iss-filter') || 'all';
        applyFilter(slug);
      });
    });

    applyFilter('all');
  });
});
JS;

    wp_register_script('industriesalon-fuehrungen-filters-helper', '', [], null, true);
    wp_enqueue_script('industriesalon-fuehrungen-filters-helper');
    wp_add_inline_script('industriesalon-fuehrungen-filters-helper', $script);
});
