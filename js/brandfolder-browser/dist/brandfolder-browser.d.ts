import { LitElement } from 'lit';
import './brandfolder-asset-base';
import './brandfolder-asset-detail';
import './brandfolder-asset-preview';
import './brandfolder-attachment';
import './brandfolder-browser-controls';
import './brandfolder-browser-labels-filter';
import './bf-browser-tags-control';
type BfBrowserSettings = {
    height: number;
    format: 'inline' | 'full';
    endpoint: string;
};
/**
 * An interface for viewing/searching/filtering/selecting assets and attachments
 * from Brandfolder.
 */
export declare class BrandfolderBrowser extends LitElement {
    static styles: import("lit").CSSResult;
    /**
     * Unique ID for the browser instance.
     */
    bfBrowserId: string | null;
    /**
     * A generic settings object with key-value pairs. Initialized as a
     * JSON string.
     */
    settings: BfBrowserSettings | string | null;
    /**
     * The URL to which API requests should be sent.
     */
    private _apiEndpoint;
    /**
     * The number of assets to fetch per page.
     */
    private _assetsPerPage;
    /**
     * The format in which the browser should be displayed. Options:
     * - 'inline' (default): Display the browser inline within the page.
     * - 'full': Display the browser in a way that consumes all available space
     *    in the host window/frame/document.
     */
    /**
     * The recommended height of the browser, in pixels.
     */
    /**
     * Active asset.
     */
    private _activeAsset;
    /**
     * An array of BfAsset items representing the current result set.
     */
    private _assetList;
    /**
     * An object with properties corresponding to user-facing controls, with
     * any corresponding user-supplied values.
     */
    private _userInput;
    /**
     * An object with data sufficient to build user-facing controls.
     */
    private _controlSchema;
    /**
     * An object containing metadata about the latest asset search/fetch,
     * including total items, total pages, current page, etc.
     */
    private _assetFetchMeta;
    /**
     * Constructor.
     */
    constructor();
    /**
     * Callback executed when the element is added to the document.
     */
    connectedCallback(): void;
    /**
     * Callback executed when the element is removed from the document.
     */
    /**
     * Callback executed when the element is updated.
     */
    /**
     * Set the browser's height based on context.
     */
    /**
     * Determine the height to which the browser should be constrained in order to
     * achieve the best UX within the containing elements.
     */
    /**
     * Async task for communicating with the Drupal backend (to submit user input,
     * fetch assets from Brandfolder, etc.).
     */
    private _browserUpdateTask;
    /**
     * Submit the search/filter/sort form.
     */
    private _controlsSubmissionHandler;
    /**
     * Handle asset selection. When a user selects an asset preview, display
     * the asset's detail view.
     */
    private _assetSelectionHandler;
    /**
     * Handle closure of asset detail pane.
     */
    private _assetDetailCloseHandler;
    /**
     * Handle attachment selection events.
     */
    private _attachmentSelectionHandler;
    /**
     * Define the element's template / rendered HTML.
     */
    render(): import("lit-html").TemplateResult<1>;
}
/**
 * Add the element to the global registry so TypeScript will infer the correct
 * class of an HTML element returned from certain DOM APIs (it does this based
 * on the tag name).
 */
declare global {
    interface HTMLElementTagNameMap {
        'brandfolder-browser': BrandfolderBrowser;
    }
}
export {};
//# sourceMappingURL=brandfolder-browser.d.ts.map