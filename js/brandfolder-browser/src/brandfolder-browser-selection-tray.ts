import {css, html, LitElement} from 'lit'
import {customElement} from 'lit/decorators.js'
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
    .bf-browser-selection-tray__inner {
      padding: 0.5rem;
    }
    .bf-browser-selection-tray__heading {
      font-size: 1.25rem;
      font-weight: bold;
      margin: 0 0 0.5rem;
    }
    .bf-browser__selected-attachments-list {
      list-style: none;
      padding: 0;
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(8rem, 1fr));
      gap: 1rem;
    }
    .bf-browser__selected-attachment-item {

    }
  `

  /**
   * Consume the browser context so we can cleanly access browser-wide data.
   */
  @consume({context: bfBrowserContext, subscribe: true})
  browserContext: BfBrowserContext

  /**
   * Render the component.
   */
  override render() {
    return html`
      <div class="bf-browser-selection-tray__inner">
        <h3 class="bf-browser-selection-tray__heading">Selected Attachments</h3>
        <ul class="bf-browser__selected-attachments-list">
          ${Object.values(this.browserContext.selectedAttachments).map(
            (attachment) => html`
              <li class="bf-browser__selected-attachment-item">
                <brandfolder-attachment
                  .attachment=${attachment}
                  .displayFormat=${'tray'}
                />
              </li>
            `
          )}
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
