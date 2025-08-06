(function ($, Drupal) {

  /**
   * Declare a custom AJAX command for populating Drupal form fields with
   * Brandfolder-derived alt text.
   */
  Drupal.AjaxCommands.prototype.brandfolderSetAltTextCommand = function (ajax, response, status) {
    const triggeringElement = document.querySelector(response.selector);
    if (!triggeringElement) {
      return;
    }
    const ancestor = triggeringElement.closest('details');
    if (!ancestor) {
      return;
    }
    const altTextFields = ancestor.querySelectorAll('[name="settings[default_image][alt]"], [name="field_storage[subform][settings][default_image][alt]"]');
    // If we didn't find a relevant alt text field, or, for some reason, we
    // found more than one, that means we're facing an unexpected form structure
    // and aren't currently sophisticated enough to proceed.
    if (altTextFields.length !== 1) {
      return;
    }
    const altTextField = altTextFields[0];
    // Set the alt text field's value to the response's alt text.
    altTextField.value = response?.altText;
  };

})(jQuery, Drupal);
