import {css, html, LitElement} from 'lit'
import {customElement, property} from 'lit/decorators.js'

/**
 * Generic/wrapper element for a Brandfolder Browser user input/control item.
 */
@customElement('brandfolder-browser-control-item')
export class BfBrowserControlItem extends LitElement {
  static override styles = css`
    fieldset {
      border: 1px solid var(--color-gray-400);
      padding: 0.75rem;
      flex: 1;
      display: flex;
    }
    fieldset legend {
      white-space: nowrap;
    }
    .bf-control-item__content {
      display: flex;
      flex-direction: column;
      gap: 0.5rem;
    }
  `

  /**
   * Control name/label/title.
   */
  @property({type: String, attribute: true})
  label: string | null = null

  override render() {
    return html`
      <fieldset
        class="bf-control-item__inner"
      >
        ${this?.label ? html`<legend>${this.label}</legend>` : 'No Label Provided'}
        <div class="bf-control-item__content">
          <slot></slot>
        </div>
      </fieldset>
    `
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'brandfolder-browser-control-item': BfBrowserControlItem
  }
}
