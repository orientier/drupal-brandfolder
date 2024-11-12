import { LitElement } from 'lit';
import { BfLabelTreeNode } from "./brandfolder-browser-labels-filter";
export type BfKvList = {
    [key: string]: string;
};
export type BfSortCriterion = 'name' | 'score' | 'position' | 'updated_at' | 'created_at';
export type BfSortOrder = 'asc' | 'desc';
export type BfAspectRatio = 'landscape' | 'portrait' | 'square' | 'panorama';
export type BfAspectRatiosList = {
    [key in BfAspectRatio]: string;
};
export type BfFiletype = 'jpg' | 'png' | 'svg' | 'gif' | 'webp';
export type BfUploadDate = 'all' | '30m' | '1d' | '7d' | '30d';
export type BfUploadDatesList = {
    [key in BfUploadDate]: string;
};
export type BfBrowserControlSchema = {
    searchText?: string;
    collections?: BfKvList;
    sections?: BfKvList;
    labels?: BfLabelTreeNode[];
    tags?: string[];
    aspect?: BfAspectRatiosList;
    filetype?: BfFiletype[];
    uploadDate?: BfUploadDatesList;
    sortCriterion?: BfSortCriterion;
    sortOrder?: BfSortOrder;
};
export type BfBrowserUserInput = {
    searchText?: string;
    collections?: string[];
    sections?: string[];
    labels?: Record<string, string>;
    tags?: string[];
    aspect?: BfAspectRatio[];
    filetype?: BfFiletype[];
    uploadDate?: BfUploadDate;
    sortCriterion?: BfSortCriterion;
    sortOrder?: BfSortOrder;
};
/**
 * User interface/input/controls for a Brandfolder browser.
 */
export declare class BrandfolderBrowserControls extends LitElement {
    static styles: import("lit").CSSResult;
    /**
     * An object with data sufficient to build user-facing controls.
     */
    controlSchema: BfBrowserControlSchema | null;
    /**
     * An object with properties corresponding to user-facing controls, with
     * any corresponding user-supplied values.
     */
    private _controlsInput;
    /**
     * Create a reference to the search text input element.
     */
    searchTextInput: HTMLInputElement;
    /**
     * Create a reference to the collections input element.
     */
    collectionInputs: HTMLInputElement[];
    /**
     * Create a reference to the sections input element.
     */
    sectionInputs: HTMLInputElement[];
    /**
     * Constructor.
     */
    constructor();
    /**
     * Handle use of the "reset" button. Reset all user input.
     */
    private _controlsResetHandler;
    /**
     * Handle the submission of the search/filter/sort form.
     */
    private _controlsSubmissionHandler;
    /**
     * Pull UI element values into state. Call this whenever any relevant UI
     * element changes. This isn't as efficient as making precise updates
     * affecting only the state/prop corresponding to the changed element, but
     * it's simpler and more convenient. Consider refactoring if control volume
     * makes the difference noticeable.
     */
    private _controlsChangeHandler;
    /**
     * Listen for label selection changes.
     */
    private _labelsChangeHandler;
    render(): import("lit-html").TemplateResult<1>;
}
declare global {
    interface HTMLElementTagNameMap {
        'brandfolder-browser-controls': BrandfolderBrowserControls;
    }
}
//# sourceMappingURL=brandfolder-browser-controls.d.ts.map