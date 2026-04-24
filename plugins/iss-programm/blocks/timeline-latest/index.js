(function () {
  if (!window.wp || !window.wp.blocks || !window.wp.element) return;

  const el = window.wp.element.createElement;
  const InspectorControls = window.wp.blockEditor && window.wp.blockEditor.InspectorControls;
  const PanelBody = window.wp.components && window.wp.components.PanelBody;
  const TextControl = window.wp.components && window.wp.components.TextControl;
  const TextareaControl = window.wp.components && window.wp.components.TextareaControl;
  const ToggleControl = window.wp.components && window.wp.components.ToggleControl;

  window.wp.blocks.registerBlockType('industriesalon/timeline-latest', {
    edit: function (props) {
      const attrs = props.attributes || {};
      const setAttributes = props.setAttributes || function () {};

      const controls =
        InspectorControls && PanelBody
          ? el(
              InspectorControls,
              null,
              el(
                PanelBody,
                { title: 'Timeline Latest', initialOpen: true },
                TextControl
                  ? el(TextControl, {
                      label: 'Title',
                      value: attrs.title || '',
                      onChange: function (v) {
                        setAttributes({ title: v });
                      },
                    })
                  : null,
                ToggleControl
                  ? el(ToggleControl, {
                      label: 'Show title',
                      checked: attrs.showTitle !== false,
                      onChange: function (v) {
                        setAttributes({ showTitle: !!v });
                      },
                    })
                  : null,
                TextControl
                  ? el(TextControl, {
                      label: 'Kicker (optional)',
                      value: attrs.kicker || '',
                      onChange: function (v) {
                        setAttributes({ kicker: v });
                      },
                    })
                  : null,
                ToggleControl
                  ? el(ToggleControl, {
                      label: 'Show kicker',
                      checked: !!attrs.showKicker,
                      onChange: function (v) {
                        setAttributes({ showKicker: !!v });
                      },
                    })
                  : null,
                TextareaControl
                  ? el(TextareaControl, {
                      label: 'Intro (optional)',
                      value: attrs.intro || '',
                      onChange: function (v) {
                        setAttributes({ intro: v });
                      },
                    })
                  : null,
                TextControl
                  ? el(TextControl, {
                      label: 'Group (slug, optional)',
                      value: attrs.group || '',
                      onChange: function (v) {
                        setAttributes({ group: v });
                      },
                    })
                  : null
              ),
              el(
                PanelBody,
                { title: 'Buttons', initialOpen: false },
                ToggleControl
                  ? el(ToggleControl, {
                      label: 'Show "Details anschauen"',
                      checked: attrs.showDetailsButton !== false,
                      onChange: function (v) {
                        setAttributes({ showDetailsButton: !!v });
                      },
                    })
                  : null,
                TextControl
                  ? el(TextControl, {
                      label: 'Details text (optional)',
                      value: attrs.detailsButtonText || '',
                      onChange: function (v) {
                        setAttributes({ detailsButtonText: v });
                      },
                    })
                  : null,
                TextControl
                  ? el(TextControl, {
                      label: 'Details URL override (optional)',
                      value: attrs.detailsButtonUrl || '',
                      onChange: function (v) {
                        setAttributes({ detailsButtonUrl: v });
                      },
                    })
                  : null,
                ToggleControl
                  ? el(ToggleControl, {
                      label: 'Show "Empfehlen"',
                      checked: attrs.showRecommendButton !== false,
                      onChange: function (v) {
                        setAttributes({ showRecommendButton: !!v });
                      },
                    })
                  : null,
                TextControl
                  ? el(TextControl, {
                      label: 'Empfehlen text (optional)',
                      value: attrs.recommendButtonText || '',
                      onChange: function (v) {
                        setAttributes({ recommendButtonText: v });
                      },
                    })
                  : null,
                TextControl
                  ? el(TextControl, {
                      label: 'Empfehlen URL override (optional)',
                      value: attrs.recommendButtonUrl || '',
                      onChange: function (v) {
                        setAttributes({ recommendButtonUrl: v });
                      },
                    })
                  : null,
                ToggleControl
                  ? el(ToggleControl, {
                      label: 'Show "Tickets kaufen"',
                      checked: attrs.showTicketsButton !== false,
                      onChange: function (v) {
                        setAttributes({ showTicketsButton: !!v });
                      },
                    })
                  : null,
                TextControl
                  ? el(TextControl, {
                      label: 'Tickets text (optional)',
                      value: attrs.ticketsButtonText || '',
                      onChange: function (v) {
                        setAttributes({ ticketsButtonText: v });
                      },
                    })
                  : null,
                TextControl
                  ? el(TextControl, {
                      label: 'Tickets URL override (optional)',
                      value: attrs.ticketsButtonUrl || '',
                      onChange: function (v) {
                        setAttributes({ ticketsButtonUrl: v });
                      },
                    })
                  : null
              ),
              el(
                PanelBody,
                { title: 'Bottom Button', initialOpen: false },
                ToggleControl
                  ? el(ToggleControl, {
                      label: 'Show bottom button',
                      checked: !!attrs.showBottomButton,
                      onChange: function (v) {
                        setAttributes({ showBottomButton: !!v });
                      },
                    })
                  : null,
                TextControl
                  ? el(TextControl, {
                      label: 'Bottom button text',
                      value: attrs.bottomButtonText || '',
                      onChange: function (v) {
                        setAttributes({ bottomButtonText: v });
                      },
                    })
                  : null,
                TextControl
                  ? el(TextControl, {
                      label: 'Bottom button URL',
                      value: attrs.bottomButtonUrl || '',
                      onChange: function (v) {
                        setAttributes({ bottomButtonUrl: v });
                      },
                    })
                  : null
              )
            )
          : null;

      return el(
        'div',
        null,
        controls,
        el('p', null, 'Timeline Latest (next 4, server-rendered).')
      );
    },
    save: function () {
      return null;
    },
  });
})();
