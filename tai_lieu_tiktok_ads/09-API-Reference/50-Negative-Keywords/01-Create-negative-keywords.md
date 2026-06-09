---
title: "Create negative keywords"
doc_id: 1775104895291393
path: "API Reference / Negative Keywords / Create negative keywords"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Create negative keywords

Use this endpoint to create negative keywords for Search Ads. If the user's search term matches a negative keyword, your ad in the campaign or ad group won't be shown.

You can either configure negative keywords for an ad group or a campaign:
- If you want to configure negative keywords for an ad group, you need to ensure Automatic Search Placement has been enabled for the ad group. 
- If you want to configure negative keywords for a campaign, you need to ensure Automatic Search Placement has been enabled for at least one ad group within the campaign. 

To learn about how to enable Automatic Search Placement for an ad group, refer to [Create Search Ads](https://business-api.tiktok.com/portal/docs?id=1780164603696130).



## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/search_ad/negative_keyword/add/

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
| object_type {Required} | string | Object type. Object type. Use this field to specify whether you want to create negative keywords for a campaign or ad group. <br><br> Enum values: <ul><li>`CAMPAIGN`: campaign.</li><li>`ADGROUP`: ad group.</li></ul> |
| object_ids {Required} | string[] | Object IDs. Use this field to pass in campaign IDs or ad group IDs that you want to create negative keywords for. <br><br>Max size: 50.<br><br> Specify this field according to `object_type`: <ul><li> If `object_type` is `CAMPAIGN`, assign campaign IDs to this field.</li><li> If `object_type` is `ADGROUP`, assign ad group IDs to this field.</li></ul>|
| replace | boolean | Whether to replace all the existing negative keywords for the campaign or ad group with the keywords that you specify via `keywords`. <br><br>Enum values: <ul><li>`true`: To replace all the existing negative keywords. </li><li>`false`: To add new negative keywords.</li></ul><br>Default value: `false`. |
| keywords {Required} | object[] | The list of negative keywords you want to create. <br><br>Max size: 1,000. <br><p><span style="color:darkred"><b>Note</b></span>: For each ad group, you can configure a maximum of 10,000 negative keywords.|
#| name {Required} | string | Name of the negative keyword you want to create. <br><br>Length limit: 80 characters. <br><br> The name needs to exclude the following special characters: [`!`, `@`, `%`, `^` , `(` , `)` , `=`, `&#123;`, `&#125;`, `;`, `~` , `&#96;` , `<` , `>` , `?`, `\` , `&#124;`]. Otherwise, an error will occur. <br><br><p><span style="color:darkred"><b>Note</b></span>: Duplicate negative keyword names will be de-duplicated to only one unique name, and for each unique name you can only specify one match type. |
#| match_type | string | Match type for the negative keyword. <br><br>Enum values: <ul><li>`PRECISE_WORD`: exact match.</li><li>`PHRASE_WORD`: phrase match.</li><li>`BROAD_WORD`: broad match.</li></ul>Default value: `BROAD_WORD`.|
```
### Example
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/search_ad/negative_keyword/add/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id":"{{advertiser_id}}",
    "object_type":"CAMPAIGN",
    "object_ids":["{{object_id}}"],
    "keywords":[
        {
            "name":"{{name}}",
            "match_type":"BROAD_WORD"
        },
        {
            "name":"{{name}}",
            "match_type":"BROAD_WORD"
        }
    ]
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
```
### Example
```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {}
}
(/code)
```
