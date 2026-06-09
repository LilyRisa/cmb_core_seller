---
title: "Get recommended search keywords"
doc_id: 1832798361818114
path: "API Reference / Discovery / Get recommended search keywords"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get recommended search keywords

Use this endpoint to retrieve a list of 20 recommended search keywords for a search query or to get recommended search keywords personalized for your Business Account.

## Before you start
Obtain a TikTok account access token with `discovery.search.words` permission and the Application specific unique ID for the TikTok account by following the steps in [Authorization](https://business-api.tiktok.com/portal/docs?id=1738083939371009) and [Authentication](https://business-api.tiktok.com/portal/docs?id=1738084387220481).
  - To confirm whether a TikTok account access token has the `discovery.search.words` permission, use [/tt_user/token_info/get/](https://business-api.tiktok.com/portal/docs?id=1765927978092545) and check the returned `scope`. If the access token doesn't have the `discovery.search.words` permission, request the TikTok account owner to approve "**Query for recommended search terms based on trends or specific keywords**" permission through the [Authorization](https://business-api.tiktok.com/portal/docs?id=1738083939371009) workflow.
  
## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/discovery/trending/search/keyword/

**Method** GET

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Access token authorized by TikTok Business Accounts.<br><br>To obtain a short-term TikTok account access token, use [/tt_user/oauth2/token/](https://business-api.tiktok.com/portal/docs?id=1738084387220481#item-link-Get%20a%20TikTok%20account%20access%20token).|
```

**Parameters**

``` xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|--- |--- |--- |
|business_id {Required}|string|Application specific unique identifier for the Business Account.<br><br>Pass the value of the `open_id` field returned in the response of [/tt_user/oauth2/token/](https://business-api.tiktok.com/portal/docs?id=1738084387220481#item-link-Get%20a%20TikTok%20account%20access%20token) to this field.|
| query  {Required}| string | A search query.<br>You can specify products, services, or brands as the search query.<br><br>Length limit: 255 characters. |
|is_personalized|boolean|Whether to return personalized search keywords for the Business Account.<br><br>Supported values:<br></li><li>`true`: To return trending search keywords that are  personalized for the Business Account.<br></li><li>`false`: To return trending search keywords that are not personalized for the Business Account.<br><br>Default value: `false`.|

```

### Example
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/discovery/trending/search/keyword/?business_id={{business_id}}&query={{query}}' \
--header 'Access-Token: {{Access-Token}}'
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
#| search_keywords | string[] | A list of 20 recommended search keywords. |
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
        "search_keywords": [
            "{{search_keyword}}",
            "{{search_keyword}}"
        ]
    }
}
(/code)
```
