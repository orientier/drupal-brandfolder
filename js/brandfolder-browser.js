(function ($, Drupal) {
  Drupal.behaviors.BrandfolderBrowser = {
    attach: function attach(context) {
      console.error('Debug');
      const asset_active_class = 'brandfolder-asset--active';
      const asset_close_button_class = 'brandfolder-asset__close_button';
      const attachment_disabled_class = 'brandfolder-attachment--disabled';
      const attachment_selected_class = 'brandfolder-attachment--selected';
      const list_delimiter = ',';
      const $browser_container = $('.brandfolder-browser', context);
      if ($browser_container.length == 0) {

        return;
      }
      let selection_limit = $browser_container.data('selection-limit');
      if (selection_limit < 1) {
        selection_limit = false;
      }
      const $parent_form = $browser_container.closest('form');
      if ($parent_form.length == 0) {

        return;
      }
      const $selected_attachment_ids_element = $parent_form.find('.selected-bf-attachment-ids');
      if ($selected_attachment_ids_element.length == 0) {

        return;
      }
      let selected_attachments_list = $selected_attachment_ids_element.val();
      let all_selected_attachments = selected_attachments_list.length > 0 ? $selected_attachment_ids_element.val().split(list_delimiter) : [];
      if (selection_limit && all_selected_attachments.length >= selection_limit) {
        $browser_container.addClass('selection-limit-reached');
      }
      let $bf_browser_assets = $browser_container.find('.brandfolder-asset');
      let $bf_browser_attachments = $browser_container.find('.brandfolder-attachment');
      $bf_browser_assets.once('brandfolder-browser').on('click', function (event) {
        event.preventDefault();
        let $targeted_asset = $(event.currentTarget);
        let asset_is_active = $targeted_asset.hasClass(asset_active_class);
        let $target = $(event.target);
        if (!asset_is_active && !$target.hasClass(asset_close_button_class)) {
          $bf_browser_assets.removeClass(asset_active_class);
          $targeted_asset.addClass(asset_active_class);
          // Load attachment images if not already present.
          $targeted_asset.find('.brandfolder-attachment').each(function(index, attachment_el) {
            let $attachment_img = $(attachment_el).find('.brandfolder-attachment__image');
            if ($attachment_img.length > 0 && $attachment_img.attr('src').length < 1) {
              let img_url = $attachment_img.data('img-src');
              if (img_url.length > 0) {
                $attachment_img.attr('src', img_url);
              }
            }
          })
        }
      });
      $bf_browser_attachments.once('brandfolder-browser').on('click', function (event) {
        event.preventDefault();
        let $targeted_attachment = $(event.currentTarget);
        let previously_selected = $targeted_attachment.hasClass(attachment_selected_class);
        let targeted_attachment_id = $targeted_attachment.data('bf-attachment-id');
        if (targeted_attachment_id.length == 0) {
          console.error('The selected attachment is missing an ID.');

          return;
        }
        if ($targeted_attachment.hasClass(attachment_disabled_class)) {
          // This attachment is disabled. No further action needed.

          return false;
        }
        if (previously_selected) {
          // @todo: Abstract.
          $targeted_attachment.removeClass(attachment_selected_class);
          let selected_index = all_selected_attachments.indexOf(targeted_attachment_id);
          if (selected_index >= 0) {
            all_selected_attachments.splice(selected_index, 1);
            $selected_attachment_ids_element.val(all_selected_attachments.join(list_delimiter));
          }
        }
        else {
          // If the maximum number of attachments has already been selected, do
          // nothing (until the user deselects some attachments or resets
          // selection).
          if (selection_limit && all_selected_attachments.length >= selection_limit) {

            return;
          }
          // If only one selection is allowed, unset any existing selection and
          // replace with this new one.
          if (selection_limit === 1) {
            $bf_browser_attachments.removeClass(attachment_selected_class);
            all_selected_attachments = [];
          }
          $targeted_attachment.addClass(attachment_selected_class);
          all_selected_attachments.push(targeted_attachment_id);
          $selected_attachment_ids_element.val(all_selected_attachments.join(list_delimiter));
        }

        $browser_container.removeClass('selection-limit-reached');
        let num_selections = all_selected_attachments.length;
        if (selection_limit && num_selections >= selection_limit) {
          $browser_container.addClass('selection-limit-reached');
        }

        // If in a Media Library context, update the selection count text.
        let $ml_selected_count = $('.js-media-library-selected-count');
        if ($ml_selected_count.length > 0) {
          // Use the same terminology as the Media Library, intuitive or not.
          let ml_selected_items_text = selection_limit === false ? Drupal.formatPlural(num_selections, '1 item selected', '@count items selected') : Drupal.formatPlural(selection_limit, '@selected of @count item selected', '@selected of @count items selected', {
            '@selected': num_selections
          });
          $ml_selected_count.html(ml_selected_items_text);
        }
      });

      // Basic "close"/"back" handling for active assets.
      let $asset_close_buttons = $browser_container.find('.' + asset_close_button_class);
      $asset_close_buttons.once('brandfolder-browser').on('click', function (event) {
        event.preventDefault();
        event.stopPropagation();
        let $button = $(event.currentTarget);
        $button.closest('.brandfolder-asset').removeClass(asset_active_class);
      });
    }
  };
})(jQuery, Drupal);
