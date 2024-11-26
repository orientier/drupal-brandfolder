var __decorate = (this && this.__decorate) || function (decorators, target, key, desc) {
    var c = arguments.length, r = c < 3 ? target : desc === null ? desc = Object.getOwnPropertyDescriptor(target, key) : desc, d;
    if (typeof Reflect === "object" && typeof Reflect.decorate === "function") r = Reflect.decorate(decorators, target, key, desc);
    else for (var i = decorators.length - 1; i >= 0; i--) if (d = decorators[i]) r = (c < 3 ? d(r) : c > 3 ? d(target, key, r) : d(target, key)) || r;
    return c > 3 && r && Object.defineProperty(target, key, r), r;
};
import { html, css } from 'lit';
import { customElement, property } from 'lit/decorators.js';
import { BrandfolderAssetBase } from './brandfolder-asset-base';
/**
 * An element for displaying a media item, e.g. an image.
 */
let BrandfolderMediaContainer = class BrandfolderMediaContainer extends BrandfolderAssetBase {
    constructor() {
        super(...arguments);
        /**
         * Whether this item functions as a link.
         */
        this.isLink = false;
        /**
         * Whether this item is actively being engaged with.
         */
        this.isActive = false;
        /**
         * Optional display format.
         */
        this.displayFormat = 'small';
    }
    render() {
        return html `
      <div class="brandfolder-media-container__inner display-format--${this.displayFormat} ${this.isLink ? 'is-link' : ''} ${this.isActive ? 'is-active' : ''}" >
        <slot name="media"></slot>
      </div>
    `;
    }
};
BrandfolderMediaContainer.styles = css `
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
  `;
__decorate([
    property({ type: Boolean, attribute: false })
], BrandfolderMediaContainer.prototype, "isLink", void 0);
__decorate([
    property({ type: Boolean, attribute: false })
], BrandfolderMediaContainer.prototype, "isActive", void 0);
__decorate([
    property({ type: String, attribute: false })
], BrandfolderMediaContainer.prototype, "displayFormat", void 0);
BrandfolderMediaContainer = __decorate([
    customElement('brandfolder-media-container')
], BrandfolderMediaContainer);
export { BrandfolderMediaContainer };
//# sourceMappingURL=brandfolder-media-container.js.map