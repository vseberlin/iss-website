(function () {
  if (!window.wp || !window.wp.blocks || !window.wp.element) return;

  const el = window.wp.element.createElement;
  const InspectorControls = window.wp.blockEditor && window.wp.blockEditor.InspectorControls;
  const PanelBody = window.wp.components && window.wp.components.PanelBody;
  const TextControl = window.wp.components && window.wp.components.TextControl;
  const TextareaControl = window.wp.components && window.wp.components.TextareaControl;
  const RangeControl = window.wp.components && window.wp.components.RangeControl;

  window.wp.blocks.registerBlockType('industriesalon/timeline-sections', {
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
                { title: 'Timeline Sections', initialOpen: true },
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
                { title: 'Next', initialOpen: false },
                TextControl
                  ? el(TextControl, {
                      label: 'Heading',
                      value: attrs.nextTitle || '',
                      onChange: function (v) {
                        setAttributes({ nextTitle: v });
                      },
                    })
                  : null,
                RangeControl
                  ? el(RangeControl, {
                      label: 'Limit',
                      value: attrs.nextLimit || 4,
                      min: 1,
                      max: 12,
                      onChange: function (v) {
                        setAttributes({ nextLimit: v });
                      },
                    })
                  : null
              ),
              el(
                PanelBody,
                { title: 'Monthly', initialOpen: false },
                TextControl
                  ? el(TextControl, {
                      label: 'Heading',
                      value: attrs.monthlyTitle || '',
                      onChange: function (v) {
                        setAttributes({ monthlyTitle: v });
                      },
                    })
                  : null,
                RangeControl
                  ? el(RangeControl, {
                      label: 'Max items',
                      value: attrs.monthlyLimit || 80,
                      min: 10,
                      max: 300,
                      onChange: function (v) {
                        setAttributes({ monthlyLimit: v });
                      },
                    })
                  : null
              ),
              el(
                PanelBody,
                { title: 'Archive', initialOpen: false },
                TextControl
                  ? el(TextControl, {
                      label: 'Heading',
                      value: attrs.archiveTitle || '',
                      onChange: function (v) {
                        setAttributes({ archiveTitle: v });
                      },
                    })
                  : null,
                RangeControl
                  ? el(RangeControl, {
                      label: 'Max items',
                      value: attrs.archiveLimit || 250,
                      min: 20,
                      max: 500,
                      onChange: function (v) {
                        setAttributes({ archiveLimit: v });
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
        el('p', null, 'Timeline Sections (server-rendered).')
      );
    },
    save: function () {
      return null;
    },
  });
})();

