import {html, css} from 'lit'
import {customElement, property} from 'lit/decorators.js'
import {BrandfolderAssetBase} from './brandfolder-asset-base'

/**
 * An element for displaying a media item, e.g. an image.
 */
@customElement('brandfolder-media-container')
export class BrandfolderMediaContainer extends BrandfolderAssetBase {
  static override styles = css`
    .brandfolder-media-container__inner {
      width: 100%;
      height: 100%;
      padding: 0.5rem;
      box-sizing: border-box;
      overflow: hidden;
      display: flex;
      justify-content: center;
      align-items: center;
      background-image: conic-gradient(
        var(--color-gray-50) 90deg,
        var(--color-gray-200) 90deg 180deg,
        var(--color-gray-50) 180deg 270deg,
        var(--color-gray-200) 270deg
      );
      background-position: top left;
      background-size: 1rem 1rem;
    }

    .brandfolder-media-container__inner.display-format--large {
      min-height: 12rem;
    }
    .brandfolder-media-container__inner.is-link {
      cursor: pointer;
    }
    .brandfolder-media-container__inner ::slotted(img) {
      max-width: 100%;
      height: auto;
      transition: scale 0.2s;
    }
    .brandfolder-media-container__inner.is-active ::slotted(img),
    .brandfolder-media-container__inner.is-link:hover ::slotted(img) {
      scale: 1.1;
    }
  `

  /**
   * Whether this item functions as a link.
   */
  @property({type: Boolean, attribute: false})
  isLink = false

  /**
   * Whether this item is actively being engaged with.
   */
  @property({type: Boolean, attribute: false})
  isActive = false

  /**
   * Optional display format.
   */
  @property({type: String, attribute: false})
  displayFormat = 'small'

  override render() {
    return html`
      <div class="brandfolder-media-container__inner display-format--${this.displayFormat} ${this.isLink ? 'is-link' : ''} ${this.isActive ? 'is-active' : ''}" >
        <slot name="media"></slot>
      </div>
    `
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'brandfolder-media-container': BrandfolderMediaContainer
  }
}
