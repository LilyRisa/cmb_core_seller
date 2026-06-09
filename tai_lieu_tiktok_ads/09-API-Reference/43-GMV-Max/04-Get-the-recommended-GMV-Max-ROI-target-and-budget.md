---
title: "Get the recommended GMV Max ROI target and budget"
doc_id: 1822001024720897
path: "API Reference / GMV Max / Get the recommended GMV Max ROI target and budget"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get the recommended GMV Max ROI target and budget

Use this endpoint to obtain the recommended ROI target and budget for a Product GMV Max or Live GMV Campaign that uses a specific TikTok Shop.


## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/gmv_max/bid/recommend/

**Method** GET

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. <br>For details, see [Authentication](https://business-api.tiktok.com/portal/docs?id=1738373164380162).|
```

**Parameters**

``` xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|--- |--- |--- |
| advertiser_id {Required} | string | Advertiser ID. |
| store_id {Required} | string | The ID of the TikTok Shop.<br><br> To obtain a TikTok Shop that is available for GMV Max Campaigns, use [/gmv_max/store/list/](https://business-api.tiktok.com/portal/docs?id=1822001044479041) and confirm that the returned `is_gmv_max_available` is `true`. |
| shopping_ads_type {Required} | string | The type of the GMV Max Campaign.<br><br>Enum values:<ul><li>`PRODUCT`: Product GMV Max Campaign.</li><li>`LIVE`: Live GMV Max Campaign. </li></ul> |
| optimization_goal {Required} | string | Optimization goal. <br><br>Enum value:<ul><li>`VALUE`: Gross revenue.</li></ul>|
| item_group_ids  | string[] | Valid only when `shopping_ads_type` is `PRODUCT`. <br><br>The list of SPU (standard product unit) IDs for specific products to be promoted in a Product GMV Max Campaign.<br><br> <ul><li>If you want to promote all products from the TikTok Shop in the Product GMV Max Campaign, you don't need to pass this field.</li><li>If you want to promote specific products from the TikTok Shop in the Product GMV Max Campaign, specify these products via this field.</li></ul><br> Max size: 50.<br><br>To obtain the list of SPU IDs for products within a TikTok Shop, use [/store/product/get/](https://business-api.tiktok.com/portal/docs?id=1793482248880130). Set `ad_creation_eligible` to `GMV_MAX` and select `item_group_id` values where `status` is `AVAILABLE` and `gmv_max_ads_status` is `UNOCCUPIED`. |
|identity_id {+Conditional}|string|Required and valid only when `shopping_ads_type` is `LIVE` <br><br>The LIVE source identity that you want to use in a LIVE GMV Max Campaign.  <br><br>To obtain a list of identities for LIVE GMV Max Campaigns, use [/gmv_max/identity/get/](https://business-api.tiktok.com/portal/docs?id=1822001101474882) and select identities with`live_gmv_max_available` as `true`.|
```

### Example
#### For Product GMV Max Campaigns
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/gmv_max/bid/recommend/?advertiser_id={{advertiser_id}}&store_id={{store_id}}&shopping_ads_type=PRODUCT&optimization_goal=VALUE' \
--header 'Access-Token: {{Access_Token}}'
(/code)
```

#### For LIVE GMV Max Campaigns
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/gmv_max/bid/recommend/?advertiser_id={{advertiser_id}}&store_id={{store_id}}&shopping_ads_type=LIVE&optimization_goal=VALUE&identity_id={{identity_id}}' \
--header 'Access-Token: {{Access_Token}}'
(/code)
```


## Response

``` xtable
|Field{30%}|Type{15%}|Description{55%}|
|---|---|---|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix-Return Code](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|message|string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|request_id |string|The log ID of a request, which uniquely identifies the request.  |
|data |object|Returned data.|
#| roas_bid | number | Recommended ROI target for the optimization goal Groess revenue.<br> The ROI is based on the average ROI for your product category. |
#| budget | number | Recommended daily budget for the optimization goal Groess revenue, in the currency of the ad account. <br>The budget is calculated with your average ad spend for your selected products or average ad spend in your product category from the previous 14 days. Higher budgets are recommended to ensure delivery throughout the day because lower budgets may spend more quickly and limit your campaign performance. <br><br>To retrieve the currency of an ad account, use [/advertiser/info/](https://business-api.tiktok.com/portal/docs?id=1739593083610113) and check the returned `currency`. |
```

### Example
```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "budget": 300,
        "roas_bid": 2
    }
}
(/code)
```
