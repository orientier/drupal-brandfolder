import { LitElement } from 'lit';
import { BfLabelTreeNode } from "./brandfolder-browser-labels-filter";
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
    filetype?: BfFiletype[];
    creationDate?: BfDateRangesList;
    modificationDate?: BfDateRangesList;
    publicationDate?: BfDateRangesList;
    sortCriteria?: BfSortCriteriaList;
    sortOrder?: BfSortOrderList;
};
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
/**
 * User interface/input/controls for a Brandfolder browser.
 */
export declare class BrandfolderBrowserControls extends LitElement {
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
     * An object with properties corresponding to user-facing controls, with
     * any corresponding user-supplied values.
     */
    private _controlsInput;
    /**
     * Manage open/closed state.
     */
    private _isOpen;
    /**
     * Create a reference to the search text input element.
     */
    searchTextInput: HTMLInputElement;
    /**
     * Create a reference to the collections input elements.
     */
    collectionInputs: HTMLInputElement[];
    /**
     * Create a reference to the sections input elements.
     */
    sectionInputs: HTMLInputElement[];
    /**
     * Create a reference to the aspect/orientation input elements.
     */
    aspectInputs: HTMLInputElement[];
    /**
     * Create a reference to the filetype input elements.
     */
    filetypeInputs: HTMLInputElement[];
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
     * Pull UI element values into state. Call this whenever any relevant UI
     * element changes. This isn't as efficient as making precise updates
     * affecting only the state/prop corresponding to the changed element, but
     * it's simpler and more convenient. Consider refactoring if control volume
     * makes the difference noticeable...but this will probably have a different
     * shape anyway once we establish filter subcomponents.
     */
    private _controlsChangeHandler;
    /**
     * Listen for label selection changes.
     */
    private _labelsChangeHandler;
    /**
     * Listen for tag selection changes.
     */
    private _tagsChangeHandler;
    /**
     * Render the component.
     *
     * @todo: Use subcomponents for each control, etc.
     */
    render(): import("lit-html").TemplateResult<1>;
}
declare global {
    interface HTMLElementTagNameMap {
        'brandfolder-browser-controls': BrandfolderBrowserControls;
    }
}
//# sourceMappingURL=brandfolder-browser-controls.d.ts.map