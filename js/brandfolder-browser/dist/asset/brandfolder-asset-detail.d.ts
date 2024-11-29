import { BrandfolderAssetBase } from './brandfolder-asset-base';
/**
 * An element displaying the details of an individual asset and allowing users
 * to select one or more of the asset's attachments.
 */
export declare class BrandfolderAssetDetail extends BrandfolderAssetBase {
    static styles: import("lit").CSSResult;
    /**
     * Dispatches a custom event to signal that the asset detail view should be
     * closed.
     */
    private _dispatchCloseEvent;
    render(): import("lit-html").TemplateResult<1>;
}
declare global {
    interface HTMLElementTagNameMap {
        'brandfolder-asset-detail': BrandfolderAssetDetail;
    }
}
//# sourceMappingURL=brandfolder-asset-detail.d.ts.map