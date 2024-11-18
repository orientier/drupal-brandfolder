import {html, css, LitElement} from 'lit'
import {
  customElement,
  property,
  query,
  queryAll,
  state,
} from 'lit/decorators.js'
import {BfLabelTreeNode} from "./brandfolder-browser-labels-filter";

export type BfKvList = {
  [key: string]: string
}

export type BfTagFilterMode = 'any' | 'all'

export type BfSortCriterion =
  'name'
  | 'score'
  | 'position'
  | 'updated_at'
  | 'created_at'
export type BfSortCriteriaList = {
  [key in BfSortCriterion]: string
}

export type BfSortOrder = 'asc' | 'desc'
export type BfSortOrderList = {
  [key in BfSortOrder]: string
}

export type BfAspectRatio = 'landscape' | 'portrait' | 'square' | 'panorama'
export type BfAspectRatiosList = {
  [key in BfAspectRatio]: string
}

export type BfFiletype = 'jpg' | 'png' | 'svg' | 'gif' | 'webp'

export type BfDateRange = 'all' | '30m' | '1d' | '7d' | '30d' | '60d' | '90d'
export type BfDateRangesList = {
  [key in BfDateRange]: string
}

export type BfBrowserControlSchema = {
  searchText?: string
  collections?: BfKvList
  sections?: BfKvList
  labels?: BfLabelTreeNode[]
  tags?: string[]
  aspect?: BfAspectRatiosList
  filetype?: BfFiletype[]
  creationDate?: BfDateRangesList
  modificationDate?: BfDateRangesList
  publicationDate?: BfDateRangesList
  sortCriteria?: BfSortCriteriaList
  sortOrder?: BfSortOrderList
}

export type BfBrowserUserInput = {
  searchText?: string
  collections?: string[]
  sections?: string[]
  labels?: Record<string, string>
  tags?: string[]
  tagFilterMode?: BfTagFilterMode
  aspect?: BfAspectRatio[]
  filetype?: BfFiletype[]
  creationDate?: BfDateRange
  modificationDate?: BfDateRange
  publicationDate?: BfDateRange
  sortCriterion?: BfSortCriterion
  sortOrder?: BfSortOrder
}

/**
 * User interface/input/controls for a Brandfolder browser.
 */
@customElement('brandfolder-browser-controls')
export class BrandfolderBrowserControls extends LitElement {
  static override styles = css`
    :host {
      color: var(--color-gray-800);
    }

    .controls__header,
    .controls__main {
      background: var(--color-gray-50);
    }

    .controls__header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 1rem;
      padding: 0.5rem;
      font-size: 1rem;
    }

    .browser-fetch-status-message {
      font-family: monospace;
      padding: 0 0.1rem;
      font-size: 0.9em;
      //background: var(--color-gray-300);
    }

    .controls__toggle {
      display: flex;
      transition: all 0.3s;
    }
    .controls__toggle:hover {
      cursor: pointer;
      font-weight: bolder;
    }
    .controls__title,
    .controls__open-indicator {
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 0.2rem;
    }
    .controls__open-indicator {
      width: 1.5rem;
      padding: 0 0.2rem;
    }
    .open-indicator__icon {
      width: 0.9rem;
      transition: transform 0.3s;
    }
    .open-indicator__icon path {
      stroke: var(--color-gray-800);
      stroke-width: 1;
    }
    .controls__toggle:hover .open-indicator__icon path {
      stroke-width: 1.5;
    }
    .open-indicator__icon.open {
      transform: scaleY(-1);
    }

    .controls__main {
      max-height: 0;
      overflow: hidden;
      transition: all 0.3s;
      padding: 0 0.25rem;
    }

    .controls__inner.is-open .controls__main {
      max-height: 100vh;
      padding: 0.5rem;
    }

    .controls__inputs {
      display: flex;
      flex-wrap: wrap;
      gap: 1rem 0.5rem;
    }

    .bf-control-item {
      flex: 1;
    }

    .bf-control-item--search-text {
      flex-basis: 100%;
      display: flex;
    }

    .bf-control-item fieldset {
      border: 1px solid var(--color-gray-400);
      padding: 0.75rem;
    }
    .bf-control-item fieldset legend {
      white-space: nowrap;
    }

    .search-text-input {
      width: 100%;
      font-size: 1rem;
      padding: 0.3rem 0.4rem;
      color: var(--color-gray-900);
    }

    .input-group {
      display: flex;
      flex-wrap: wrap;
      gap: 0.5rem 1rem;
    }

    .input-item {
      display: flex;
      align-items: center;
      justify-content: flex-start;
      gap: 0.25rem;
      flex-wrap: nowrap;
    }

    .controls__actions {
      display: flex;
      justify-content: flex-start;
      align-items: center;
      gap: 1rem;
      padding: 1rem 0.25rem 0.5rem;
    }
  `

