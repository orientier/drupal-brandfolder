var __decorate = (this && this.__decorate) || function (decorators, target, key, desc) {
    var c = arguments.length, r = c < 3 ? target : desc === null ? desc = Object.getOwnPropertyDescriptor(target, key) : desc, d;
    if (typeof Reflect === "object" && typeof Reflect.decorate === "function") r = Reflect.decorate(decorators, target, key, desc);
    else for (var i = decorators.length - 1; i >= 0; i--) if (d = decorators[i]) r = (c < 3 ? d(r) : c > 3 ? d(target, key, r) : d(target, key)) || r;
    return c > 3 && r && Object.defineProperty(target, key, r), r;
};
import { LitElement } from 'lit';
import { property } from 'lit/decorators.js';
/**
 * Base class for elements corresponding to Brandfolder assets.
 */
export class BrandfolderAssetBase extends LitElement {
    constructor() {
        super(...arguments);
        /**
         * Brandfolder's unique ID for the asset.
         */
        this.assetId = null;
        /**
         * The Brandfolder asset.
         */
        this.asset = null;
    }
}
__decorate([
    property({ type: String, attribute: 'bf-asset-id' })
], BrandfolderAssetBase.prototype, "assetId", void 0);
__decorate([
    property({ type: Object, attribute: false })
], BrandfolderAssetBase.prototype, "asset", void 0);
//# sourceMappingURL=brandfolder-asset-base.js.map