import {LitElement, html, css} from 'lit'
import {Task, TaskFunction} from '@lit/task'
import {customElement, property, state, query} from 'lit/decorators.js'
import {BfAsset} from './brandfolder-asset-base'
import {BrandfolderAssetPreview} from './brandfolder-asset-preview'
// Import all subcomponents and class dependencies so we can compile
// everything into a single JS file with this file as the sole entry point.
import './brandfolder-asset-base'
import './brandfolder-asset-detail'
import './brandfolder-asset-preview'
import './brandfolder-attachment'

// type BfAssetFetchResponse = {
//   assets: BfAsset[]
//   meta: {
//     total: number
//     page: number
//     per_page: number
//   }
// }

type BfBrowserSettings = {
  height: number
}

/**
 * An interface for viewing/searching/filtering/selecting assets and attachments
 * from Brandfolder.
 */
@customElement('brandfolder-browser')
export class BrandfolderBrowser extends LitElement {
  static override styles = css`
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

    .brandfolder-browser__inner {
      position: relative;
      display: grid;
      grid-template-rows: auto 1fr;
      height: 100%;
    }

    .search-and-filter {
      grid-area: 1 / 1 / 2 / -1;
      padding: 0.5rem;
      margin: 0 0 1rem;
      background: var(--color-gray-50);
    }

    .main-content {
      grid-area: 2 / 1 / 3 / -1;
      padding: 0.5rem;
      overflow: scroll;
    }

    .asset-list {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
      gap: 1rem;
    }

    .asset-list .error-message {
      grid-column: 1 / -1;
    }
  `

  /**
   * Unique ID for the browser instance.
   */
  @property({type: String, attribute: 'bf-browser-id'})
  bfBrowserId: string | null = null

  /**
   * The format in which the browser should be displayed. Options:
   * - 'inline' (default): Display the browser inline within the page.
   * - 'full': Display the browser in a way that consumes all available space
   *    in the host window/frame/document.
   */
  @property({type: String, attribute: 'format'})
  format: string = 'inline'

  /**
   * A generic settings object with key-value pairs. Initialized as a
   * JSON string.
   */
  @property({type: String, attribute: 'settings'})
  settings: BfBrowserSettings | string | null = null

  /**
   * A stringified object of criteria for determining which assets may be
   * accessed via this browser.
   */
  @property({type: String, attribute: 'bf-gatekeeper-criteria'})
  bfGatekeeperCriteria = JSON.stringify({disallowed: []})

  /**
   * User-provided search query text.
   */
  @state()
  private _searchText: string | null = null

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
   * The number of assets to fetch per page.
   */
  @property({type: Number, attribute: false})
  assetsPerPage = 100

  /**
   * An object containing metadata about the latest asset search/fetch,
   * including total items, total pages, current page, etc.
   */
  @state()
  private _assetFetchMeta: {
    current_page: number
    next_page: number
    prev_page: number
    total_pages: number
    total_count: number
  } | null = null

