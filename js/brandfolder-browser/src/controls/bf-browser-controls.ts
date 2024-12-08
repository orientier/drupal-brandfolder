import {html, css, LitElement} from 'lit'
import {
  customElement,
  property,
  query,
  state,
} from 'lit/decorators.js'
import {BfLabelTreeNode} from "./bf-browser-control--labels";

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
export type BfFiletypeList = {
  [key in BfFiletype]: string
}

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
  filetype?: BfFiletypeList
  creationDate?: BfDateRangesList
  modificationDate?: BfDateRangesList
  publicationDate?: BfDateRangesList
  sortCriteria?: BfSortCriteriaList
  sortOrder?: BfSortOrderList
}

export type BfBrowserControlSchemaKey = keyof BfBrowserControlSchema

export type BfControlSchemaKvEsque = Pick<BfBrowserControlSchema, 'collections' | 'sections' | 'aspect' | 'filetype' | 'creationDate' | 'modificationDate' | 'publicationDate' | 'sortCriteria' | 'sortOrder'>
export type BfControlSchemaKvEsqueKey = keyof BfControlSchemaKvEsque

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

export type BfBrowserUserInputKey = keyof BfBrowserUserInput

export type BfControlInputStringArrayEsque = Pick<BfBrowserUserInput, 'collections' | 'sections' | 'tags' | 'aspect' | 'filetype'>
export type BfControlInputStringArrayEsqueKey = keyof BfControlInputStringArrayEsque

export type BfControlInputStringEsque = Pick<BfBrowserUserInput, 'searchText' | 'tagFilterMode' | 'creationDate' | 'modificationDate' | 'publicationDate' | 'sortCriterion' | 'sortOrder'>
export type BfControlInputStringEsqueKey = keyof BfControlInputStringEsque
export type BfControlStringEsqueType = string | BfTagFilterMode | BfDateRange | BfSortCriterion | BfSortOrder

/**
 * User interface/input/controls for a Brandfolder browser.
 */
@customElement('brandfolder-browser-controls')
export class BfBrowserControls extends LitElement {
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

    .controls__control-items {
      display: flex;
      flex-wrap: wrap;
      gap: 1rem 0.5rem;
      max-height: calc(var(--bf-browser-height) - 6rem);
      overflow: scroll;
    }

    .bf-control-item {
      flex: 1;
    }
    brandfolder-browser-control-item {
      flex: 1;
    }
    .bf-control-item--search-text {
      flex-basis: 100%;
      display: flex;
    }

