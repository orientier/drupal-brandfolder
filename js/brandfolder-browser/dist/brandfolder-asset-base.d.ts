import { LitElement } from 'lit';
import { BfAttachment } from './brandfolder-attachment';
import { BfLabelTreeNode } from "./controls/bf-browser-control--labels";
import { BfKvList } from "./controls/bf-browser-controls";
export type BfAsset = {
    id: string;
    attributes: BfAssetAttributes;
    attachments: BfAttachment[];
};
type BfAssetAttributes = {
    name: string;
    thumbnail_url: string;
    description: string;
    cdn_url: string;
    created_at: string;
    updated_at: string;
    availability_start: string | null;
    availability_end: string | null;
};
/**
 * Base class for elements corresponding to Brandfolder assets.
 */
export declare class BrandfolderAssetBase extends LitElement {
    /**
     * Brandfolder's unique ID for the asset.
     */
    assetId: string | null;
    /**
     * The asset.
     */
    asset: BfAsset | null;
    /**
     * The asset name.
     */
    name: string | null;
    /**
     * Thumbnail URL.
     */
    thumbnailUrl: string | null;
    /**
     * Description.
     */
    description: string | null;
    /**
     * CDN URL.
     */
    cdnUrl: string | null;
    /**
     * Attachments.
     */
    attachments: BfAttachment[] | null;
    /**
     * Tags.
     */
    tags: string[] | null;
    /**
     * Labels to which the asset belongs.
     */
    labels: BfLabelTreeNode[] | null;
    /**
     * Collections to which the asset belongs.
     */
    collections: BfKvList | null;
    /**
     * Date the asset was created.
     */
    creationDate: string | null;
    /**
     * Date the asset was last modified.
     */
    modificationDate: string | null;
    /**
     * Date the asset was published.
     */
    publicationDate: string | null;
    /**
     * Date the asset expires.
     */
    expirationDate: string | null;
    /**
     * A string used in CDN URLs for assets and attachments in this asset's
     * Brandfolder.
     */
    bfCdnUrlBase: string | null;
    /**
     * Connected callback. Set as many properties as possible from the asset.
     */
    connectedCallback(): void;
}
export {};
//# sourceMappingURL=brandfolder-asset-base.d.ts.map