(function ($, Drupal) {
  Drupal.behaviors.BrandfolderBrowser = {
    attach: function attach(context) {
      let $bf_browser_assets = $('.brandfolder-browser .brandfolder-asset', context);
      $bf_browser_assets.once('brandfolder-browser').on('click', function (event) {
        event.preventDefault();
        let $selected_asset = $(event.currentTarget);
        let $widget = $selected_asset.closest('.brandfolder-browser-widget');
        // Store asset ID as selection, to be mapped to FID.
        // @todo: Multiple selections, etc.
        let selected_asset_id = $selected_asset.data('bf-asset-id');
        let $selected_asset_ids_element = $widget.find('.bf-asset-ids');
        if ($selected_asset_ids_element.length > 0 && selected_asset_id.length > 0) {
          $selected_asset_ids_element.val(selected_asset_id);
        }
      });
    }
  };
})(jQuery, Drupal);
