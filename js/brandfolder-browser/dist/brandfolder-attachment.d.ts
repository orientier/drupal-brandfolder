import { LitElement } from 'lit';
export type BfAttachment = {
    id: string;
    mimetype: string;
    extension: string;
    filename: string;
    size: number;
    width: number;
    height: number;
    thumbnail_url: string;
    cdn_url: string;
    url: string;
};
/**
 * An element corresponding to an attachment in Brandfolder.
 */
export declare class BrandfolderAttachment extends LitElement {
    /**
     * Brandfolder's unique ID for the attachment.
     */
    attachmentId: string | null;
    /**
     * An object matching the Brandfolder attachment schema.
     */
    attachment: BfAttachment | null;
    mimetype: string | null;
    extension: string;
    filename: string;
    size: number;
    width: number;
    height: number;
    thumbnail_url: string;
    cdn_url: string;
    url: string;
    /**
     * Callback executed when the element is added to the document.
     */
    connectedCallback(): void;
    private _attachmentSelectionHandler;
    render(): import("lit-html").TemplateResult<1>;
}
declare global {
    interface HTMLElementTagNameMap {
        'brandfolder-attachment': BrandfolderAttachment;
    }
}
//# sourceMappingURL=brandfolder-attachment.d.ts.map