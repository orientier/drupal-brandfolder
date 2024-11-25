import { BfBrowserControlBase } from "./bf-browser-control-base";
/**
 * UI for specifying Brandfolder tags for asset filtering.
 */
export declare class BfBrowserSearchControl extends BfBrowserControlBase {
    static styles: import("lit").CSSResult;
    /**
     * Create a reference to the input element.
     */
    searchTextInput: HTMLInputElement;
    /**
     * Respond to text input changes.
     */
    private _changeHandler;
    /**
     * Render the search control.
     */
    render(): import("lit-html").TemplateResult<1>;
}
declare global {
    interface HTMLElementTagNameMap {
        'brandfolder-browser-control--search': BfBrowserSearchControl;
    }
}
//# sourceMappingURL=bf-browser-control--search.d.ts.map