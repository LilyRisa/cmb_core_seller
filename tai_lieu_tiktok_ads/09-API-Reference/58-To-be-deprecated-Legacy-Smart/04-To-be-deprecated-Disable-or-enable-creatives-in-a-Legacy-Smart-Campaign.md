---
title: "(To be deprecated) Disable or enable creatives in a Legacy Smart+ Campaign"
doc_id: 1837969177953361
path: "API Reference / (To be deprecated) Legacy Smart+ / (To be deprecated) Disable or enable creatives in a Legacy Smart+ Campaign"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# (To be deprecated) Disable or enable creatives in a Legacy Smart+ Campaign

Use this endpoint to disable or enable one or more creatives in a Smart+ Campaign. 

The creative can be a video, a carousel, or a TikTok post. 

> <span style="color:DarkRed">**Important**</span><br>
> - **Upgraded Smart+ API**:
>     - We are actively upgrading the Smart+ API to deliver enhanced capabilities and performance. The new Upgraded Smart+ API is already launched. Learn more about [Upgraded Smart+ Campaign](https://business-api.tiktok.com/portal/docs?id=1853452461203458) and [available Upgraded Smart+ API endpoints](https://business-api.tiktok.com/portal/docs?id=1843301794874370).
>     - **Get started**:
>         - **Reporting**: No additional allowlist required.
>         - **Campaign Management**: To access the upgraded Smart+ experience, contact your TikTok sales representative to have your ad account allowlisted. If your ad account is already in the allowlist, you can directly use the Upgraded Smart+ API creation and modification endpoints.  
> - **Legacy API Deprecation**: 
>     - After **March 31, 2026**, you will no longer be able to create legacy Smart+ Campaigns using the [/campaign/spc/create/](https://business-api.tiktok.com/portal/docs?id=1767334204047362) endpoint.
>     - Note that legacy Smart+ Campaigns that have not been migrated will not be transitioned to the new API automatically. Attempts to use the legacy Smart+ API endpoint afterward will result in errors, rendering new campaign creation via this endpoint unavailable.

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/campaign/spc/material_status/update/

**Method** POST

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://business-api.tiktok.com/portal/docs?id=1738373164380162).|
|Content-Type {Required}|string|Request message type.<br>Allowed format: `application/json`.  |
```

**Parameters**

``` xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|--- |--- |--- |
|advertiser_id{Required}|string|Advertiser ID.|
|campaign_id{Required}|string|The ID of a Smart+ Campaign.<br><br>To retrieve existing Smart+ Campaigns within your ad account, use [/campaign/get/](https://business-api.tiktok.com/portal/docs?id=1739315828649986) and set `filtering` to `&#123;"is_smart_performance_campaign" : true&#125;`.|
|ad_material_ids{Required}|string[]|A list of ad material IDs for creatives to operate on.<br>An ad material ID is an ad-specific material ID generated when a particular creative is used in a Smart+ ad. This ID differs from the creative ID you obtain when uploading the creative to your ad account’s Creative Library.<br><br>To obtain ad material IDs associated with a Smart+ ad, use [/campaign/spc/get/](https://business-api.tiktok.com/portal/docs?id=1767334299811842).<br><br>Max size: 20.<br><br>The number of creatives you can operate on varies based on the type of creatives you want to operate on:<ul><li>If you want to operate on the following types of creatives, you can only specify one ID in `ad_material_ids`:<ul><li>a carousel in Spark Ad Standard Carousel created through Sparks Ads Push</li><li>a carousel in non-Spark Ad Standard Carousel</li></ul></li><li>If you want to operate on the following types of creatives, you can specify up to 20 IDs in `ad_material_ids`:<ul><li>non-Spark Ad Video</li><li>Spark Ad Video created through Sparks Ads Push</li><li>TikTok video posts in Spark Ad Video created through Sparks Ads Pull</li><li>TikTok photo posts in Spark Ad Standard Carousel created through Sparks Ads Pull</li></ul></li></ul>|
|material_status{Required}|string|The operation to perform on the creatives.<br><br>Enum values:<ul><li>`DISABLE`: disable.</li><li>`ENABLE`: enable.</li></ul>|
```


### Example
```xcodeblock
(code curl http)
curl --location  --request POST 'https://business-api.tiktok.com/open_api/v1.3/campaign/spc/material_status/update/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "campaign_id": "{{campaign_id}}",
    "ad_material_ids": ["{{ad_material_id}}"],
    "material_status": "DISABLE"
}'
(/code)
```
## Response

``` xtable
|Field{30%}|Type{15%}|Description{55%}|
|---|---|---|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix-Return Code](https://business-api.tiktok.com/portal/docs?id=1737172488964097). |
|message|string|Response message. For details, see [Appendix - Return Codes](https://business-api.tiktok.com/portal/docs?id=1737172488964097).|
|request_id |string|The log ID of a request, which uniquely identifies the request.  |
|data |object|Returned data.|
#|campaign_id|string|The ID of a Smart+ Campaign.|
#|ad_material_ids|string[]|A list of ad material IDs for creatives operated on.<br>An ad material ID is an ad-specific material ID generated when a particular creative is used in a Smart+ ad.<br> This ID differs from the creative ID you obtain when uploading the creative to your ad account’s Creative Library.|
#|material_status|string|The statuses of the creatives.<br><br>Enum values:<ul><li>`DISABLE`: The creatives are disabled (in 'OFF' status).</li><li>`ENABLE`: The creatives are enabled (in 'ON' status).</li></ul>|
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
        "material_status": "DISABLE",
        "campaign_id": "{{campaign_id}}",
        "ad_material_ids": [
            "{{ad_material_id}}"
        ]
    }
}
(/code)
```
