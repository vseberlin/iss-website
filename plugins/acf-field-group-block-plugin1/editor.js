(function (wp) {
  var el = wp.element.createElement;
  var __ = wp.i18n.__;
  var registerBlockType = wp.blocks.registerBlockType;
  var InspectorControls = wp.blockEditor.InspectorControls;
  var PanelBody = wp.components.PanelBody;
  var ToggleControl = wp.components.ToggleControl;
  var SelectControl = wp.components.SelectControl;
  var Fragment = wp.element.Fragment;

  registerBlockType('acf-field-group-block/fields', {
    edit: function (props) {
      var attributes = props.attributes;
      var setAttributes = props.setAttributes;
      var fieldOptions = [];
      var hasMulti = Array.isArray(attributes.fieldNames);
      var currentMulti = hasMulti ? attributes.fieldNames : [];
      var currentSingle = attributes.fieldName || '';
      var previewClasses = [
        props.className || '',
        'acf-field-group-block',
        'is-' + (attributes.variant || 'default'),
        'cols-' + String(attributes.columns || 1),
        'accent-' + (attributes.accent || 'none')
      ].join(' ').trim();

      if (window.acfFieldGroupBlockData && Array.isArray(window.acfFieldGroupBlockData.fields)) {
        fieldOptions = window.acfFieldGroupBlockData.fields.slice();
      }

      fieldOptions.unshift({
        label: __('All fields', 'acf-field-group-block'),
        value: ''
      });

      return el(
        Fragment,
        null,
        el(
          InspectorControls,
          null,
          el(
            PanelBody,
            { title: __('ACF Field Group', 'acf-field-group-block'), initialOpen: true },
            el(SelectControl, {
              label: __('Field', 'acf-field-group-block'),
              value: currentSingle,
              options: fieldOptions,
              onChange: function (value) {
                setAttributes({ fieldName: value, fieldNames: [] });
              }
            }),
            el(SelectControl, {
              label: __('Fields (multi-select)', 'acf-field-group-block'),
              multiple: true,
              value: currentMulti,
              options: fieldOptions.filter(function (option) {
                return option.value !== '';
              }),
              onChange: function (value) {
                var next = Array.isArray(value) ? value.filter(Boolean) : [];
                setAttributes({ fieldNames: next, fieldName: '' });
              },
              help: __('Hold Ctrl/Command to select multiple fields. Leave empty to use single field or all fields.', 'acf-field-group-block')
            }),
            el(SelectControl, {
              label: __('Layout', 'acf-field-group-block'),
              value: attributes.layout,
              options: [
                { label: __('List', 'acf-field-group-block'), value: 'list' },
                { label: __('Definition List', 'acf-field-group-block'), value: 'definition' }
              ],
              onChange: function (value) {
                setAttributes({ layout: value });
              }
            }),
            el(ToggleControl, {
              label: __('Show empty fields', 'acf-field-group-block'),
              checked: !!attributes.showEmpty,
              onChange: function (value) {
                setAttributes({ showEmpty: !!value });
              }
            })
          ),
          el(
            PanelBody,
            { title: __('Style', 'acf-field-group-block'), initialOpen: false },
            el(SelectControl, {
              label: __('Variant', 'acf-field-group-block'),
              value: attributes.variant || 'default',
              options: [
                { label: __('Default', 'acf-field-group-block'), value: 'default' },
                { label: __('Card', 'acf-field-group-block'), value: 'card' },
                { label: __('Minimal', 'acf-field-group-block'), value: 'minimal' }
              ],
              onChange: function (value) {
                setAttributes({ variant: value });
              }
            }),
            el(SelectControl, {
              label: __('Columns', 'acf-field-group-block'),
              value: String(attributes.columns || 1),
              options: [
                { label: __('1 column', 'acf-field-group-block'), value: '1' },
                { label: __('2 columns', 'acf-field-group-block'), value: '2' }
              ],
              onChange: function (value) {
                var next = parseInt(value, 10);
                setAttributes({ columns: isNaN(next) ? 1 : next });
              }
            }),
            el(SelectControl, {
              label: __('Accent', 'acf-field-group-block'),
              value: attributes.accent || 'none',
              options: [
                { label: __('None', 'acf-field-group-block'), value: 'none' },
                { label: __('Highlight', 'acf-field-group-block'), value: 'highlight' }
              ],
              onChange: function (value) {
                setAttributes({ accent: value });
              }
            })
          )
        ),
        el(
          'div',
          { className: previewClasses },
          el('strong', null, __('ACF Field Group: Führung', 'acf-field-group-block')),
          el(
            'p',
            null,
            __('Fields render on the front end for the current post.', 'acf-field-group-block')
          ),
          el(
            'p',
            { style: { marginTop: '0.5rem', opacity: 0.75 } },
            __('Preview uses the selected style classes.', 'acf-field-group-block')
          )
        )
      );
    },
    save: function () {
      return null;
    }
  });
})(window.wp);
