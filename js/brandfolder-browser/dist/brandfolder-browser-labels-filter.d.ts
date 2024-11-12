import { LitElement, TemplateResult } from 'lit';
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
 * UI for a selecting Brandfolder labels.
 */
export declare class BrandfolderBrowserLabelsFilter extends LitElement {
    static styles: import("lit").CSSResult;
    /**
     * All eligible labels.
     */
    allLabels: BfLabelTreeNode[] | null;
    /**
     * A list of all currently selected labels. An object keyed by label ID with
     * label names as values.
     */
    selectedLabels: Record<string, string> | null;
    /**
     * Create a reference to the select element.
     */
    labelsSelect: HTMLSelectElement;
    /**
     * Change event handler for selecting labels.
     */
    private _changeHandler;
    /**
     * Render filter content for a label node and its children.
     */
    private renderLabelNode;
    render(): TemplateResult<1>;
}
declare global {
    interface HTMLElementTagNameMap {
        'brandfolder-browser-labels-filter': BrandfolderBrowserLabelsFilter;
    }
}
//# sourceMappingURL=brandfolder-browser-labels-filter.d.ts.map