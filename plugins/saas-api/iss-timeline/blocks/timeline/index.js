(function () {
  if (!window.wp || !window.wp.blocks || !window.wp.element) return;

  const el = window.wp.element.createElement;
  const InspectorControls = window.wp.blockEditor && window.wp.blockEditor.InspectorControls;
  const PanelBody = window.wp.components && window.wp.components.PanelBody;
  const TextControl = window.wp.components && window.wp.components.TextControl;
  const TextareaControl = window.wp.components && window.wp.components.TextareaControl;
  const RangeControl = window.wp.components && window.wp.components.RangeControl;
  const ToggleControl = window.wp.components && window.wp.components.ToggleControl;

  window.wp.blocks.registerBlockType('industriesalon/timeline', {
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
                { title: 'Timeline', initialOpen: true },
                TextControl
                  ? el(TextControl, {
                      label: 'Title (optional)',
                      value: attrs.title || '',
                      onChange: function (v) {
                        setAttributes({ title: v });
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
                RangeControl
                  ? el(RangeControl, {
                      label: 'Limit',
                      value: attrs.limit || 50,
                      min: 1,
                      max: 200,
                      onChange: function (v) {
                        setAttributes({ limit: v });
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
                  : null,
                ToggleControl
                  ? el(ToggleControl, {
                      label: 'Group by year',
                      checked: attrs.yearGrouping !== false,
                      onChange: function (v) {
                        setAttributes({ yearGrouping: !!v });
                      },
                    })
                  : null
              )
            )
          : null;

      return el('div', null, controls, el('p', null, 'Timeline (server-rendered).'));
    },
    save: function () {
      return null;
    },
  });
})();

