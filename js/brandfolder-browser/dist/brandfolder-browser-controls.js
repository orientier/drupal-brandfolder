var __decorate = (this && this.__decorate) || function (decorators, target, key, desc) {
    var c = arguments.length, r = c < 3 ? target : desc === null ? desc = Object.getOwnPropertyDescriptor(target, key) : desc, d;
    if (typeof Reflect === "object" && typeof Reflect.decorate === "function") r = Reflect.decorate(decorators, target, key, desc);
    else for (var i = decorators.length - 1; i >= 0; i--) if (d = decorators[i]) r = (c < 3 ? d(r) : c > 3 ? d(target, key, r) : d(target, key)) || r;
    return c > 3 && r && Object.defineProperty(target, key, r), r;
};
import { html, css, LitElement } from 'lit';
import { customElement, property, query, queryAll, state, } from 'lit/decorators.js';
/**
 * User interface/input/controls for a Brandfolder browser.
 */
let BrandfolderBrowserControls = class BrandfolderBrowserControls extends LitElement {
    constructor() {
        super(...arguments);
        /**
         * An object with data sufficient to build user-facing controls.
         */
        this.controlSchema = null;
        /**
         * An object with properties corresponding to user-facing controls, with
         * any corresponding user-supplied values.
         */
        this._userInput = null;
    }
    /**
     * Handle use of the "reset" button. Reset all user input.
     */
    _controlsResetHandler() {
        this._userInput = null;
    }
    /**
     * Handle the submission of the search/filter/sort form.
     */
    _controlsSubmissionHandler() {
        // Notify ancestors of the submission.
        const options = {
            detail: { userInput: this._userInput },
            bubbles: true,
            composed: true,
        };
        this.dispatchEvent(new CustomEvent('bfBrowserControlsSubmission', options));
    }
    /**
     * Pull UI element values into state.
     */
    _controlsChangeHandler() {
        const userInput = {
            searchText: this.searchTextInput.value,
        };
        if (this.collectionInputs) {
            const collectionInputsArray = Array.from(this.collectionInputs);
            userInput.collections = collectionInputsArray
                .filter((input) => input.checked)
                .map((input) => input.value);
        }
        if (this.sectionInputs) {
            const sectionInputsArray = Array.from(this.sectionInputs);
            userInput.sections = sectionInputsArray
                .filter((input) => input.checked)
                .map((input) => input.value);
        }
        this._userInput = userInput;
    }
    render() {
        return html `
      <input type="text" class="search-text-input" aria-label="Search" .value="${this._userInput?.searchText ?? ''}" @change=${this._controlsChangeHandler} />
      ${(this.controlSchema?.collections && Object.keys(this.controlSchema?.collections)?.length > 1) &&
            html `
          <fieldset class="collections-container">
            <legend>Collections</legend>
            <div class="collections">
                ${Object.keys(this.controlSchema.collections).map((collectionId) => {
                const collectionName = this.controlSchema.collections[collectionId];
                const isSelected = this._userInput?.collections?.includes(collectionId);
                const inputName = 'collection';
                return html `
                      <input
                        type="checkbox"
                        class="collection-input"
                        id="collection-input--${collectionId}"
                        aria-label="${collectionName}"
                        name="${inputName}"
                        value=${collectionId}
                        .checked=${isSelected}
                        @change=${this._controlsChangeHandler}
                      />
                      <label for=${inputName}>${collectionName}</label>
                    `;
            })}
            </div>
          </fieldset>
          <fieldset class="sections-container">
            <legend>Sections</legend>
            <div class="sections">
              ${Object.keys(this.controlSchema.sections).map((sectionId) => {
                const sectionName = this.controlSchema.sections[sectionId];
                const isSelected = this._userInput?.sections?.includes(sectionId);
                const inputName = 'section';
                return html `
                    <input
                      type="checkbox"
                      class="section-input"
                      id="section-input--${sectionId}"
                      aria-label="${sectionName}"
                      name="${inputName}"
                      value=${sectionId}
                      .checked=${isSelected}
                      @change=${this._controlsChangeHandler}
                    />
                    <label for=${inputName}>${sectionName}</label>
                  `;
            })}
            </div>
          </fieldset>
        `}
      <button @click=${this._controlsResetHandler}>Reset</button>
      <button @click=${this._controlsSubmissionHandler}>Submit</button>
    `;
    }
};
BrandfolderBrowserControls.styles = css `
    :host {
      height: auto;
    }
  `;
__decorate([
    property({ type: Object, attribute: false })
], BrandfolderBrowserControls.prototype, "controlSchema", void 0);
__decorate([
    state()
], BrandfolderBrowserControls.prototype, "_userInput", void 0);
__decorate([
    query('.search-text-input')
], BrandfolderBrowserControls.prototype, "searchTextInput", void 0);
__decorate([
    queryAll('.collection-input')
], BrandfolderBrowserControls.prototype, "collectionInputs", void 0);
__decorate([
    queryAll('.section-input')
], BrandfolderBrowserControls.prototype, "sectionInputs", void 0);
BrandfolderBrowserControls = __decorate([
    customElement('brandfolder-browser-controls')
], BrandfolderBrowserControls);
export { BrandfolderBrowserControls };
//# sourceMappingURL=brandfolder-browser-controls.js.map