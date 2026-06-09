---
title: "Create a Brand Profile for your TTO account"
doc_id: 1830335387914241
path: "API Reference / TikTok One / Create a Brand Profile for your TTO account"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Create a Brand Profile for your TTO account

Use this endpoint to create a Brand Profile for your TikTok One (TTO) Creator Marketplace account.


## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/tto/tcm/brand/profile/create/

**Method** POST

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Access token authorized by a TTO Creator Marketplace account. <br>For details, see [Authentication](https://business-api.tiktok.com/portal/docs?id=1825117875361793).|
|Content-Type {Required}|string|Request message type.<br>Allowed format: `application/json`.  |
```


**Parameters**

``` xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|--- |--- |--- |
| tto_tcm_account_id {Required} | string | The ID of the TTO Creator Marketplace account.<br><br>To obtain the IDs of the accounts that you can access via `Access-Token`, use [/tto/oauth2/tcm/](https://business-api.tiktok.com/portal/docs?id=1815693478150146). |
| brand_name {Required}  | string | Brand name.<br><br>Length limit: 60 characters.<br><br><p><span style="color:darkred"><b>Note</b></span>: Once set, this field cannot be updated. |
| brand_industry_id {Required}  | string | The ID of the brand industry.<br><br>For enum values, see [Brand Profile industry IDs](https://business-api.tiktok.com/portal/docs?id=1830336456075458).<br><br><p><span style="color:darkred"><b>Note</b></span>: Once set, this field cannot be updated. |
| brand_website {Required}  | string | Brand website link.<br><br>The link should start with `https://`. |
| logo_url {Required} | string | The URL of the brand logo image.<br><br>Specification requirements:<ul><li>File format: JPG, PNG, or GIF.</li><li>Aspect ratio: 1:1.</li><li>File size: Up to 5MB.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: Once set, this field cannot be updated. |
```

### Example
```xcodeblock
(code curl http)
curl --location  --request POST 'https://business-api.tiktok.com/open_api/v1.3/tto/tcm/brand/profile/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "tto_tcm_account_id": "{{tto_tcm_account_id}}",
    "brand_name": "{{brand_name}}",
    "brand_website": "{{brand_website}}",
    "brand_industry_id": "290101",
    "logo_url": "{{logo_url}}"
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
#|brand_profile_id | string | The ID of the created Brand Profile. |
#|brand_name | string | Brand name. |
#|brand_industry_id | string | The ID of the brand industry. |
#|brand_website | string | Brand website link. |
#|logo_url | string | The URL of the brand logo image for your TTO Creator Marketplace account.<br><br><p><span style="color:darkred"><b>Note</b></span>: This URL is system-generated for the logo image and is different from the URL specified through `logo_url` in the request for Brand Profile creation. |
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
        "brand_industry_id": "290101",
        "brand_name": "{{brand_name}}",
        "brand_profile_id": "{{brand_profile_id}}",
        "brand_website": "{{brand_website}}",
        "logo_url": "{{logo_url}}"
    }
}
(/code)
```
