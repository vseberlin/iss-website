(function (blocks, element, components, blockEditor, serverSideRender) {
  var el = element.createElement;
  var Fragment = element.Fragment;
  var InspectorControls = blockEditor.InspectorControls;
  var PanelBody = components.PanelBody;
  var SelectControl = components.SelectControl;
  var TextControl = components.TextControl;
  var TextareaControl = components.TextareaControl;
  var ToggleControl = components.ToggleControl;
  var ServerSideRender = serverSideRender;

  var fieldOptions = [
    { label: 'Telefon', value: 'contact.phone' },
    { label: 'E-Mail', value: 'contact.email' },
    { label: 'Buchungs-E-Mail', value: 'contact.booking_email' },
    { label: 'Ansprechperson', value: 'contact.contact_person' },
    { label: 'Website', value: 'contact.website' },
    { label: 'Buchungslink', value: 'contact.booking_url' },
    { label: 'Straße', value: 'general.street' },
    { label: 'PLZ', value: 'general.postal_code' },
    { label: 'Ort', value: 'general.city' },
    { label: 'Stadtteil', value: 'general.district' },
    { label: 'Volle Adresse', value: 'address.full' },
    { label: 'Google Maps URL', value: 'maps.google_maps_url' },
    { label: 'Preis Erwachsene', value: 'prices.adult' },
    { label: 'Preis Ermäßigt', value: 'prices.reduced' },
    { label: 'Preis Gruppen', value: 'prices.group' },
    { label: 'Preis Schulklassen', value: 'prices.school' },
    { label: 'Preis Führung', value: 'prices.tour' },
    { label: 'Preis Raumvermietung', value: 'prices.rental' }
  ];

  function fieldInspector(props) {
    return el(
      InspectorControls,
      null,
      el(
        PanelBody,
        { title: 'Feld', initialOpen: true },
        el(SelectControl, {
          label: 'Schlüssel',
          value: props.attributes.key,
          options: fieldOptions,
          onChange: function (value) { props.setAttributes({ key: value }); }
        }),
        el(SelectControl, {
          label: 'HTML-Tag',
          value: props.attributes.tagName,
          options: [
            { label: 'div', value: 'div' },
            { label: 'p', value: 'p' },
            { label: 'span', value: 'span' },
            { label: 'strong', value: 'strong' },
            { label: 'h2', value: 'h2' },
            { label: 'h3', value: 'h3' }
          ],
          onChange: function (value) { props.setAttributes({ tagName: value }); }
        }),
        el(SelectControl, {
          label: 'Link',
          value: props.attributes.linkMode,
          options: [
            { label: 'Automatisch', value: 'auto' },
            { label: 'Kein Link', value: 'none' },
            { label: 'Telefon-Link', value: 'tel' },
            { label: 'E-Mail-Link', value: 'email' },
            { label: 'URL-Link', value: 'url' }
          ],
          onChange: function (value) { props.setAttributes({ linkMode: value }); }
        }),
        el(TextControl, {
          label: 'Optionales Label',
          value: props.attributes.label,
          onChange: function (value) { props.setAttributes({ label: value }); }
        })
      )
    );
  }

  blocks.registerBlockType('industriesalon/field', {
    title: 'IS Feld',
    icon: 'admin-generic',
    category: 'widgets',
    attributes: {
      key: { type: 'string', default: 'contact.phone' },
      tagName: { type: 'string', default: 'div' },
      linkMode: { type: 'string', default: 'auto' },
      label: { type: 'string', default: '' }
    },
    edit: function (props) {
      return el(
        Fragment,
        null,
        fieldInspector(props),
        el(ServerSideRender, {
          block: 'industriesalon/field',
          attributes: props.attributes
        })
      );
    },
    save: function () { return null; }
  });

  blocks.registerBlockType('industriesalon/hours', {
    title: 'IS Zeiten',
    icon: 'clock',
    category: 'widgets',
    attributes: {
      type: { type: 'string', default: 'public' },
      title: { type: 'string', default: '' }
    },
    edit: function (props) {
      return el(
        Fragment,
        null,
        el(
          InspectorControls,
          null,
          el(
            PanelBody,
            { title: 'Zeiten', initialOpen: true },
            el(SelectControl, {
              label: 'Typ',
              value: props.attributes.type,
              options: [
                { label: 'Besuchszeiten', value: 'public' },
                { label: 'Bürozeiten', value: 'office' }
              ],
              onChange: function (value) { props.setAttributes({ type: value }); }
            }),
            el(TextControl, {
              label: 'Optionaler Titel',
              value: props.attributes.title,
              onChange: function (value) { props.setAttributes({ title: value }); }
            })
          )
        ),
        el(ServerSideRender, {
          block: 'industriesalon/hours',
          attributes: props.attributes
        })
      );
    },
    save: function () { return null; }
  });

  blocks.registerBlockType('industriesalon/visit-info', {
    title: 'Besuchszeiten',
    icon: 'clock',
    category: 'widgets',
    supports: {
      align: ['wide', 'full'],
      html: false
    },
    attributes: {
      align: { type: 'string', default: '' },
      show_status: { type: 'boolean', default: true },
      show_museum_hours: { type: 'boolean', default: true },
      show_office_hours: { type: 'boolean', default: true },
      show_exceptions: { type: 'boolean', default: false },
      variant: { type: 'string', default: 'compact' },
      kicker: { type: 'string', default: '' },
      title: { type: 'string', default: '' },
      show_upcoming: { type: 'boolean', default: true },
      upcoming_label: { type: 'string', default: 'Demnächst' },
      upcoming_url: { type: 'string', default: '' },
      tour_label: { type: 'string', default: 'Individuell geführt oder bei einer Tour dabei sein? Auch besondere Führungen für Kinder und Familien. Planen Sie einen Betriebsausflug? Schauen Sie mal rein.' },
      tour_url: { type: 'string', default: '' },
      room_label: { type: 'string', default: 'Feier, Treffen, Party oder Vortrag? Sie brauchen Raum? Wir haben etwas Passendes' },
      room_url: { type: 'string', default: '' },
      address_label: { type: 'string', default: 'Sie finden uns am Spreeufer.' },
      show_social: { type: 'boolean', default: true }
    },
    edit: function (props) {
      return el(
        Fragment,
        null,
        el(
          InspectorControls,
          null,
          el(
            PanelBody,
            { title: 'Besuchszeiten', initialOpen: true },
            el(SelectControl, {
              label: 'Darstellung',
              value: props.attributes.variant,
              options: [
                { label: 'Kurz', value: 'compact' },
                { label: 'Ausführlich', value: 'full' },
                { label: 'Einzeilig', value: 'inline' }
              ],
              onChange: function (value) { props.setAttributes({ variant: value }); }
            }),
            el(ToggleControl, {
              label: 'Status zeigen',
              checked: !!props.attributes.show_status,
              onChange: function (value) { props.setAttributes({ show_status: value }); }
            }),
            el(ToggleControl, {
              label: 'Besuchszeiten zeigen',
              checked: !!props.attributes.show_museum_hours,
              onChange: function (value) { props.setAttributes({ show_museum_hours: value }); }
            }),
            el(ToggleControl, {
              label: 'Bürozeiten zeigen',
              checked: !!props.attributes.show_office_hours,
              onChange: function (value) { props.setAttributes({ show_office_hours: value }); }
            }),
            el(ToggleControl, {
              label: 'Sondertage zeigen',
              checked: !!props.attributes.show_exceptions,
              onChange: function (value) { props.setAttributes({ show_exceptions: value }); }
            }),
            el(ToggleControl, {
              label: 'Social zeigen',
              checked: !!props.attributes.show_social,
              onChange: function (value) { props.setAttributes({ show_social: value }); }
            })
          ),
          el(
            PanelBody,
            { title: 'Besucherkarte', initialOpen: true },
            el(TextControl, {
              label: 'Kicker',
              value: props.attributes.kicker,
              onChange: function (value) { props.setAttributes({ kicker: value }); }
            }),
            el(TextControl, {
              label: 'Titel',
              value: props.attributes.title,
              onChange: function (value) { props.setAttributes({ title: value }); }
            }),
            el(ToggleControl, {
              label: 'Demnächst zeigen',
              checked: !!props.attributes.show_upcoming,
              onChange: function (value) { props.setAttributes({ show_upcoming: value }); }
            }),
            el(TextControl, {
              label: 'Demnächst Text',
              value: props.attributes.upcoming_label,
              onChange: function (value) { props.setAttributes({ upcoming_label: value }); }
            }),
            el(TextControl, {
              label: 'Demnächst Link',
              value: props.attributes.upcoming_url,
              placeholder: 'https://…',
              onChange: function (value) { props.setAttributes({ upcoming_url: value }); }
            }),
            el(TextareaControl, {
              label: 'Touren Text',
              value: props.attributes.tour_label,
              onChange: function (value) { props.setAttributes({ tour_label: value }); }
            }),
            el(TextControl, {
              label: 'Touren Link',
              value: props.attributes.tour_url,
              placeholder: 'https://…',
              onChange: function (value) { props.setAttributes({ tour_url: value }); }
            }),
            el(TextareaControl, {
              label: 'Raum Text',
              value: props.attributes.room_label,
              onChange: function (value) { props.setAttributes({ room_label: value }); }
            }),
            el(TextControl, {
              label: 'Raum Link',
              value: props.attributes.room_url,
              placeholder: 'https://…',
              onChange: function (value) { props.setAttributes({ room_url: value }); }
            }),
            el(TextControl, {
              label: 'Adresssatz',
              value: props.attributes.address_label,
              onChange: function (value) { props.setAttributes({ address_label: value }); }
            })
          )
        ),
        el(ServerSideRender, {
          block: 'industriesalon/visit-info',
          attributes: props.attributes
        })
      );
    },
    save: function () { return null; }
  });

  function simpleGroupBlock(name, title, icon) {
    blocks.registerBlockType(name, {
      title: title,
      icon: icon,
      category: 'widgets',
      attributes: {
        title: { type: 'string', default: '' }
      },
      edit: function (props) {
        return el(
          Fragment,
          null,
          el(
            InspectorControls,
            null,
            el(
              PanelBody,
              { title: title, initialOpen: true },
              el(TextControl, {
                label: 'Optionaler Titel',
                value: props.attributes.title,
                onChange: function (value) { props.setAttributes({ title: value }); }
              })
            )
          ),
          el(ServerSideRender, {
            block: name,
            attributes: props.attributes
          })
        );
      },
      save: function () { return null; }
    });
  }

  simpleGroupBlock('industriesalon/contact', 'IS Kontakt', 'id');
  simpleGroupBlock('industriesalon/prices', 'IS Preise', 'tickets-alt');
  simpleGroupBlock('industriesalon/faq', 'IS FAQ', 'editor-help');

  blocks.registerBlockType('industriesalon/mission-statement', {
    title: 'IS Mission Statement',
    icon: 'excerpt-view',
    category: 'widgets',
    attributes: {
      title: { type: 'string', default: '' },
      heading: { type: 'string', default: '' }
    },
    edit: function (props) {
      return el(
        Fragment,
        null,
        el(
          InspectorControls,
          null,
          el(
            PanelBody,
            { title: 'IS Mission Statement', initialOpen: true },
            el(TextControl, {
              label: 'Kicker Titel',
              value: props.attributes.title,
              onChange: function (value) { props.setAttributes({ title: value }); }
            }),
            el(TextControl, {
              label: 'Überschrift',
              value: props.attributes.heading,
              onChange: function (value) { props.setAttributes({ heading: value }); }
            })
          )
        ),
        el(ServerSideRender, {
          block: 'industriesalon/mission-statement',
          attributes: props.attributes
        })
      );
    },
    save: function () { return null; }
  });
})(window.wp.blocks, window.wp.element, window.wp.components, window.wp.blockEditor, window.wp.serverSideRender);
