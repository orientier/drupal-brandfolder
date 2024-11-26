var __decorate = (this && this.__decorate) || function (decorators, target, key, desc) {
    var c = arguments.length, r = c < 3 ? target : desc === null ? desc = Object.getOwnPropertyDescriptor(target, key) : desc, d;
    if (typeof Reflect === "object" && typeof Reflect.decorate === "function") r = Reflect.decorate(decorators, target, key, desc);
    else for (var i = decorators.length - 1; i >= 0; i--) if (d = decorators[i]) r = (c < 3 ? d(r) : c > 3 ? d(target, key, r) : d(target, key)) || r;
    return c > 3 && r && Object.defineProperty(target, key, r), r;
};
import { html } from 'lit';
import { customElement } from 'lit/decorators.js';
import { BrandfolderAssetBase } from './brandfolder-asset-base';
/**
 * An element corresponding to an asset in Brandfolder.
 */
let BrandfolderAssetPreview = class BrandfolderAssetPreview extends BrandfolderAssetBase {
    render() {
        return html `
      <brandfolder-media-container .isLink=${true}>
        <img
          slot="media"
          class="brandfolder-asset__image"
          src="${this?.asset?.attributes?.thumbnail_url}"
          alt="${this?.asset?.attributes?.name}"
        />
      </brandfolder-media-container>
    `;
    }
};
BrandfolderAssetPreview = __decorate([
    customElement('brandfolder-asset-preview')
], BrandfolderAssetPreview);
export { BrandfolderAssetPreview };
//# sourceMappingURL=brandfolder-asset-preview.js.map