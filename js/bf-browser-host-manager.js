(function (Drupal, once) {
  /**
   * Attaches to Drupal contexts within which the Brandfolder Browser is hosted
   * and serves to sync browser selections with that context.
   */
  Drupal.behaviors.brandfolderBrowserHostManager = {
    attach: function (context, settings) {
      once('bfBrowserHostManagement', 'input.selected-bf-attachment-ids', context).forEach(
        function (element) {
          // Find the most relevant container.
          let container = element.closest('.brandfolder-browser-form-element');
          if (!container) {
            // If no container is found, try to find the closest form.
            container = element.closest('form');
          }
          if (!container) {
            return
          }
          // Listen for brandfolderBrowserAttachmentSelectionChange events
          // bubbling up to the container and store the attachment IDs in the
          // dedicated input.
          container.addEventListener('brandfolderBrowserAttachmentSelectionChange', function (event) {
            const attachmentIds = event?.detail?.selectedAttachmentIds ?? []
            element.value = attachmentIds.join(',')

            // If a Media Library selection counter is present, update it.
            // It will live in a modal outside the form, so we need to search
            // globally to find it.
            // @todo: Review.
            const mediaLibrarySelectionCounter = document.querySelector('.media-library-selected-count')
            if (!mediaLibrarySelectionCounter) {
              return
            }
            const numAttachments = attachmentIds.length
            const selectionLimit = event?.detail?.selectionLimit
            const countMessage = `${numAttachments}${selectionLimit ? ' of ' + selectionLimit : ''} item${(selectionLimit ?? numAttachments) === 1 ? '' : 's'} selected`
            mediaLibrarySelectionCounter.textContent = countMessage
          });
        }
      );
    }
  }
})(Drupal, once)
