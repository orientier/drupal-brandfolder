(function ($, Drupal, once) {
  Drupal.behaviors.BrandfolderBrowser = {
    attach: function attach(context) {
      // console.error('Debug');
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
      // Tag input handling.
      $browser_container.find('.brandfolder-browser-control--tag-text-input').on('keypress', function(event) {
        if (event.which === 13) {
          event.preventDefault();
          event.stopPropagation();
          let $textfield = $(this);
          let tag_name = $textfield.val();
          if (tag_name.length > 0) {
            // Drupal.behaviors.BrandfolderBrowser.handleTagInput(tag_name, $browser_container);
            $textfield.closest('fieldset').find('input[type="submit"]').trigger('mousedown');
          }
        }
      });
      let $bf_browser_assets = $browser_container.find('.brandfolder-asset');
      let $bf_browser_attachments = $browser_container.find('.brandfolder-attachment');
      $(once('brandfolder-browser', $bf_browser_assets)).on('click', function(event) {
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
      $(once('brandfolder-browser', $bf_browser_attachments)).on('click', function (event) {
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
      $(once('brandfolder-browser', $asset_close_buttons)).on('click', function (event) {
        event.preventDefault();
        event.stopPropagation();
        let $button = $(event.currentTarget);
        $button.closest('.brandfolder-asset').removeClass(asset_active_class);
      });
    },
    // handleTagInput: function(tag_name, $context) {
    //   // Clear the text field.
    //   $context.find('.brandfolder-browser-control--tag-text-input').val('');
    //   // Add the new tag to our data store if it's not already there.
    //   let $tag_list_store_el = $context.find('.brandfolder-controls-tag-list-store');
    //   if ($tag_list_store_el.length > 0) {
    //     let tag_list_store = JSON.parse($tag_list_store_el.val());
    //     // @todo: Deterministic and unique key gen, e.g. hash.
    //     let tag_key = tag_name.replace(/\s/, '_');
    //     if (!(tag_key in tag_list_store)) {
    //       tag_list_store[tag_key] = 1;
    //       $tag_list_store_el.val(JSON.stringify(tag_list_store));
    //       // Add a checkbox for the new tag.
    //       let $tag_list = $context.find('.brandfolder-browser-controls__tag_list');
    //       let $tag_checkbox_starter = $context.find('.brandfolder-controls__tags-read-only-reference');
    //       if ($tag_list.length > 0 && $tag_checkbox_starter.length > 0) {
    //         let $new_tag_checkbox = $($tag_checkbox_starter.clone(true));
    //         $new_tag_checkbox.removeClass('hidden', 'brandfolder-controls__tags-read-only-reference');
    //         let checkbox_el = $new_tag_checkbox.find('input[type="checkbox"');
    //         if (checkbox_el) {
    //           checkbox_el.val(tag_key);
    //           $new_tag_checkbox.find('label').first().innerHTML(tag_name);
    //           $tag_list.append($new_tag_checkbox);
    //           // Check the checkbox and trigger AJAX form submission.
    //           checkbox_el.prop('checked', true);
    //         }
    //       }
    //     }
    //   }
    // }
  };
})(jQuery, Drupal, once);
