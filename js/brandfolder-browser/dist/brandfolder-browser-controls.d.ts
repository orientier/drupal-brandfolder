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
     * Create a reference to the creation date input elements.
     */
    creationDateSelect: HTMLInputElement;
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
     * Render the component.
     *
     * @todo: Refactor to use subcomponents for each control.
     */
    render(): import("lit-html").TemplateResult<1>;
}
declare global {
    interface HTMLElementTagNameMap {
        'brandfolder-browser-controls': BrandfolderBrowserControls;
    }
}
//# sourceMappingURL=brandfolder-browser-controls.d.ts.map