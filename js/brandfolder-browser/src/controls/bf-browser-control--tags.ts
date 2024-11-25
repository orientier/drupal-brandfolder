import {css, html} from 'lit'
import {customElement, query} from 'lit/decorators.js'
import {BfBrowserControlBase} from "./bf-browser-control-base";

/**
 * UI for specifying Brandfolder tags for asset filtering.
 */
@customElement('brandfolder-browser-control--tags')
export class BfBrowserTagsControl extends BfBrowserControlBase {
  static override styles = css`
    .bf-browser-tags-control__inner {
      display: flex;
      flex-direction: column;
      gap: 1rem;
    }

    .tag-input-container {
      display: flex;
      gap: 0.5rem;
    }
    .tag-text-input {
      flex-grow: 1;
    }
    .tag-add-button {
      flex-shrink: 0;
    }

    .tag-list {
      display: flex;
      gap: 0.5rem;
      flex-wrap: wrap;
    }
    .tag {
      display: flex;
      font-size: 0.875rem;
    }
    .tag-text {
      background-color: var(--color-gray-200);
      padding: 0.25rem 0.5rem;
      align-items: center;
    }
    .tag-removal {
      font-size: 0.7em;
      background-color: var(--color-gray-200);
      border-left: 1px solid var(--color-gray-300);
      //clip-path: polygon(0 0, 50% 0, 100% 25%, 100% 75%, 50% 100%, 0 100%);
      color: var(--color-gray-500);
      //padding: 0.25rem 0.4rem 0.25rem 0.25rem;
      padding: 0.25rem 0.5rem;
      cursor: pointer;
      display: flex;
      align-items: center;
    }
    .tag-removal:hover {
      background-color: var(--color-gray-300);
    }

    .tag-filter-mode {
      display: flex;
      flex-direction: column;
      gap: 0.5rem;
      font-size: 0.875rem;
    }
    .tag-filter-mode__option {
      padding: 0 0 0 0.5rem;
      display: flex;
      gap: 0.25rem;
    }
  `

  // /**
  //  * A list of all currently selected tags.
  //  */
  // @property({type: Object, attribute: false})
  // selectedTags: string[] = []
  //
  // /**
  //  * The selected tag filter mode.
  //  */
  // @property({type: String, attribute: false})
  // tagFilterMode: BfTagFilterMode | null = 'any'

  // /**
  //  * A list of all currently selected tags.
  //  */
  // @state()
  // private _selectedTags: string[] = []
  //
  // /**
  //  * The selected tag filter mode.
  //  */
  // @state()
  // private _tagFilterMode: BfTagFilterMode | null = 'any'

  /**
   * Create a reference to the input element.
   */
  @query('.tag-text-input')
  tagTextInput: HTMLInputElement

  /**
   * Event handler for adding a tag.
   */
  private _addTag() {
    let tagText = this.tagTextInput.value ? this.tagTextInput.value.trim() : ''
    if (tagText.length > 0) {
      // Brandfolder tag searches are case-insensitive.
      tagText = tagText.toLowerCase()
      // Add the tag and notify our ancestors (unless the tag is already in
      // the list).
      if (!this.controlInput.tags.includes(tagText)) {
        this.controlInput.tags = [...this.controlInput.tags, tagText]
        this._dispatchChangeEvent()
      }
      // Reset the text input.
      this.tagTextInput.value = ''
    }
  }

  /**
   * Event handler for removing a tag.
   */
  private _removeTag(event: Event) {
    const tagText = (event.target as HTMLElement).dataset.tagText
    this.controlInput.tags = this.controlInput.tags.filter(tag => tag !== tagText)
    this._dispatchChangeEvent()
  }

  /**
   * Render the tags control.
   */
  override render() {
    return html`
      <div class="bf-browser-tags-control__inner" >
        <div class="tag-input-container" >
          <input
            class="tag-text-input"
            type="text"
            placeholder="Enter a tag name"
            @keydown=${(e: KeyboardEvent) => {
              if (e.key === 'Enter') {
                this._addTag()
              }
            }}
          />
          <button
            class="tag-add-button"
            @click=${this._addTag}
            type="button"
          >
            Add
          </button>
        </div>
        ${this.controlInput.tags.length > 0 ? html`
          <div class="tag-list">
            ${this.controlInput.tags.map((tag) => html`
              <span class="tag">
                <span class="tag-text">${tag}</span>
                <span
                  class="tag-removal"
                  data-tag-text=${tag}
                  @click=${this._removeTag}
                >
                  X
                </span>
              </span>
            `)}
          </div>
        ` : ''}
        ${this.controlInput.tags.length > 1 ? html`
          <div class="tag-filter-mode">
            <span class="tag-filter-mode__intro">Include assets matching:</span>
            <label class="tag-filter-mode__option">
              <input
                type="radio"
                name="tag-filter-mode"
                value="any"
                .checked=${this.controlInput.tagFilterMode === 'any'}
                @change=${() => {
                  this.controlInput.tagFilterMode = 'any'
                  this._dispatchChangeEvent()
                }}
              />
              <span class="tag-filter-mode__option-text">
                <em>Any</em> of these tags
              </span>
            </label>
            <label class="tag-filter-mode__option">
              <input
                type="radio"
                name="tag-filter-mode"
                value="all"
                .checked=${this.controlInput.tagFilterMode === 'all'}
                @change=${() => {
                  this.controlInput.tagFilterMode = 'all'
                  this._dispatchChangeEvent()
                }}
              />
              <span class="tag-filter-mode__option-text">
                <em>All</em> of these tags
              </span>
            </label>
          </div>
        ` : ''
        }
      </div>
    `
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'brandfolder-browser-control--tags': BfBrowserTagsControl
  }
}
