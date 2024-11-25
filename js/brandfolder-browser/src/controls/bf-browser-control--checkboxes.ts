import {css, html} from 'lit'
import {
  customElement,
  // property,
  // query,
  queryAll
} from 'lit/decorators.js'
import {live} from 'lit/directives/live.js'
import {BfBrowserControlBase} from "./bf-browser-control-base";
import {
  BfControlInputStringArrayEsque, BfControlInputStringArrayEsqueKey,
  BfControlSchemaKvEsque, BfControlSchemaKvEsqueKey
} from "./bf-browser-controls";
// import {
//   BfBrowserControlSchemaKey,
//   BfBrowserUserInputKey
// } from "./bf-browser-controls";

/**
 * UI for specifying Brandfolder tags for asset filtering.
 */
@customElement('brandfolder-browser-control--checkboxes')
export class BfBrowserCheckboxesControl extends BfBrowserControlBase {
  static override styles = css`
    :host {
      flex: 1;
      display: flex;
    }

    .bf-browser-checkboxes-control__inner {
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
  `

  /**
   * Create a reference to the input elements.
   */
  @queryAll('input[type="checkbox"]')
  inputElements: HTMLInputElement[]

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
      this.controlInputKey = Object.keys(this.controlInput)[0] as BfControlInputStringArrayEsqueKey
    }
  }

  /**
   * Respond to input changes.
   */
  private _changeHandler() {
    const inputsArray = Array.from(this.inputElements)
    const selectedValues = inputsArray
      .filter((input) => input.checked)
      .map((input) => input.value)

    this.controlInput = {
      ...this.controlInput,
      [this.controlInputKey]: selectedValues as BfControlInputStringArrayEsque
    }

    this._dispatchChangeEvent()
  }

  /**
   * Render the checkboxes control.
   */
  override render() {
    const schema = this?.controlSchema as BfControlSchemaKvEsque | null
    const schemaKey = this?.controlSchemaKey as BfControlSchemaKvEsqueKey | null
    const schemaData = schema && schemaKey ? schema[schemaKey] : null
    const userInput = this?.controlInput as BfControlInputStringArrayEsque | null
    const controlInputKey = this?.controlInputKey as BfControlInputStringArrayEsqueKey | null
    const userInputData = userInput && controlInputKey ? userInput[controlInputKey] : null

    return schemaData && controlInputKey ? html`
      <div class="bf-browser-checkboxes-control__inner">
        ${Object.entries(schemaData).map(
          ([itemId, itemLabel]) => {
            const isSelected = userInputData && userInputData.includes(itemId)
            const inputId = `input--${itemId}`

            return html`
              <span class="input-item">
                <input
                  type="checkbox"
                  id="${inputId}"
                  aria-label="${itemLabel}"
                  name="${controlInputKey}"
                  value=${itemId}
                  .checked=${live(isSelected)}
                  @change=${this._changeHandler}
                />
                <label for=${inputId}>${itemLabel}</label>
              </span>
            `
          }
        )}
      </div>
    ` : ''
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'brandfolder-browser-control--checkboxes': BfBrowserCheckboxesControl
  }
}
