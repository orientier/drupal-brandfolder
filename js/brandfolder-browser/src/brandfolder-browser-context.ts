import {createContext} from '@lit/context'
import {BfAttachmentList} from "./attachment/brandfolder-attachment-base"

export type BfBrowserContext = {
  selectedAttachments: BfAttachmentList
}
export const bfBrowserContext = createContext<BfBrowserContext>('brandfolder-browser')
