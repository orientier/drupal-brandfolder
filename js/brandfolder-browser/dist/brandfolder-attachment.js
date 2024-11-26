var __decorate = (this && this.__decorate) || function (decorators, target, key, desc) {
    var c = arguments.length, r = c < 3 ? target : desc === null ? desc = Object.getOwnPropertyDescriptor(target, key) : desc, d;
    if (typeof Reflect === "object" && typeof Reflect.decorate === "function") r = Reflect.decorate(decorators, target, key, desc);
    else for (var i = decorators.length - 1; i >= 0; i--) if (d = decorators[i]) r = (c < 3 ? d(r) : c > 3 ? d(target, key, r) : d(target, key)) || r;
    return c > 3 && r && Object.defineProperty(target, key, r), r;
};
import { html, css, LitElement } from 'lit';
import { customElement, property } from 'lit/decorators.js';
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
        this.mimetype = null;
    }
    /**
     * Callback executed when the element is added to the document.
     */
    connectedCallback() {
        super.connectedCallback();
        // If the attachment property is set, use it to set other properties.
        if (this.attachment) {
            this.attachmentId = this.attachment?.id;
            this.mimetype = this.attachment?.mimetype;
            this.extension = this.attachment?.extension;
            this.filename = this.attachment?.filename;
            this.size = this.attachment?.size;
            this.width = this.attachment?.width;
            this.height = this.attachment?.height;
            this.thumbnail_url = this.attachment?.thumbnail_url;
            this.cdn_url = this.attachment?.cdn_url;
            this.url = this.attachment?.url;
        }
    }
    _attachmentSelectionHandler() {
        // @todo: Internal state tracking selected status, and UI indicating it.
        const options = {
            detail: { attachmentId: this.attachmentId },
            bubbles: true,
            composed: true,
        };
        this.dispatchEvent(new CustomEvent('bfAttachmentSelection', options));
    }
    render() {
        return html `
      <!--      @todo: UI indicating and facilitating selected status/selection.-->
      <div @click=${this._attachmentSelectionHandler}>
        <brandfolder-media-container .isLink=${true}>
          <img
            slot="media"
            class="brandfolder-attachment__image"
            src="${this?.thumbnail_url}"
            alt="${this?.filename}"
          />
        </brandfolder-media-container>
        <p>${this?.filename}</p>
      </div>
    `;
    }
};
BrandfolderAttachment.styles = css `
    //img {
    //  max-width: 100%;
    //  height: auto;
    //}
    //
    //:host(:hover) {
    //  cursor: pointer;
    //}
    //
    //:host(:hover) img {
    //  transition: scale 0.2s;
    //  scale: 1.1;
    //}
  `;
__decorate([
    property({ type: String, attribute: 'bf-attachment-id' })
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
], BrandfolderAttachment.prototype, "thumbnail_url", void 0);
__decorate([
    property({ type: String, attribute: false })
], BrandfolderAttachment.prototype, "cdn_url", void 0);
__decorate([
    property({ type: String, attribute: false })
], BrandfolderAttachment.prototype, "url", void 0);
BrandfolderAttachment = __decorate([
    customElement('brandfolder-attachment')
], BrandfolderAttachment);
export { BrandfolderAttachment };
//# sourceMappingURL=brandfolder-attachment.js.map