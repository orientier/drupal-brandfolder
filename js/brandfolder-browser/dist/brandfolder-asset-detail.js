var __decorate = (this && this.__decorate) || function (decorators, target, key, desc) {
    var c = arguments.length, r = c < 3 ? target : desc === null ? desc = Object.getOwnPropertyDescriptor(target, key) : desc, d;
    if (typeof Reflect === "object" && typeof Reflect.decorate === "function") r = Reflect.decorate(decorators, target, key, desc);
    else for (var i = decorators.length - 1; i >= 0; i--) if (d = decorators[i]) r = (c < 3 ? d(r) : c > 3 ? d(target, key, r) : d(target, key)) || r;
    return c > 3 && r && Object.defineProperty(target, key, r), r;
};
import { html, css } from 'lit';
import { customElement } from 'lit/decorators.js';
import { BrandfolderAssetBase } from './brandfolder-asset-base';
/**
 * An element displaying the details of an individual asset and allowing users
 * to select one or more of the asset's attachments.
 */
let BrandfolderAssetDetail = class BrandfolderAssetDetail extends BrandfolderAssetBase {
    constructor() {
        super(...arguments);
        /**
         * Dispatches a custom event to signal that the asset detail view should be
         * closed.
         */
        this._dispatchCloseEvent = () => {
            const closeEvent = new Event('bfAssetDetailClose', {
                bubbles: true,
                // Allow event to bubble up past the boundary of this element's shadow
                // DOM.
                composed: true,
            });
            this.dispatchEvent(closeEvent);
        };
    }
    render() {
        return html `
      <div class="brandfolder-asset__container">
        <div class="close-button" @click=${this._dispatchCloseEvent}>X</div>
        <div class="brandfolder-asset__content">
          <div class="brandfolder-asset__info">
            <h2>${this?.asset?.attributes?.name}</h2>
            <img
              class="brandfolder-asset__image"
              src="${this?.asset?.attributes?.thumbnail_url}"
              alt="${this?.asset?.attributes?.name}"
            />
            ${this?.asset?.attributes?.description &&
            `<p>${this.asset.attributes.description}</p>`}
          </div>
          <div class="brandfolder-asset__attachments">
            <h4>Attachments</h4>
            <ul class="brandfolder-asset__attachments-list">
            ${Object.values(this?.asset?.attachments).map((attachment) => html `
                <li class="brandfolder-asset__attachment-list-item">
                  <!-- @todo: include attachment cdn_url in data set if possible without extra API calls. -->
                  <brandfolder-attachment .attachment=${attachment} />
                </li>
              `)}
          </div>
        </div>
      </div>
    `;
    }
};
BrandfolderAssetDetail.styles = css `
    :host {
      //position: fixed;
      z-index: 2;
      //top: var(--top-offset);
      //left: 0;
      //width: 100%;
      //height: 100%;
      background: white;
      grid-area: 1 / 1 / -1 / -1;
    }

    .brandfolder-asset__container {
      position: relative;
      height: 100%;
    }

    .close-button {
      position: absolute;
      top: 0;
      right: 0;
      width: 1rem;
      height: 1rem;
      padding: 0.5rem;
      cursor: pointer;
      font-weight: bold;
      display: flex;
      align-items: center;
    }
    .close-button:hover {
      scale: 1.1;
      transition: scale 0.2s;
    }

    .brandfolder-asset__content {
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

    .brandfolder-asset__attachments-list {
      list-style-type: none;
      padding: 0;
      display: flex;
      flex-direction: column;
      gap: 1rem;
      align-items: center;
    }
  `;
BrandfolderAssetDetail = __decorate([
    customElement('brandfolder-asset-detail')
], BrandfolderAssetDetail);
export { BrandfolderAssetDetail };
//# sourceMappingURL=brandfolder-asset-detail.js.map