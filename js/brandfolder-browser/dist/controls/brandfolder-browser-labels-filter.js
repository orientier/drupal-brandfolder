var __decorate = (this && this.__decorate) || function (decorators, target, key, desc) {
    var c = arguments.length, r = c < 3 ? target : desc === null ? desc = Object.getOwnPropertyDescriptor(target, key) : desc, d;
    if (typeof Reflect === "object" && typeof Reflect.decorate === "function") r = Reflect.decorate(decorators, target, key, desc);
    else for (var i = decorators.length - 1; i >= 0; i--) if (d = decorators[i]) r = (c < 3 ? d(r) : c > 3 ? d(target, key, r) : d(target, key)) || r;
    return c > 3 && r && Object.defineProperty(target, key, r), r;
};
import { css, html, LitElement } from 'lit';
import { customElement, property, query, } from 'lit/decorators.js';
/**
 * UI for a selecting Brandfolder labels.
 */
let BrandfolderBrowserLabelsFilter = class BrandfolderBrowserLabelsFilter extends LitElement {
    constructor() {
        super(...arguments);
        /**
         * All eligible labels.
         */
        this.allLabels = null;
        /**
         * A list of all currently selected labels. An object keyed by label ID with
         * label names as values.
         */
        this.selectedLabels = null;
    }
    /**
     * Change event handler for selecting labels.
     */
    _changeHandler() {
        const selectedOptions = Array.from(this.labelsSelect.selectedOptions);
        const selectedLabelsById = selectedOptions.reduce((acc, option) => {
            acc[option.value] = option.text;
            return acc;
        }, {});
        this.selectedLabels = selectedLabelsById;
        this.dispatchEvent(new CustomEvent('bfLabelsChanged', {
            detail: {
                selectedLabelsById
            },
            bubbles: true,
            composed: true,
        }));
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
        .selected=${!!this.selectedLabels?.[labelObject?.id]}
      >
        ${depthIndicator} ${labelObject?.attributes?.name}
      </option>
      ${labelNode?.children ?
            Object.values(labelNode.children).map((child) => this.renderLabelNode(child))
            : ''}
    `;
    }
    render() {
        const labelsArray = Object.values(this.allLabels ?? []);
        return html `
      <select
        name="brandfolder-browser-controls-labels"
        class="brandfolder-browser-controls__labels"
        multiple
        size="${Math.max(labelsArray.length, 5)}"
        @change=${this._changeHandler}
      >
        ${labelsArray.map((labelNode) => this.renderLabelNode(labelNode))}
      </select>
    `;
    }
};
BrandfolderBrowserLabelsFilter.styles = css `
    .brandfolder-browser-controls__labels {
      max-height: 12rem;
    }
  `;
__decorate([
    property({ type: Object, attribute: false })
], BrandfolderBrowserLabelsFilter.prototype, "allLabels", void 0);
__decorate([
    property({ type: Object, attribute: false })
], BrandfolderBrowserLabelsFilter.prototype, "selectedLabels", void 0);
__decorate([
    query('.brandfolder-browser-controls__labels')
], BrandfolderBrowserLabelsFilter.prototype, "labelsSelect", void 0);
BrandfolderBrowserLabelsFilter = __decorate([
    customElement('brandfolder-browser-labels-filter')
], BrandfolderBrowserLabelsFilter);
export { BrandfolderBrowserLabelsFilter };
//# sourceMappingURL=brandfolder-browser-labels-filter.js.map