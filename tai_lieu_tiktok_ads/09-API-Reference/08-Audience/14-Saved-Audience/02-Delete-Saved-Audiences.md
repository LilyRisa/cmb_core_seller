---
title: "Delete Saved Audiences"
doc_id: 1780154666312706
path: "API Reference / Audience / Saved Audience / Delete Saved Audiences"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Delete Saved Audiences

Use this endpoint to delete Saved Audiences. 

>  <span style="color:DodgerBlue">**Note**</span><br>If you have already applied a Saved Audience to an ad group, then you can only delete the Saved Audience when the ad group has been deleted.

## Request

**Endpoint** <https://business-api.tiktok.com/open_api/v1.3/dmp/saved_audience/delete/>

**Method** POST

**Header**

```xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
|Content-Type {Required}|string|Request message type<br>Allowed format: `"application/json"`.  |
```

**Parameters**
```xtable
|Field|Data Type|Description|
|---|---|---|
|advertiser_id {Required}|string|Advertiser ID.|
|saved_audience_ids  {Required}|string[]|IDs of Saved Audiences that you want to delete. <br>Max size = 100.|
```

### Example
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/dmp/saved_audience/delete/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "saved_audience_ids": [
        "{{saved_audience_id}}"
    ]
}'
(/code)
```


## Response
```xtable
|Field|Data Type|Description|
|---|---|---|
|code |number|Return code. For the complete list of response codes and descriptions, see [Return codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|message |string|Response message. For details, see [Return codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|request_id |string| The log ID of a request, which uniquely identifies the request.|
|data |object|Returned data.  |
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
