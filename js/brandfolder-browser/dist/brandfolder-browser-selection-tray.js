var __decorate = (this && this.__decorate) || function (decorators, target, key, desc) {
    var c = arguments.length, r = c < 3 ? target : desc === null ? desc = Object.getOwnPropertyDescriptor(target, key) : desc, d;
    if (typeof Reflect === "object" && typeof Reflect.decorate === "function") r = Reflect.decorate(decorators, target, key, desc);
    else for (var i = decorators.length - 1; i >= 0; i--) if (d = decorators[i]) r = (c < 3 ? d(r) : c > 3 ? d(target, key, r) : d(target, key)) || r;
    return c > 3 && r && Object.defineProperty(target, key, r), r;
};
import { css, html, LitElement } from 'lit';
import { customElement } from 'lit/decorators.js';
import { bfBrowserContext } from "./brandfolder-browser-context";
import { consume } from "@lit/context";
/**
 * An element to display a list of currently selected attachments, allow
 * deselection, etc.
 */
let BrandfolderBrowserSelectionTray = class BrandfolderBrowserSelectionTray extends LitElement {
    /**
     * Render the component.
     */
    render() {
        return html `
      <div class="bf-browser-selection-tray__inner">
        <h3 class="bf-browser-selection-tray__heading">Selected Attachments</h3>
        <ul class="bf-browser__selected-attachments-list">
          ${Object.values(this.browserContext.selectedAttachments).map((attachment) => html `
              <li class="bf-browser__selected-attachment-item">
                <brandfolder-attachment
                  .attachment=${attachment}
                  .displayFormat=${'tray'}
                />
              </li>
            `)}
        </ul>
      </div>
    `;
    }
};
BrandfolderBrowserSelectionTray.styles = css `
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
  `;
__decorate([
    consume({ context: bfBrowserContext, subscribe: true })
], BrandfolderBrowserSelectionTray.prototype, "browserContext", void 0);
BrandfolderBrowserSelectionTray = __decorate([
    customElement('brandfolder-browser-selection-tray')
], BrandfolderBrowserSelectionTray);
export { BrandfolderBrowserSelectionTray };
//# sourceMappingURL=brandfolder-browser-selection-tray.js.map