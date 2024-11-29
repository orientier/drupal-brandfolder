import {css, html} from 'lit'
import {customElement} from 'lit/decorators.js'
import {BrandfolderAttachmentBase} from "./brandfolder-attachment-base";

/**
 * An element corresponding to an attachment in Brandfolder.
 */
@customElement('brandfolder-attachment-selection')
export class BrandfolderAttachmentSelection extends BrandfolderAttachmentBase {
  static override styles = css`
    .bf-attachment__inner {
      display: grid;
      grid-template: 1fr auto / 1fr;
    }
    .bf-attachment__image-wrapper,
    .bf-attachment__content {
      grid-area: 1 / 1 / 2 / 2;
    }
    .bf-attachment__content {
      display: grid;
      grid-template: 1.5rem auto / auto 1.5rem;
    }
    .attachment__deselection {
      grid-area: 1 / 2 / 2 / 3;
      padding: 0.25rem 0.25rem 0.5rem;
      background: var(--color-gray-900-trans);
      color: var(--color-white);
      transition: background 0.2s, font-size 0.2s;
      display: flex;
      justify-content: center;
      align-items: center;
      cursor: pointer;
      font-size: 1rem;
    }
    .attachment__deselection:hover {
      background: var(--color-gray-900);
      font-size: 1.2rem;
    }
    .bf-attachment__info {
      grid-area: 2 / 1 / 3 / 3;
      opacity: 0;
      transition: opacity 0.2s;
      font-weight: 200;
      font-size: 0.7rem;
      font-style: italic;
      display: flex;
      flex-direction: column;
      justify-content: flex-end;
    }
    .bf-attachment__content:hover .bf-attachment__info {
      opacity: 1;
    }
    .bf-attachment__name {
      background: var(--color-gray-900-trans);
      color: var(--color-white);
      padding: 0.1rem;
      max-width: var(--selection-item-size);
      overflow-wrap: break-word;
      box-sizing: border-box;
    }
  `

  /**
   * Render the component.
   */
  override render() {
    return html`
      <div class="bf-attachment__inner">
        <div class="bf-attachment__image-wrapper">
          <brandfolder-media-container
            .isActive=${this._isHovered}
            .displayFormat=${'mini'}
          >
            <img
              slot="media"
              class="bf-attachment__image"
              src="${this.imageSrcUrl}"
              alt="${this?.filename}"
            />
          </brandfolder-media-container>
        </div>
        <div class="bf-attachment__content">
          <div
            class="attachment__deselection"
            @click=${this._attachmentSelectionHandler}
          >
            x
          </div>
          <div class="bf-attachment__info">
            <div class="bf-attachment__name">${this?.filename}</div>
          </div>
        </div>
      </div>
    `
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'brandfolder-attachment-selection': BrandfolderAttachmentSelection
  }
}