  /**
   * Default values for user-facing controls.
   */
  private controlsInputDefaults: BfBrowserUserInput = {
    searchText: '',
    collections: [],
    sections: [],
    labels: {},
    tags: [],
    aspect: [],
    filetype: [],
    creationDate: 'all',
    modificationDate: 'all',
    publicationDate: 'all',
    sortCriterion: 'created_at',
    sortOrder: 'desc',
  }

  /**
   * An object with data sufficient to build user-facing controls.
   */
  @property({type: Object, attribute: false})
  controlSchema: BfBrowserControlSchema | null = null

  /**
   * A message indicating the status of the current/latest browser fetch
   * operation.
   */
  @property({type: String})
  browserFetchStatusMessage: string | null = null

  /**
   * An object with properties corresponding to user-facing controls, with
   * any corresponding user-supplied values.
   */
  @state()
  private _controlsInput: BfBrowserUserInput = {...this.controlsInputDefaults}

  /**
   * Manage open/closed state.
   */
  @state()
  private _isOpen = false

  /**
   * Create a reference to the search text input element.
   */
  @query('.search-text-input')
  searchTextInput: HTMLInputElement

  /**
   * Create a reference to the collections input elements.
   */
  @queryAll('.collection-input')
  collectionInputs: HTMLInputElement[]

  /**
   * Create a reference to the sections input elements.
   */
  @queryAll('.section-input')
  sectionInputs: HTMLInputElement[]

  /**
   * Create a reference to the aspect/orientation input elements.
   */
  @queryAll('.aspect-input')
  aspectInputs: HTMLInputElement[]

  /**
   * Create a reference to the filetype input elements.
   */
  @queryAll('.filetype-input')
  filetypeInputs: HTMLInputElement[]

  /**
   * Create a reference to the creation date select element.
   */
  @query('.brandfolder-browser-controls__creation-date')
  creationDateSelect: HTMLSelectElement

  /**
   * Create a reference to the modification date select element.
   */
  @query('.brandfolder-browser-controls__modification-date')
  modificationDateSelect: HTMLSelectElement

  /**
   * Create a reference to the publication date select element.
   */
  @query('.brandfolder-browser-controls__publication-date')
  publicationDateSelect: HTMLSelectElement

  /**
   * Create a reference to the sort criterion select element.
   */
  @query('.brandfolder-browser-controls__sort-criterion')
  sortCriterionSelect: HTMLSelectElement

  /**
   * Create a reference to the sort order select element.
   */
  @query('.brandfolder-browser-controls__sort-order')
  sortOrderSelect: HTMLSelectElement

  /**
   * Constructor.
   */
  constructor() {
    super()
    this.addEventListener(
      'bfLabelsChanged',
      this._labelsChangeHandler
    )
    this.addEventListener(
      'bfTagsChanged',
      this._tagsChangeHandler
    )
  }

  /**
   * Handle use of the "reset" button. Reset all user input.
   */
  private _controlsResetHandler() {
    this._controlsInput = {...this.controlsInputDefaults}
  }

  /**
   * Handle the submission of the search/filter/sort form.
   */
  private _controlsSubmissionHandler() {
    // Notify ancestors of the submission.
    const options = {
      detail: {userInput: this._controlsInput},
      bubbles: true,
      composed: true,
    }
    this.dispatchEvent(new CustomEvent('bfBrowserControlsSubmission', options))
  }

