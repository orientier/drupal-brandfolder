import {css, html, LitElement} from 'lit'
import {Task, TaskStatus} from '@lit/task'
import {customElement, property, state} from 'lit/decorators.js'
import {BfAsset} from './brandfolder-asset-base'
import {BrandfolderAssetPreview} from './brandfolder-asset-preview'
import {
  BfBrowserControlSchema,
  BfBrowserUserInput,
} from './brandfolder-browser-controls'
// Import all subcomponents and class dependencies so we can compile
// everything into a single JS file with this file as the sole entry point.
import './brandfolder-asset-base'
import './brandfolder-asset-detail'
import './brandfolder-asset-preview'
import './brandfolder-attachment'
import './brandfolder-browser-controls'
import './brandfolder-browser-labels-filter'
import './bf-browser-tags-control'

type BfAssetFetchMeta = {
  current_page: number
  next_page: number
  prev_page: number
  total_pages: number
  total_count: number
}

type BfFetchResponse = {
  assets: BfAsset[]
  meta: BfAssetFetchMeta
  controlSchema: BfBrowserControlSchema
}

type BfBrowserSettings = {
  height: number
  format: 'inline' | 'full'
  endpoint: string
}

// type bfGatekeeperCriteriaBase = {
//   collection?: string[]
//   section?: string[]
//   label?: string[]
//   filetype?: string[]
// }
//
// type bfGatekeeperCriteria = {
//   allowed: bfGatekeeperCriteriaBase
//   disallowed: bfGatekeeperCriteriaBase
// }

/**
 * An interface for viewing/searching/filtering/selecting assets and attachments
 * from Brandfolder.
 */
@customElement('brandfolder-browser')
export class BrandfolderBrowser extends LitElement {
  static override styles = css`
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

      --color-white: #ffffff;

      --bf-browser-height: 100%;

      display: block;
      font-family: system-ui;
      background: white;
      width: 100%;
      height: var(--bf-browser-height);
      color: var(--color-gray-800);
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
      grid-template-rows: auto auto 1fr;
      height: 100%;
    }

    .bf-browser__controls-container {
      grid-area: 1 / 1 / span 1 / -1;
      padding: 0.5rem;
      background-color: var(--color-gray-50);
    }

    .bf-browser__metadata-container {
      grid-area: 2 / 1 / span 1 / -1;
      background: var(--color-white);
      display: flex;
      align-items: center;
    }

    .results-metadata {
      padding: 0.5rem;
      font-style: italic;
      font-size: 0.9em;
    }

    .bf-browser__results-container {
      grid-area: 3 / 1 / span 1 / -1;
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

    .bf-browser__pagination {
      display: flex;
      justify-content: center;
      padding: 2rem 0;
    }
  `

  /**
   * Unique ID for the browser instance.
   */
  @property({type: String, attribute: 'bf-browser-id'})
  bfBrowserId: string | null = null

  /**
   * A generic settings object with key-value pairs. Initialized as a
   * JSON string.
   */
  @property({type: String, attribute: 'settings'})
  settings: BfBrowserSettings | string | null = null

  /**
   * The URL to which API requests should be sent.
   */
  @state()
  private _apiEndpoint = '/brandfolder-browser-update'

  /**
   * The number of assets to fetch per page.
   */
  @state()
  private _assetsPerPage = 100

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
  @state()
  private _activeAsset: BfAsset | null = null

  /**
   * An array of BfAsset items representing the current result set.
   */
  @state()
  private _assetList: BfAsset[] = []

  /**
   * An object with properties corresponding to user-facing controls, with
   * any corresponding user-supplied values.
   */
  @state()
  private _userInput: BfBrowserUserInput | null = null

  /**
   * An object with data sufficient to build user-facing controls.
   */
  @state()
  private _controlSchema: BfBrowserControlSchema | null = null

  /**
   * An object containing metadata about the latest asset search/fetch,
   * including total items, total pages, current page, etc.
   */
  @state()
  private _assetFetchMeta: BfAssetFetchMeta | null = null

  /**
   * Constructor.
   */
  constructor() {
    super()
    this.addEventListener('bfAssetDetailClose', this._assetDetailCloseHandler)
    this.addEventListener(
      'bfAttachmentSelection',
      this._attachmentSelectionHandler
    )
    this.addEventListener(
      'bfBrowserControlsSubmission',
      this._controlsSubmissionHandler
    )
  }

