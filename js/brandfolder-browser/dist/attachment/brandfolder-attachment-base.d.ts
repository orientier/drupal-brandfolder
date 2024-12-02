import { LitElement, PropertyValues } from 'lit';
import { BfAsset } from "../asset/brandfolder-asset-base";
import { BfBrowserContext } from "../brandfolder-browser-context";
export type BfAttachment = {
    id: string;
    mimetype?: string;
    extension?: string;
    filename?: string;
    size?: number;
    width?: number;
    height?: number;
    thumbnail_url?: string;
    cdn_url: string;
    url?: string;
    asset?: BfAsset;
};
export type BfAttachmentList = {
    [key: string]: BfAttachment;
};
/**
 * A base class for custom elements pertaining to Brandfolder attachments.
 */
export declare class BrandfolderAttachmentBase extends LitElement {
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
     * The standard URL of the attachment. Not as performant or manipulable as
     * the CDN URL.
     */
    url: string | null;
    /**
     * The base URL for all CDN URLs for this attachment's Brandfolder.
     */
    bfCdnUrlBase: string | null;
    /**
     * CDN URL.
     */
    cdnUrl: string | null;
    /**
     * Default image URL for display. Derived from the CDN URL. It's more
     * performant to use the same exact image URL for all instances of the same
     * attachment, so we can benefit from browser caching (even though we could
     * use a smaller image in the attachment selection tray, for example).
     */
    imageSrcUrl: string | null;
    /**
     * State tracking whether the user is hovering over the attachment.
     */
    protected _isHovered: boolean;
    /**
     * Property tracking whether the attachment is selected.
     */
    protected _isSelected: boolean;
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
    protected _attachmentSelectionHandler(): void;
}
//# sourceMappingURL=brandfolder-attachment-base.d.ts.map