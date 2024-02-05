(function (Drupal, once) {
  Drupal.behaviors.BrandfolderBrowser = {
    attach: function attach(context) {
      once('bfBrowserBehavior', '.brandfolder-browser-container', context).forEach(
        (browserContainer) => {
          console.log('Drupal.behaviors.BrandfolderBrowser:attach:browserContainer', browserContainer)
          const browserId = browserContainer.dataset.bfBrowserId
          console.log('Drupal.behaviors.BrandfolderBrowser:attach:browserId', browserId)
          const browserSettings = drupalSettings.brandfolderBrowser[browserId]
          console.log('Drupal.behaviors.BrandfolderBrowser:attach:browserSettings', browserSettings)
          // Test asset fetch.
          fetch(
            '/brandfolder-asset-fetch',
            {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json; charset=UTF-8',
              },
              body: JSON.stringify({
                asset_id: '5f9a3c7e7b2d4b0001b5c5b2',
                customMsg: 'Hello, World!',
              }),
            }
          )
            .then((response) => response.json())
            .then((data) => {
              console.log('Drupal.behaviors.BrandfolderBrowser:attach:assetFetchUrl:data', data)
              if (data?.assets?.length > 0) {
                browserContainer.innerHTML = data.assets.map((asset) => {
                  return asset?.attributes?.thumbnail_url ? `
                    <div class="brandfolder-asset">
                      <div class="brandfolder-asset__image">
                        <img src="${asset?.attributes?.thumbnail_url}" alt="${asset.name}" />
                      </div>
                    </div>
                  ` : ''
                }).join('')
              }
              else {
                browserContainer.innerHTML = 'No assets found.'
              }
            })
            .catch((error) => {
              browserContainer.innerHTML = 'Error fetching assets.'
              console.error('Drupal.behaviors.BrandfolderBrowser:attach:assetFetchUrl:error', error)
            })
        }
      )
    }
  }
})(Drupal, once)
