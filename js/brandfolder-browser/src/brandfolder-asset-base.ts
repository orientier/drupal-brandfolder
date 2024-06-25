import {LitElement} from 'lit'
import {property} from 'lit/decorators.js'
import {BfAttachment} from './brandfolder-attachment'

export type BfAsset = {
  id: string
  attributes: BfAssetAttributes
  attachments: BfAttachment[]
}

type BfAssetAttributes = {
  name: string
  thumbnail_url: string
  description: string
  cdn_url: string
}

/**
 * Base class for elements corresponding to Brandfolder assets.
 */
export class BrandfolderAssetBase extends LitElement {
  /**
   * Brandfolder's unique ID for the asset.
   */
  @property({type: String, attribute: 'bf-asset-id'})
  assetId: string | null = null

  /**
   * The Brandfolder asset.
   */
  @property({type: Object, attribute: false})
  asset: BfAsset | null = null
}
