---
title: "Check the availability of a TikTok Shop for Product GMV Max Campaigns"
doc_id: 1822001084174338
path: "API Reference / GMV Max / Check the availability of a TikTok Shop for Product GMV Max Campaigns"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Check the availability of a TikTok Shop for Product GMV Max Campaigns

Use this endpoint to check whether a TikTok Shop has been occupied by enabled[ Video Shopping Ads](https://business-api.tiktok.com/portal/docs?id=1750361719059457) or [Product Shopping Ads](https://business-api.tiktok.com/portal/docs?id=1785886237030401), and whether the TikTok Shop can be used to create Product GMV Max Campaigns by promoting all products in the TikTok Shop.


## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/gmv_max/store/shop_ad_usage_check/

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
```

### Example
```xcodeblock
(code curl http)
curl --location  --request GET 'https://business-api.tiktok.com/open_api/v1.3/gmv_max/store/shop_ad_usage_check/?advertiser_id={{advertiser_id}}&store_id={{store_id}}' \
--header 'Access-Token: {{Access-Token}}'
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
#| promote_all_products_allowed | boolean | Whether the TikTok Shop can be used to create Product GMV Max Campaigns by promoting all products in the TikTok Shop. <br> <br>Supported values: `true`, `false`. |
#| is_running_custom_shop_ads | boolean| Whether the TikTok Shop is being used in enabled Video Shopping Ads or Product Shopping Ads. <br> <br>Supported values: `true`, `false`. |
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
        "is_running_custom_shop_ads": true,
        "promote_all_products_allowed": false
    }
}
(/code)
```
