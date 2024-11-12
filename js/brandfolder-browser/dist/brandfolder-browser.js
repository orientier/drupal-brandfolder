var __decorate = (this && this.__decorate) || function (decorators, target, key, desc) {
    var c = arguments.length, r = c < 3 ? target : desc === null ? desc = Object.getOwnPropertyDescriptor(target, key) : desc, d;
    if (typeof Reflect === "object" && typeof Reflect.decorate === "function") r = Reflect.decorate(decorators, target, key, desc);
    else for (var i = decorators.length - 1; i >= 0; i--) if (d = decorators[i]) r = (c < 3 ? d(r) : c > 3 ? d(target, key, r) : d(target, key)) || r;
    return c > 3 && r && Object.defineProperty(target, key, r), r;
};
import { css, html, LitElement } from 'lit';
import { Task, TaskStatus } from '@lit/task';
import { customElement, property, state } from 'lit/decorators.js';
// Import all subcomponents and class dependencies so we can compile
// everything into a single JS file with this file as the sole entry point.
import './brandfolder-asset-base';
import './brandfolder-asset-detail';
import './brandfolder-asset-preview';
import './brandfolder-attachment';
import './brandfolder-browser-controls';
import './brandfolder-browser-labels-filter';
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
         * The format in which the browser should be displayed. Options:
         * - 'inline' (default): Display the browser inline within the page.
         * - 'full': Display the browser in a way that consumes all available space
         *    in the host window/frame/document.
         */
        this.format = 'inline';
        // /**
        //  * A generic settings object with key-value pairs. Initialized as a
        //  * JSON string.
        //  */
        // @property({type: String, attribute: 'settings'})
        // settings: BfBrowserSettings | string | null = null
        /**
         * An object of criteria for determining which assets may be
         * accessed via this browser.
         */
        this.bfGatekeeperCriteria = { allowed: {}, disallowed: {} };
        /**
         * The number of assets to fetch per page.
         */
        this.assetsPerPage = 100;
        /**
         * Active asset.
         */
        this._activeAsset = null;
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
         * Callback executed when the element is removed from the document.
         */
        // override disconnectedCallback() {
        //   if (this.format === 'inline') {
        //     window.removeEventListener('resize', this._calibrateHeight)
        //   }
        //   super.disconnectedCallback()
        // }
        /**
         * Callback executed when the element is updated.
         */
        // override updated(_changedProperties: Map<string | number | symbol, unknown>) {
        //   if (this.format === 'inline') {
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
                const response = await fetch(
                // `/brandfolder-browser-update`,
                // Dev:
                `https://brandfolder-drupal-11.orien.tier/brandfolder-browser-update?XDEBUG_SESSION_START=PHPSTORM`, {
                    signal,
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json; charset=UTF-8',
                    },
                    body: JSON.stringify({
                        bfBrowserId: this.bfBrowserId,
                        userInput: this?._userInput,
                        requestedPage,
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
        };
        /**
         * Handle attachment selection events.
         */
        this._attachmentSelectionHandler = (e) => {
            // @todo: Manage selection limits, maintain a tray showing all selected items, etc.
            const attachmentId = e.detail.attachmentId;
            if (!attachmentId?.length) {
                return;
            }
            // Find the closest form ancestor, then find the hidden input element
            // named "selected_bf_attachment_ids" and append the attachment ID to its
            // value if it's not already present.
            const browserElement = e.target;
            const form = browserElement.closest('form');
            if (form) {
                const selectedAttachmentIdsInput = form.querySelector('input[name="selected_bf_attachment_ids"]');
                if (selectedAttachmentIdsInput) {
                    const inputVal = selectedAttachmentIdsInput.value.trim();
                    const selectedAttachmentIds = inputVal.split(',').filter((id) => id);
                    if (!selectedAttachmentIds.includes(attachmentId)) {
                        selectedAttachmentIds.push(attachmentId);
                        selectedAttachmentIdsInput.value = selectedAttachmentIds.join(',');
                    }
                }
            }
        };
        this.addEventListener('bfAssetDetailClose', this._assetDetailCloseHandler);
        this.addEventListener('bfAttachmentSelection', this._attachmentSelectionHandler);
        this.addEventListener('bfBrowserControlsSubmission', this._controlsSubmissionHandler);
    }
    /**
     * Callback executed when the element is added to the document.
     */
    connectedCallback() {
        super.connectedCallback();
        // Perform an initial data fetch (requesting the first page of assets).
        this._browserUpdateTask.run([1]).then();
        // Convert settings attribute from JSON string to object.
        // if (this.settings && typeof this.settings === 'string') {
        //   this.settings = JSON.parse(this.settings)
        // }
        // console.log('BrandfolderBrowser connectedCallback')
        // if (this.format === 'inline') {
        //   // We might need to adjust the browser height when the window is resized
        //   // (e.g. when the browser lives within a modal that occupies a certain
        //   // percentage of the viewport).
        //   window.addEventListener('resize', this._calibrateHeight)
        // } else if (this.format === 'full') {
        //   if (typeof this.settings === 'object' && this.settings?.height) {
        //     this.style.height = `${this.settings.height}px`
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
        return html `
      <div class="bf-browser__inner">
        <div class="bf-browser__controls-container">
          <brandfolder-browser-controls .controlSchema="${this._controlSchema}" />
        </div>
        <div class="bf-browser__results-container">
          <div class="asset-list">
            ${this._assetList?.length > 0 ?
            this._assetList.map((asset) => html `
                  <brandfolder-asset-preview
                    @click="${this._assetSelectionHandler}"
                    bf-asset-id=${asset.id}
                    .asset=${asset}
                  />
                `)
            : (this._browserUpdateTask.status === TaskStatus.COMPLETE ? html `<p>No assets found.</p>` : '')}
            ${this._browserUpdateTask.render({
            pending: () => html `<p>Fetching assets...</p>`,
            error: (e) => {
                console.error(e);
                return html `
                  <div class="error-message">
                    <p>There was an error fetching assets.</p>
                  </div>
                `;
            },
        })}
          </div>
          ${this._assetFetchMeta?.next_page
            ? html `
                <div class="pagination">
                  <p class="pagination__info">
                    ...and
                    ${new Intl.NumberFormat().format(this._assetFetchMeta.total_count -
                this._assetFetchMeta.current_page * this.assetsPerPage)}
                    more
                  </p>
                  <div class="load-more">
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
      </div>
    `;
    }
};
BrandfolderBrowser.styles = css `
    :host {
      --color-gray-50: #f1f1f1;
      --color-gray-100: #dddddd;
      --color-gray-200: #c6c6c7;
      --color-gray-300: #afafb0;
      --color-gray-400: #9e9e9f;
      --color-gray-500: #8d8d8e;
      --color-gray-600: #858586;
      --color-gray-700: #7a7a7b;
      --color-gray-800: #707071;
      --color-gray-900: #5d5d5f;

      --bf-browser-height: 100%;

      display: block;
      font-family: system-ui;
      background: white;
      width: 100%;
      height: var(--bf-browser-height);
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
      grid-template-rows: auto 1fr;
      height: 100%;
    }

    .bf-browser__controls-container {
      grid-area: 1 / 1 / 2 / -1;
      padding: 0.5rem;
      margin: 0 0 1rem;
      background: var(--color-gray-50);
    }

    .bf-browser__results-container {
      grid-area: 2 / 1 / 3 / -1;
      padding: 0.5rem;
      overflow: scroll;
    }

    :host.is-asset-detail-open .bf-browser__controls-container,
    :host.is-asset-detail-open .bf-browser__results-container {
      display: none;
    }

    .asset-list {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
      gap: 1rem;
    }

    .asset-list .error-message {
      grid-column: 1 / -1;
    }
  `;
__decorate([
    property({ type: String, attribute: 'bf-browser-id' })
], BrandfolderBrowser.prototype, "bfBrowserId", void 0);
__decorate([
    property({ type: String, attribute: 'format' })
], BrandfolderBrowser.prototype, "format", void 0);
__decorate([
    property({ type: Object, attribute: null })
], BrandfolderBrowser.prototype, "bfGatekeeperCriteria", void 0);
__decorate([
    property({ type: Number, attribute: false })
], BrandfolderBrowser.prototype, "assetsPerPage", void 0);
__decorate([
    state()
], BrandfolderBrowser.prototype, "_activeAsset", void 0);
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
BrandfolderBrowser = __decorate([
    customElement('brandfolder-browser')
], BrandfolderBrowser);
export { BrandfolderBrowser };
//# sourceMappingURL=brandfolder-browser.js.map