  /**
   * Callback executed when the element is added to the document.
   */
  override connectedCallback() {
    super.connectedCallback()

    // Apply any configurable settings.
    if (this.settings && typeof this.settings === 'string') {
      const settings = JSON.parse(this.settings)
      if (settings.apiEndpoint) {
        this._apiEndpoint = settings.apiEndpoint
      }
      if (settings.assetsPerPage) {
        this._assetsPerPage = settings.assetsPerPage
      }
      // if (settings.format) {
      //   this._format = settings.format
      // }
      // if (settings.height) {
      //   this._height = settings.height
      // }
    }

    // Perform an initial data fetch (requesting the first page of assets).
    this._browserUpdateTask.run([1]).then()

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
  private _browserUpdateTask = new Task(this, {
    task: async (
      [requestedPage]: [number],
      {
        signal,
      }: {
        signal: AbortSignal
      }
    ) : Promise<BfFetchResponse> => {
      const response = await fetch(
        this._apiEndpoint,
        {
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
        }
      )
      if (!response.ok) {
        throw new Error(response?.statusText || response?.status.toString())
      }
      const responseBody: BfFetchResponse = await response.json()

      // Reset the asset list if we're fetching the first page of results.
      if (!requestedPage || requestedPage === 1) {
        this._assetList = []
      }

      if (responseBody?.assets?.length) {
        this._assetList = this._assetList.concat(responseBody.assets)
      }
      if (responseBody?.meta) {
        this._assetFetchMeta = responseBody.meta
      }
      if (responseBody?.controlSchema) {
        this._controlSchema = responseBody.controlSchema
      }

      return responseBody
    },
    autoRun: false,
  })

  /**
   * Submit the search/filter/sort form.
   */
  private _controlsSubmissionHandler(e: CustomEvent) {
    // Check to see if the user has made any changes to the form, and only
    // submit if they have.
    // @todo: Initialize this._userInput with a value equivalent to that of an empty form submission.
    if (JSON.stringify(this._userInput) !== JSON.stringify(e.detail.userInput)) {
      this._assetList = []
      this._assetFetchMeta = null
      // Set the new user input with the data provided by the controls. Copy
      // it to avoid establishing a reference to the child component's data.
      // If we did that, changes to the controls would immediately be
      // reflected in this._userInput here, which isn't how data is supposed to
      // be communicated from children to parent components, and would thwart
      // our change detection strategy.
      this._userInput = {...e.detail.userInput}
      this._browserUpdateTask.run([1]).then()
    }
    else {
      // @todo: If there's no need to fetch new data, do a little flash/flourish of some sort to signal a near-instantaneous update.

    }
  }

  /**
   * Handle asset selection. When a user selects an asset preview, display
   * the asset's detail view.
   */
  private _assetSelectionHandler(e: Event) {
    const assetPreview = e.target as BrandfolderAssetPreview
    if (assetPreview?.asset) {
      this._activeAsset = assetPreview.asset
      this.classList.add('is-asset-detail-open')
    }
  }

  /**
   * Handle closure of asset detail pane.
   */
  private _assetDetailCloseHandler = () => {
    this._activeAsset = null
    this.classList.remove('is-asset-detail-open')
  }

  /**
   * Handle attachment selection events.
   */
  private _attachmentSelectionHandler = (e: CustomEvent) => {
    // @todo: Manage selection limits, maintain a tray showing all selected items, etc.
    const attachmentId = e.detail.attachmentId
    if (!attachmentId?.length) {
      return
    }
    // Find the closest form ancestor, then find the hidden input element
    // named "selected_bf_attachment_ids" and append the attachment ID to its
    // value if it's not already present.
    const browserElement = e.target as HTMLElement
    const form = browserElement.closest('form')
    if (form) {
      const selectedAttachmentIdsInput = form.querySelector(
        'input[name="selected_bf_attachment_ids"]'
      ) as HTMLInputElement
      if (selectedAttachmentIdsInput) {
        const inputVal = selectedAttachmentIdsInput.value.trim()
        const selectedAttachmentIds = inputVal.split(',').filter((id) => id)
        if (!selectedAttachmentIds.includes(attachmentId)) {
          selectedAttachmentIds.push(attachmentId)
          selectedAttachmentIdsInput.value = selectedAttachmentIds.join(',')
        }
      }
    }
  }

  /**
   * Define the element's template / rendered HTML.
   */
  override render() {
    const numAssets = this._assetList?.length
    const numAssetsTotal = this._assetFetchMeta?.total_count
    let metadataText = 'Fetching assets...'
    const taskStatus = this._browserUpdateTask.status
    if (taskStatus === TaskStatus.COMPLETE) {
      metadataText = numAssets > 0
        ? `Showing ${numAssets}${numAssetsTotal ? ` of ${new Intl.NumberFormat().format(numAssetsTotal)} ` : ' '}assets.`
        : 'No assets found.'
    }
    else if (taskStatus === TaskStatus.ERROR) {
      metadataText = 'There was an error fetching assets.'
    }

    return html`
      <div class="bf-browser__inner">
        <div class="bf-browser__controls-container">
          <brandfolder-browser-controls .controlSchema="${this._controlSchema}" />
        </div>
        <div class="bf-browser__metadata-container">
          <div class="results-metadata">
            ${metadataText}
          </div>
        </div>
        <div class="bf-browser__results-container">
          <div class="asset-list">
            ${numAssets > 0 ?
              this._assetList.map(
                (asset) => html`
                  <brandfolder-asset-preview
                    @click="${this._assetSelectionHandler}"
                    bf-asset-id=${asset.id}
                    .asset=${asset}
                  />
                `
              )
              :  ''}
          </div>
          ${this._assetFetchMeta?.next_page
            ? html`
                <div class="bf-browser__pagination">
                  <div class="bf-browser__load-more">
                    <button
                      @click=${() =>
                        this._browserUpdateTask.run([
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
          ? html`
              <brandfolder-asset-detail
                bf-asset-id=${this._activeAsset.id}
                .asset=${this._activeAsset}
              />
            `
          : ''}
      </div>
    `
  }
}

/**
 * Add the element to the global registry so TypeScript will infer the correct
 * class of an HTML element returned from certain DOM APIs (it does this based
 * on the tag name).
 */
declare global {
  interface HTMLElementTagNameMap {
    'brandfolder-browser': BrandfolderBrowser
  }
}