  /**
   * Pull UI element values into state. Call this whenever any relevant UI
   * element changes. This isn't as efficient as making precise updates
   * affecting only the state/prop corresponding to the changed element, but
   * it's simpler and more convenient. Consider refactoring if control volume
   * makes the difference noticeable...but this will probably have a different
   * shape anyway once we establish filter subcomponents.
   */
  private _controlsChangeHandler() {
    this._controlsInput.searchText = this.searchTextInput.value
    if (this?.collectionInputs?.length) {
      const collectionInputsArray = Array.from(this.collectionInputs)
      this._controlsInput.collections = collectionInputsArray
        .filter((input) => input.checked)
        .map((input) => input.value)
    }
    if (this?.sectionInputs?.length) {
      const sectionInputsArray = Array.from(this.sectionInputs)
      this._controlsInput.sections = sectionInputsArray
        .filter((input) => input.checked)
        .map((input) => input.value)
    }
    if (this?.aspectInputs?.length) {
      const aspectInputsArray = Array.from(this.aspectInputs)
      this._controlsInput.aspect = aspectInputsArray
        .filter((input) => input.checked)
        .map((input) => input.value as BfAspectRatio)
    }
    if (this?.filetypeInputs?.length) {
      const filetypeInputsArray = Array.from(this.filetypeInputs)
      this._controlsInput.filetype = filetypeInputsArray
        .filter((input) => input.checked)
        .map((input) => input.value as BfFiletype)
    }
    if (this?.creationDateSelect) {
      this._controlsInput.creationDate = this.creationDateSelect.value as BfDateRange
    }
    if (this?.modificationDateSelect) {
      this._controlsInput.modificationDate = this.modificationDateSelect.value as BfDateRange
    }
    if (this?.publicationDateSelect) {
      this._controlsInput.publicationDate = this.publicationDateSelect.value as BfDateRange
    }
    if (this?.sortCriterionSelect) {
      this._controlsInput.sortCriterion = this.sortCriterionSelect.value as BfSortCriterion
    }
    if (this?.sortOrderSelect) {
      this._controlsInput.sortOrder = this.sortOrderSelect.value as BfSortOrder
    }
  }

  /**
   * Listen for label selection changes.
   */
  private _labelsChangeHandler(e: CustomEvent) {
    this._controlsInput = {
      ...this._controlsInput,
      labels: e.detail.selectedLabelsById
    }
  }

  /**
   * Listen for tag selection changes.
   */
  private _tagsChangeHandler(e: CustomEvent) {
    this._controlsInput = {
      ...this._controlsInput,
      tags: e.detail.selectedTags,
      tagFilterMode: e.detail.tagFilterMode
    }
  }

