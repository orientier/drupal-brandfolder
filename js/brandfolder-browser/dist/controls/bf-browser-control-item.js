var __decorate = (this && this.__decorate) || function (decorators, target, key, desc) {
    var c = arguments.length, r = c < 3 ? target : desc === null ? desc = Object.getOwnPropertyDescriptor(target, key) : desc, d;
    if (typeof Reflect === "object" && typeof Reflect.decorate === "function") r = Reflect.decorate(decorators, target, key, desc);
    else for (var i = decorators.length - 1; i >= 0; i--) if (d = decorators[i]) r = (c < 3 ? d(r) : c > 3 ? d(target, key, r) : d(target, key)) || r;
    return c > 3 && r && Object.defineProperty(target, key, r), r;
};
import { css, html, LitElement } from 'lit';
import { customElement, property } from 'lit/decorators.js';
/**
 * Generic/wrapper element for a Brandfolder Browser user input/control item.
 */
let BfBrowserControlItem = class BfBrowserControlItem extends LitElement {
    constructor() {
        super(...arguments);
        /**
         * Control name/label/title.
         */
        this.label = null;
    }
    render() {
        return html `
      <fieldset
        class="bf-control-item__inner"
      >
        ${this?.label ? html `<legend>${this.label}</legend>` : 'No Label Provided'}
        <div class="bf-control-item__content">
          <slot></slot>
        </div>
      </fieldset>
    `;
    }
};
BfBrowserControlItem.styles = css `
    fieldset.bf-control-item__inner {
      box-sizing: border-box;
      border: 1px solid var(--color-gray-400);
      padding: 0.75rem;
      flex: 1;
      display: flex;
    }
    fieldset legend {
      white-space: nowrap;
    }
    .bf-control-item__content {
      display: flex;
      flex-direction: column;
      gap: 0.5rem;
      width: 100%;
    }
  `;
__decorate([
    property({ type: String, attribute: true })
], BfBrowserControlItem.prototype, "label", void 0);
BfBrowserControlItem = __decorate([
    customElement('brandfolder-browser-control-item')
], BfBrowserControlItem);
export { BfBrowserControlItem };
//# sourceMappingURL=bf-browser-control-item.js.map