import { BrandfolderAssetBase } from './asset/brandfolder-asset-base';
/**
 * An element for displaying a media item, e.g. an image.
 */
export declare class BrandfolderMediaContainer extends BrandfolderAssetBase {
    static styles: import("lit").CSSResult;
    /**
     * Whether this item functions as a link.
     */
    isLink: boolean;
    /**
     * Whether this item is actively being engaged with.
     */
    isActive: boolean;
    /**
     * Optional display format.
     */
    displayFormat: string;
    render(): import("lit-html").TemplateResult<1>;
}
declare global {
    interface HTMLElementTagNameMap {
        'brandfolder-media-container': BrandfolderMediaContainer;
    }
}
//# sourceMappingURL=brandfolder-media-container.d.ts.map