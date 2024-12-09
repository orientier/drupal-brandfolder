import {html, css} from 'lit'
import {customElement} from 'lit/decorators.js'
import { unsafeHTML } from 'lit/directives/unsafe-html.js'
import {BrandfolderAssetBase} from './brandfolder-asset-base'
import {bfBrowserFormatDateAndTime} from '../bf-browser-utils'

import '../brandfolder-media-container'
import '../attachment/brandfolder-attachment-detail'

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
      grid-area: 1 / 1 / -2 / -1;
    }

    .brandfolder-asset__container {
      height: 100%;
      display: grid;
      grid-template-columns: clamp(4rem, 20%, 10rem) 1fr;
      container-type: size;
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
      box-sizing: border-box;
    }

    @container (max-width: 768px) {
      .brandfolder-asset__content {
        grid-template-columns: 1fr;
      }
    }

    img {
      max-width: 100%;
      height: auto;
      max-height: max(20rem, 50vh);
    }

    .brandfolder-asset__info,
    .brandfolder-asset__attachments {
      padding: 1rem;
    }

    .brandfolder-asset__name {
      margin: 0 0 1rem;
      font-size: 1.5rem;
    }

    .brandfolder-asset__metadata {
      display: flex;
      flex-direction: column;
      gap: 0.5em;
      font-size: 0.75em;
      color: var(--color-gray-500);
      padding: 0.5rem 0;
      font-style: italic;
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

    .brandfolder-asset__attachment-list-item {
      width: 100%;
      box-shadow: 0.2rem 0.2rem 0.5rem var(--color-gray-300);
      border: 1px solid var(--color-gray-50);
      border-radius: 0.25rem;
      box-sizing: border-box;
      padding: 0.5rem;
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
    let imgUrl = this?.thumbnailUrl
    if (this?.cdnUrl) {
      const urlSansQuery = this.cdnUrl.replace(/^([^?]*)(\?.*)?$/, '$1')
      imgUrl = urlSansQuery + '?width=720&auto=webp&quality=80'
    }

    const attachments = Object.values(this?.attachments ?? {})
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
            <h2 class="brandfolder-asset__name">${this?.name}</h2>
            <div class="brandfolder-asset__image-wrapper">
              <brandfolder-media-container .displayFormat=${'large'}>
                <img
                  slot="media"
                  class="brandfolder-asset__image"
                  src="${imgUrl}"
                  alt="${this?.name}"
                />
              </brandfolder-media-container>
            </div>
            <div class="brandfolder-asset__metadata">
              <div class="brandfolder-asset__metadata-item">
                Created on ${bfBrowserFormatDateAndTime(this?.creationDate)}
              </div>
              <div class="brandfolder-asset__metadata-item">
                Updated on ${bfBrowserFormatDateAndTime(this?.modificationDate)}
              </div>
              ${this?.publicationDate ? html`
                <div class="brandfolder-asset__metadata-item">
                  Published on ${bfBrowserFormatDateAndTime(this.publicationDate)}
                </div>
              ` : ''
              }
              ${this?.expirationDate ? html`
                <div class="brandfolder-asset__metadata-item">
                  Expires on ${bfBrowserFormatDateAndTime(this.expirationDate)}
                </div>
              ` : ''
              }
            </div>
            ${this?.description ? html`
              <div class="brandfolder-asset__description">${unsafeHTML(this.description)}</div>
              ` : ''
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
            ${Object.values(this?.attachments).map(
              (attachment) => html`
                <li class="brandfolder-asset__attachment-list-item">
                  <brandfolder-attachment-detail .attachment=${attachment} .bfCdnUrlBase="${this?.bfCdnUrlBase}" />
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
