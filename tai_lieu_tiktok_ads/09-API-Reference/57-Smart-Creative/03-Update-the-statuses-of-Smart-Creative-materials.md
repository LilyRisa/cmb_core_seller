---
title: "Update the statuses of Smart Creative materials"
doc_id: 1739506701165570
path: "API Reference / Smart Creative / Update the statuses of Smart Creative materials"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Update the statuses of Smart Creative materials

Use this endpoint to update the status of creative materials for Smart Creative ads, including ad texts, images, and video materials. This is a totally new endpoint in v1.3.


## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/ad/aco/material_status/update/

**Method** POST

**Header**

``` xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
|Content-Type {Required}|string|Request message type<br data-tomark-pass>Allowed format: `"application/json"`  |
```

**Parameters**

``` xtable
|Field|Data Type|Description|
|---|---|---|
|advertiser_id {Required}|string| Advertiser ID. |
|ad_group_id {Required}|string| Ad group ID. <br><br><p><span style="color:darkred"><b>Note</b></span>: Pass the ID of an ad group that you have enabled Smart Creative for. The `creative_material_mode` for the ad group should be `SMART_CREATIVE`.|
|material_ids {Required}|string[]| Material IDs. You can pass in only one Material ID. |
|material_status {Required}|string| Status of the material. Enum values: `ENABLE` (the material can be used), `DISABLE`(the material cannot be used).|
```

### Example

```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/ad/aco/material_status/update/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "ad_group_id": "{{ad_group_id}}",
    "material_ids": ["{{material_id}}"],
    "material_status": "DISABLE"
}'
(/code)
```


## Response

``` xtable
|Field|Data Type|Description|
|---|---|---|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|data |object|Returned data.  |
#|ad_group_id |string| A list of ad group IDs. Quantity: 1-100.|
#|material_ids |string[]| Material IDs.|
#|material_status|string| Status of the material. Enum values: `ENABLE` (the material can be used), `DISABLE`(the material cannot be used).|
|request_id |string|The log id of a request, which uniquely identifies the request. |
```

### Example

``` xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "material_ids": [
            "{{material_id}}"
        ],
        "ad_group_id": "{{ad_group_id}}",
        "material_status": "DISABLE"
    }
}
(/code);
```

