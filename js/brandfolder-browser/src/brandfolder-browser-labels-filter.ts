import {css, html, LitElement, TemplateResult} from 'lit'
import {customElement, property, query,} from 'lit/decorators.js'

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
@customElement('brandfolder-browser-labels-filter')
export class BrandfolderBrowserLabelsFilter extends LitElement {
  static override styles = css`
    .brandfolder-browser-controls__labels {
      max-height: 12rem;
    }
  `

  /**
   * All eligible labels.
   */
  @property({type: Object, attribute: false})
  allLabels: BfLabelTreeNode[] | null = null

  /**
   * A list of all currently selected labels. An object keyed by label ID with
   * label names as values.
   */
  @property({type: Object, attribute: false})
  selectedLabels: Record<string, string> | null = null

  /**
   * Create a reference to the select element.
   */
  @query('.brandfolder-browser-controls__labels')
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
    this.selectedLabels = selectedLabelsById
    this.dispatchEvent(
      new CustomEvent('bfLabelsChanged', {
        detail: {
          selectedLabelsById
        },
        bubbles: true,
        composed: true,
      })
    )
  }

  /**
   * Render filter content for a label node and its children.
   */
  private renderLabelNode(labelNode: BfLabelTreeNode): TemplateResult {
    const labelObject = labelNode?.label
    const depth = labelObject?.attributes?.depth
    const depthIndicator = '-'.repeat(depth - 1).replace(/^-/, ' -')

    return html`
      <option
        value=${labelObject?.id}
        .selected=${!!this.selectedLabels?.[labelObject?.id]}
      >
        ${depthIndicator} ${labelObject?.attributes?.name}
      </option>
      ${labelNode?.children ?
        Object.values(labelNode.children).map((child) => this.renderLabelNode(child))
        : ''}
    `
  }

  override render() {
    const labelsArray = Object.values(this.allLabels ?? [])

    return html`
      <select
        name="brandfolder-browser-controls-labels"
        class="brandfolder-browser-controls__labels"
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
    'brandfolder-browser-labels-filter': BrandfolderBrowserLabelsFilter
  }
}
