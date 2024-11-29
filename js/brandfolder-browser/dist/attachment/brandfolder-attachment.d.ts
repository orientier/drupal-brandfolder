import { LitElement, PropertyValues } from 'lit';
import { BfAsset } from "../brandfolder-asset-base";
import { BfBrowserContext } from "../brandfolder-browser-context";
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
    asset?: BfAsset;
};
export type BfAttachmentList = {
    [key: string]: BfAttachment;
};
/**
 * An element corresponding to an attachment in Brandfolder.
 */
export declare class BrandfolderAttachment extends LitElement {
    static styles: import("lit").CSSResult;
    /**
     * Brandfolder's unique ID for the attachment.
     */
    attachmentId: string | null;
    /**
     * An object matching the Brandfolder attachment schema.
     */
    attachment: BfAttachment | null;
    /**
     * The MIME type of the file.
     */
    mimetype: string | null;
    /**
     * The file extension.
     */
    extension: string | null;
    /**
     * The filename of the file.
     */
    filename: string | null;
    /**
     * The size of the file in bytes.
     */
    size: number | null;
    /**
     * The width of the file in pixels.
     */
    width: number | null;
    /**
     * The height of the file in pixels.
     */
    height: number | null;
    /**
     * The URL of the thumbnail image.
     */
    thumbnailUrl: string | null;
    /**
     * CDN URL.
     */
    cdnUrl: string | null;
    /**
     * The standard URL of the attachment. Not as performant or manipulable as
     * the CDN URL.
     */
    url: string | null;
    /**
     * The base URL for all CDN URLs for this attachment's Brandfolder.
     */
    bfCdnUrlBase: string | null;
    /**
     * The format/variant in which the attachment should be displayed.
     */
    displayFormat: string;
    /**
     * State tracking whether the user is hovering over the attachment.
     */
    private _isHovered;
    /**
     * Property tracking whether the attachment is selected.
     */
    private _isSelected;
    /**
     * Consume the browser context so we can cleanly access browser-wide data
     * (and subscribe to be made aware of any changes).
     */
    browserContext: BfBrowserContext;
    /**
     * Lifecycle method called before update() to compute values needed during
     * the update.
     */
    willUpdate(changedProperties: PropertyValues<this>): void;
    /**
     * Handle selection/deselection of this attachment.
     */
    private _attachmentSelectionHandler;
    /**
     * Render the component.
     */
    render(): import("lit-html").TemplateResult<1>;
}
declare global {
    interface HTMLElementTagNameMap {
        'brandfolder-attachment': BrandfolderAttachment;
    }
}
//# sourceMappingURL=brandfolder-attachment.d.ts.map