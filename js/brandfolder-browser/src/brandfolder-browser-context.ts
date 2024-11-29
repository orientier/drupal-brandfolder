import {createContext} from '@lit/context'
import {BfAttachmentList} from "./attachment/brandfolder-attachment-base"

export type BfBrowserContext = {
  selectedAttachments: BfAttachmentList
  selectionLimit?: number
}
export const bfBrowserContext = createContext<BfBrowserContext>('brandfolder-browser')
