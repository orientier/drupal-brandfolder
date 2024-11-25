var __decorate = (this && this.__decorate) || function (decorators, target, key, desc) {
    var c = arguments.length, r = c < 3 ? target : desc === null ? desc = Object.getOwnPropertyDescriptor(target, key) : desc, d;
    if (typeof Reflect === "object" && typeof Reflect.decorate === "function") r = Reflect.decorate(decorators, target, key, desc);
    else for (var i = decorators.length - 1; i >= 0; i--) if (d = decorators[i]) r = (c < 3 ? d(r) : c > 3 ? d(target, key, r) : d(target, key)) || r;
    return c > 3 && r && Object.defineProperty(target, key, r), r;
};
import { css, html } from 'lit';
import { customElement, 
// property,
// query,
queryAll } from 'lit/decorators.js';
import { live } from 'lit/directives/live.js';
import { BfBrowserControlBase } from "./bf-browser-control-base";
// import {
//   BfBrowserControlSchemaKey,
//   BfBrowserUserInputKey
// } from "./bf-browser-controls";
/**
 * UI for specifying Brandfolder tags for asset filtering.
 */
let BfBrowserCheckboxesControl = class BfBrowserCheckboxesControl extends BfBrowserControlBase {
    /**
     * Callback executed when the element is added to the document.
     */
    connectedCallback() {
        super.connectedCallback();
        // Initialize controlSchemaKey and controlInputKey based on controlSchema
        // and controlInput, if not explicitly set.
        if (!this.controlSchemaKey) {
            this.controlSchemaKey = Object.keys(this.controlSchema)[0];
        }
        if (!this.controlInputKey) {
            this.controlInputKey = Object.keys(this.controlInput)[0];
        }
    }
    /**
     * Respond to input changes.
     */
    _changeHandler() {
        const inputsArray = Array.from(this.inputElements);
        const selectedValues = inputsArray
            .filter((input) => input.checked)
            .map((input) => input.value);
        this.controlInput = {
            ...this.controlInput,
            [this.controlInputKey]: selectedValues
        };
        this._dispatchChangeEvent();
    }
    render() {
        const schema = this?.controlSchema;
        const schemaKey = this?.controlSchemaKey;
        const schemaData = schema && schemaKey ? schema[schemaKey] : null;
        const userInput = this?.controlInput;
        const controlInputKey = this?.controlInputKey;
        const userInputData = userInput && controlInputKey ? userInput[controlInputKey] : null;
        return schemaData && controlInputKey ? html `
      <div class="bf-browser-checkboxes-control__inner">
        ${Object.entries(schemaData).map(([itemId, itemLabel]) => {
            const isSelected = userInputData && userInputData.includes(itemId);
            const inputId = `input--${itemId}`;
            return html `
              <span class="input-item">
                <input
                  type="checkbox"
                  id="${inputId}"
                  aria-label="${itemLabel}"
                  name="${controlInputKey}"
                  value=${itemId}
                  .checked=${live(isSelected)}
                  @change=${this._changeHandler}
                />
                <label for=${inputId}>${itemLabel}</label>
              </span>
            `;
        })}
      </div>
    ` : '';
    }
};
BfBrowserCheckboxesControl.styles = css `
    :host {
      flex: 1;
      display: flex;
    }

    .bf-browser-checkboxes-control__inner {
      display: flex;
      flex-wrap: wrap;
      gap: 0.5rem 1rem;
    }

    .input-item {
      display: flex;
      align-items: center;
      justify-content: flex-start;
      gap: 0.25rem;
      flex-wrap: nowrap;
    }
  `;
__decorate([
    queryAll('input[type="checkbox"]')
], BfBrowserCheckboxesControl.prototype, "inputElements", void 0);
BfBrowserCheckboxesControl = __decorate([
    customElement('brandfolder-browser-control--checkboxes')
], BfBrowserCheckboxesControl);
export { BfBrowserCheckboxesControl };
//# sourceMappingURL=bf-browser-control--checkboxes.js.map