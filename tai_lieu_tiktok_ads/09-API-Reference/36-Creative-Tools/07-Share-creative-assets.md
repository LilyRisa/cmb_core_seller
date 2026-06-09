---
title: "Share creative assets"
doc_id: 1773192725768193
path: "API Reference / Creative Tools / Share creative assets"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Share creative assets

Use this endpoint to share existing creative assets under an advertiser account with other advertiser accounts. 
<br>To share creative assets, you need to be an Admin or Operator for both the ad account that has the assets and the ad accounts you want to share these assets with. Additionally, you should be the owner of these assets. 

## Request 
**Endpoint** https://business-api.tiktok.com/open_api/v1.3/creative/asset/share/

**Method** POST

**Header**
```xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
| Content-Type {Required} | string | The content type of the request. <br>Allowed value: `"application/json"`.|
```
**Parameters**

``` xtable
|Field|Data Type|Description|
|---|---|---|
|advertiser_id{Required}|string|Operator advertiser ID.|
|asset_type|string|Type of creative assets that you want to share. <br>Enum values: `VIDEO`, `IMAGE`, `MUSIC`<br>Default value: `VIDEO`|
|material_ids{Required}|string[]|Material ID. Max size: 20<br>After successful sharing, the name of the shared creative materials will be copied in the shared advertiser account. <br><p><span style="color:darkred"><b>Note</b></span>: Currently, the sharing will only fail when there is a creative material with the same name as the material you want to share under the shared advertiser account.|
|shared_advertiser_ids{Required}|string[]|IDs of advertisers that you want to share creative materials with. <br>Max size: 10.|
```

### Examples
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/creative/asset/share/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data-raw '{
    "advertiser_id": "{{advertiser_id}}",
    "asset_type": "MUSIC",
    "material_ids": ["{{material_id}}", "{{material_id}}"],

    "shared_advertiser_ids": ["{{shared_advertiser_id}}", "{{shared_advertiser_id}}"]
}'
(/code)
```

## Response

``` xtable
|Field|Data Type|Description|
|---|---|---|
|code |number|Return code, see [Appendix-Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|message |string|Return message, see [Appendix-Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|data |object|Returned data.|
#|failed_infos|map < string, string>|Information about the failed sharing. <br><p><span style="color:darkred"><b>Note</b></span>: Currently, the sharing will only fail when there is a creative material with the same name as the material you want to share under the shared advertiser account.|
##|key|string|Advertiser ID that you fail to share creative materials with. |
##|value|string[]|Material IDs that you fail to share. |
|request_id |string|The log id of the request, which uniquely identifies a request. |
```

### Example

``` xcodeblock
(code curl http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "failed_infos": {
            "×××××××××××××××": [
                "×××××××××××××××",
                "×××××××××××××××"
            ],
            "×××××××××××××××": [
                "×××××××××××××××",
                "×××××××××××××××"
            ]
        }
    }
}
(/code);
```
