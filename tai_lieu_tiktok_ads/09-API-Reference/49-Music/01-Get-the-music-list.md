---
title: "Get the music list"
doc_id: 1740053909509122
path: "API Reference / Music / Get the music list"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get the music list

Use this endpoint to get the list of available music that can be used for video creation or Carousel Ads creation. The list will contain the music that the advertiser has uploaded as well as the music in the [Audio Library](https://ads.tiktok.com/business/creativecenter/music/pc/en)  (also known as [Commercial Music Library](https://ads.tiktok.com/help/article/commercial-music-library?redirected=2)). The Audio Library does not update frequently, so we recommend that you cache the music from the Audio Library and update the music monthly.

> <span style="color:darkred">**Copyright Notice**</span><br>
Due to music copyright restrictions, video creatives made with the music we provide can only be served on TikTok. You shall not download, make available, distribute or perform the video and/or the music file incorporated in the video on any media other than TikTok, unless you have obtained separate permission form the music rights holders. You shall be solely responsible for any third party claims and disputes caused by your breach of these terms, and will defend and indemnify TikTok and its affiliates for all losses sustained.

 ## Comparing v1.2 and v1.3
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/file/music/get/|/v1.3/file/music/get/|
|Request parameter data type |`advertiser_id`: number|`advertiser_id`: string|
|New request parameters|/|`music_scene`<br>`search_type` <br>`keyword`<br>`image_urls`<br>`catalog_id` <br>`catalog_authorized_bc_id`<br> `sku_ids`<br> `product_set_id`<br>`item_group_ids`<br>`carousel_image_index`|
|Response parameter deprecated in v1.3| / | `id`: string |
|New response parameters|/|`author`<br>`liked`<br>`cover_url`|
```

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/file/music/get/

**Method** GET

**Header**
```xtable
|Field|Data Type|Description|
|--- |--- |--- |
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
```

**Parameters**
```xtable
|Field|Data Type|Description|
|-|-|-|
| advertiser_id {Required} | string | Advertiser ID. |
| music_scene| string | The scenario where the piece of music is used.  <br>Enum values:  <ul><li>`CREATIVE_ASSET`: Creative Tools. </li><li> `CAROUSEL_ADS`: Non-Shopping Ads - Carousel format.</li><li>`CATALOG_CAROUSEL`: Shopping Ads (VSA) - Carousel format.</li></ul>Default value: `CREATIVE_ASSET`.   |
| search_type {+Conditional} | string | Required when `music_scene` is set to `CAROUSEL_ADS` or `CATALOG_CAROUSEL`.   <br>Not supported when `music_scene` is set to `CREATIVE_ASSET` or not passed. <br><br>The type of music search that you want to perform.   <br>Enum values:   <ul><li>`SEARCH_BY_KEYWORD`: To fuzzy search for music via keyword. You need to pass in `keyword` as well. Note that you cannot search for user-uploaded music through this search type.</li><li>`SEARCH_BY_RECOMMEND`:<ul><li>When `music_scene` is set to `CAROUSEL_ADS`: To get recommended music for the images specified via the image URLs. You need to pass in `image_urls` as well. A maximum of 500 pieces of recommended music can be returned. Note that you cannot search for user-uploaded music through this search type.</li><li>When `music_scene` is set to `CATALOG_CAROUSEL`: To get recommended music for the specified catalog products. You need to pass in `catalog_id` and `catalog_authorized_bc_id` as well. Additionally, you need to choose one of the three settings below. Note that you cannot search for user-uploaded music through this search type. <ul><li> To get recommended music for all products in a catalog, do not pass any of `product_set_id`, `item_group_ids`, and `sku_ids`. </li><li> To get recommended music for products in a product set, pass in either `product_set_id` or `item_group_ids`. </li><li> To get recommended music for specific products, pass in `sku_ids`. </li></ul></li></ul> </li><li> `SEARCH_BY_LIKED`: To search the Liked list for music. A maximum of 1,000 pieces of music in the Liked list can be returned. </li><li>`SEARCH_BY_HISTORY`: To search the History list for music. </li><li>`SEARCH_BY_MUSIC_ID`: To search for music via music IDs. </li><li>`SEARCH_BY_SOURCE`: To search for music by music source. You need to pass in `sources` as well. Note that you can only filter user-uploaded music for Carousel Ads.</li></ul> <p><span style="color:darkred"><b>Note</b></span>:  When this field is set to `SEARCH_BY_RECOMMEND`, `SEARCH_BY_LIKED` or `SEARCH_BY_MUSIC_ID`, pagination is not supported and the parameters `page` and `page_size` will be ignored.   |
| filtering | object | Filter condition. |
#| keyword {+Conditional} | string | Required when `search_type` is set to `SEARCH_BY_KEYWORD`.   <br>Invalid when `search_type` is not set to `SEARCH_BY_KEYWORD`.  <br>The keyword for music search.   |
#| image_urls {+Conditional} | string[] | Required when `music_scene` is set to `CAROUSEL_ADS` and `search_type` is set to `SEARCH_BY_RECOMMEND`.  <br> Invalid when `search_type` is not set to `SEARCH_BY_RECOMMEND`.   <br>A list of image URLs to get recommended music for. <br>Size range: &#91;2, 35&#93;. <br>You need to specify at least 2 image URLs obtained from [/file/image/ad/upload/](https://ads.tiktok.com/marketing_api/docs?id=1739067433456642) and [/file/image/ad/search/](https://ads.tiktok.com/marketing_api/docs?id=1740052016789506). <br>The image URLs should also meet the requirements below <strong>at the same time</strong>: <ul><li> Specifications: <ul><li> File Type: JPG, JPEG, or PNG.</li><li> Image Resolution: a maximum of 1242*2340 px or 2340*1242 px.</li><li> Aspect Ratio: a maximum of 9:20 or 20:9.</li><li> File Size: below 50 MB. </li></ul></li><li> The value of the parameter `is_carousel_usable` returned from [/file/image/ad/search/](https://ads.tiktok.com/marketing_api/docs?id=1740052016789506) for the image is `true`. </li></ul>If you use the recommended music to create non-VSA Carousel Ads, you need to use the same images for which the recommended music is provided in the ad. |
#| music_ids {+Conditional} | string[] | Required when `search_type` is set to `SEARCH_BY_MUSIC_ID`.   <br>Music IDs. <br>Max size: 100.   |
#| catalog_id {+Conditional} | string | Required when `music_scene` is set to `CATALOG_CAROUSEL` and `search_type` is set to `SEARCH_BY_RECOMMEND`. <br><br>Catalog ID. |
#| catalog_authorized_bc_id{+Conditional} | string | Required when `music_scene` is set to `CATALOG_CAROUSEL` and `search_type` is set to `SEARCH_BY_RECOMMEND`. <br><br> ID of the Business Center that has the permission to access the catalog. <br>For a catalog in Business Center, specify the ID of the Business Center that the catalog belongs to. |
#| item_group_ids {+Conditional} | string[] | If `music_scene` is set to `CATALOG_CAROUSEL` , `search_type` is set to `SEARCH_BY_RECOMMEND`, and you want to get recommended music for a VSA Carousel Ad with products dynamically selected from a product set, either `product_set_id` or `item_group_ids` must be specified. <br> Do not pass this field if you want to get recommended music for a VSA Carousel Ad with products dynamically selected from all products in the catalog. <br><br> Product SPU IDs. <br><br>Max size: 20. |
#| product_set_id {+Conditional} | string | If `music_scene` is set to `CATALOG_CAROUSEL` , `search_type` is set to `SEARCH_BY_RECOMMEND`, and you want to get recommended music for a VSA Carousel Ad with products dynamically selected from a product set, either `product_set_id` or `item_group_ids` must be specified. <br>Do not pass this field if you want to get recommended music for a VSA Carousel Ad with products dynamically selected from all products in the catalog. <br><br> The ID of the product set. |
#| sku_ids {+Conditional} | string[] | Required if `music_scene` is set to `CATALOG_CAROUSEL` ,`search_type` is set to `SEARCH_BY_RECOMMEND`, and you want to get recommended music for a VSA Carousel Ad with specific products from the catalog. <br> Do not pass this field if you want to get recommended music for a VSA Carousel Ad with products dynamically selected from all products in the catalog. <br><br>IDs of the SKUs. <br><br>Max size: 20. <br>Min size: 2. |
#| carousel_image_index | integer | Valid only if `music_scene` is set to `CATALOG_CAROUSEL`, `search_type` is set to `SEARCH_BY_RECOMMEND`, and a list of additional images has been configured for the specified products. <br><br>The index that specifies the additional images to be used in the VSA Carousel Ad. <br>If this field is not passed, the main images of catalog products will be used in the VSA Carousel Ad. <br><br> Value range: &#91;0, 9&#93;.<ul><li> The number you set via this field represents the position of the product image within the additional image list (`additional_image_urls`) for each catalog product. For instance, `carousel_image_index` = <strong>1</strong> means that you will use all the images specified via the <strong>second</strong> image URL in each `additional_image_urls` value as images in the Carousel Ad. You can retrieve the additional images configured for a product via `additional_image_urls` returned by [/catalog/product/get/](https://ads.tiktok.com/marketing_api/docs?id=1740564136678402). </li><li> If you set a number greater than the total number of additional Image URLs specified for the product, this field will be ignored, and the main image (`image_url`) for the product will be used. You can retrieve the main image for a product via `image_url` returned by [/catalog/product/get/](https://ads.tiktok.com/marketing_api/docs?id=1740564136678402).</li></ul>|
#| material_ids | string[] | Not supported when `music_scene` is set to `CAROUSEL_ADS` or `CATALOG_CAROUSEL`. <br> Material IDs.   <br>Max size: 100.   |
#| styles | string[] | Not supported when `music_scene` is set to `CAROUSEL_ADS` or `CATALOG_CAROUSEL`.  <br>Music style. <br>Max size: 1.   |
#| sources{+Conditional} | string[] | When `music_scene` is set to `CREATIVE_ASSET` or not passed, this field is optional.<br>When `music_scene` is set to `CAROUSEL_ADS` or `CATALOG_CAROUSEL` and `search_type` is set to `SEARCH_BY_SOURCE`, this field is required and can only be set to `USER` to filter user-uploaded music for Carousel Ads.  <br><br>Music source. `USER` represents user uploaded music, and `SYSTEM` represents system music. <br>Max size: 1.   |
| page | number | Current page number. <br><br>Default value: 1. <br>Value Range: ≥ 1.<br>The product of `page` and `page_size` cannot exceed 100,000.<br> <br><p><span style="color:darkred"><b>Note</b></span>:   When `search_type` is set to `SEARCH_BY_RECOMMEND`, `SEARCH_BY_LIKED`, or `SEARCH_BY_MUSIC_ID`, pagination is not supported and the parameters `page` and `page_size` will be ignored.  |
| page_size| number | Page size. <br><br>Default value: 1,000. <br>Value Range: 1-1,000.   <br>The product of `page` and `page_size` cannot exceed 100,000.<br> <br><p><span style="color:darkred"><b>Note</b></span>:  <ul><li> When `search_type` is set to `SEARCH_BY_RECOMMEND`, `SEARCH_BY_LIKED`, or `SEARCH_BY_MUSIC_ID`, pagination is not supported and the parameters `page` and `page_size` will be ignored.  </li><li> When `search_type` is set to `SEARCH_BY_KEYWORD` or `SEARCH_BY_HISTORY`, the default value is 200 and the value range is [1,200].</li></ul>|
```
### Example
```xcodeblock
(code curl http)
curl --get -H "Access-Token:xxx" \
--data-urlencode "advertiser_id=ADVERTISER_ID" \
https://ads.tiktok.com/open_api/v1.3/file/music/get/
(/code)
```

## Response
```xtable
|Field|Data Type|Description|
|-|-|-|
|code |number|Return code, see [Appendix-Return code](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string|Return message, see [Appendix-Return information](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|data |object|Returned data.|
#|musics |list|Music data.|
##| music_id | string | Music ID. |
##| material_id | string | Material ID. |
##| sources | string[] | Music source. <br>`USER` means the adveritser's uploaded music, and `SYSTEM` means the system music. |
##| author | string | Returned only for music that can be used in Carousel Ads. <br>Author of the piece of music.   |
##| liked | boolean | Returned only for music that can be used in Carousel Ads.  <br>Whether the piece of music is added to the Liked list.   |
##| cover_url | string | Returned only for music that can be used in Carousel Ads.  <br>URL of the cover for the piece of music.   |
##| url | string | Music link.  Effective duration: 12h. |
##| duration | float | Video Duration (Seconds). Only returned for system music. |
##| style  | string | Music style. Only returned for system music.  |
##| signature | string | Music file MD5. |
##| name | string | Music Name. |
##| file_name | string | File Name. |
##| copyright | string | Copyright information. Enum values: <ul><li>`MUSIC_FORBID_VIDEO_ALLOW`: Music is not allowed to download, but the generated video is allowed to download.</li><li>`MUSIC_FORBID_VIDEO_FORBID`: Both music and the generated video are not allowed to download. </li></ul>|
##| create_time  {+conditional} | string | Creation time. Only returned for advertiser's music. UTC Time. Format: 2020-06-10 T07: 39: 14Z. |
##| modify_time {+conditional}  | string | Modified time. Only returned for advertiser's music. Custom Soundtracks return this field, system music does not return this field. UTC Time. Format: 2020-06-10 T07: 39: 14Z. |
#|page_info |object|Pagination information.|
##|page |number|Current page number.|
##|page_size |number|Page size.|
##|total_number |number|Total number of results.|
##|total_page |number|Total pages of results.|
|request_id |string|The log id of a request, which uniquely identifies the request.|
```

### Example
```xcodeblock
(code Success-Response python)
HTTPS/1.1 200 OK
{
    "message": "OK",
    "code": 0,
    "data": {
        "page_info": {
            "total_number": 1,
            "page": 1,
            "page_size": 1000,
            "total_page": 1
        },
        "musics": [
            {
                "style": "Teen",
                "name": "Her World",
                "copyright": "MUSIC_FORBID_VIDEO_ALLOW",
                "url": "http://sf-tk-sg.ibytedtos.com/obj/cc-music-sg/6650280667860962050",
                "file_name": "Her World",
                "music_id": "6707429901383565318",
                "material_id": "6707429901383565318",
                "sources": [
                    "SYSTEM"
                ],
                "signature": "bdb2ff10fb44bb38151c98bfee94de89",
                "duration": 127.5,
            }
        ]
    },
    "request_id": "2020120606494801011517615607983119"
}
(/code)
```


