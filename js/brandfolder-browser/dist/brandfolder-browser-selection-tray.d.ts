import { LitElement } from 'lit';
import { BfBrowserContext } from "./brandfolder-browser-context";
/**
 * An element to display a list of currently selected attachments, allow
 * deselection, etc.
 */
export declare class BrandfolderBrowserSelectionTray extends LitElement {
    static styles: import("lit").CSSResult;
    /**
     * Consume the browser context so we can cleanly access browser-wide data.
     */
    browserContext: BfBrowserContext;
    /**
     * Render the component.
     */
    render(): import("lit-html").TemplateResult<1>;
}
declare global {
    interface HTMLElementTagNameMap {
        'brandfolder-browser-selection-tray': BrandfolderBrowserSelectionTray;
    }
}
//# sourceMappingURL=brandfolder-browser-selection-tray.d.ts.map