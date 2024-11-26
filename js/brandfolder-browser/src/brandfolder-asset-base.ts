import {LitElement} from 'lit'
import {property} from 'lit/decorators.js'
import {BfAttachment} from './brandfolder-attachment'
import {BfLabelTreeNode} from "./controls/bf-browser-control--labels";
import {BfKvList} from "./controls/bf-browser-controls";

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
  created_at: string
  updated_at: string
  availability_start: string | null
  availability_end: string | null
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
   * The asset.
   */
  @property({type: Object, attribute: false})
  asset: BfAsset | null = null

  /**
   * The asset name.
   */
  @property({type: String, attribute: false})
  name: string | null = null

  /**
   * Thumbnail URL.
   */
  @property({type: String, attribute: false})
  thumbnailUrl: string | null = null

  /**
   * Description.
   */
  @property({type: String, attribute: false})
  description: string | null = null

  /**
   * CDN URL.
   */
  @property({type: String, attribute: false})
  cdnUrl: string | null = null

  /**
   * Attachments.
   */
  @property({type: Array, attribute: false})
  attachments: BfAttachment[] | null = null

  /**
   * Tags.
   */
  @property({type: Array, attribute: false})
  tags: string[] | null = null

  /**
   * Labels to which the asset belongs.
   */
  @property({type: Array, attribute: false})
  labels: BfLabelTreeNode[] | null = null

  /**
   * Collections to which the asset belongs.
   */
  @property({type: Object, attribute: false})
  collections: BfKvList | null = null

  /**
   * Date the asset was created.
   */
  @property({type: String, attribute: false})
  creationDate: string | null = null

  /**
   * Date the asset was last modified.
   */
  @property({type: String, attribute: false})
  modificationDate: string | null = null

  /**
   * Date the asset was published.
   */
  @property({type: String, attribute: false})
  publicationDate: string | null = null

  /**
   * Date the asset expires.
   */
  @property({type: String, attribute: false})
  expirationDate: string | null = null

  /**
   * A string used in CDN URLs for assets and attachments in this asset's
   * Brandfolder.
   */
  @property({type: String, attribute: false})
  bfCdnUrlBase: string | null = null

  /**
   * Connected callback. Set as many properties as possible from the asset.
   */
  override connectedCallback() {
    super.connectedCallback()
    if (this.asset) {
      this.assetId = this.asset.id
      this.name = this.asset.attributes.name
      this.thumbnailUrl = this.asset.attributes.thumbnail_url
      this.description = this.asset.attributes.description
      this.attachments = this.asset.attachments
      this.creationDate = this.asset.attributes.created_at
      this.modificationDate = this.asset.attributes.updated_at
      this.publicationDate = this.asset.attributes.availability_start
      this.expirationDate = this.asset.attributes.availability_end

      const cdnUrl = this.asset.attributes.cdn_url
      this.cdnUrl = cdnUrl
      this.bfCdnUrlBase = cdnUrl.replace(/^(.*)\/as\/.*$/, '$1')
    }
  }
}
