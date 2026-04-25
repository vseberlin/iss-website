(function (wp) {
  var el = wp.element.createElement;
  var __ = wp.i18n.__;
  var registerBlockType = wp.blocks.registerBlockType;
  var InspectorControls = wp.blockEditor.InspectorControls;
  var PanelBody = wp.components.PanelBody;
  var ToggleControl = wp.components.ToggleControl;
  var SelectControl = wp.components.SelectControl;
  var TextControl = wp.components.TextControl;
  var Notice = wp.components.Notice;
  var Fragment = wp.element.Fragment;
  var useState = wp.element.useState;
  var useEffect = wp.element.useEffect;
  var apiFetch = wp.apiFetch;

  registerBlockType('acf-field-group-block/fields', {
    edit: function (props) {
      var attributes = props.attributes;
      var setAttributes = props.setAttributes;
      var hasMulti = Array.isArray(attributes.fieldNames);
      var currentMulti = hasMulti ? attributes.fieldNames : [];
      var currentSingle = attributes.fieldName || '';
      var selectionMode = attributes.selectionMode || 'single';
      var initialFields = (window.acfFieldGroupBlockData && Array.isArray(window.acfFieldGroupBlockData.fields))
        ? window.acfFieldGroupBlockData.fields
        : [];
      var _state = useState(initialFields);
      var fieldOptions = _state[0];
      var setFieldOptions = _state[1];
      var _loadingState = useState(false);
      var isLoading = _loadingState[0];
      var setIsLoading = _loadingState[1];
      var _errorState = useState('');
      var errorMessage = _errorState[0];
      var setErrorMessage = _errorState[1];
      var _groupState = useState([]);
      var groupOptions = _groupState[0];
      var setGroupOptions = _groupState[1];
      var _groupLoadingState = useState(false);
      var groupLoading = _groupLoadingState[0];
      var setGroupLoading = _groupLoadingState[1];
      var _groupErrorState = useState('');
      var groupError = _groupErrorState[0];
      var setGroupError = _groupErrorState[1];
      var previewClasses = [
        props.className || '',
        'acf-field-group-block',
        'is-' + (attributes.variant || 'default'),
        'cols-' + String(attributes.columns || 1),
        'accent-' + (attributes.accent || 'none')
      ].join(' ').trim();

      useEffect(function () {
        var data = window.acfFieldGroupBlockData || {};
        if (!data.restPath) {
          return;
        }

        var groupKey = (attributes.groupKey || '').trim();
        var groupTitle = (attributes.groupTitle || '').trim();
        if (!groupKey && !groupTitle) {
          setIsLoading(false);
          setErrorMessage('');
          setFieldOptions([]);
          return;
        }
        var query = [];
        if (groupKey) {
          query.push('groupKey=' + encodeURIComponent(groupKey));
        }
        if (groupTitle) {
          query.push('groupTitle=' + encodeURIComponent(groupTitle));
        }
        var path = data.restPath + (query.length ? ('?' + query.join('&')) : '');

        setIsLoading(true);
        setErrorMessage('');
        apiFetch({
          path: path,
          headers: data.nonce ? { 'X-WP-Nonce': data.nonce } : {}
        })
          .then(function (response) {
            setFieldOptions(Array.isArray(response) ? response : []);
          })
          .catch(function () {
            setErrorMessage(__('Unable to load fields for the selected group.', 'acf-field-group-block'));
            setFieldOptions([]);
          })
          .finally(function () {
            setIsLoading(false);
          });
      }, [attributes.groupKey, attributes.groupTitle]);

      useEffect(function () {
        var data = window.acfFieldGroupBlockData || {};
        if (!data.groupsPath) {
          return;
        }

        setGroupLoading(true);
        setGroupError('');
        apiFetch({
          path: data.groupsPath,
          headers: data.nonce ? { 'X-WP-Nonce': data.nonce } : {}
        })
          .then(function (response) {
            setGroupOptions(Array.isArray(response) ? response : []);
          })
          .catch(function () {
            setGroupError(__('Unable to load ACF groups.', 'acf-field-group-block'));
            setGroupOptions([]);
          })
          .finally(function () {
            setGroupLoading(false);
          });
      }, []);

      useEffect(function () {
        if (!Array.isArray(fieldOptions)) {
          return;
        }
        var available = fieldOptions.map(function (option) { return option.value; });
        if (selectionMode === 'single') {
          if (currentSingle && available.indexOf(currentSingle) === -1) {
            setAttributes({ fieldName: '' });
          }
        } else {
          if (currentMulti.length) {
            var next = currentMulti.filter(function (name) {
              return available.indexOf(name) !== -1;
            });
            if (next.length !== currentMulti.length) {
              setAttributes({ fieldNames: next });
            }
          }
        }
      }, [fieldOptions, selectionMode, currentSingle, currentMulti]);

      var displayOptions = fieldOptions.slice();
      displayOptions.unshift({
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
            errorMessage
              ? el(Notice, { status: 'error', isDismissible: false }, errorMessage)
              : null,
            !errorMessage && !isLoading && displayOptions.length <= 1
              ? el(Notice, { status: 'warning', isDismissible: false }, __('Select an ACF group to load fields.', 'acf-field-group-block'))
              : null,
            el(SelectControl, {
              label: __('Selection mode', 'acf-field-group-block'),
              value: selectionMode,
              options: [
                { label: __('Single field', 'acf-field-group-block'), value: 'single' },
                { label: __('Multiple fields', 'acf-field-group-block'), value: 'multi' }
              ],
              onChange: function (value) {
                if (value === 'multi') {
                  setAttributes({ selectionMode: 'multi', fieldName: '' });
                } else {
                  setAttributes({ selectionMode: 'single', fieldNames: [] });
                }
              }
            }),
            selectionMode === 'single'
              ? el(SelectControl, {
                  label: __('Field', 'acf-field-group-block'),
                  value: currentSingle,
                  options: displayOptions,
                  onChange: function (value) {
                    setAttributes({ fieldName: value, fieldNames: [] });
                  }
                })
              : el(SelectControl, {
                  label: __('Fields (multi-select)', 'acf-field-group-block'),
                  multiple: true,
                  value: currentMulti,
                  options: displayOptions.filter(function (option) {
                    return option.value !== '';
                  }),
                  onChange: function (value) {
                    var next = Array.isArray(value) ? value.filter(Boolean) : [];
                    setAttributes({ fieldNames: next, fieldName: '' });
                  },
                  help: __('Hold Ctrl/Command to select multiple fields.', 'acf-field-group-block')
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
            { title: __('Group', 'acf-field-group-block'), initialOpen: false },
            groupError
              ? el(Notice, { status: 'error', isDismissible: false }, groupError)
              : null,
            groupLoading
              ? el('p', null, __('Loading groups…', 'acf-field-group-block'))
              : null,
            groupOptions.length
              ? el(SelectControl, {
                  label: __('ACF group', 'acf-field-group-block'),
                  value: attributes.groupKey || '',
                  options: [{ label: __('Select a group', 'acf-field-group-block'), value: '' }].concat(groupOptions),
                  onChange: function (value) {
                    setAttributes({ groupKey: value, groupTitle: '' });
                  }
                })
              : null,
            el(TextControl, {
              label: __('Group key (optional)', 'acf-field-group-block'),
              value: attributes.groupKey || '',
              onChange: function (value) {
                setAttributes({ groupKey: value });
              },
              help: __('If set, this takes precedence over the title.', 'acf-field-group-block')
            }),
            el(TextControl, {
              label: __('Group title (optional)', 'acf-field-group-block'),
              value: attributes.groupTitle || '',
              onChange: function (value) {
                setAttributes({ groupTitle: value });
              },
              help: __('Use when you want to target a group by title instead of key.', 'acf-field-group-block')
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
          el('strong', null, __('ACF Field Group', 'acf-field-group-block')),
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
