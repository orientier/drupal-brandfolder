import {html, css} from 'lit'
import {customElement} from 'lit/decorators.js'
import {BrandfolderAssetBase} from './brandfolder-asset-base'

/**
 * An element corresponding to an asset in Brandfolder.
 */
@customElement('brandfolder-asset-preview')
export class BrandfolderAssetPreview extends BrandfolderAssetBase {
  static override styles = css`
    :host {
      overflow: hidden;
      display: flex;
      justify-content: center;
      align-items: center;
      background-image: conic-gradient(
        var(--color-gray-50) 90deg,
        var(--color-gray-100) 90deg 180deg,
        var(--color-gray-50) 180deg 270deg,
        var(--color-gray-100) 270deg
      );
      background-position: top left;
      background-size: 0.5rem 0.5rem;
    }

    img {
      max-width: 100%;
      height: auto;
    }

    :host(:hover) {
      cursor: pointer;
    }

    :host(:hover) img {
      transition: scale 0.2s;
      scale: 1.1;
    }
  `

  override render() {
    return html`
      <img
        class="brandfolder-asset__image"
        src="${this?.asset?.attributes?.thumbnail_url}"
        alt="${this?.asset?.attributes?.name}"
      />
    `
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'brandfolder-asset-preview': BrandfolderAssetPreview
  }
}
