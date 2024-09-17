import {html, css, LitElement} from 'lit'
import {
  customElement,
  property,
  query,
  queryAll,
  state,
} from 'lit/decorators.js'

export type BfKvList = {
  [key: string]: string
}

export type BfLabel = {
  id: string
  name: string
  depth: number
  parent: BfLabel | null
  children: BfLabel[]
}

export type BfSortCriterion = 'name' | 'score' | 'position' | 'updated_at' | 'created_at'

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
  labels?: BfLabel[]
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
  labels?: string[]
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
  private _userInput: BfBrowserUserInput | null = null

  /**
   * Create a reference to the search text input element.
   */
  @query('.search-text-input')
  searchTextInput: HTMLInputElement

  /**
   * Create a reference to the collections input element.
   */
  @queryAll('.collection-input')
  collectionInputs: HTMLInputElement[]

  /**
   * Handle the submission of the search/filter/sort form.
   */
  private _controlsSubmissionHandler() {
    const userInput: BfBrowserUserInput = {
      searchText: this.searchTextInput.value,
    }
    if (this.collectionInputs) {
      const collectionInputsArray = Array.from(this.collectionInputs)
      userInput.collections = collectionInputsArray
        .filter((input) => input.checked)
        .map((input) => input.value)
    }
    this._userInput = userInput

    // Notify ancestors of the submission.
    const options = {
      detail: {userInput: this._userInput},
      bubbles: true,
      composed: true,
    }
    this.dispatchEvent(new CustomEvent('bfBrowserControlsSubmission', options))
  }

  override render() {
    return html`
      <input type="text" class="search-text-input" aria-label="Search" />
      ${(this.controlSchema?.collections && Object.keys(this.controlSchema?.collections)?.length > 1) &&
        html`
          <fieldset class="collections-container">
            <legend>Collections</legend>
            <div class="collections">
                ${Object.keys(this.controlSchema.collections).map(
                  (collectionId) => {
                    const collectionName = this.controlSchema.collections[collectionId]
                    const isSelected = this._userInput?.collections?.includes(collectionId)
                    const inputName = 'collection'

                    return html`
                      <input
                        type="checkbox"
                        class="collection-input"
                        id="collection-input--${collectionId}"
                        aria-label="${collectionName}"
                        name="${inputName}"
                        value=${collectionId}
                        ?checked=${isSelected}
                      />
                      <label for=${inputName}>${collectionName}</label>
                    `
                  }
                )}
            </div>
          </fieldset>
        `}
      <button @click=${this._controlsSubmissionHandler}>Submit</button>
    `
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'brandfolder-browser-controls': BrandfolderBrowserControls
  }
}