    .controls__actions {
      display: flex;
      justify-content: space-between;
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
    tagFilterMode: 'any',
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
      'bfControlInputChange',
      this._controlInputHandler
    )
    // Listen for submission events dispatched from descendant controls
    // (e.g. when the user presses the "Enter" key from within a text search
    // field).
    this.addEventListener(
      'bfControlsSubmit',
      this._controlsSubmissionHandler
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
   * Listen for user input events from descendant controls.
   */
  private _controlInputHandler(e: CustomEvent) {
    if (e.detail.controlInput) {
      this._controlsInput = {
        ...this._controlsInput,
        ...e.detail.controlInput
      }
    }
  }

  /**
   * Render the component.
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
          <div class="controls__control-items">
            <brandfolder-browser-control-item label="Search" class="bf-control-item--search-text">
              <brandfolder-browser-control--search
                .controlInput=${{searchText: this._controlsInput.searchText}}
              >
              </brandfolder-browser-control--search>
            </brandfolder-browser-control-item>
            ${this?.controlSchema?.collections ? html`
            <brandfolder-browser-control-item label="Collections" class="bf-control-item--collections">
              <brandfolder-browser-control--checkboxes
                .controlSchema=${{collections: this.controlSchema.collections}}
                .controlInput=${{collections: this._controlsInput.collections}}
              >
              </brandfolder-browser-control--checkboxes>
            </brandfolder-browser-control-item>
              ` : ''
            }
            ${this?.controlSchema?.sections ? html`
              <brandfolder-browser-control-item label="Sections" class="bf-control-item--sections">
                <brandfolder-browser-control--checkboxes
                  .controlSchema=${{sections: this.controlSchema.sections}}
                  .controlInput=${{sections: this._controlsInput.sections}}
                >
                </brandfolder-browser-control--checkboxes>
              </brandfolder-browser-control-item>
            ` : ''
            }
            ${this?.controlSchema?.labels ? html`
              <brandfolder-browser-control-item label="Labels" class="bf-control-item--labels">
                  <brandfolder-browser-control--labels
                    .controlSchema=${{labels: this.controlSchema.labels}}
                    .controlInput=${{labels: this._controlsInput?.labels}}
                  >
                  </brandfolder-browser-control--labels>
              </brandfolder-browser-control-item>
            ` : ''
            }
            <brandfolder-browser-control-item label="Tags" class="bf-control-item--tags">
              <brandfolder-browser-control--tags
                .controlInput=${{tags: this._controlsInput.tags, tagFilterMode: this._controlsInput.tagFilterMode}}
              >
              </brandfolder-browser-control--tags>
            </brandfolder-browser-control-item>
            ${this?.controlSchema?.aspect ? html`
              <brandfolder-browser-control-item label="Orientation" class="bf-control-item--aspect">
                <brandfolder-browser-control--checkboxes
                  .controlSchema=${{aspect: this.controlSchema.aspect}}
                  .controlInput=${{aspect: this._controlsInput.aspect}}
                >
                </brandfolder-browser-control--checkboxes>
              </brandfolder-browser-control-item>
            ` : ''
            }
            ${(this?.controlSchema?.filetype && Object.keys(this.controlSchema.filetype)?.length > 1) ? html`
              <brandfolder-browser-control-item label="File Type" class="bf-control-item--filetype">
                <brandfolder-browser-control--checkboxes
                  .controlSchema=${{filetype: this.controlSchema.filetype}}
                  .controlInput=${{filetype: this._controlsInput.filetype}}
                >
                </brandfolder-browser-control--checkboxes>
              </brandfolder-browser-control-item>
            ` : ''
            }
            ${this?.controlSchema?.creationDate ? html`
              <brandfolder-browser-control-item
                label="Created/Uploaded"
                class="bf-control-item--creation-date"
              >
                <brandfolder-browser-control--select
                  .controlSchema=${{creationDate: this.controlSchema.creationDate}}
                  .controlInput=${{creationDate: this._controlsInput.creationDate}}
                >
                </brandfolder-browser-control--select>
              </brandfolder-browser-control-item>` : ''
            }
            ${(this?.controlSchema?.modificationDate && Object.keys(this.controlSchema.modificationDate)?.length > 1) ? html`
              <brandfolder-browser-control-item
                label="Last Updated"
                class="bf-control-item--modification-date"
              >
                <brandfolder-browser-control--select
                  .controlSchema=${{modificationDate: this.controlSchema.modificationDate}}
                  .controlInput=${{modificationDate: this._controlsInput.modificationDate}}
                >
                </brandfolder-browser-control--select>
              </brandfolder-browser-control-item>` : ''
            }
            ${(this?.controlSchema?.publicationDate && Object.keys(this.controlSchema.publicationDate)?.length > 1) ? html`
              <brandfolder-browser-control-item
                label="Published"
                class="bf-control-item--publication-date"
              >
                <brandfolder-browser-control--select
                  .controlSchema=${{publicationDate: this.controlSchema.publicationDate}}
                  .controlInput=${{publicationDate: this._controlsInput.publicationDate}}
                >
                </brandfolder-browser-control--select>
              </brandfolder-browser-control-item>` : ''
            }
            ${(this?.controlSchema?.sortCriteria && Object.keys(this.controlSchema.sortCriteria)?.length > 1) ? html`
              <brandfolder-browser-control-item
                label="Sorting"
                class="bf-control-item--sorting"
              >
                <brandfolder-browser-control--select
                  .label="${`Sort by`}"
                  .controlSchema=${{sortCriteria: this.controlSchema.sortCriteria}}
                  .controlInput=${{sortCriterion: this._controlsInput.sortCriterion}}
                >
                </brandfolder-browser-control--select>
                <brandfolder-browser-control--select
                  .label="${`Order`}"
                  .controlSchema=${{sortOrder: this.controlSchema.sortOrder}}
                  .controlInput=${{sortOrder: this._controlsInput.sortOrder}}
                >
                </brandfolder-browser-control--select>
              </brandfolder-browser-control-item>` : ''
            }
          </div>
          <div class="controls__actions">
            <button @click=${this._controlsSubmissionHandler}>Submit</button>
            <button @click=${this._controlsResetHandler}>Reset</button>
          </div>
        </main>
      </div>
    `
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'brandfolder-browser-controls': BfBrowserControls
  }
}
