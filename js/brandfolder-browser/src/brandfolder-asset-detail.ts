import {html, css} from 'lit'
import {customElement} from 'lit/decorators.js'
import {BrandfolderAssetBase} from './brandfolder-asset-base'

/**
 * An element displaying the details of an individual asset and allowing users
 * to select one or more of the asset's attachments.
 */
@customElement('brandfolder-asset-detail')
export class BrandfolderAssetDetail extends BrandfolderAssetBase {
  static override styles = css`
    :host {
      z-index: 2;
      background: var(--color-gray-900-trans);
      grid-area: 1 / 1 / -1 / -1;
    }

    .brandfolder-asset__container {
      height: 100%;
      display: grid;
      grid-template-columns: clamp(4rem, 20%, 10rem) 1fr;
    }

    .backward-navigation-pane {
      display: flex;
      justify-content: center;
      align-items: center;
      cursor: pointer;
    }

    .back-button {
      width: 2rem;
      height: 2rem;
      padding: 0.5rem;
      display: flex;
      align-items: center;
      transition: scale 0.2s;
    }
    .backward-navigation-pane:hover .back-button {
      scale: 1.25;
    }
    svg {
      stroke: var(--color-gray-100);
      stroke-width: 0.1rem;
      stroke-linecap: round;
    }

    .brandfolder-asset__content {
      background: var(--color-white);
      padding: 1.5rem;
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1rem;
      height: 100%;
      overflow: scroll;
    }

    img {
      max-width: 100%;
      height: auto;
    }

    .brandfolder-asset__info,
    .brandfolder-asset__attachments {
      padding: 1rem;
    }

    .brandfolder-asset__attachments-header {
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .attachments__icon-container {
      display: flex;
      align-items: center;
      width: 0.8rem;
    }

    .brandfolder-asset__attachments-list {
      list-style-type: none;
      padding: 0;
      display: flex;
      flex-direction: column;
      gap: 1rem;
      align-items: center;
    }
  `

  /**
   * Dispatches a custom event to signal that the asset detail view should be
   * closed.
   */
  private _dispatchCloseEvent = () => {
    const closeEvent = new Event('bfAssetDetailClose', {
      bubbles: true,
      // Allow event to bubble up past the boundary of this element's shadow
      // DOM.
      composed: true,
    })
    this.dispatchEvent(closeEvent)
  }

  override render() {
    let imgUrl = this?.asset?.attributes?.thumbnail_url
    if (this?.asset?.attributes?.cdn_url) {
      imgUrl = this.asset.attributes.cdn_url.replace(/\?.*$/, '') + '?width=720&auto=webp&quality=80'
    }

    const attachments = Object.values(this?.asset?.attachments ?? {})
    const numAttachments = attachments.length

    return html`
      <div class="brandfolder-asset__container">
        <div class="backward-navigation-pane" @click=${this._dispatchCloseEvent}>
          <div class="back-button">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 19.6 19.6" class="back-button__icon">
              <path d="M15.7,10.3H2.9M2.9,10.3l4.1,4.6M2.9,10.3l4.1-4.6"/>
            </svg>
          </div>
        </div>
        <div class="brandfolder-asset__content">
          <div class="brandfolder-asset__info">
            <h2>${this?.asset?.attributes?.name}</h2>
            <div class="brandfolder-asset__image-wrapper">
              <brandfolder-media-container .displayFormat=${'large'}>
                <img
                  slot="media"
                  class="brandfolder-asset__image"
                  src="${imgUrl}"
                  alt="${this?.asset?.attributes?.name}"
                />
              </brandfolder-media-container>
            </div>
            ${
              this?.asset?.attributes?.description &&
              `<p>${this.asset.attributes.description}</p>`
            }
          </div>
          <div class="brandfolder-asset__attachments">
            <header class="brandfolder-asset__attachments-header">
              <span class="attachments__icon-container">
                <svg class="attachments__icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path d="M364.2 83.8c-24.4-24.4-64-24.4-88.4 0l-184 184c-42.1 42.1-42.1 110.3 0 152.4s110.3 42.1 152.4 0l152-152c10.9-10.9 28.7-10.9 39.6 0s10.9 28.7 0 39.6l-152 152c-64 64-167.6 64-231.6 0s-64-167.6 0-231.6l184-184c46.3-46.3 121.3-46.3 167.6 0s46.3 121.3 0 167.6l-176 176c-28.6 28.6-75 28.6-103.6 0s-28.6-75 0-103.6l144-144c10.9-10.9 28.7-10.9 39.6 0s10.9 28.7 0 39.6l-144 144c-6.7 6.7-6.7 17.7 0 24.4s17.7 6.7 24.4 0l176-176c24.4-24.4 24.4-64 0-88.4z"/></svg>
              </span>
              <span class="attachments__count">
                ${numAttachments} attachment${numAttachments == 1 ? '' : 's'}
              </span>
            </header>
            <ul class="brandfolder-asset__attachments-list">
            ${Object.values(this?.asset?.attachments).map(
              (attachment) => html`
                <li class="brandfolder-asset__attachment-list-item">
                  <!-- @todo: include attachment cdn_url in data set if possible without extra API calls. -->
                  <brandfolder-attachment .attachment=${attachment} />
                </li>
              `
            )}
          </div>
        </div>
      </div>
    `
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'brandfolder-asset-detail': BrandfolderAssetDetail
  }
}
