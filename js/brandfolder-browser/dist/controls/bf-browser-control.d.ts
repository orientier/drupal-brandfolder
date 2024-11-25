import { LitElement } from 'lit';
export type BfLabelTreeNode = {
    label: BfLabel | null;
    children: BfLabelTreeNode[];
};
export type BfLabel = {
    id: string;
    name: string;
    depth: number;
    attributes: BfLabelAttributes;
};
export type BfLabelAttributes = {
    name: string;
    depth: number;
    path: string[];
    position: number;
};
/**
 * Generic element for a Brandfolder Browser user input/control item.
 */
export declare class BfBrowserControlItem extends LitElement {
    static styles: import("lit").CSSResult;
    /**
     * Control name/label.
     */
    label: string | null;
    render(): import("lit-html").TemplateResult<1>;
}
declare global {
    interface HTMLElementTagNameMap {
        'brandfolder-browser-control-item': BfBrowserControlItem;
    }
}
//# sourceMappingURL=bf-browser-control.d.ts.map