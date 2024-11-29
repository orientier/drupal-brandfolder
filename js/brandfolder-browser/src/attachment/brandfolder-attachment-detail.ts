import {css, html} from 'lit'
import {customElement} from 'lit/decorators.js'
import {bfBrowserFormatFilesize} from "../brandfolder-browser";
import {live} from "lit/directives/live.js";
import {BrandfolderAttachmentBase} from "./brandfolder-attachment-base";

/**
 * An element corresponding to an attachment in Brandfolder.
 */
@customElement('brandfolder-attachment-detail')
export class BrandfolderAttachmentDetail extends BrandfolderAttachmentBase {
  static override styles = css`
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

    .attachment__selection {
      margin: 0.5rem 0 0.25rem;
    }
    .attachment__selection-status {
      display: flex;
      align-items: center;
      justify-content: flex-start;
    }
    .attachment__selection-status input,
    .attachment__selection-status label {
      cursor: pointer;
    }
    .attachment__selection-status input {
      width: 1.25rem;
      height: 1.25rem;
    }
    .attachment__selection-status label {
      padding: 0.5rem 0.25rem;
      font-style: italic;
      transition: color 0.2s;
      color: var(--color-gray-700);
    }
    .attachment__selection-status:hover label {
      color: var(--color-gray-900);
    }
  `

  /**
   * Render the component.
   */
  override render() {
    return html`
      <div class="bf-attachment__inner">
        <div class="bf-attachment__image-wrapper">
          <brandfolder-media-container .isActive=${this._isHovered}>
            <img
              slot="media"
              class="bf-attachment__image"
              src="${this.imageSrcUrl}"
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
      </div>
    `
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'brandfolder-attachment-detail': BrandfolderAttachmentDetail
  }
}
