var __decorate = (this && this.__decorate) || function (decorators, target, key, desc) {
    var c = arguments.length, r = c < 3 ? target : desc === null ? desc = Object.getOwnPropertyDescriptor(target, key) : desc, d;
    if (typeof Reflect === "object" && typeof Reflect.decorate === "function") r = Reflect.decorate(decorators, target, key, desc);
    else for (var i = decorators.length - 1; i >= 0; i--) if (d = decorators[i]) r = (c < 3 ? d(r) : c > 3 ? d(target, key, r) : d(target, key)) || r;
    return c > 3 && r && Object.defineProperty(target, key, r), r;
};
import { css, html, LitElement } from 'lit';
import { Task, TaskStatus } from '@lit/task';
import { provide } from '@lit/context';
import { customElement, property, state } from 'lit/decorators.js';
import { bfBrowserContext } from "./brandfolder-browser-context";
// Import all subcomponents and class dependencies so we can compile
// everything into a single JS file with this file as the sole entry point.
import './brandfolder-media-container';
import './brandfolder-browser-selection-tray';
import './asset/brandfolder-asset-base';
import './asset/brandfolder-asset-detail';
import './asset/brandfolder-asset-preview';
import './attachment/brandfolder-attachment-base';
import './attachment/brandfolder-attachment-detail';
import './attachment/brandfolder-attachment-selection';
import './controls/bf-browser-controls';
import './controls/bf-browser-control-base';
import './controls/bf-browser-control-item';
import './controls/bf-browser-control--checkboxes';
import './controls/bf-browser-control--labels';
import './controls/bf-browser-control--search';
import './controls/bf-browser-control--select';
import './controls/bf-browser-control--tags';
/**
 * Format a date (or date+time) string according to our preferred
 * date-only format.
 */
export function bfBrowserFormatDate(date) {
    return new Intl.DateTimeFormat('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    }).format(new Date(date));
}
/**
 * Format a date+time string according to our preferred format.
 */
export function bfBrowserFormatDateAndTime(datetime) {
    return new Intl.DateTimeFormat('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: 'numeric',
        minute: 'numeric',
    }).format(new Date(datetime));
}
/**
 * Format a file size (in bytes) as a human-readable string.
 */
export function bfBrowserFormatFilesize(size) {
    let sizeString = '';
    if (size) {
        const units = ['B', 'KB', 'MB', 'GB', 'TB'];
        let unitIndex = 0;
        let sizeInUnits = size;
        while (sizeInUnits >= 1024 && unitIndex < units.length - 1) {
            sizeInUnits /= 1024;
            unitIndex++;
        }
        sizeString = `${sizeInUnits.toFixed(1)} ${units[unitIndex]}`;
    }
    return sizeString;
}
/**
 * An interface for viewing/searching/filtering/selecting assets and attachments
 * from Brandfolder.
 */
