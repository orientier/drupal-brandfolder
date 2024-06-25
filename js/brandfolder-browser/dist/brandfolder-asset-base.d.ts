import { LitElement } from 'lit';
import { BfAttachment } from './brandfolder-attachment';
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
     * The Brandfolder asset.
     */
    asset: BfAsset | null;
}
export {};
//# sourceMappingURL=brandfolder-asset-base.d.ts.map