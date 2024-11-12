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
    /**
     * Constructor.
     */
    constructor() {
        super();
        /**
         * An object with data sufficient to build user-facing controls.
         */
        this.controlSchema = null;
        /**
         * An object with properties corresponding to user-facing controls, with
         * any corresponding user-supplied values.
         */
        this._controlsInput = null;
        this.addEventListener('bfLabelsChanged', this._labelsChangeHandler);
    }
    /**
     * Handle use of the "reset" button. Reset all user input.
     */
    _controlsResetHandler() {
        this._controlsInput = null;
    }
    /**
     * Handle the submission of the search/filter/sort form.
     */
    _controlsSubmissionHandler() {
        // Notify ancestors of the submission.
        const options = {
            detail: { userInput: this._controlsInput },
            bubbles: true,
            composed: true,
        };
        this.dispatchEvent(new CustomEvent('bfBrowserControlsSubmission', options));
    }
    /**
     * Pull UI element values into state. Call this whenever any relevant UI
     * element changes. This isn't as efficient as making precise updates
     * affecting only the state/prop corresponding to the changed element, but
     * it's simpler and more convenient. Consider refactoring if control volume
     * makes the difference noticeable.
     */
    _controlsChangeHandler() {
        this._controlsInput = this._controlsInput ?? {};
        this._controlsInput.searchText = this.searchTextInput.value;
        if (this.collectionInputs) {
            const collectionInputsArray = Array.from(this.collectionInputs);
            this._controlsInput.collections = collectionInputsArray
                .filter((input) => input.checked)
                .map((input) => input.value);
        }
        if (this.sectionInputs) {
            const sectionInputsArray = Array.from(this.sectionInputs);
            this._controlsInput.sections = sectionInputsArray
                .filter((input) => input.checked)
                .map((input) => input.value);
        }
        if (this.aspectInputs) {
            const aspectInputsArray = Array.from(this.aspectInputs);
            this._controlsInput.aspect = aspectInputsArray
                .filter((input) => input.checked)
                .map((input) => input.value);
        }
    }
    /**
     * Listen for label selection changes.
     */
    _labelsChangeHandler(e) {
        this._controlsInput = { ...this._controlsInput, labels: e.detail.selectedLabelsById };
    }
    render() {
        return html `
      <input type="text" class="search-text-input" aria-label="Search"
             .value="${this._controlsInput?.searchText ?? ''}"
             @change=${this._controlsChangeHandler}/>
      ${(this?.controlSchema?.collections && Object.keys(this.controlSchema?.collections)?.length > 1) ? html `
        <fieldset class="collections-container">
          <legend>Collections</legend>
          <div class="collections">
            ${Object.keys(this.controlSchema.collections).map((collectionId) => {
            const collectionName = this.controlSchema.collections[collectionId];
            const isSelected = this._controlsInput?.collections?.includes(collectionId);
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
        </fieldset>` : ''}
      ${(this?.controlSchema?.sections && Object.keys(this.controlSchema?.sections)?.length > 1) ? html `
        <fieldset class="sections-container">
          <legend>Sections</legend>
          <div class="sections">
            ${Object.keys(this.controlSchema.sections).map((sectionId) => {
            const sectionName = this.controlSchema.sections[sectionId];
            const isSelected = this._controlsInput?.sections?.includes(sectionId);
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
        </fieldset>` : ''}
      ${(this?.controlSchema?.labels && Object.keys(this.controlSchema?.labels)?.length > 1) ? html `
        <div class="labels-container">
          <brandfolder-browser-labels-filter
            .allLabels=${this.controlSchema.labels}
            .selectedLabels=${this._controlsInput?.labels}
          />
        </div>` : ''}
      ${(this?.controlSchema?.aspect && Object.keys(this.controlSchema.aspect)?.length > 1) ? html `
        <fieldset class="aspect-container">
          <legend>Orientation</legend>
          <div class="aspect-options">
            ${Object.keys(this.controlSchema.aspect).map((aspectKey) => {
            const aspectName = this.controlSchema.aspect[aspectKey];
            const isSelected = this._controlsInput?.aspect?.includes(aspectKey);
            const inputName = 'aspect';
            return html `
                  <input
                    type="checkbox"
                    class="aspect-input"
                    id="aspect-input--${aspectKey}"
                    aria-label="${aspectName}"
                    name="${inputName}"
                    value=${aspectKey}
                    .checked=${isSelected}
                    @change=${this._controlsChangeHandler}
                  />
                  <label for=${inputName}>${aspectName}</label>
                `;
        })}
          </div>
        </fieldset>` : ''}
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
], BrandfolderBrowserControls.prototype, "_controlsInput", void 0);
__decorate([
    query('.search-text-input')
], BrandfolderBrowserControls.prototype, "searchTextInput", void 0);
__decorate([
    queryAll('.collection-input')
], BrandfolderBrowserControls.prototype, "collectionInputs", void 0);
__decorate([
    queryAll('.section-input')
], BrandfolderBrowserControls.prototype, "sectionInputs", void 0);
__decorate([
    queryAll('.aspect-input')
], BrandfolderBrowserControls.prototype, "aspectInputs", void 0);
BrandfolderBrowserControls = __decorate([
    customElement('brandfolder-browser-controls')
], BrandfolderBrowserControls);
export { BrandfolderBrowserControls };
//# sourceMappingURL=brandfolder-browser-controls.js.map