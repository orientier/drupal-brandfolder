import { BfBrowserControlBase } from "./bf-browser-control-base";
/**
 * UI for specifying Brandfolder tags for asset filtering.
 */
export declare class BfBrowserSelectControl extends BfBrowserControlBase {
    static styles: import("lit").CSSResult;
    /**
     * Create a reference to the input elements.
     */
    selectElement: HTMLSelectElement;
    /**
     * Callback executed when the element is added to the document.
     */
    connectedCallback(): void;
    /**
     * Respond to select changes.
     */
    private _changeHandler;
    /**
     * Render the select control.
     */
    render(): import("lit-html").TemplateResult<1> | "";
}
declare global {
    interface HTMLElementTagNameMap {
        'brandfolder-browser-control--select': BfBrowserSelectControl;
    }
}
//# sourceMappingURL=bf-browser-control--select.d.ts.map