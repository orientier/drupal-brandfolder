(function ($, Drupal) {
  Drupal.behaviors.BrandfolderBrowser = {
    attach: function attach(context) {
      let $browser_container = $('.brandfolder-browser', context);
      let $parent_form = $browser_container.closest('form');
      let $bf_browser_assets = $browser_container.find('.brandfolder-asset');
      let all_selected_attachments = [];
      $bf_browser_assets.once('brandfolder-browser').on('click', function (event) {
        event.preventDefault();
        let $selected_asset = $(event.currentTarget);
        const selected_class = 'brandfolder-asset--selected';
        $bf_browser_assets.removeClass(selected_class);
        $selected_asset.addClass(selected_class);

        // @todo: Multi-step process of drilling into assets to choose one attachment, if the asset has multiple attachments and Drupal admins have configured things to allow users to select specific attachments. For now, we'll use the first attachment for each asset.
        let selected_attachment_id = $selected_asset.data('bf-attachment-id');
        // @todo: Support selection of multiple attachments from one or more assets.
        all_selected_attachments = [selected_attachment_id];
        let $selected_attachment_ids_element = $parent_form.find('.selected-bf-attachment-ids');
        if ($selected_attachment_ids_element.length > 0 && selected_attachment_id.length > 0) {
          $selected_attachment_ids_element.val(all_selected_attachments.join());
        }
      });
    }
  };
})(jQuery, Drupal);
