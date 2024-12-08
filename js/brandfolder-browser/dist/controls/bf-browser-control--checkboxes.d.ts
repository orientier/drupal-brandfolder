import { BfBrowserControlBase } from "./bf-browser-control-base";
/**
 * UI for specifying Brandfolder tags for asset filtering.
 */
export declare class BfBrowserCheckboxesControl extends BfBrowserControlBase {
    static styles: import("lit").CSSResult;
    /**
     * Create a reference to the input elements.
     */
    inputElements: HTMLInputElement[];
    /**
     * Callback executed when the element is added to the document.
     */
    connectedCallback(): void;
    /**
     * Respond to input changes.
     */
    private _changeHandler;
    /**
     * Render the checkboxes control.
     */
    render(): "" | import("lit-html").TemplateResult<1>;
}
declare global {
    interface HTMLElementTagNameMap {
        'brandfolder-browser-control--checkboxes': BfBrowserCheckboxesControl;
    }
}
//# sourceMappingURL=bf-browser-control--checkboxes.d.ts.map