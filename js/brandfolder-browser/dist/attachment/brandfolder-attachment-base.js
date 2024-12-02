var __decorate = (this && this.__decorate) || function (decorators, target, key, desc) {
    var c = arguments.length, r = c < 3 ? target : desc === null ? desc = Object.getOwnPropertyDescriptor(target, key) : desc, d;
    if (typeof Reflect === "object" && typeof Reflect.decorate === "function") r = Reflect.decorate(decorators, target, key, desc);
    else for (var i = decorators.length - 1; i >= 0; i--) if (d = decorators[i]) r = (c < 3 ? d(r) : c > 3 ? d(target, key, r) : d(target, key)) || r;
    return c > 3 && r && Object.defineProperty(target, key, r), r;
};
import { LitElement } from 'lit';
import { property, state } from 'lit/decorators.js';
import { bfBrowserContext } from "../brandfolder-browser-context";
import { consume } from "@lit/context";
/**
 * A base class for custom elements pertaining to Brandfolder attachments.
 */
export class BrandfolderAttachmentBase extends LitElement {
    constructor() {
        super(...arguments);
        /**
         * Brandfolder's unique ID for the attachment.
         */
        this.attachmentId = null;
        /**
         * An object matching the Brandfolder attachment schema.
         */
        this.attachment = null;
        /**
         * The MIME type of the file.
         */
        this.mimetype = null;
        /**
         * The file extension.
         */
        this.extension = null;
        /**
         * The filename of the file.
         */
        this.filename = null;
        /**
         * The size of the file in bytes.
         */
        this.size = null;
        /**
         * The width of the file in pixels.
         */
        this.width = null;
        /**
         * The height of the file in pixels.
         */
        this.height = null;
        /**
         * The URL of the thumbnail image.
         */
        this.thumbnailUrl = null;
        /**
         * The standard URL of the attachment. Not as performant or manipulable as
         * the CDN URL.
         */
        this.url = null;
        /**
         * The base URL for all CDN URLs for this attachment's Brandfolder.
         */
        this.bfCdnUrlBase = null;
        /**
         * CDN URL.
         */
        this.cdnUrl = null;
        /**
         * Default image URL for display. Derived from the CDN URL. It's more
         * performant to use the same exact image URL for all instances of the same
         * attachment, so we can benefit from browser caching (even though we could
         * use a smaller image in the attachment selection tray, for example).
         */
        this.imageSrcUrl = null;
        /**
         * State tracking whether the user is hovering over the attachment.
         */
        this._isHovered = false;
        /**
         * Property tracking whether the attachment is selected.
         */
        this._isSelected = false;
    }
    /**
     * Lifecycle method called before update() to compute values needed during
     * the update.
     */
    willUpdate(changedProperties) {
        // Use the attachment property to populate numerous derivative properties.
        if (changedProperties.has('attachment') && this.attachment) {
            this.attachmentId = this.attachment?.id;
            this.mimetype = this.attachment?.mimetype;
            this.extension = this.attachment?.extension;
            this.filename = this.attachment?.filename;
            this.size = this.attachment?.size;
            this.width = this.attachment?.width;
            this.height = this.attachment?.height;
            this.thumbnailUrl = this.attachment?.thumbnail_url;
            this.url = this.attachment?.url;
            let cdnUrl = null;
            if (this.attachment?.cdn_url) {
                cdnUrl = this.attachment?.cdn_url;
            }
            else if (this.bfCdnUrlBase) {
                // Extract the URL-friendly filename and extension from the standard URL.
                // e.g. if the standard URL is "https://storage-us-gcs.bfldr.com/3qjgh7v9cwkb65r5cnn6hrt/v/1228706135/original/isabella-mendes-tropical-cocktail.jpg?Expires=1732681812&KeyName=gcs-bfldr-prod&Signature=g3Jh7LBsmDVeXfbAWcfUCHkAH-4=",
                // the extracted filename and extension would be "isabella-mendes-tropical-cocktail.jpg"
                let urlFilename = this.url ? this.url.replace(/^[^?]*\/([^/?]+)(\?.*)?$/, '$1') : null;
                if (!urlFilename?.length) {
                    urlFilename = 'attachment.jpg';
                }
                cdnUrl = `${this.bfCdnUrlBase}/at/${this.attachmentId}/${urlFilename}`;
                // Add the computed CDN URL to the attachment object if it was
                // missing. This will be useful when accessing the attachment elsewhere
                // in the app, outside an asset context.
                this.attachment.cdn_url = cdnUrl;
            }
            if (cdnUrl) {
                // Store the basic CDN URL without any default query params.
                this.cdnUrl = cdnUrl.replace(/^([^?]*)(\?.*)?$/, '$1');
                // Set the default image URL for display, with CDN image
                // transformations/directives.
                let imageSrcUrl = this.cdnUrl;
                // Add URL params for supported URL/image types.
                const imgIsSvg = (this.mimetype && !this.mimetype?.includes('svg')) || (this.extension && !this.extension?.includes('svg') || imageSrcUrl.match(/\.svg$/));
                if (!imgIsSvg) {
                    imageSrcUrl += '?width=480&auto=webp&quality=75';
                }
                this.imageSrcUrl = imageSrcUrl;
            }
            else {
                this.imageSrcUrl = this.thumbnailUrl;
            }
        }
        if (this?.attachmentId && this?.browserContext?.selectedAttachments) {
            this._isSelected = !!this.browserContext.selectedAttachments[this.attachmentId];
        }
    }
    /**
     * Handle selection/deselection of this attachment.
     */
    _attachmentSelectionHandler() {
        this._isSelected = !this._isSelected;
        const options = {
            detail: {
                attachmentId: this.attachmentId,
                attachment: this.attachment,
                isSelected: this._isSelected,
            },
            bubbles: true,
            composed: true,
        };
        this.dispatchEvent(new CustomEvent('bfAttachmentSelection', options));
    }
}
__decorate([
    property({ type: String, attribute: 'bf-attachment-id', reflect: true })
], BrandfolderAttachmentBase.prototype, "attachmentId", void 0);
__decorate([
    property({ type: Object, attribute: false })
], BrandfolderAttachmentBase.prototype, "attachment", void 0);
__decorate([
    property({ type: String, attribute: false })
], BrandfolderAttachmentBase.prototype, "mimetype", void 0);
__decorate([
    property({ type: String, attribute: false })
], BrandfolderAttachmentBase.prototype, "extension", void 0);
__decorate([
    property({ type: String, attribute: false })
], BrandfolderAttachmentBase.prototype, "filename", void 0);
__decorate([
    property({ type: Number, attribute: false })
], BrandfolderAttachmentBase.prototype, "size", void 0);
__decorate([
    property({ type: Number, attribute: false })
], BrandfolderAttachmentBase.prototype, "width", void 0);
__decorate([
    property({ type: Number, attribute: false })
], BrandfolderAttachmentBase.prototype, "height", void 0);
__decorate([
    property({ type: String, attribute: false })
], BrandfolderAttachmentBase.prototype, "thumbnailUrl", void 0);
__decorate([
    property({ type: String, attribute: false })
], BrandfolderAttachmentBase.prototype, "url", void 0);
__decorate([
    property({ type: String, attribute: false })
], BrandfolderAttachmentBase.prototype, "bfCdnUrlBase", void 0);
__decorate([
    property({ type: String, attribute: false })
], BrandfolderAttachmentBase.prototype, "cdnUrl", void 0);
__decorate([
    property({ type: String, attribute: false })
], BrandfolderAttachmentBase.prototype, "imageSrcUrl", void 0);
__decorate([
    state()
], BrandfolderAttachmentBase.prototype, "_isHovered", void 0);
__decorate([
    state()
], BrandfolderAttachmentBase.prototype, "_isSelected", void 0);
__decorate([
    consume({ context: bfBrowserContext, subscribe: true })
], BrandfolderAttachmentBase.prototype, "browserContext", void 0);
//# sourceMappingURL=brandfolder-attachment-base.js.map