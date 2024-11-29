import {css, html, LitElement} from 'lit'
import {customElement, property} from 'lit/decorators.js'
import {
  BfBrowserContext,
  bfBrowserContext
} from "./brandfolder-browser-context";
import {consume} from "@lit/context";

/**
 * An element to display a list of currently selected attachments, allow
 * deselection, etc.
 */
@customElement('brandfolder-browser-selection-tray')
export class BrandfolderBrowserSelectionTray extends LitElement {
  static override styles = css`
    :host {
      --selection-item-size: 6rem;
    }
    .selection-tray__inner {
      box-shadow: 0 0.5rem 1.5rem var(--color-gray-600);
    }

    /* @todo Make this a component/pattern and dedupe with Controls. */
    .selection-tray__header {
      padding: 0.25rem;
      display: flex;
      gap: 0.5rem;
      transition: all 0.3s;
    }
    .selection-tray__inner.is-openable .selection-tray__header:hover {
      cursor: pointer;
      background: var(--color-gray-50);
    }
    .selection-tray__title,
    .selection-tray__open-indicator {
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 0.2rem;
    }
    .selection-tray__title {
      font-size: 1rem;
      font-weight: normal;
    }
    .selection-tray__open-indicator {
      width: 1.5rem;
      padding: 0 0.2rem;
    }
    .open-indicator__icon {
      width: 0.9rem;
      transition: transform 0.3s;
      transform: scaleY(-1);
    }
    .open-indicator__icon path {
      stroke: var(--color-gray-800);
      stroke-width: 1;
    }
    .selection-tray__header:hover .open-indicator__icon path {
      stroke-width: 1.5;
    }
    .open-indicator__icon.open {
      transform: scaleY(1);
    }

    .selection-tray__attachments-list {
      list-style: none;
      padding: 0 0.5rem 0;
      margin: 0;
      display: grid;
      grid-template-columns: repeat(auto-fill, var(--selection-item-size));
      gap: 1rem;
      max-height: 0;
      overflow: hidden;
      transition: max-height 0.3s, padding 0.3s;
    }
    .selection-tray__inner.is-open .selection-tray__attachments-list {
      padding: 0.5rem;
      max-height: 10rem;
      overflow: scroll;
    }
    .selection-tray__attachment-item {
      height: var(--selection-item-size);
      overflow: hidden;
    }
  `

  /**
   * Consume the browser context so we can cleanly access browser-wide data.
   */
  @consume({context: bfBrowserContext, subscribe: true})
  browserContext: BfBrowserContext

  /**
   * Manage open/closed state.
   */
  @property({type: Boolean, attribute: false})
  isOpen = true

  /**
   * Header click handler. Toggle open state if there are attachments to show.
   */
  private _headerClickHandler() {
    if (Object.values(this.browserContext.selectedAttachments).length > 0) {
      const trayToggleEvent = new Event('bfSelectionTrayToggle', {
        bubbles: true,
        composed: true,
      })
      this.dispatchEvent(trayToggleEvent)
    }
  }


  /**
   * Render the component.
   */
  override render() {
    const attachments = Object.values(this.browserContext.selectedAttachments)
    const numAttachments = attachments.length
    const selectionLimit = this.browserContext?.selectionLimit

    return html`
      <div class="selection-tray__inner ${this.isOpen ? 'is-open' : 'is-closed'} ${numAttachments > 0 ? 'is-openable' : 'is-not-openable'}">
        <header class="selection-tray__header" @click=${this._headerClickHandler}>
          <span class="selection-tray__title">
            ${numAttachments}${selectionLimit ? ' of ' + selectionLimit : ''} item${numAttachments == 1 ? '' : 's'} selected
          </span>
          ${numAttachments > 0 ? html`
          <span class="selection-tray__open-indicator">
            <svg
              class="open-indicator__icon ${this.isOpen ? 'open' : 'closed'}"
              xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 9"
            >
              <path d="M15 1.57812L7.66667 7.57812" />
              <path d="M1 1.57812L7.66667 7.57813" />
            </svg>
          </span>
          ` : ''}
        </header>
        <ul class="selection-tray__attachments-list">
          ${numAttachments > 0 ? attachments.map(
            (attachment) => html`
              <li class="selection-tray__attachment-item">
                <brandfolder-attachment-selection
                  .attachment=${attachment}
                />
              </li>
            `
          ) : ''}
        </ul>
      </div>
    `
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'brandfolder-browser-selection-tray': BrandfolderBrowserSelectionTray
  }
}
