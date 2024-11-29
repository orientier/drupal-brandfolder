import {LitElement, PropertyValues} from 'lit'
import {property, state} from 'lit/decorators.js'
import {BfAsset} from "../asset/brandfolder-asset-base";
import {
  BfBrowserContext,
  bfBrowserContext
} from "../brandfolder-browser-context";
import {consume} from "@lit/context";

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
  asset?: BfAsset
}

export type BfAttachmentList = {
  [key: string]: BfAttachment
}

/**
 * A base class for custom elements pertaining to Brandfolder attachments.
 */
export class BrandfolderAttachmentBase extends LitElement {

  /**
   * Brandfolder's unique ID for the attachment.
   */
  @property({type: String, attribute: 'bf-attachment-id', reflect: true})
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
   * CDN URL.
   */
  @property({type: String, attribute: false})
  cdnUrl: string | null = null

  /**
   * Default image URL for display. Derived from the CDN URL. It's more
   * performant to use the same exact image URL for all instances of the same
   * attachment, so we can benefit from browser caching (even though we could
   * use a smaller image in the attachment selection tray, for example).
   */
  @property({type: String, attribute: false})
  imageSrcUrl: string | null = null

  /**
   * State tracking whether the user is hovering over the attachment.
   */
  @state()
  protected _isHovered = false

  /**
   * Property tracking whether the attachment is selected.
   */
  @state()
  protected _isSelected = false

  /**
   * Consume the browser context so we can cleanly access browser-wide data
   * (and subscribe to be made aware of any changes).
   */
  @consume({context: bfBrowserContext, subscribe: true})
  browserContext: BfBrowserContext

  /**
   * Lifecycle method called before update() to compute values needed during
   * the update.
   */
  override willUpdate(changedProperties: PropertyValues<this>) {
    // Use the attachment property to populate numerous derivative properties.
    if (changedProperties.has('attachment') && this.attachment) {
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
        // Add the computed CDN URL to the attachment object if it was
        // missing. This will be useful when accessing the attachment elsewhere
        // in the app, outside an asset context.
        this.attachment.cdn_url = cdnUrl
      }
      if (cdnUrl) {
        // Store the basic CDN URL without any default query params.
        this.cdnUrl = cdnUrl.replace(/^([^?]*)(\?.*)?$/, '$1')
        // Set the default image URL for display, with CDN image
        // transformations/directives.
        this.imageSrcUrl = this.cdnUrl + '?width=480&auto=webp&quality=80'
      }
      else {
        this.imageSrcUrl = this.thumbnailUrl
      }
    }
    if (this?.attachmentId && this?.browserContext?.selectedAttachments) {
      this._isSelected = !!this.browserContext.selectedAttachments[this.attachmentId]
    }
  }

  /**
   * Handle selection/deselection of this attachment.
   */
  protected _attachmentSelectionHandler() {
    this._isSelected = !this._isSelected
    const options = {
      detail: {
        attachmentId: this.attachmentId,
        attachment: this.attachment,
        isSelected: this._isSelected,
      },
      bubbles: true,
      composed: true,
    }
    this.dispatchEvent(new CustomEvent('bfAttachmentSelection', options))
  }
}
