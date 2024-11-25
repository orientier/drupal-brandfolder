import {LitElement} from 'lit'
import {property} from 'lit/decorators.js'
import {
  BfBrowserControlSchema, BfBrowserControlSchemaKey,
  BfBrowserUserInput, BfBrowserUserInputKey
} from "./bf-browser-controls";

/**
 * Base class for a Brandfolder Browser user input/control element.
 */
export class BfBrowserControlBase extends LitElement {

  /**
   * A relevant subset of the control schema.
   */
  @property({type: String, attribute: false})
  label: string | null = null

  /**
   * A relevant subset of the control schema.
   */
  @property({type: Object, attribute: false})
  controlSchema: BfBrowserControlSchema | null = null

  /**
   * User input data for/from this particular control.
   */
  @property({type: Object, attribute: false})
  controlInput: BfBrowserUserInput | null = null

  /**
   * Property storing the name of the schema subset to be used.
   */
  @property({type: String, attribute: false})
  controlSchemaKey: BfBrowserControlSchemaKey | null = null

  /**
   * Property storing the name of the user input subset to be used.
   */
  @property({type: String, attribute: false})
  controlInputKey: BfBrowserUserInputKey | null = null

  /**
   * Dispatch an event indicating that user input has changed.
   */
  protected _dispatchChangeEvent() {
    this.dispatchEvent(
      new CustomEvent('bfControlInputChange', {
        detail: {
          controlInput: this.controlInput
        },
        bubbles: true,
        composed: true,
      })
    )
  }

  /**
   * Dispatch an event indicating that controls should be submitted.
   */
  protected _dispatchSubmitEvent() {
    this.dispatchEvent(
      new CustomEvent('bfControlsSubmit', {
        bubbles: true,
        composed: true,
      })
    )
  }

}

