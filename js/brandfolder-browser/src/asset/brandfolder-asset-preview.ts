import {html} from 'lit'
import {customElement} from 'lit/decorators.js'
import {BrandfolderAssetBase} from './brandfolder-asset-base'

/**
 * An element corresponding to an asset in Brandfolder.
 */
@customElement('brandfolder-asset-preview')
export class BrandfolderAssetPreview extends BrandfolderAssetBase {

  override render() {
    return html`
      <brandfolder-media-container .isLink=${true}>
        <img
          slot="media"
          class="brandfolder-asset__image"
          src="${this?.thumbnailUrl}"
          alt="${this?.name}"
        />
      </brandfolder-media-container>
    `
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'brandfolder-asset-preview': BrandfolderAssetPreview
  }
}
