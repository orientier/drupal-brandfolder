import {css, html, TemplateResult} from 'lit'
import {customElement, query} from 'lit/decorators.js'
import {BfBrowserControlBase} from "./bf-browser-control-base";

export type BfLabelTreeNode = {
  label: BfLabel | null
  children: BfLabelTreeNode[]
}

export type BfLabel = {
  id: string
  name: string
  depth: number
  attributes: BfLabelAttributes
}

export type BfLabelAttributes = {
  name: string
  depth: number
  path: string[]
  position: number
}

/**
 * UI for a selecting Brandfolder labels.
 */
@customElement('brandfolder-browser-control--labels')
export class BfBrowserLabelsControl extends BfBrowserControlBase {
  static override styles = css`
    .labels-select {
      max-height: 12rem;
    }
  `

  // /**
  //  * All eligible labels.
  //  */
  // @property({type: Object, attribute: false})
  // allLabels: BfLabelTreeNode[] | null = null
  //
  // /**
  //  * A list of all currently selected labels. An object keyed by label ID with
  //  * label names as values.
  //  */
  // @property({type: Object, attribute: false})
  // selectedLabels: Record<string, string> | null = null

  /**
   * Create a reference to the select element.
   */
  @query('.brandfolder-browser-controls__labels-select')
  labelsSelect: HTMLSelectElement

  /**
   * Change event handler for selecting labels.
   */
  private _changeHandler() {
    const selectedOptions = Array.from(this.labelsSelect.selectedOptions)
    const selectedLabelsById = selectedOptions.reduce(
      (acc, option) => {
        acc[option.value] = option.text
        return acc
      },
      {} as Record<string, string>
    )

    this.controlInput.labels = selectedLabelsById
    this._dispatchChangeEvent()
  }

  /**
   * Render filter content for a label node and its children.
   */
  private renderLabelNode(labelNode: BfLabelTreeNode): TemplateResult {
    const labelObject = labelNode?.label
    const labelId = labelObject?.id ?? ''
    const depth = labelObject?.attributes?.depth
    const depthIndicator = '-'.repeat(depth - 1).replace(/^-/, ' -')

    return html`
      <option
        value=${labelId}
        .selected=${!!this?.controlInput?.labels?.[labelId]}
      >
        ${depthIndicator} ${labelObject?.attributes?.name}
      </option>
      ${labelNode?.children ?
        Object.values(labelNode.children).map((child) => this.renderLabelNode(child))
        : ''}
    `
  }

  /**
   * Render the labels control.
   */
  override render() {
    const labelsArray = Object.values(this?.controlSchema?.labels ?? [])

    return html`
      <select
        name="brandfolder-browser-controls-labels"
        class="brandfolder-browser-controls__labels-select"
        multiple
        size="${Math.max(labelsArray.length, 5)}"
        @change=${this._changeHandler}
      >
        ${labelsArray.map((labelNode) => this.renderLabelNode(labelNode))}
      </select>
    `
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'brandfolder-browser-control--labels': BfBrowserLabelsControl
  }
}
