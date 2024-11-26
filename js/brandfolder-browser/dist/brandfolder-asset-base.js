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
         * The asset.
         */
        this.asset = null;
        /**
         * The asset name.
         */
        this.name = null;
        /**
         * Thumbnail URL.
         */
        this.thumbnailUrl = null;
        /**
         * Description.
         */
        this.description = null;
        /**
         * CDN URL.
         */
        this.cdnUrl = null;
        /**
         * Attachments.
         */
        this.attachments = null;
        /**
         * Tags.
         */
        this.tags = null;
        /**
         * Labels to which the asset belongs.
         */
        this.labels = null;
        /**
         * Collections to which the asset belongs.
         */
        this.collections = null;
        /**
         * Date the asset was created.
         */
        this.creationDate = null;
        /**
         * Date the asset was last modified.
         */
        this.modificationDate = null;
        /**
         * Date the asset was published.
         */
        this.publicationDate = null;
        /**
         * Date the asset expires.
         */
        this.expirationDate = null;
        /**
         * A string used in CDN URLs for assets and attachments in this asset's
         * Brandfolder.
         */
        this.bfCdnUrlBase = null;
    }
    /**
     * Connected callback. Set as many properties as possible from the asset.
     */
    connectedCallback() {
        super.connectedCallback();
        if (this.asset) {
            this.assetId = this.asset.id;
            this.name = this.asset.attributes.name;
            this.thumbnailUrl = this.asset.attributes.thumbnail_url;
            this.description = this.asset.attributes.description;
            this.attachments = this.asset.attachments;
            this.creationDate = this.asset.attributes.created_at;
            this.modificationDate = this.asset.attributes.updated_at;
            this.publicationDate = this.asset.attributes.availability_start;
            this.expirationDate = this.asset.attributes.availability_end;
            const cdnUrl = this.asset.attributes.cdn_url;
            this.cdnUrl = cdnUrl;
            this.bfCdnUrlBase = cdnUrl.replace(/^(.*)\/as\/.*$/, '$1');
        }
    }
}
__decorate([
    property({ type: String, attribute: 'bf-asset-id' })
], BrandfolderAssetBase.prototype, "assetId", void 0);
__decorate([
    property({ type: Object, attribute: false })
], BrandfolderAssetBase.prototype, "asset", void 0);
__decorate([
    property({ type: String, attribute: false })
], BrandfolderAssetBase.prototype, "name", void 0);
__decorate([
    property({ type: String, attribute: false })
], BrandfolderAssetBase.prototype, "thumbnailUrl", void 0);
__decorate([
    property({ type: String, attribute: false })
], BrandfolderAssetBase.prototype, "description", void 0);
__decorate([
    property({ type: String, attribute: false })
], BrandfolderAssetBase.prototype, "cdnUrl", void 0);
__decorate([
    property({ type: Array, attribute: false })
], BrandfolderAssetBase.prototype, "attachments", void 0);
__decorate([
    property({ type: Array, attribute: false })
], BrandfolderAssetBase.prototype, "tags", void 0);
__decorate([
    property({ type: Array, attribute: false })
], BrandfolderAssetBase.prototype, "labels", void 0);
__decorate([
    property({ type: Object, attribute: false })
], BrandfolderAssetBase.prototype, "collections", void 0);
__decorate([
    property({ type: String, attribute: false })
], BrandfolderAssetBase.prototype, "creationDate", void 0);
__decorate([
    property({ type: String, attribute: false })
], BrandfolderAssetBase.prototype, "modificationDate", void 0);
__decorate([
    property({ type: String, attribute: false })
], BrandfolderAssetBase.prototype, "publicationDate", void 0);
__decorate([
    property({ type: String, attribute: false })
], BrandfolderAssetBase.prototype, "expirationDate", void 0);
__decorate([
    property({ type: String, attribute: false })
], BrandfolderAssetBase.prototype, "bfCdnUrlBase", void 0);
//# sourceMappingURL=brandfolder-asset-base.js.map