---
title: "Appeal a rejection"
doc_id: 1740571821728770
path: "API Reference / Ad Review / Appeal a rejection"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Appeal a rejection

Use this endpoint to appeal the rejection decision, that is, to request for the ad group to be re-evaluated, when your ad group is rejected during review. 

After the appeal, you need to wait for some time and call the [/adgroup/review_info/](https://ads.tiktok.com/marketing_api/docs?id=1740554277061634) endpoint to check the appeal status and see if the review status is updated.

## Comparing v1.2 and v1.3 
The following table outlines the differences between v1.2 and v1.3 endpoints. 
```xtable
|Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path| /v1.2/adgroup/appeal/| /v1.3/adgroup/appeal/|
|Request parameter data type |`advertiser_id`: number <br> `adgroup_id`: number <br> `ad_id`: number|`advertiser_id`: string<br> `adgroup_id`: string<br> `ad_id`: string|
```

## Request

**Endpoint**

https://business-api.tiktok.com/open_api/v1.3/adgroup/appeal/

**Method** POST

**Header**

``` xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token{Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
|Content-Type{Required}|string|The content type of the request. <br>Allowed value: `"application/json"`.|
```

**Parameters**

``` xtable
|Field|Data Type|Description|
|---|---|---|
| advertiser_id {Required} | string | Advertiser ID. |
| adgroup_id {Required} | string| Ad group ID. |
| ad_id | string | Ad ID. Optional. |
| appeal_reason | string | The reason for the appeal. |
| attachment_list | string[] | List of attachment links. |
```

### Example

```xcodeblock
(code curl http)
curl -H "Access-Token:xxx" -H "Content-Type:application/json" -X POST \
-d '{
    "advertiser_id": "ADVERTISER_ID",
    "adgroup_id": "ADGROUP_ID",
    "ad_id": "AD_ID",
    "appeal_reason": "APPEAL_REASON",
    "attachment_list": [
        "ATTACHMENT_LIST"
    ]
}' \
https://business-api.tiktok.com/open_api/v1.3/adgroup/appeal/
(/code)
```

## Response

``` xtable
|Field|Data Type|Description|
|---|---|---|
|code |number | Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string | Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|data |object| Returned data. |
|request_id|string|The log id of the request, which uniquely identifies a request. |
```


### Example

```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "message": "OK",
    "code": 0,
    "data": {}
}
(/code)
```