  /**
   * Render the component.
   *
   * @todo: Use subcomponents for each control, etc.
   */
  override render() {
    return html`
      <div class="controls__inner ${this._isOpen ? 'is-open' : ''}">
        <header class="controls__header">
          <span class="browser-fetch-status-message">${this.browserFetchStatusMessage}</span>
          <span class="controls__toggle" @click=${() => this._isOpen = !this._isOpen}>
            <span class="controls__title">Search & Filter</span>
            <span class="controls__open-indicator">
              <svg
                class="open-indicator__icon ${this._isOpen ? 'open' : 'closed'}"
                xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 9"
              >
                <path d="M15 1.57812L7.66667 7.57812" />
                <path d="M1 1.57812L7.66667 7.57813" />
              </svg>
            </span>
          </span>
        </header>
        <main class="controls__main">
          <div class="controls__inputs">
            <div class="bf-control-item bf-control-item--search-text">
              <input type="text" class="search-text-input" aria-label="Search"
                     placeholder="Enter search text..."
                     .value="${this._controlsInput?.searchText ?? ''}"
                     @change=${this._controlsChangeHandler}
                     @keyup=${(e: KeyboardEvent) => {
                       if (e.key === 'Enter') {
                         this._controlsSubmissionHandler()
                       }
                     }}
              />
            </div>
            ${(this?.controlSchema?.collections && Object.keys(this.controlSchema?.collections)?.length > 1) ? html`
              <div class="bf-control-item">
                <fieldset class="collections-container">
                  <legend>Collections</legend>
                  <div class="collections input-group">
                    ${Object.keys(this.controlSchema.collections).map(
                      (collectionId) => {
                        const collectionName = this.controlSchema.collections[collectionId]
                        const isSelected = this._controlsInput?.collections?.includes(collectionId)
                        const inputName = 'collection'

                        return html`
                          <span class="input-item">
                            <input
                              type="checkbox"
                              class="collection-input"
                              id="collection-input--${collectionId}"
                              aria-label="${collectionName}"
                              name="${inputName}"
                              value=${collectionId}
                              .checked=${isSelected}
                              @change=${this._controlsChangeHandler}
                            />
                            <label for=${inputName}>${collectionName}</label>
                          </span>
                        `
                      }
                    )}
                  </div>
                </fieldset>
              </div>` : ''
            }
            ${(this?.controlSchema?.sections && Object.keys(this.controlSchema?.sections)?.length > 1) ? html`
              <div class="bf-control-item">
                <fieldset class="sections-container">
                  <legend>Sections</legend>
                  <div class="sections input-group">
                    ${Object.keys(this.controlSchema.sections).map(
                      (sectionId) => {
                        const sectionName = this.controlSchema.sections[sectionId]
                        const isSelected = this._controlsInput?.sections?.includes(sectionId)
                        const inputName = 'section'

                        return html`
                          <span class="input-item">
                            <input
                              type="checkbox"
                              class="section-input"
                              id="section-input--${sectionId}"
                              aria-label="${sectionName}"
                              name="${inputName}"
                              value=${sectionId}
                              .checked=${isSelected}
                              @change=${this._controlsChangeHandler}
                            />
                            <label for=${inputName}>${sectionName}</label>
                          </span>
                        `
                      }
                    )}
                  </div>
                </fieldset>
              </div>` : ''
            }
            ${(this?.controlSchema?.labels && Object.keys(this.controlSchema?.labels)?.length > 1) ? html`
              <div class="bf-control-item">
                <fieldset class="labels-container">
                  <legend>Labels</legend>
                  <brandfolder-browser-labels-filter
                    .allLabels=${this.controlSchema.labels}
                    .selectedLabels=${this._controlsInput?.labels}
                  />
                </fieldset>
              </div>` : ''
            }
            <div class="bf-control-item">
              <fieldset class="tags-container">
                <legend>Tags</legend>
                <bf-browser-tags-control
                  .selectedTags=${this._controlsInput?.tags}
                />
              </fieldset>
            </div>
            ${(this?.controlSchema?.aspect && Object.keys(this.controlSchema.aspect)?.length > 1) ? html`
              <div class="bf-control-item">
                <fieldset class="aspect-container">
                  <legend>Orientation</legend>
                  <div class="aspects input-group">
                    ${Object.keys(this.controlSchema.aspect).map(
                      (aspectKey: BfAspectRatio) => {
                        const aspectName: string = this.controlSchema.aspect[aspectKey]
                        const isSelected = this._controlsInput?.aspect?.includes(aspectKey)
                        const inputName = 'aspect'

                        return html`
                          <span class="input-item">
                            <input
                              type="checkbox"
                              class="aspect-input"
                              id="aspect-input--${aspectKey}"
                              aria-label="${aspectName}"
                              name="${inputName}"
                              value=${aspectKey}
                              .checked=${isSelected}
                              @change=${this._controlsChangeHandler}
                            />
                            <label for=${inputName}>${aspectName}</label>
                          </span>
                        `
                      }
                    )}
                  </div>
                </fieldset>
              </div>` : ''
            }
            ${(this?.controlSchema?.filetype && Object.keys(this.controlSchema.filetype)?.length > 1) ? html`
              <div class="bf-control-item">
                <fieldset class="filetype-container">
                  <legend>File Type</legend>
                  <div class="filetypes input-group">
                    ${this.controlSchema.filetype.map(
                      (filetype: BfFiletype) => {
                        const isSelected = this._controlsInput?.filetype?.includes(filetype)
                        const inputName = 'filetype'

                        return html`
                          <span class="input-item">
                            <input
                              type="checkbox"
                              class="filetype-input"
                              id="filetype-input--${filetype}"
                              aria-label="${filetype}"
                              name="${inputName}"
                              value=${filetype}
                              .checked=${isSelected}
                              @change=${this._controlsChangeHandler}
                            />
                            <label for=${inputName}>${filetype}</label>
                          </span>
                        `
                      }
                    )}
                  </div>
                </fieldset>
              </div>` : ''
            }
            ${(this?.controlSchema?.creationDate && Object.keys(this.controlSchema.creationDate)?.length > 1) ? html`
              <div class="bf-control-item">
                <fieldset class="creation-date-container">
                  <legend>Created/Uploaded</legend>
                  <select
                    name="brandfolder-browser-controls-creation-date"
                    class="brandfolder-browser-controls__creation-date"
                    @change=${this._controlsChangeHandler}
                  >
                    ${Object.keys(this.controlSchema.creationDate).map(
                      (creationDateKey: BfDateRange) => {
                        const creationDateName: string = this.controlSchema.creationDate[creationDateKey]
                        const isSelected = this._controlsInput?.creationDate === creationDateKey

                        return html`
                          <option
                            value=${creationDateKey}
                            .selected=${isSelected}
                          >
                            ${creationDateName}
                          </option>
                        `
                      }
                    )}
                </fieldset>
              </div>` : ''
            }
            ${(this?.controlSchema?.modificationDate && Object.keys(this.controlSchema.modificationDate)?.length > 1) ? html`
              <div class="bf-control-item">
                <fieldset class="modification-date-container">
                  <legend>Last Updated</legend>
                  <select
                    name="brandfolder-browser-controls-modification-date"
                    class="brandfolder-browser-controls__modification-date"
                    @change=${this._controlsChangeHandler}
                  >
                    ${Object.keys(this.controlSchema.modificationDate).map(
                      (modificationDateKey: BfDateRange) => {
                        const modificationDateName: string = this.controlSchema.modificationDate[modificationDateKey]
                        const isSelected = this._controlsInput?.modificationDate === modificationDateKey

                        return html`
                          <option
                            value=${modificationDateKey}
                            .selected=${isSelected}
                          >
                            ${modificationDateName}
                          </option>
                        `
                      }
                    )}
                </fieldset>
              </div>` : ''
            }
            ${(this?.controlSchema?.publicationDate && Object.keys(this.controlSchema.publicationDate)?.length > 1) ? html`
              <div class="bf-control-item">
                <fieldset class="publication-date-container">
                  <legend>Published</legend>
                  <select
                    name="brandfolder-browser-controls-publication-date"
                    class="brandfolder-browser-controls__publication-date"
                    @change=${this._controlsChangeHandler}
                  >
                    ${Object.keys(this.controlSchema.publicationDate).map(
                      (publicationDateKey: BfDateRange) => {
                        const publicationDateName: string = this.controlSchema.publicationDate[publicationDateKey]
                        const isSelected = this._controlsInput?.publicationDate === publicationDateKey

                        return html`
                          <option
                            value=${publicationDateKey}
                            .selected=${isSelected}
                          >
                            ${publicationDateName}
                          </option>
                        `
                      }
                    )}
                </fieldset>
              </div>` : ''
            }
            ${(this?.controlSchema?.sortCriteria && Object.keys(this.controlSchema.sortCriteria)?.length > 1) ? html`
              <div class="bf-control-item">
                <fieldset class="sorting-container">
                  <legend>Sorting</legend>
                  <div
                    class="brandfolder-browser-controls__sort-criterion-container">
                    <label for="brandfolder-browser-controls-sort-criterion">Sort
                      by:</label>
                    <select
                      name="brandfolder-browser-controls-sort-criterion"
                      class="brandfolder-browser-controls__sort-criterion"
                      @change=${this._controlsChangeHandler}
                    >
                      ${Object.keys(this.controlSchema.sortCriteria).map(
                        (sortCriterion: BfSortCriterion) => {
                          const sortCriterionName: string = this.controlSchema.sortCriteria[sortCriterion]
                          const isSelected = this._controlsInput?.sortCriterion === sortCriterion

                          return html`
                            <option
                              value=${sortCriterion}
                              .selected=${isSelected}
                            >
                              ${sortCriterionName}
                            </option>
                          `
                        }
                      )}
                    </select>
                  </div>
                  ${(this?.controlSchema?.sortOrder && Object.keys(this.controlSchema.sortOrder)?.length > 1) ? html`
                    <div
                      class="brandfolder-browser-controls__sort-order-container">
                      <label for="brandfolder-browser-controls-sort-order">Sort
                        order:</label>
                      <select
                        name="brandfolder-browser-controls-sort-order"
                        class="brandfolder-browser-controls__sort-order"
                        @change=${this._controlsChangeHandler}
                      >
                        ${Object.keys(this.controlSchema.sortOrder).map(
                          (sortOrder: BfSortOrder) => {
                            const sortOrderName: string = this.controlSchema.sortOrder[sortOrder]
                            const isSelected = this._controlsInput?.sortOrder === sortOrder

                            return html`
                              <option
                                value=${sortOrder}
                                .selected=${isSelected}
                              >
                                ${sortOrderName}
                              </option>
                            `
                          }
                        )}
                      </select>
                    </div>` : ''
                  }
                </fieldset>
              </div>` : ''
            }
          </div>
          <footer class="controls__actions">
            <button @click=${this._controlsResetHandler}>Reset</button>
            <button @click=${this._controlsSubmissionHandler}>Submit</button>
          </footer>
        </main>
      </div>
    `
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'brandfolder-browser-controls': BrandfolderBrowserControls
  }
}
