---
title: "Remove or add back creatives in a GMV Max Campaign"
doc_id: 1861260625563202
path: "API Reference / GMV Max / Remove or add back creatives in a GMV Max Campaign"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Remove or add back creatives in a GMV Max Campaign

Use this endpoint to either remove creatives (videos) from a Product or Live GMV Max Campaign or to restore creatives that were previously removed.


## Request
**Endpoint** https://business-api.tiktok.com/open_api/v1.3/campaign/gmv_max/creative/update/


**Method** POST

**Header**

```xtable
|Field{35%}|Type{15%}|Description{50%}|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://business-api.tiktok.com/portal/docs?id=1738373164380162).|
|Content-Type {Required}|string|Request message type.<br>Allowed value: `application/json`.|
```

**Parameters**
```xtable
|Field{35%}|Data Type{15%}|Description{50%}|
|---|---|---|
|advertiser_id {Required}|string|Advertiser ID.|
|campaign_id {Required}|string|Campaign ID.<br><br><font color="darkred"><b>Note</b></font>:<ul><li>The campaign must be in an active status. To retrieve active GMV Max Campaigns in your ad account, use [/gmv_max/report/get/](https://business-api.tiktok.com/portal/docs?id=1824721673497601) with `filtering` set to `&#123;"campaign_statuses":&#91;"STATUS_DELIVERY_OK"&#93;&#125;`.</li><li>If the campaign is a Product GMV Max Campaign, the `product_video_specific_type` of the campaign must be `AUTO_SELECTION`.</li></ul>|
|action {Required}|string|The type of action to perform.<br><br>Enum values:<ul><li>`REMOVE`: To remove creatives from a GMV Max Campaign.</li><li>`ADD`: To add previously removed creatives back into a GMV Max Campaign.</li></ul><br><font color="darkred"><b>Note</b></font>:<ul><li>Once the action is performed, wait 20 minutes before verifying the updated statuses using [/gmv_max/report/get/](https://business-api.tiktok.com/portal/docs?id=1824721673497601).</li><li>If a creative is successfully removed from a Product GMV Max Campaign, its `creative_delivery_status` in [/gmv_max/report/get/](https://business-api.tiktok.com/portal/docs?id=1824721673497601) will be `EXCLUDED`.</li></ul>|
|item_list {Required}|object[]|The list of TikTok posts (videos) to be operated on.<br><br>Max size: 400.<br><br><font color="darkred"><b>Note</b></font>:<ul><li>This endpoint allows for the removal of up to 10,000 posts from a GMV Max Campaign, with a limit of 400 posts per request.</li><li>For example, if you need to exclude 800 posts, send a first request with 400 posts in this field, followed by a second request with the remaining 400.</li></ul>|
#|item_id {Required}|string|The ID of the TikTok post to operate on.<ul><li>When `action` is `REMOVE`, use [/gmv_max/report/get/](https://business-api.tiktok.com/portal/docs?id=1824721673497601) with `filtering` set to `&#123;"creative_delivery_statuses":&#91;"IN_QUEUE","LEARNING","DELIVERING","NOT_DELIVERYING","UNAVAILABLE","NOT_ACTIVE"&#93;&#125;` to retrieve eligible creatives.</li><li>When `action` is `ADD`, use [/gmv_max/report/get/](https://business-api.tiktok.com/portal/docs?id=1824721673497601) with `filtering` set to `&#123;"creative_delivery_statuses":&#91;"EXCLUDED"&#93;&#125;` to retrieve previously removed creatives.</li></ul><br><font color="darkred"><b>Note</b></font>: Currently, you can only obtain the `item_id` of TikTok posts in Live GMV Max Campaigns on TikTok Ads Manager.|
#|spu_id_list {+Conditional}|string[]|Required for a Product GMV Max Campaign.<br><br>The list of Product SPU IDs that the TikTok post is associated with.<br><br>To obtain the list of Product SPU IDs associated with a TikTok post in a Product GMV Max Campaign, use [/campaign/gmv_max/info/](https://business-api.tiktok.com/portal/docs?id=1822000968821762).|
```

### Example
#### Remove creatives
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/campaign/gmv_max/creative/update/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "campaign_id": "{{campaign_id}}",
    "item_list": [
        {
            "item_id": "{{item_id}}",
            "spu_id_list": ["{{spu_id}}"]
        },
        {
            "item_id": "{{item_id}}",
            "spu_id_list": ["{{spu_id}}"]
        }
    ],
    "action": "REMOVE"
}'
(/code)
```

#### Add back creatives
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/campaign/gmv_max/creative/update/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "campaign_id": "{{campaign_id}}",
    "item_list": [
        {
            "item_id": "{{item_id}}",
            "spu_id_list": ["{{spu_id}}"]
        },
        {
            "item_id": "{{item_id}}",
            "spu_id_list": ["{{spu_id}}"]
        }
    ],
    "action": "ADD"
}'
(/code)
```

## Response

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|-|-|-|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://business-api.tiktok.com/portal/docs/return-codes-appendix/v1.3).|
|message |string|Response message. For details, see [Appendix - Return Codes](https://business-api.tiktok.com/portal/docs/return-codes-appendix/v1.3).|
|request_id|string| The log ID of the request, which uniquely identifies a request.|
|data|object|Returned data.|
```
### Example
```xcodeblock
(code curl http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {}
}
(/code)
```


