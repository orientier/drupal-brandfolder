import {css, html, LitElement} from 'lit'
import {customElement, property, state} from 'lit/decorators.js'
import {bfBrowserFormatFilesize} from "./brandfolder-browser";

export type BfAttachment = {
  id: string
  mimetype: string
  extension: string
  filename: string
  size: number
  width: number
  height: number
  thumbnail_url: string
  cdn_url: string
  url: string
}

/**
 * An element corresponding to an attachment in Brandfolder.
 */
@customElement('brandfolder-attachment')
export class BrandfolderAttachment extends LitElement {
  static override styles = css`
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
  `

  /**
   * Brandfolder's unique ID for the attachment.
   */
  @property({type: String, attribute: 'bf-attachment-id'})
  attachmentId: string | null = null

  /**
   * An object matching the Brandfolder attachment schema.
   */
  @property({type: Object, attribute: false})
  attachment: BfAttachment | null = null

  /**
   * The MIME type of the file.
   */
  @property({type: String, attribute: false})
  mimetype: string | null = null

  /**
   * The file extension.
   */
  @property({type: String, attribute: false})
  extension: string | null = null

  /**
   * The filename of the file.
   */
  @property({type: String, attribute: false})
  filename: string | null = null

  /**
   * The size of the file in bytes.
   */
  @property({type: Number, attribute: false})
  size: number | null = null

  /**
   * The width of the file in pixels.
   */
  @property({type: Number, attribute: false})
  width: number | null = null

  /**
   * The height of the file in pixels.
   */
  @property({type: Number, attribute: false})
  height: number | null = null

  /**
   * The URL of the thumbnail image.
   */
  @property({type: String, attribute: false})
  thumbnailUrl: string | null = null

  /**
   * CDN URL.
   */
  @property({type: String, attribute: false})
  cdnUrl: string | null = null

  /**
   * The standard URL of the attachment. Not as performant or manipulable as
   * the CDN URL.
   */
  @property({type: String, attribute: false})
  url: string | null = null

  /**
   * The base URL for all CDN URLs for this attachment's Brandfolder.
   */
  @property({type: String, attribute: false})
  bfCdnUrlBase: string | null = null

  /**
   * State tracking whether the user is hovering over the attachment.
   */
  @state()
  private _isHovered = false

  /**
   * Callback executed when the element is added to the document.
   */
  override connectedCallback() {
    super.connectedCallback()
    // If the attachment property is set, use it to set other properties.
    if (this.attachment) {
      this.attachmentId = this.attachment?.id
      this.mimetype = this.attachment?.mimetype
      this.extension = this.attachment?.extension
      this.filename = this.attachment?.filename
      this.size = this.attachment?.size
      this.width = this.attachment?.width
      this.height = this.attachment?.height
      this.thumbnailUrl = this.attachment?.thumbnail_url
      this.url = this.attachment?.url

      let cdnUrl = null
      if (this.attachment?.cdn_url) {
        cdnUrl = this.attachment?.cdn_url
      }
      else if (this.bfCdnUrlBase) {
        // Extract the URL-friendly filename and extension from the standard URL.
        // e.g. if the standard URL is "https://storage-us-gcs.bfldr.com/3qjgh7v9cwkb65r5cnn6hrt/v/1228706135/original/isabella-mendes-tropical-cocktail.jpg?Expires=1732681812&KeyName=gcs-bfldr-prod&Signature=g3Jh7LBsmDVeXfbAWcfUCHkAH-4=",
        // the extracted filename and extension would be "isabella-mendes-tropical-cocktail.jpg"
        let urlFilename = this.url ? this.url.replace(/^[^?]*\/([^/?]+)(\?.*)?$/, '$1') : null
        if (!urlFilename?.length) {
          urlFilename = 'attachment.jpg'
        }
        cdnUrl = `${this.bfCdnUrlBase}/at/${this.attachmentId}/${urlFilename}`
      }
      this.cdnUrl = cdnUrl
    }
  }

  private _attachmentSelectionHandler() {
    // @todo: Internal state tracking selected status, and UI indicating it.
    const options = {
      detail: {attachmentId: this.attachmentId},
      bubbles: true,
      composed: true,
    }
    this.dispatchEvent(new CustomEvent('bfAttachmentSelection', options))
  }

  override render() {
    let imgUrl = this?.thumbnailUrl
    if (this?.cdnUrl) {
      const urlSansQuery = this.cdnUrl.replace(/^([^?]*)(\?.*)?$/, '$1')
      imgUrl = urlSansQuery + '?width=480&auto=webp&quality=80'
    }

    return html`
      <!--      @todo: UI indicating and facilitating selected status/selection.-->
      <div
        class="bf-attachment__inner"
        @mouseenter=${() => {this._isHovered = true}}
        @mouseleave=${() => {this._isHovered = false}}
        @click=${this._attachmentSelectionHandler}
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
      </div>
    `
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'brandfolder-attachment': BrandfolderAttachment
  }
}
