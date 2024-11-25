import { LitElement } from 'lit';
/**
 * Generic/wrapper element for a Brandfolder Browser user input/control item.
 */
export declare class BfBrowserControlItem extends LitElement {
    static styles: import("lit").CSSResult;
    /**
     * Control name/label/title.
     */
    label: string | null;
    render(): import("lit-html").TemplateResult<1>;
}
declare global {
    interface HTMLElementTagNameMap {
        'brandfolder-browser-control-item': BfBrowserControlItem;
    }
}
//# sourceMappingURL=bf-browser-control-item.d.ts.map