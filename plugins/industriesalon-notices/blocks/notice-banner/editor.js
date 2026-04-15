(function (blocks, element, serverSideRender, blockEditor) {
  var el = element.createElement;
  var useBlockProps = blockEditor.useBlockProps;

  blocks.registerBlockType("industriesalon/notice-banner", {
    apiVersion: 2,
    title: "Notice Banner",
    icon: "megaphone",
    category: "theme",
    attributes: {
      area: {
        type: "string",
        default: "front_page_banner",
      },
    },
    edit: function (props) {
      var blockProps = useBlockProps();
      return el(
        "div",
        blockProps,
        el(serverSideRender, {
          block: "industriesalon/notice-banner",
          attributes: props.attributes,
        })
      );
    },
    save: function () {
      return null;
    },
  });
})(
  window.wp.blocks,
  window.wp.element,
  window.wp.serverSideRender,
  window.wp.blockEditor
);
