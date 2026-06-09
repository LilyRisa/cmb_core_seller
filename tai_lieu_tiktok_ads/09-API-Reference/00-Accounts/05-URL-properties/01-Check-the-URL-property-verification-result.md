---
title: "Check the URL property verification result"
doc_id: 1769325308162050
path: "API Reference / Accounts / URL properties / Check the URL property verification result"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Check the URL property verification result

Use this endpoint to check the result of the ownership verification for a URL property (domain or URL prefix).

To learn about the workflow for adding and verifying URL properties, refer to [Manage URL properties](https://ads.tiktok.com/marketing_api/docs?id=1769324038780930).

> <span style="color:DarkRed">**Important**</span><br>
> Starting October 15th, 2025, you need to also specify your developer app secret to be able to use this endpoint.

> <span style="color:DodgerBlue">**Note**</span><br>
> To avoid URL prefix verification failures, double-check the following details before checking the verification results:<ul><li>The verification file is publicly accessible after uploaded for verification.</li><li>The file name matches the value of the parameter `file_name` after uploading. If the file name includes an additional file extension of ".txt", remove it so that the name matches the required format with only one ".txt" file extension. </li></ul>


## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/business/property/verify/

**Method** POST

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Content-Type {Required}|string|Request message type.<br>Allowed format: `application/json`.  |
```

**Parameters**

``` xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|--- |--- |--- |
| app_id {Required} | string | ID of your developer application. <br><br>To find the <strong>App ID</strong>, navigate to <strong>[My Apps](https://business-api.tiktok.com/portal/apps)</strong> > <strong>App Detail</strong> > <strong>Basic Information</strong>. |
|secret{Required}|string| Secret of your developer app. <br><br>To find the <b>Secret</b>, navigate to [My Apps](https://ads.tiktok.com/marketing_api/apps/) > <b>App Detail</b> > <b>Basic Information</b>. |
| url_property_meta {Required} | object |Information about the URL property that you want to check the ownership verification result for. |
#| property_type {Required} | integer | Type of the URL property.<br>Enum values：<ul><li>1: Domain.</li><li>2: URL prefix. </li></ul>|
#| url {Required} | string | An owned URL that you want to check the ownership verification result for. Pass in the URL according to the `property_type` that you specify. <ul><li>If `property_type` is 1, you need to specify a base domain (for example, `example.com`) or a subdomain (for example, `subdomain.example.com`).</li><li> If `property_type` is 2, you need to specify a URL prefix which consists of: `https://` + host (must be a domain) + path + `/`, and should not include any redirections to other URLs. For instance, in the URL prefix `https://www.example.com/folder/` , the host is the domain `www.example.com` and the path is `/folder`.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: Pass to this field a URL property that you have added to the advertiser account through [/business/property/add/](https://business-api.tiktok.com/portal/docs?id=1769325280876545). Otherwise, an error will occur. Note that subdomains of a verified domain do not require separate verification, because they are automatically considered verified.|
```
### Example
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/business/property/verify/' \
--header 'Content-Type: application/json' \
--data '{
    "app_id": "{{app_id}}",
	"secret": "{{secret}}",
    "url_property_meta": {
        "property_type": {{property_type}},
        "url": "{{url}}"
    }
}'
(/code)
```
## Response

``` xtable
|Field{30%}|Type{15%}|Description{55%}|
|-|-|-|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix-Return Code](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|message|string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|request_id |string|The log ID of a request, which uniquely identifies the request.  |
|data |object|Returned data.|
#| url_property_info| object |Information about the ownership verification result for the URL property. |
##| property_type | integer | Type of the URL property.<br>Enum values：<ul><li>1: Domain.</li><li>2: URL prefix. </li></ul>|
##| url | string | The specified owned URL. |
##| property_status | integer | Verification status of the URL property. <br>Enum values:<ul><li> 1: Verified. </li><li>2: Not verified because the verification attempt failed. You need to re-verify the URL property.</li></ul>|
##| signature | string | Signature string to be used in the ownership verification process. |
##| file_name | string | Signature file name to be used in the ownership verification process. |
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
        "url_property_info": {
            "file_name": "{{file_name}}",
            "property_status": 1,
            "property_type": {{property_type}},
            "signature": "{{signature}}",
            "url": "{{url}}"
        }
    }
}
(/code)
```
