import { LitElement } from 'lit';
import { BfLabelTreeNode } from "./bf-browser-control--labels";
export type BfKvList = {
    [key: string]: string;
};
export type BfTagFilterMode = 'any' | 'all';
export type BfSortCriterion = 'name' | 'score' | 'position' | 'updated_at' | 'created_at';
export type BfSortCriteriaList = {
    [key in BfSortCriterion]: string;
};
export type BfSortOrder = 'asc' | 'desc';
export type BfSortOrderList = {
    [key in BfSortOrder]: string;
};
export type BfAspectRatio = 'landscape' | 'portrait' | 'square' | 'panorama';
export type BfAspectRatiosList = {
    [key in BfAspectRatio]: string;
};
export type BfFiletype = 'jpg' | 'png' | 'svg' | 'gif' | 'webp';
export type BfFiletypeList = {
    [key in BfFiletype]: string;
};
export type BfDateRange = 'all' | '30m' | '1d' | '7d' | '30d' | '60d' | '90d';
export type BfDateRangesList = {
    [key in BfDateRange]: string;
};
export type BfBrowserControlSchema = {
    searchText?: string;
    collections?: BfKvList;
    sections?: BfKvList;
    labels?: BfLabelTreeNode[];
    tags?: string[];
    aspect?: BfAspectRatiosList;
    filetype?: BfFiletypeList;
    creationDate?: BfDateRangesList;
    modificationDate?: BfDateRangesList;
    publicationDate?: BfDateRangesList;
    sortCriteria?: BfSortCriteriaList;
    sortOrder?: BfSortOrderList;
};
export type BfBrowserControlSchemaKey = keyof BfBrowserControlSchema;
export type BfControlSchemaKvEsque = Pick<BfBrowserControlSchema, 'collections' | 'sections' | 'aspect' | 'filetype' | 'creationDate' | 'modificationDate' | 'publicationDate' | 'sortCriteria' | 'sortOrder'>;
export type BfControlSchemaKvEsqueKey = keyof BfControlSchemaKvEsque;
export type BfBrowserUserInput = {
    searchText?: string;
    collections?: string[];
    sections?: string[];
    labels?: Record<string, string>;
    tags?: string[];
    tagFilterMode?: BfTagFilterMode;
    aspect?: BfAspectRatio[];
    filetype?: BfFiletype[];
    creationDate?: BfDateRange;
    modificationDate?: BfDateRange;
    publicationDate?: BfDateRange;
    sortCriterion?: BfSortCriterion;
    sortOrder?: BfSortOrder;
};
export type BfBrowserUserInputKey = keyof BfBrowserUserInput;
export type BfControlInputStringArrayEsque = Pick<BfBrowserUserInput, 'collections' | 'sections' | 'tags' | 'aspect' | 'filetype'>;
export type BfControlInputStringArrayEsqueKey = keyof BfControlInputStringArrayEsque;
export type BfControlInputStringEsque = Pick<BfBrowserUserInput, 'searchText' | 'tagFilterMode' | 'creationDate' | 'modificationDate' | 'publicationDate' | 'sortCriterion' | 'sortOrder'>;
export type BfControlInputStringEsqueKey = keyof BfControlInputStringEsque;
export type BfControlStringEsqueType = string | BfTagFilterMode | BfDateRange | BfSortCriterion | BfSortOrder;
/**
 * User interface/input/controls for a Brandfolder browser.
 */
export declare class BfBrowserControls extends LitElement {
    static styles: import("lit").CSSResult;
    /**
     * Default values for user-facing controls.
     */
    private controlsInputDefaults;
    /**
     * An object with data sufficient to build user-facing controls.
     */
    controlSchema: BfBrowserControlSchema | null;
    /**
     * A message indicating the status of the current/latest browser fetch
     * operation.
     */
    browserFetchStatusMessage: string | null;
    /**
     * An object with properties corresponding to user-facing controls, with
     * any corresponding user-supplied values.
     */
    private _controlsInput;
    /**
     * Manage open/closed state.
     */
    private _isOpen;
    /**
     * Create a reference to the creation date select element.
     */
    creationDateSelect: HTMLSelectElement;
    /**
     * Create a reference to the modification date select element.
     */
    modificationDateSelect: HTMLSelectElement;
    /**
     * Create a reference to the publication date select element.
     */
    publicationDateSelect: HTMLSelectElement;
    /**
     * Create a reference to the sort criterion select element.
     */
    sortCriterionSelect: HTMLSelectElement;
    /**
     * Create a reference to the sort order select element.
     */
    sortOrderSelect: HTMLSelectElement;
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
     * Listen for user input events from descendant controls.
     */
    private _controlInputHandler;
    /**
     * Render the component.
     */
    render(): import("lit-html").TemplateResult<1>;
}
declare global {
    interface HTMLElementTagNameMap {
        'brandfolder-browser-controls': BfBrowserControls;
    }
}
//# sourceMappingURL=bf-browser-controls.d.ts.map