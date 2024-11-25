var __decorate = (this && this.__decorate) || function (decorators, target, key, desc) {
    var c = arguments.length, r = c < 3 ? target : desc === null ? desc = Object.getOwnPropertyDescriptor(target, key) : desc, d;
    if (typeof Reflect === "object" && typeof Reflect.decorate === "function") r = Reflect.decorate(decorators, target, key, desc);
    else for (var i = decorators.length - 1; i >= 0; i--) if (d = decorators[i]) r = (c < 3 ? d(r) : c > 3 ? d(target, key, r) : d(target, key)) || r;
    return c > 3 && r && Object.defineProperty(target, key, r), r;
};
import { LitElement } from 'lit';
import { property } from 'lit/decorators.js';
/**
 * Base class for a Brandfolder Browser user input/control element.
 */
export class BfBrowserControlBase extends LitElement {
    constructor() {
        super(...arguments);
        /**
         * A relevant subset of the control schema.
         */
        this.label = null;
        /**
         * A relevant subset of the control schema.
         */
        this.controlSchema = null;
        /**
         * User input data for/from this particular control.
         */
        this.controlInput = null;
        /**
         * Property storing the name of the schema subset to be used.
         */
        this.controlSchemaKey = null;
        /**
         * Property storing the name of the user input subset to be used.
         */
        this.controlInputKey = null;
    }
    /**
     * Dispatch an event indicating that user input has changed.
     */
    _dispatchChangeEvent() {
        this.dispatchEvent(new CustomEvent('bfControlInputChange', {
            detail: {
                controlInput: this.controlInput
            },
            bubbles: true,
            composed: true,
        }));
    }
    /**
     * Dispatch an event indicating that controls should be submitted.
     */
    _dispatchSubmitEvent() {
        this.dispatchEvent(new CustomEvent('bfControlsSubmit', {
            bubbles: true,
            composed: true,
        }));
    }
}
__decorate([
    property({ type: String, attribute: false })
], BfBrowserControlBase.prototype, "label", void 0);
__decorate([
    property({ type: Object, attribute: false })
], BfBrowserControlBase.prototype, "controlSchema", void 0);
__decorate([
    property({ type: Object, attribute: false })
], BfBrowserControlBase.prototype, "controlInput", void 0);
__decorate([
    property({ type: String, attribute: false })
], BfBrowserControlBase.prototype, "controlSchemaKey", void 0);
__decorate([
    property({ type: String, attribute: false })
], BfBrowserControlBase.prototype, "controlInputKey", void 0);
//# sourceMappingURL=bf-browser-control-base.js.map