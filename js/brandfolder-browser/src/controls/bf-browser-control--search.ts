import {css, html} from 'lit'
import {customElement, query} from 'lit/decorators.js'
import {BfBrowserControlBase} from "./bf-browser-control-base";

/**
 * UI for specifying Brandfolder tags for asset filtering.
 */
@customElement('brandfolder-browser-control--search')
export class BfBrowserSearchControl extends BfBrowserControlBase {
  static override styles = css`
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
  `

  /**
   * Create a reference to the input element.
   */
  @query('.search-text-input')
  searchTextInput: HTMLInputElement

  /**
   * Respond to text input changes.
   */
  private _changeHandler() {
    this.controlInput.searchText = this.searchTextInput.value
    this._dispatchChangeEvent()
  }

  /**
   * Render the search control.
   */
  override render() {
    return html`
      <input type="text" class="search-text-input" aria-label="Search"
             name="brandfolder-browser-controls-search"
             placeholder="Enter search text..."
             .value="${this.controlInput?.searchText ?? ''}"
             @change=${this._changeHandler}
             @keyup=${(e: KeyboardEvent) => {
               if (e.key === 'Enter') {
                 this._dispatchSubmitEvent()
               }
             }}
      />
    `
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'brandfolder-browser-control--search': BfBrowserSearchControl
  }
}