let BrandfolderBrowser = class BrandfolderBrowser extends LitElement {
    /**
     * Constructor.
     */
    constructor() {
        super();
        /**
         * Unique ID for the browser instance.
         */
        this.bfBrowserId = null;
        /**
         * A generic settings object with key-value pairs. Initialized as a
         * JSON string.
         */
        this.settings = null;
        /**
         * The URL to which API requests should be sent.
         */
        this._apiEndpoint = '/brandfolder-browser-update';
        /**
         * The number of assets to fetch per page.
         */
        this._assetsPerPage = 100;
        /**
         * The format in which the browser should be displayed. Options:
         * - 'inline' (default): Display the browser inline within the page.
         * - 'full': Display the browser in a way that consumes all available space
         *    in the host window/frame/document.
         */
        // @state()
        // private _format = 'inline'
        /**
         * The recommended height of the browser, in pixels.
         */
        // @state()
        // private _height: number | null = null
        /**
         * Active asset.
         */
        this._activeAsset = null;
        /**
         * Whether the selection tray is open.
         */
        this._isSelectionTrayOpen = false;
        /**
         * An array of BfAsset items representing the current result set.
         */
        this._assetList = [];
        /**
         * An object with properties corresponding to user-facing controls, with
         * any corresponding user-supplied values.
         */
        this._userInput = null;
        /**
         * An object with data sufficient to build user-facing controls.
         */
        this._controlSchema = null;
        /**
         * An object containing metadata about the latest asset search/fetch,
         * including total items, total pages, current page, etc.
         */
        this._assetFetchMeta = null;
        /**
         * Our custom BF Browser context, which is used to store things like the list
         * of selected attachments so descendant components can access it by consuming
         * the context, rather than us having to pass data down through the component
         * tree manually.
         */
        this._browserContext = {
            selectedAttachments: {},
            selectionLimit: null,
        };
        /**
         * Callback executed when the element is removed from the document.
         */
        // override disconnectedCallback() {
        //   if (this._format === 'inline') {
        //     window.removeEventListener('resize', this._calibrateHeight)
        //   }
        //   super.disconnectedCallback()
        // }
        /**
         * Callback executed when the element is updated.
         */
        // override updated(_changedProperties: Map<string | number | symbol, unknown>) {
        //   if (this._format === 'inline') {
        //     // After fetching and rendering new assets, determine whether the
        //     // browser's height should be constrained in order to achieve
        //     // the best UX within the containing context.
        //     if (_changedProperties.has('_assetList')) {
        //       this._calibrateHeight()
        //     }
        //   }
        // }
        /**
         * Set the browser's height based on context.
         */
        // private _calibrateHeight = () => {
        //   console.log('Calibrating height...')
        //   this.style.setProperty('--bf-browser-height', '100%')
        //   const heightConstraint = this._determineHeightConstraint()
        //   if (heightConstraint) {
        //     this.style.setProperty('--bf-browser-height', `${heightConstraint}px`)
        //   }
        // }
        /**
         * Determine the height to which the browser should be constrained in order to
         * achieve the best UX within the containing elements.
         */
        // private _determineHeightConstraint() {
        //   // Ascend the DOM tree to find the first ancestor with a height that is
        //   // less than this element's "natural" height. If one is found, use its
        //   // height as the constraint.
        //   const thisHeight = this.getBoundingClientRect().height
        //   let ancestor = this.parentElement
        //   while (ancestor) {
        //     const ancestorHeight = ancestor.getBoundingClientRect().height
        //     if (ancestorHeight < thisHeight) {
        //       return ancestorHeight
        //     }
        //     ancestor = ancestor.parentElement
        //   }
        //
        //   return null
        // }
        /**
         * Async task for communicating with the Drupal backend (to submit user input,
         * fetch assets from Brandfolder, etc.).
         */
        this._browserUpdateTask = new Task(this, {
            task: async ([requestedPage], { signal, }) => {
                const response = await fetch(this._apiEndpoint, {
                    signal,
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json; charset=UTF-8',
                    },
                    body: JSON.stringify({
                        bfBrowserId: this.bfBrowserId,
                        userInput: this?._userInput,
                        requestedPage,
                        assetsPerPage: this._assetsPerPage
                    }),
                });
                if (!response.ok) {
                    throw new Error(response?.statusText || response?.status.toString());
                }
                const responseBody = await response.json();
                // Reset the asset list if we're fetching the first page of results.
                if (!requestedPage || requestedPage === 1) {
                    this._assetList = [];
                }
                if (responseBody?.assets?.length) {
                    this._assetList = this._assetList.concat(responseBody.assets);
                }
                if (responseBody?.meta) {
                    this._assetFetchMeta = responseBody.meta;
                }
                if (responseBody?.controlSchema) {
                    this._controlSchema = responseBody.controlSchema;
                }
                return responseBody;
            },
            autoRun: false,
        });
        /**
         * Handle closure of asset detail pane.
         */
        this._assetDetailCloseHandler = () => {
            this._activeAsset = null;
            this.classList.remove('is-asset-detail-open');
            // Close the selection tray when the asset detail pane is closed, to
            // conserve screen real estate. User can always reopen it if they desire.
            this._isSelectionTrayOpen = false;
        };
        /**
         * Handle attachment selection events.
         */
        this._attachmentSelectionHandler = (e) => {
            // @todo: Manage selection limits
            const { attachmentId, attachment, isSelected } = e.detail;
            if (!attachmentId?.length) {
                return;
            }
            // Update the browser context to indicate whether the attachment is
            // selected. Do this in a way that will trigger an update.
            const selectedAttachments = this._browserContext.selectedAttachments;
            if (isSelected) {
                selectedAttachments[attachmentId] = attachment;
                // Always ensure the selection tray is open when an attachment is newly
                // selected.
                this._isSelectionTrayOpen = true;
            }
            else {
                delete selectedAttachments[attachmentId];
            }
            this._browserContext = {
                ...this._browserContext,
                selectedAttachments
            };
            // Find the closest form ancestor, then find the hidden input element
            // named "selected_bf_attachment_ids" and append the attachment ID to its
            // value if it's not already present.
            const browserElement = e.target;
            const form = browserElement.closest('form');
            if (form) {
                const selectedAttachmentIdsInput = form.querySelector('input[name="selected_bf_attachment_ids"]');
                if (selectedAttachmentIdsInput) {
                    selectedAttachmentIdsInput.value = Object.keys(this._browserContext.selectedAttachments).join(',');
                }
            }
        };
        this.addEventListener('bfAssetDetailClose', this._assetDetailCloseHandler);
        this.addEventListener('bfAttachmentSelection', this._attachmentSelectionHandler);
        this.addEventListener('bfBrowserControlsSubmission', this._controlsSubmissionHandler);
        this.addEventListener('bfSelectionTrayToggle', () => {
            this._isSelectionTrayOpen = !this._isSelectionTrayOpen;
        });
    }
    /**
     * Callback executed when the element is added to the document.
     */
    connectedCallback() {
        super.connectedCallback();
        let previouslySelectedAttachmentIds = null;
        // Apply any configurable settings.
        if (this.settings && typeof this.settings === 'string') {
            const settings = JSON.parse(this.settings);
            if (settings.apiEndpoint) {
                this._apiEndpoint = settings.apiEndpoint;
            }
            if (settings.assetsPerPage) {
                this._assetsPerPage = settings.assetsPerPage;
            }
            if (settings.selectedAttachmentIds) {
                previouslySelectedAttachmentIds = settings.selectedAttachmentIds;
                // Generate an object keyed by the given IDs, where each value is a
                // basic object with placeholder attachments.
                this._browserContext.selectedAttachments = previouslySelectedAttachmentIds.reduce((acc, id) => {
                    acc[id] = {
                        id,
                        mimetype: 'image/jpeg',
                        extension: 'jpg',
                        filename: `loading-${id}.jpg`,
                        size: 12345,
                        width: 800,
                        height: 600,
                    };
                    return acc;
                }, {});
            }
            if (settings.selectionLimit) {
                this._browserContext.selectionLimit = settings.selectionLimit;
            }
            // if (settings.format) {
            //   this._format = settings.format
            // }
            // if (settings.height) {
            //   this._height = settings.height
            // }
        }
        // Perform an initial data fetch (requesting the first page of assets).
        this._browserUpdateTask.run([1]).then();
        // if (this._format === 'inline') {
        //   // We might need to adjust the browser height when the window is resized
        //   // (e.g. when the browser lives within a modal that occupies a certain
        //   // percentage of the viewport).
        //   window.addEventListener('resize', this._calibrateHeight)
        // } else if (this._format === 'full') {
        //   if (typeof this.settings === 'object' && this._height) {
        //     this.style.height = `${this._height}px`
        //   }
        // }
    }
    /**
     * Submit the search/filter/sort form.
     */
    _controlsSubmissionHandler(e) {
        // Check to see if the user has made any changes to the form, and only
        // submit if they have.
        // @todo: Initialize this._userInput with a value equivalent to that of an empty form submission.
        if (JSON.stringify(this._userInput) !== JSON.stringify(e.detail.userInput)) {
            this._assetList = [];
            this._assetFetchMeta = null;
            // Set the new user input with the data provided by the controls. Copy
            // it to avoid establishing a reference to the child component's data.
            // If we did that, changes to the controls would immediately be
            // reflected in this._userInput here, which isn't how data is supposed to
            // be communicated from children to parent components, and would thwart
            // our change detection strategy.
            this._userInput = { ...e.detail.userInput };
            this._browserUpdateTask.run([1]).then();
        }
        else {
            // @todo: If there's no need to fetch new data, do a little flash/flourish of some sort to signal a near-instantaneous update.
        }
    }
    /**
     * Handle asset selection. When a user selects an asset preview, display
     * the asset's detail view.
     */
    _assetSelectionHandler(e) {
        const assetPreview = e.target;
        if (assetPreview?.asset) {
            this._activeAsset = assetPreview.asset;
            this.classList.add('is-asset-detail-open');
        }
    }
    /**
     * Define the element's template / rendered HTML.
     */
    render() {
        const numAssets = this._assetList?.length;
        const numAssetsTotal = this._assetFetchMeta?.total_count;
        let fetchStatusMessage = 'fetching assets...';
        const taskStatus = this._browserUpdateTask.status;
        if (taskStatus === TaskStatus.COMPLETE) {
            fetchStatusMessage = numAssets > 0
                ? `showing ${numAssets}${numAssetsTotal ? ` of ${new Intl.NumberFormat().format(numAssetsTotal)} ` : ' '}assets`
                : 'no assets found';
        }
        else if (taskStatus === TaskStatus.ERROR) {
            fetchStatusMessage = 'there was an error fetching assets';
        }
        return html `
      <div class="bf-browser__inner">
        <div class="bf-browser__controls-container">
          <brandfolder-browser-controls .controlSchema="${this._controlSchema}" .browserFetchStatusMessage="${fetchStatusMessage}" />
        </div>
        <div class="bf-browser__results-container">
          <div class="asset-list">
            ${numAssets > 0 ?
            this._assetList.map((asset) => html `
                  <brandfolder-asset-preview
                    @click="${this._assetSelectionHandler}"
                    bf-asset-id=${asset.id}
                    .asset=${asset}
                  />
                `)
            : ''}
          </div>
          ${this._assetFetchMeta?.next_page
            ? html `
                <div class="bf-browser__pagination">
                  <div class="bf-browser__load-more">
                    <button
                      @click=${() => this._browserUpdateTask.run([
                this._assetFetchMeta?.next_page,
            ])}
                    >
                      Load More
                    </button>
                  </div>
                </div>
              `
            : ''}
        </div>
        ${this._activeAsset
            ? html `
              <brandfolder-asset-detail
                bf-asset-id=${this._activeAsset.id}
                .asset=${this._activeAsset}
              />
            `
            : ''}
        <div class="bf-browser__selection-tray-container">
          <brandfolder-browser-selection-tray .isOpen=${this._isSelectionTrayOpen && Object.values(this._browserContext.selectedAttachments).length > 0}>
          </brandfolder-browser-selection-tray>
        </div>
      </div>
    `;
    }
};
BrandfolderBrowser.styles = css `
    :host {
      --color-gray-50: #f9f9fa;
      --color-gray-100: #f2f2f3;
      --color-gray-200: #e9e9ea;
      --color-gray-300: #d9d9da;
      --color-gray-400: #b5b5b6;
      --color-gray-500: #959596;
      --color-gray-600: #6d6d6e;
      --color-gray-700: #59595a;
      --color-gray-800: #3b3b3c;
      --color-gray-900: #1a1a1b;
      --color-gray-900-trans: #1a1a1bdd;

      --color-white: #ffffff;

      --bf-browser-height: 100%;

      display: block;
      font-family: system-ui;
      background: white;
      width: 100%;
      height: var(--bf-browser-height);
      color: var(--color-gray-800);
      box-sizing: border-box;
    }

    :host([format='full']) {
      position: fixed;
      top: 0;
      left: 0;
      z-index: 1;
    }

    :host(.is-asset-detail-open) {
      overflow: hidden;
    }

    .bf-browser__inner {
      position: relative;
      display: grid;
      grid-template-rows: auto 1fr auto;
      height: 100%;
    }

    .bf-browser__controls-container {
      grid-area: 1 / 1 / span 1 / -1;
    }

    .bf-browser__results-container {
      grid-area: 2 / 1 / span 1 / -1;
      padding: 0.5rem;
      overflow: scroll;
    }

    .bf-browser__selection-tray-container {
      grid-area: 3 / 1 / span 1 / -1;
      z-index: 3;
    }

    .asset-list {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
      gap: 1rem;
    }

    .bf-browser__pagination {
      display: flex;
      justify-content: center;
      padding: 2rem 0;
    }
  `;
