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
 * UI for a selecting Brandfolder labels.
 */
let BfBrowserLabelsControl = class BfBrowserLabelsControl extends BfBrowserControlBase {
    /**
     * Change event handler for selecting labels.
     */
    _changeHandler() {
        const selectedOptions = Array.from(this.labelsSelect.selectedOptions);
        const selectedLabelsById = selectedOptions.reduce((acc, option) => {
            acc[option.value] = option.text;
            return acc;
        }, {});
        this.controlInput.labels = selectedLabelsById;
        this._dispatchChangeEvent();
    }
    /**
     * Render filter content for a label node and its children.
     */
    renderLabelNode(labelNode) {
        const labelObject = labelNode?.label;
        const depth = labelObject?.attributes?.depth;
        const depthIndicator = '-'.repeat(depth - 1).replace(/^-/, ' -');
        return html `
      <option
        value=${labelObject?.id}
        .selected=${!!this?.controlInput?.labels?.[labelObject?.id]}
      >
        ${depthIndicator} ${labelObject?.attributes?.name}
      </option>
      ${labelNode?.children ?
            Object.values(labelNode.children).map((child) => this.renderLabelNode(child))
            : ''}
    `;
    }
    /**
     * Render the labels control.
     */
    render() {
        const labelsArray = Object.values(this?.controlSchema?.labels ?? []);
        return html `
      <select
        name="brandfolder-browser-controls-labels"
        class="brandfolder-browser-controls__labels-select"
        multiple
        size="${Math.max(labelsArray.length, 5)}"
        @change=${this._changeHandler}
      >
        ${labelsArray.map((labelNode) => this.renderLabelNode(labelNode))}
      </select>
    `;
    }
};
BfBrowserLabelsControl.styles = css `
    .labels-select {
      max-height: 12rem;
    }
  `;
__decorate([
    query('.brandfolder-browser-controls__labels-select')
], BfBrowserLabelsControl.prototype, "labelsSelect", void 0);
BfBrowserLabelsControl = __decorate([
    customElement('brandfolder-browser-control--labels')
], BfBrowserLabelsControl);
export { BfBrowserLabelsControl };
//# sourceMappingURL=bf-browser-control--labels.js.map