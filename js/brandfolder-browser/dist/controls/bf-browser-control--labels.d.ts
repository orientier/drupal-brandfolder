import { TemplateResult } from 'lit';
import { BfBrowserControlBase } from "./bf-browser-control-base";
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
export declare class BfBrowserLabelsControl extends BfBrowserControlBase {
    static styles: import("lit").CSSResult;
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
    /**
     * Render the labels control.
     */
    render(): TemplateResult<1>;
}
declare global {
    interface HTMLElementTagNameMap {
        'brandfolder-browser-control--labels': BfBrowserLabelsControl;
    }
}
//# sourceMappingURL=bf-browser-control--labels.d.ts.map