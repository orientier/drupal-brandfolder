var __decorate = (this && this.__decorate) || function (decorators, target, key, desc) {
    var c = arguments.length, r = c < 3 ? target : desc === null ? desc = Object.getOwnPropertyDescriptor(target, key) : desc, d;
    if (typeof Reflect === "object" && typeof Reflect.decorate === "function") r = Reflect.decorate(decorators, target, key, desc);
    else for (var i = decorators.length - 1; i >= 0; i--) if (d = decorators[i]) r = (c < 3 ? d(r) : c > 3 ? d(target, key, r) : d(target, key)) || r;
    return c > 3 && r && Object.defineProperty(target, key, r), r;
};
import { css, html } from 'lit';
import { customElement, query, } from 'lit/decorators.js';
import { BfBrowserControlBase } from "./bf-browser-control-base";
/**
 * UI for specifying Brandfolder tags for asset filtering.
 */
let BfBrowserSelectControl = class BfBrowserSelectControl extends BfBrowserControlBase {
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
     * Respond to select changes.
     */
    _changeHandler() {
        this.controlInput = {
            ...this.controlInput,
            [this.controlInputKey]: this.selectElement.value
        };
        this._dispatchChangeEvent();
    }
    /**
     * Render the select control.
     */
    render() {
        const schema = this?.controlSchema;
        const schemaKey = this?.controlSchemaKey;
        const schemaData = schema && schemaKey ? schema[schemaKey] : null;
        const userInput = this?.controlInput;
        const controlInputKey = this?.controlInputKey;
        const userInputValue = userInput && controlInputKey ? userInput[controlInputKey] : null;
        const elementId = `brandfolder-browser-controls-${controlInputKey}`;
        return schemaData && controlInputKey ? html `
      <div class="bf-browser-select-control__inner">
        ${this?.label ? html `
        <label for="${elementId}">${this.label}</label>
        ` : ''}
        <select
          id="${elementId}"
          name="${`brandfolder-browser-controls--${controlInputKey}`}"
          class="${`brandfolder-browser-controls__${controlInputKey}`}"
          @change=${this._changeHandler}
        >
          ${Object.entries(schemaData).map(([itemKey, itemLabel]) => {
            const isSelected = userInputValue === itemKey;
            return html `
                <option
                  value=${itemKey}
                  .selected=${isSelected}
                >
                  ${itemLabel}
                </option>
              `;
        })}
      </div>
    ` : '';
    }
};
BfBrowserSelectControl.styles = css `
    :host {
      flex: 1;
      display: flex;
    }

    .bf-browser-select-control__inner {
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    label {
      font-size: 0.875rem;
    }
  `;
__decorate([
    query('select')
], BfBrowserSelectControl.prototype, "selectElement", void 0);
BfBrowserSelectControl = __decorate([
    customElement('brandfolder-browser-control--select')
], BfBrowserSelectControl);
export { BfBrowserSelectControl };
//# sourceMappingURL=bf-browser-control--select.js.map