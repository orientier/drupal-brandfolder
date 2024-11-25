import {css, html} from 'lit'
import {
  customElement, query,
} from 'lit/decorators.js'
import {BfBrowserControlBase} from "./bf-browser-control-base";
import {
  BfControlInputStringEsque,
  BfControlInputStringEsqueKey,
  BfControlSchemaKvEsque,
  BfControlSchemaKvEsqueKey,
  BfControlStringEsqueType,
} from "./bf-browser-controls";

/**
 * UI for specifying Brandfolder tags for asset filtering.
 */
@customElement('brandfolder-browser-control--select')
export class BfBrowserSelectControl extends BfBrowserControlBase {
  static override styles = css`
    :host {
      flex: 1;
      display: flex;
    }

    .bf-browser-select-control__inner {
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    label {
      font-size: 0.875rem;
    }
  `

  /**
   * Create a reference to the input elements.
   */
  @query('select')
  selectElement: HTMLSelectElement

  /**
   * Callback executed when the element is added to the document.
   */
  override connectedCallback() {
    super.connectedCallback();
    // Initialize controlSchemaKey and controlInputKey based on controlSchema
    // and controlInput, if not explicitly set.
    if (!this.controlSchemaKey) {
      this.controlSchemaKey = Object.keys(this.controlSchema)[0] as BfControlSchemaKvEsqueKey
    }
    if (!this.controlInputKey) {
      this.controlInputKey = Object.keys(this.controlInput)[0] as BfControlInputStringEsqueKey
    }
  }

  /**
   * Respond to select changes.
   */
  private _changeHandler() {
    this.controlInput = {
      ...this.controlInput,
      [this.controlInputKey]: this.selectElement.value as BfControlStringEsqueType
    }

    this._dispatchChangeEvent()
  }

  /**
   * Render the select control.
   */
  override render() {
    const schema = this?.controlSchema as BfControlSchemaKvEsque | null
    const schemaKey = this?.controlSchemaKey as BfControlSchemaKvEsqueKey | null
    const schemaData = schema && schemaKey ? schema[schemaKey] : null
    const userInput = this?.controlInput as BfControlInputStringEsque | null
    const controlInputKey = this?.controlInputKey as BfControlInputStringEsqueKey | null
    const userInputValue = userInput && controlInputKey ? userInput[controlInputKey] : null
    const elementId = `brandfolder-browser-controls-${controlInputKey}`

    return schemaData && controlInputKey ? html`
      <div class="bf-browser-select-control__inner">
        ${this?.label ? html`
        <label for="${elementId}">${this.label}</label>
        ` : ''}
        <select
          id="${elementId}"
          name="${`brandfolder-browser-controls--${controlInputKey}`}"
          class="${`brandfolder-browser-controls__${controlInputKey}`}"
          @change=${this._changeHandler}
        >
          ${Object.entries(schemaData).map(
            ([itemKey, itemLabel]) => {
              const isSelected = userInputValue === itemKey

              return html`
                <option
                  value=${itemKey}
                  .selected=${isSelected}
                >
                  ${itemLabel}
                </option>
              `
            }
          )}
      </div>
    ` : ''
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'brandfolder-browser-control--select': BfBrowserSelectControl
  }
}
