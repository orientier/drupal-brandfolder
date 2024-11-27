var __decorate = (this && this.__decorate) || function (decorators, target, key, desc) {
    var c = arguments.length, r = c < 3 ? target : desc === null ? desc = Object.getOwnPropertyDescriptor(target, key) : desc, d;
    if (typeof Reflect === "object" && typeof Reflect.decorate === "function") r = Reflect.decorate(decorators, target, key, desc);
    else for (var i = decorators.length - 1; i >= 0; i--) if (d = decorators[i]) r = (c < 3 ? d(r) : c > 3 ? d(target, key, r) : d(target, key)) || r;
    return c > 3 && r && Object.defineProperty(target, key, r), r;
};
import { css, html, LitElement } from 'lit';
import { customElement, property, state } from 'lit/decorators.js';
import { bfBrowserFormatFilesize } from "./brandfolder-browser";
import { bfBrowserContext } from "./brandfolder-browser-context";
import { consume } from "@lit/context";
import { live } from "lit/directives/live.js";
/**
 * An element corresponding to an attachment in Brandfolder.
 */
let BrandfolderAttachment = class BrandfolderAttachment extends LitElement {
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
         * CDN URL.
         */
        this.cdnUrl = null;
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
         * The format/variant in which the attachment should be displayed.
         */
        this.displayFormat = 'default';
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
            this.cdnUrl = cdnUrl;
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
    /**
     * Render the component.
     */
    render() {
        let imgUrl = this?.thumbnailUrl;
        if (this?.cdnUrl) {
            const urlSansQuery = this.cdnUrl.replace(/^([^?]*)(\?.*)?$/, '$1');
            imgUrl = urlSansQuery + '?width=480&auto=webp&quality=80';
        }
        return html `
      <div
        class="bf-attachment__inner"
      >
        <div class="bf-attachment__image-wrapper">
          <brandfolder-media-container .isActive=${this._isHovered}>
            <img
              slot="media"
              class="bf-attachment__image"
              src="${imgUrl}"
              alt="${this?.filename}"
            />
          </brandfolder-media-container>
        </div>
        <div class="bf-attachment__info">
          <div class="bf-attachment__name">${this?.filename}</div>
          ${this?.displayFormat !== 'tray' ? html `
            <div class="bf-attachment__metadata">
              <div class="bf-attachment__metadata-item">
                ${this?.mimetype}
              </div>
              <div class="bf-attachment__metadata-item">
                ${this?.width} x ${this?.height}
              </div>
              <div class="bf-attachment__metadata-item">
                ${bfBrowserFormatFilesize(this?.size)}
              </div>
            </div>
          ` : ''}
        </div>
        ${this?.displayFormat === 'tray' ? html `
          <div class="attachment__deselection">
            <button
              @click=${this._attachmentSelectionHandler}
            >
              Deselect
            </button
          </div>
        ` : html `
          <div class="attachment__selection">
            <div class="attachment__selection-status">
              <input
                id="attachment-selection--${this.attachmentId}"
                name="attachment-selection--${this.attachmentId}"
                type="checkbox"
                .checked=${live(this._isSelected)}
                @change=${this._attachmentSelectionHandler}
              />
              <label for="attachment-selection--${this.attachmentId}">
                Select this attachment
              </label>
            </div>
          </div>
        `}
      </div>
    `;
    }
};
BrandfolderAttachment.styles = css `
    .bf-attachment__inner {
      cursor: pointer;
    }

    img {
      max-width: 100%;
      height: auto;
      max-height: max(8rem, 32vh);
    }

    .bf-attachment__info {
      padding: 0.25rem 0;
    }

    .bf-attachment__name {
      font-weight: bold;
      padding: 0.25rem;
    }

    .bf-attachment__metadata {
      display: flex;
      flex-wrap: wrap;
      gap: 0.5rem;
      font-size: 0.75em;
    }

    .bf-attachment__metadata-item {
      color: var(--color-gray-500);
      font-style: italic;
      padding: 0.25rem;
    }
  `;
__decorate([
    property({ type: String, attribute: 'bf-attachment-id', reflect: true })
], BrandfolderAttachment.prototype, "attachmentId", void 0);
__decorate([
    property({ type: Object, attribute: false })
], BrandfolderAttachment.prototype, "attachment", void 0);
__decorate([
    property({ type: String, attribute: false })
], BrandfolderAttachment.prototype, "mimetype", void 0);
__decorate([
    property({ type: String, attribute: false })
], BrandfolderAttachment.prototype, "extension", void 0);
__decorate([
    property({ type: String, attribute: false })
], BrandfolderAttachment.prototype, "filename", void 0);
__decorate([
    property({ type: Number, attribute: false })
], BrandfolderAttachment.prototype, "size", void 0);
__decorate([
    property({ type: Number, attribute: false })
], BrandfolderAttachment.prototype, "width", void 0);
__decorate([
    property({ type: Number, attribute: false })
], BrandfolderAttachment.prototype, "height", void 0);
__decorate([
    property({ type: String, attribute: false })
], BrandfolderAttachment.prototype, "thumbnailUrl", void 0);
__decorate([
    property({ type: String, attribute: false })
], BrandfolderAttachment.prototype, "cdnUrl", void 0);
__decorate([
    property({ type: String, attribute: false })
], BrandfolderAttachment.prototype, "url", void 0);
__decorate([
    property({ type: String, attribute: false })
], BrandfolderAttachment.prototype, "bfCdnUrlBase", void 0);
__decorate([
    property({ type: String, attribute: false })
], BrandfolderAttachment.prototype, "displayFormat", void 0);
__decorate([
    state()
], BrandfolderAttachment.prototype, "_isHovered", void 0);
__decorate([
    state()
], BrandfolderAttachment.prototype, "_isSelected", void 0);
__decorate([
    consume({ context: bfBrowserContext, subscribe: true })
], BrandfolderAttachment.prototype, "browserContext", void 0);
BrandfolderAttachment = __decorate([
    customElement('brandfolder-attachment')
], BrandfolderAttachment);
export { BrandfolderAttachment };
//# sourceMappingURL=brandfolder-attachment.js.map