import { LitElement } from 'lit';
import { BfTagFilterMode } from "./brandfolder-browser-controls";
/**
 * UI for specifying Brandfolder tags for asset filtering.
 */
export declare class BfBrowserTagsControl extends LitElement {
    static styles: import("lit").CSSResult;
    /**
     * A list of all currently selected tags.
     */
    selectedTags: string[];
    /**
     * The selected tag filter mode.
     */
    tagFilterMode: BfTagFilterMode | null;
    /**
     * Create a reference to the select element.
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
     * Dispatch a "tags changed" event.
     */
    private _dispatchTagChangeEvent;
    render(): import("lit-html").TemplateResult<1>;
}
declare global {
    interface HTMLElementTagNameMap {
        'bf-browser-tags-control': BfBrowserTagsControl;
    }
}
//# sourceMappingURL=bf-browser-tags-control.d.ts.map