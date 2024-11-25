import { LitElement } from 'lit';
import { BfBrowserControlSchema, BfBrowserControlSchemaKey, BfBrowserUserInput, BfBrowserUserInputKey } from "./bf-browser-controls";
/**
 * Base class for a Brandfolder Browser user input/control element.
 */
export declare class BfBrowserControlBase extends LitElement {
    /**
     * A relevant subset of the control schema.
     */
    label: string | null;
    /**
     * A relevant subset of the control schema.
     */
    controlSchema: BfBrowserControlSchema | null;
    /**
     * User input data for/from this particular control.
     */
    controlInput: BfBrowserUserInput | null;
    /**
     * Property storing the name of the schema subset to be used.
     */
    controlSchemaKey: BfBrowserControlSchemaKey | null;
    /**
     * Property storing the name of the user input subset to be used.
     */
    controlInputKey: BfBrowserUserInputKey | null;
    /**
     * Dispatch an event indicating that user input has changed.
     */
    protected _dispatchChangeEvent(): void;
    /**
     * Dispatch an event indicating that controls should be submitted.
     */
    protected _dispatchSubmitEvent(): void;
}
//# sourceMappingURL=bf-browser-control-base.d.ts.map