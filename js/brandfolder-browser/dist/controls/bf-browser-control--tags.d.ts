import { BfBrowserControlBase } from "./bf-browser-control-base";
/**
 * UI for specifying Brandfolder tags for asset filtering.
 */
export declare class BfBrowserTagsControl extends BfBrowserControlBase {
    static styles: import("lit").CSSResult;
    /**
     * Create a reference to the input element.
     */
    tagTextInput: HTMLInputElement;
    /**
     * Event handler for adding a tag.
     */
    private _addTag;
    /**
     * Event handler for removing a tag.
     */
    private _removeTag;
    /**
     * Render the tags control.
     */
    render(): import("lit-html").TemplateResult<1>;
}
declare global {
    interface HTMLElementTagNameMap {
        'brandfolder-browser-control--tags': BfBrowserTagsControl;
    }
}
//# sourceMappingURL=bf-browser-control--tags.d.ts.map