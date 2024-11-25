var __decorate = (this && this.__decorate) || function (decorators, target, key, desc) {
    var c = arguments.length, r = c < 3 ? target : desc === null ? desc = Object.getOwnPropertyDescriptor(target, key) : desc, d;
    if (typeof Reflect === "object" && typeof Reflect.decorate === "function") r = Reflect.decorate(decorators, target, key, desc);
    else for (var i = decorators.length - 1; i >= 0; i--) if (d = decorators[i]) r = (c < 3 ? d(r) : c > 3 ? d(target, key, r) : d(target, key)) || r;
    return c > 3 && r && Object.defineProperty(target, key, r), r;
};
import { css, html } from 'lit';
import { customElement, query } from 'lit/decorators.js';
import { BfBrowserControlBase } from "./bf-browser-control-base";
/**
 * UI for specifying Brandfolder tags for asset filtering.
 */
let BfBrowserSearchControl = class BfBrowserSearchControl extends BfBrowserControlBase {
    /**
     * Respond to text input changes.
     */
    _changeHandler() {
        this.controlInput.searchText = this.searchTextInput.value;
        this._dispatchChangeEvent();
    }
    render() {
        return html `
      <input type="text" class="search-text-input" aria-label="Search"
             name="brandfolder-browser-controls-search"
             placeholder="Enter search text..."
             .value="${this.controlInput?.searchText ?? ''}"
             @change=${this._changeHandler}
             @keyup=${(e) => {
            if (e.key === 'Enter') {
                this._dispatchSubmitEvent();
            }
        }}
      />
    `;
    }
};
BfBrowserSearchControl.styles = css `
    :host {
      flex: 1;
      display: flex;
    }

    .search-text-input {
      width: 100%;
      font-size: 1rem;
      padding: 0.3rem 0.4rem;
      color: var(--color-gray-900);
    }
  `;
__decorate([
    query('.search-text-input')
], BfBrowserSearchControl.prototype, "searchTextInput", void 0);
BfBrowserSearchControl = __decorate([
    customElement('brandfolder-browser-control--search')
], BfBrowserSearchControl);
export { BfBrowserSearchControl };
//# sourceMappingURL=bf-browser-control--search.js.map