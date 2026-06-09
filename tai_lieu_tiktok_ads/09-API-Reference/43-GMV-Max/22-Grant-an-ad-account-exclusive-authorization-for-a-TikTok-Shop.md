---
title: "Grant an ad account exclusive authorization for a TikTok Shop"
doc_id: 1822001200356354
path: "API Reference / GMV Max / Grant an ad account exclusive authorization for a TikTok Shop"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Grant an ad account exclusive authorization for a TikTok Shop

Use this endpoint to authorize an ad account to create GMV Max Campaigns for a specific TikTok Shop.

For each TikTok Shop, only one ad account can be authorized to create GMV Max Campaigns using the TikTok Shop for optimized revenue and sales campaigns. 

> <span style="color:DarkRed">**Important**</span><br>
> By using the endpoint, you agree to and acknowledge the potential impacts listed in the [GMV Max Guidelines](https://ads.tiktok.com/help/article/gmv-max-guidelines). 


## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/gmv_max/exclusive_authorization/create/

**Method** POST

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Authorized access token.<br> For details, see [Authentication](https://business-api.tiktok.com/portal/docs?id=1738373164380162).|
|Content-Type {Required}|string|Request message type.<br>Allowed format: `application/json`.  |
```

**Parameters**

``` xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|--- |--- |--- |
| store_id {Required} | string | The ID of the TikTok Shop.<br><br> To obtain a TikTok Shop that is available for GMV Max Campaigns, use [/gmv_max/store/list/](https://business-api.tiktok.com/portal/docs?id=1822001044479041) and confirm that the returned `is_gmv_max_available` is `true`. |
| store_authorized_bc_id {Required} | string | ID of the Business Center that owns the TikTok Shop (`store_id`).<br><br>To confirm that a Business Center owns a TikTok Shop, use [/gmv_max/store/list/](https://business-api.tiktok.com/portal/docs?id=1822001044479041) and check whether the returned `is_owner_bc` for the ID in `store_authorized_bc_id` is `true`. |
| advertiser_id {Required} | string | The ID of the ad account that you want to exclusively authorize to create GMV Max Campaigns for the TikTok Shop.<br><br>To obtain the list of ad accounts within a Business Center, use [/bc/asset/get/](https://business-api.tiktok.com/portal/docs?id=1739432717798401) and set `asset_type` to `ADVERTISER`. |
```

### Example
```xcodeblock
(code curl http)
curl --location  --request POST 'https://business-api.tiktok.com/open_api/v1.3/gmv_max/exclusive_authorization/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "store_id": "{{store_id}}",
    "store_authorized_bc_id":"{{bc_id}}",
	"advertiser_id": "{{advertiser_id}}"
}'
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
```

### Example
```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {}
}
(/code)
```
