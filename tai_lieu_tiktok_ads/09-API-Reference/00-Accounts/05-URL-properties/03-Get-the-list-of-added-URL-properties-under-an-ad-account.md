---
title: "Get the list of added URL properties under an ad account"
doc_id: 1769325368603650
path: "API Reference / Accounts / URL properties / Get the list of added URL properties under an ad account"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get the list of added URL properties under an ad account

Use this endpoint to get the list of URL properties that have been added to an advertiser account.

To learn about the workflow for adding and verifying URL properties, refer to [Manage URL properties](https://ads.tiktok.com/marketing_api/docs?id=1769324038780930).

> <span style="color:DarkRed">**Important**</span><br>
> Starting October 15th, 2025, you need to also specify your developer app secret to be able to use this endpoint.

> <span style="color:DodgerBlue">**Note**</span><br>
> Although subdomains of a verified domain are automatically considered verified, they will not be included in the response of this endpoint unless they have been specifically added to the advertiser account through [/business/property/add/](https://business-api.tiktok.com/portal/docs?id=1769325280876545). 

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/business/property/list/

**Method** GET

**Parameters**

``` xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|--- |--- |--- |
| app_id {Required} | string | ID of your developer application. <br><br>To find the <strong>App ID</strong>, navigate to <strong>[My Apps](https://business-api.tiktok.com/portal/apps)</strong> > <strong>App Detail</strong> > <strong>Basic Information</strong>. |
|secret{Required}|string| Secret of your developer app. <br><br>To find the <b>Secret</b>, navigate to [My Apps](https://ads.tiktok.com/marketing_api/apps/) > <b>App Detail</b> > <b>Basic Information</b>. |
```
### Example
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/business/property/list/?app_id={{app_id}}&secret={{secret}}' \
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
#| url_property_info_list| object |Information about the URL properties that have been added. |
##| property_type | integer | Type of the URL property.<br>Enum values：<ul><li>1: Domain.</li><li>2: URL prefix. </li></ul>|
##| url | string | An owned URL that is added. |
##| property_status | integer | Verification status of the URL property. <br>Enum values:<ul><li> 0: Pending for verification.</li><li>1: Verified. </li><li>2: Not verified because the verification attempt failed. You need to re-verify the URL property.</li></ul>|
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
        "url_property_info_list": [
            {
              "property_type": {{property_type}},
              "url": "{{url}}",
              "property_status": {{property_status}},
              "signature": "{{signature}}",
              "file_name": "{{file_name}}"
            }
        ]
    }
}
(/code)
```
