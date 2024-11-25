var __decorate = (this && this.__decorate) || function (decorators, target, key, desc) {
    var c = arguments.length, r = c < 3 ? target : desc === null ? desc = Object.getOwnPropertyDescriptor(target, key) : desc, d;
    if (typeof Reflect === "object" && typeof Reflect.decorate === "function") r = Reflect.decorate(decorators, target, key, desc);
    else for (var i = decorators.length - 1; i >= 0; i--) if (d = decorators[i]) r = (c < 3 ? d(r) : c > 3 ? d(target, key, r) : d(target, key)) || r;
    return c > 3 && r && Object.defineProperty(target, key, r), r;
};
import { css, html, LitElement } from 'lit';
import { customElement, property, query, } from 'lit/decorators.js';
/**
 * UI for specifying Brandfolder tags for asset filtering.
 */
let BfBrowserTagsControl = class BfBrowserTagsControl extends LitElement {
    constructor() {
        super(...arguments);
        /**
         * A list of all currently selected tags.
         */
        this.selectedTags = [];
        /**
         * The selected tag filter mode.
         */
        this.tagFilterMode = 'any';
    }
    /**
     * Event handler for adding a tag.
     */
    _addTag() {
        let tagText = this.tagTextInput.value ? this.tagTextInput.value.trim() : '';
        if (tagText.length > 0) {
            // Brandfolder tag searches are case-insensitive.
            tagText = tagText.toLowerCase();
            // Add the tag and notify our ancestors (unless the tag is already in
            // the list).
            if (!this.selectedTags.includes(tagText)) {
                this.selectedTags = [...this.selectedTags, tagText];
                this._dispatchTagChangeEvent();
            }
            // Reset the text input.
            this.tagTextInput.value = '';
        }
    }
    /**
     * Event handler for removing a tag.
     */
    _removeTag(event) {
        const tagText = event.target.dataset.tagText;
        this.selectedTags = this.selectedTags.filter(tag => tag !== tagText);
        this._dispatchTagChangeEvent();
    }
    /**
     * Dispatch a "tags changed" event.
     */
    _dispatchTagChangeEvent() {
        this.dispatchEvent(new CustomEvent('bfTagsChanged', {
            detail: {
                selectedTags: this.selectedTags,
                tagFilterMode: this.tagFilterMode,
            },
            bubbles: true,
            composed: true,
        }));
    }
    render() {
        return html `
      <div class="bf-browser-tags-control__inner" >
        <div class="tag-input-container" >
          <input
            class="tag-text-input"
            type="text"
            placeholder="Enter a tag name"
            @keydown=${(e) => {
            if (e.key === 'Enter') {
                this._addTag();
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
        ${this.selectedTags.length > 0 ? html `
          <div class="tag-list">
            ${this.selectedTags.map((tag) => html `
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
        ${this.selectedTags.length > 1 ? html `
          <div class="tag-filter-mode">
            <span class="tag-filter-mode__intro">Include assets matching:</span>
            <label class="tag-filter-mode__option">
              <input
                type="radio"
                name="tag-filter-mode"
                value="any"
                .checked=${this.tagFilterMode === 'any'}
                @change=${() => {
            this.tagFilterMode = 'any';
            this._dispatchTagChangeEvent();
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
                .checked=${this.tagFilterMode === 'all'}
                @change=${() => {
            this.tagFilterMode = 'all';
            this._dispatchTagChangeEvent();
        }}
              />
              <span class="tag-filter-mode__option-text">
                <em>All</em> of these tags
              </span>
            </label>
          </div>
        ` : ''}
      </div>
    `;
    }
};
BfBrowserTagsControl.styles = css `
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
  `;
__decorate([
    property({ type: Object, attribute: false })
], BfBrowserTagsControl.prototype, "selectedTags", void 0);
__decorate([
    property({ type: String, attribute: false })
], BfBrowserTagsControl.prototype, "tagFilterMode", void 0);
__decorate([
    query('.tag-text-input')
], BfBrowserTagsControl.prototype, "tagTextInput", void 0);
BfBrowserTagsControl = __decorate([
    customElement('brandfolder-browser-tags-control')
], BfBrowserTagsControl);
export { BfBrowserTagsControl };
//# sourceMappingURL=bf-browser-tags-control.js.map