__decorate([
    property({ type: String, attribute: 'bf-browser-id' })
], BrandfolderBrowser.prototype, "bfBrowserId", void 0);
__decorate([
    property({ type: String, attribute: 'settings' })
], BrandfolderBrowser.prototype, "settings", void 0);
__decorate([
    state()
], BrandfolderBrowser.prototype, "_apiEndpoint", void 0);
__decorate([
    state()
], BrandfolderBrowser.prototype, "_assetsPerPage", void 0);
__decorate([
    state()
], BrandfolderBrowser.prototype, "_activeAsset", void 0);
__decorate([
    state()
], BrandfolderBrowser.prototype, "_isSelectionTrayOpen", void 0);
__decorate([
    state()
], BrandfolderBrowser.prototype, "_assetList", void 0);
__decorate([
    state()
], BrandfolderBrowser.prototype, "_userInput", void 0);
__decorate([
    state()
], BrandfolderBrowser.prototype, "_controlSchema", void 0);
__decorate([
    state()
], BrandfolderBrowser.prototype, "_assetFetchMeta", void 0);
__decorate([
    provide({ context: bfBrowserContext }),
    state()
], BrandfolderBrowser.prototype, "_browserContext", void 0);
BrandfolderBrowser = __decorate([
    customElement('brandfolder-browser')
], BrandfolderBrowser);
export { BrandfolderBrowser };
//# sourceMappingURL=brandfolder-browser.js.map