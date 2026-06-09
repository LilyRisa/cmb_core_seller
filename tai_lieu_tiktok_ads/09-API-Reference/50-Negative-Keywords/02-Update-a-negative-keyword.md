---
title: "Update a negative keyword"
doc_id: 1775104902283266
path: "API Reference / Negative Keywords / Update a negative keyword"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Update a negative keyword

Use this endpoint to update a negative keyword for Search Ads.



## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/search_ad/negative_keyword/update/

**Method** POST

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
|Content-Type {Required}|string|Request message type.<br>Allowed format: `application/json`.  |
```

**Parameters**

``` xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|--- |--- |--- |
| advertiser_id {Required} | string | Advertiser ID. |
| object_type {Required} | string | Object type. Use this field to specify whether you want to update the negative keywords for a campaign or ad group. <br><br> Enum values: <ul><li>`CAMPAIGN`: campaign.</li><li>`ADGROUP`: ad group.</li></ul> |
| object_id {Required} | string | Object ID. Object ID. Use this field to pass in a campaign ID or ad group ID that you want to update the negative keyword for.   <br><br> Specify this field according to `object_type`: <ul><li> If `object_type` is `CAMPAIGN`, assign to this field a campaign ID.</li><li> If `object_type` is `ADGROUP`, assign to this field an ad group ID.</li></ul>|
| old_keyword_id {Required} | string | The ID of the negative keyword that you want to update.<br><br>To obtain the ID of a negative keyword, use [/search_ad/negative_word/get/](https://business-api.tiktok.com/portal/docs?id=1775104887052289). <br><br><p><span style="color:darkred"><b>Note</b></span>: If the negative keyword ID that you specify does not exist, an error will occur. |
| keyword {Required} | object| Information about the update to the original keyword. |
#| name {Required} | string | The new name for the negative keyword you specify via `old_keyword_id`.<br><br>Length limit: no more than 80 characters. <br><br> The name needs to exclude the following special characters: [`!`, `@`, `%`, `^` , `(` , `)` , `=`, `&#123;`, `&#125;`, `;`, `~` , `&#96;` , `<` , `>` , `?`, `\` , `&#124;`]. Otherwise, an error will occur. <br><br><p><span style="color:darkred"><b>Note</b></span>: Duplicate negative keyword names will be de-duplicated to only one unique name, and for each unique name you can only specify one match type.<br> However, if you want to update the match type of a negative keyword, you can pass in the same name via `name` and assign a different match type via `match_type`. |
#| match_type | string | Match type for the negative keyword. <br><br>Enum values: <ul><li>`PRECISE_WORD`: exact match.</li><li>`PHRASE_WORD`: phrase match.</li><li>`BROAD_WORD`: broad match.</li></ul>Default value: `BROAD_WORD`.|
```

### Example - Update the name of a negative keyword
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/search_ad/negative_keyword/update/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id":"{{advertiser_id}}",
    "object_type":"CAMPAIGN",
    "object_id":"{{object_id}}",
    "old_keyword_id":"{{old_keyword_id}}",
    "keyword":{
            "name":"{{name}}"
        }
}'
(/code)
```
### Example - Update the match type of a negative keyword
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/search_ad/negative_keyword/update/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id":"{{advertiser_id}}",
    "object_type":"CAMPAIGN",
    "object_id":"{{object_id}}",
    "old_keyword_id":"{{old_keyword_id}}",
    "keyword":{
            "name":"{{name}}",
            "match_type":"PRECISE_WORD"
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
#| new_keyword_id | string | ID of the updated negative keyword. |
```
### Example - Update the name of a negative keyword
```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{}}",
    "data": {
        "new_keyword_id": "{{new_keyword_id}}"
    }
}
(/code)
```
### Example - Update the match type of a negative keyword
```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{}}",
    "data": {
        "new_keyword_id": "{{old_keyword_id}}"
    }
}
(/code)
```
