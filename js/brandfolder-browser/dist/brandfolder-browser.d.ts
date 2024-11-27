import { LitElement } from 'lit';
import './brandfolder-asset-base';
import './brandfolder-asset-detail';
import './brandfolder-asset-preview';
import './brandfolder-attachment';
import './brandfolder-media-container';
import './brandfolder-browser-selection-tray';
import './controls/bf-browser-controls';
import './controls/bf-browser-control-base';
import './controls/bf-browser-control-item';
import './controls/bf-browser-control--checkboxes';
import './controls/bf-browser-control--labels';
import './controls/bf-browser-control--search';
import './controls/bf-browser-control--select';
import './controls/bf-browser-control--tags';
type BfBrowserSettings = {
    height: number;
    format: 'inline' | 'full';
    endpoint: string;
};
/**
 * Format a date (or date+time) string according to our preferred
 * date-only format.
 */
export declare function bfBrowserFormatDate(date: string): string;
/**
 * Format a date+time string according to our preferred format.
 */
export declare function bfBrowserFormatDateAndTime(datetime: string): string;
/**
 * Format a file size (in bytes) as a human-readable string.
 */
export declare function bfBrowserFormatFilesize(size: number): string;
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
     * Our custom BF Browser context, which is used to store things like the list
     * of selected attachments so descendant components can access it by consuming
     * the context, rather than us having to pass data down through the component
     * tree manually.
     */
    private _browserContext;
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