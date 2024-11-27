import {createContext} from '@lit/context';
import {BfAttachmentList} from "./brandfolder-attachment";

export type BfBrowserContext = {
  selectedAttachments: BfAttachmentList
}
export const bfBrowserContext = createContext<BfBrowserContext>('brandfolder-browser');
