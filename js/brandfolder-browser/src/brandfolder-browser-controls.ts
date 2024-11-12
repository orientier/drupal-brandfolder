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

export type BfSortCriterion =
  'name'
  | 'score'
  | 'position'
  | 'updated_at'
  | 'created_at'

export type BfSortOrder = 'asc' | 'desc'

export type BfAspectRatio = 'landscape' | 'portrait' | 'square' | 'panorama'
export type BfAspectRatiosList = {
  [key in BfAspectRatio]: string
}

export type BfFiletype = 'jpg' | 'png' | 'svg' | 'gif' | 'webp'

export type BfUploadDate = 'all' | '30m' | '1d' | '7d' | '30d'
export type BfUploadDatesList = {
  [key in BfUploadDate]: string
}

export type BfBrowserControlSchema = {
  searchText?: string
  collections?: BfKvList
  sections?: BfKvList
  labels?: BfLabelTreeNode[]
  tags?: string[]
  aspect?: BfAspectRatiosList
  filetype?: BfFiletype[]
  uploadDate?: BfUploadDatesList
  sortCriterion?: BfSortCriterion
  sortOrder?: BfSortOrder
}

export type BfBrowserUserInput = {
  searchText?: string
  collections?: string[]
  sections?: string[]
  labels?: Record<string, string>
  tags?: string[]
  aspect?: BfAspectRatio[]
  filetype?: BfFiletype[]
  uploadDate?: BfUploadDate
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
      height: auto;
    }
  `

  /**
   * An object with data sufficient to build user-facing controls.
   */
  @property({type: Object, attribute: false})
  controlSchema: BfBrowserControlSchema | null = null

  /**
   * An object with properties corresponding to user-facing controls, with
   * any corresponding user-supplied values.
   */
  @state()
  private _controlsInput: BfBrowserUserInput | null = null

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
   * Constructor.
   */
  constructor() {
    super()
    this.addEventListener(
      'bfLabelsChanged',
      this._labelsChangeHandler
    )
  }

  /**
   * Handle use of the "reset" button. Reset all user input.
   */
  private _controlsResetHandler() {
    this._controlsInput = null
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
   * makes the difference noticeable.
   */
  private _controlsChangeHandler() {
    this._controlsInput = this._controlsInput ?? {}
    this._controlsInput.searchText = this.searchTextInput.value
    if (this.collectionInputs) {
      const collectionInputsArray = Array.from(this.collectionInputs)
      this._controlsInput.collections = collectionInputsArray
        .filter((input) => input.checked)
        .map((input) => input.value)
    }
    if (this.sectionInputs) {
      const sectionInputsArray = Array.from(this.sectionInputs)
      this._controlsInput.sections = sectionInputsArray
        .filter((input) => input.checked)
        .map((input) => input.value)
    }
    if (this.aspectInputs) {
      const aspectInputsArray = Array.from(this.aspectInputs)
      this._controlsInput.aspect = aspectInputsArray
        .filter((input) => input.checked)
        .map((input) => input.value as BfAspectRatio)
    }
  }

  /**
   * Listen for label selection changes.
   */
  private _labelsChangeHandler(e: CustomEvent) {
    this._controlsInput = {...this._controlsInput, labels: e.detail.selectedLabelsById}
  }

  override render() {
    return html`
      <input type="text" class="search-text-input" aria-label="Search"
             .value="${this._controlsInput?.searchText ?? ''}"
             @change=${this._controlsChangeHandler}/>
      ${(this?.controlSchema?.collections && Object.keys(this.controlSchema?.collections)?.length > 1) ? html`
        <fieldset class="collections-container">
          <legend>Collections</legend>
          <div class="collections">
            ${Object.keys(this.controlSchema.collections).map(
              (collectionId) => {
                const collectionName = this.controlSchema.collections[collectionId]
                const isSelected = this._controlsInput?.collections?.includes(collectionId)
                const inputName = 'collection'

                return html`
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
                `
              }
            )}
          </div>
        </fieldset>` : ''
      }
      ${(this?.controlSchema?.sections && Object.keys(this.controlSchema?.sections)?.length > 1) ? html`
        <fieldset class="sections-container">
          <legend>Sections</legend>
          <div class="sections">
            ${Object.keys(this.controlSchema.sections).map(
              (sectionId) => {
                const sectionName = this.controlSchema.sections[sectionId]
                const isSelected = this._controlsInput?.sections?.includes(sectionId)
                const inputName = 'section'

                return html`
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
                `
              }
            )}
          </div>
        </fieldset>` : ''
      }
      ${(this?.controlSchema?.labels && Object.keys(this.controlSchema?.labels)?.length > 1) ? html`
        <div class="labels-container">
          <brandfolder-browser-labels-filter
            .allLabels=${this.controlSchema.labels}
            .selectedLabels=${this._controlsInput?.labels}
          />
        </div>` : ''
      }
      ${(this?.controlSchema?.aspect && Object.keys(this.controlSchema.aspect)?.length > 1) ? html`
        <fieldset class="aspect-container">
          <legend>Orientation</legend>
          <div class="aspect-options">
            ${Object.keys(this.controlSchema.aspect).map(
        (aspectKey: BfAspectRatio) => {
          const aspectName: string = this.controlSchema.aspect[aspectKey]
          const isSelected = this._controlsInput?.aspect?.includes(aspectKey)
          const inputName = 'aspect'

          return html`
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
                `
        }
      )}
          </div>
        </fieldset>` : ''
      }
      <button @click=${this._controlsResetHandler}>Reset</button>
      <button @click=${this._controlsSubmissionHandler}>Submit</button>
    `
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'brandfolder-browser-controls': BrandfolderBrowserControls
  }
}