  /**
   * Create a reference to the search text input element.
   */
  @query('.search-text-input')
  searchTextInput: HTMLInputElement

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
  }

  /**
   * Callback executed when the element is added to the document.
   */
  override connectedCallback() {
    super.connectedCallback()

    // Perform an initial asset fetch (requesting the first page of results).
    this._assetFetchTask.run([1])

    // Convert settings attribute from JSON string to object.
    // @todo: Pull this from a browser-ID-keyed registry in Drupal settings if possible.
    if (this.settings && typeof this.settings === 'string') {
      this.settings = JSON.parse(this.settings)
    }

    if (this.format === 'inline') {
      // We might need to adjust the browser height when the window is resized
      // (e.g. when the browser lives within a modal that occupies a certain
      // percentage of the viewport).
      window.addEventListener('resize', this._calibrateHeight)
    } else if (this.format === 'full') {
      if (typeof this.settings === 'object' && this.settings?.height) {
        this.style.height = `${this.settings.height}px`
      }
    }
  }

  /**
   * Callback executed when the element is removed from the document.
   */
  override disconnectedCallback() {
    if (this.format === 'inline') {
      window.removeEventListener('resize', this._calibrateHeight)
    }
    super.disconnectedCallback()
  }

  /**
   * Callback executed when the element is updated.
   */
  override updated(_changedProperties: Map<string | number | symbol, unknown>) {
    if (this.format === 'inline') {
      // After fetching and rendering new assets, determine whether the
      // browser's height should be constrained in order to achieve
      // the best UX within the containing context.
      if (_changedProperties.has('_assetList')) {
        this._calibrateHeight()
      }
    }
  }

  /**
   * Set the browser's height based on context.
   */
  private _calibrateHeight = () => {
    console.log('Calibrating height...')
    this.style.setProperty('--bf-browser-height', '100%')
    const heightConstraint = this._determineHeightConstraint()
    if (heightConstraint) {
      this.style.setProperty('--bf-browser-height', `${heightConstraint}px`)
    }
  }

  /**
   * Determine the height to which the browser should be constrained in order to
   * achieve the best UX within the containing elements.
   */
  private _determineHeightConstraint() {
    // Ascend the DOM tree to find the first ancestor with a height that is
    // less than this element's "natural" height. If one is found, use its
    // height as the constraint.
    const thisHeight = this.getBoundingClientRect().height
    let ancestor = this.parentElement
    while (ancestor) {
      const ancestorHeight = ancestor.getBoundingClientRect().height
      if (ancestorHeight < thisHeight) {
        return ancestorHeight
      }
      ancestor = ancestor.parentElement
    }

    return null
  }

  /**
   * Async task for fetching assets from Brandfolder via Drupal backend.
   */
  private _assetFetchTask = new Task(this, {
    task: async (
      [requestedPage]: [number],
      {
        signal,
      }: {
        signal: AbortSignal
      }
    ): Promise<TaskFunction<any>> => {
      const response = await fetch(`/brandfolder-asset-fetch`, {
        signal,
        method: 'POST',
        headers: {
          'Content-Type': 'application/json; charset=UTF-8',
        },
        body: JSON.stringify({
          bfBrowserId: this.bfBrowserId,
          bfGatekeeperCriteria: JSON.parse(this.bfGatekeeperCriteria),
          userInput: {
            searchText: this._searchText,
          },
          requestedPage,
        }),
      })
      if (!response.ok) {
        throw new Error(response?.statusText || response?.status.toString())
      }
      const responseBody = await response.json()

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

      return responseBody
    },
    autoRun: false,
  })

  /**
   * Submit the search/filter/sort form.
   */
  private _submitSearchAndFilter() {
    this._searchText = this.searchTextInput.value
    this._assetFetchTask.run([1])
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
    console.log('Attachment selected:', e)
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
    return html`
      <div class="brandfolder-browser__inner">
        <div class="search-and-filter">
          <input type="text" class="search-text-input" aria-label="Search" />
          <button @click=${this._submitSearchAndFilter}>Submit</button>
        </div>
        <div class="main-content">
          <div class="asset-list">
            ${this._assetList.map(
              (asset) => html`
                <brandfolder-asset-preview
                  @click="${this._assetSelectionHandler}"
                  bf-asset-id=${asset.id}
                  .asset=${asset}
                />
              `
            )}
            ${this._assetFetchTask.render({
              pending: () => html`<p>Fetching assets...</p>`,
              error: (e: string) => {
                console.error(e)

                return html`
                  <div class="error-message">
                    <p>There was an error fetching assets.</p>
                  </div>
                `
              },
            })}
          </div>
          ${this._assetFetchMeta?.next_page
            ? html`
                <div class="pagination">
                  <p class="pagination__info">
                    ...and
                    ${new Intl.NumberFormat().format(
                      this._assetFetchMeta.total_count -
                        this._assetFetchMeta.current_page * this.assetsPerPage
                    )}
                    more
                  </p>
                  <div class="load-more">
                    <button
                      @click=${() =>
                        this._assetFetchTask.run([
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
