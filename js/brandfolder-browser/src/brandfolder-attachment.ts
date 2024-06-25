import {html, css, LitElement} from 'lit'
import {customElement, property} from 'lit/decorators.js'

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
    img {
      max-width: 100%;
      height: auto;
    }

    :host(:hover) {
      cursor: pointer;
    }

    :host(:hover) img {
      transition: scale 0.2s;
      scale: 1.1;
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

  @property({type: String, attribute: false})
  mimetype: string | null = null

  @property({type: String, attribute: false})
  extension: string

  @property({type: String, attribute: false})
  filename: string

  @property({type: Number, attribute: false})
  size: number

  @property({type: Number, attribute: false})
  width: number

  @property({type: Number, attribute: false})
  height: number

  @property({type: String, attribute: false})
  thumbnail_url: string

  @property({type: String, attribute: false})
  cdn_url: string

  @property({type: String, attribute: false})
  url: string

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
      this.thumbnail_url = this.attachment?.thumbnail_url
      this.cdn_url = this.attachment?.cdn_url
      this.url = this.attachment?.url
    }
  }

  private _attachmentSelectionHandler() {
    // @todo: Internal state stacking selected status, and UI indicating it.
    const options = {
      detail: {attachmentId: this.attachmentId},
      bubbles: true,
      composed: true,
    }
    this.dispatchEvent(new CustomEvent('bfAttachmentSelection', options))
  }

  override render() {
    return html`
      <!--      @todo: UI indicating and facilitating selected status/selection.-->
      <div @click=${this._attachmentSelectionHandler}>
        <img
          class="brandfolder-attachment__image"
          src="${this?.thumbnail_url}"
          alt="${this?.filename}"
        />
        <p>${this?.filename}</p>
      </div>
    `
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'brandfolder-attachment': BrandfolderAttachment
  }
}
