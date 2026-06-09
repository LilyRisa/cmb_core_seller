---
title: "Get recommended hashtags for TikTok accounts"
doc_id: 1762228503821314
path: "API Reference / Accounts / Posts / Get recommended hashtags for TikTok accounts"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get recommended hashtags for TikTok accounts

Use this endpoint to specify a keyword and get a list of recommended hashtags to be used for your TikTok account videos. 

The returned hashtag (`name`) can be used in the `caption` field as part of the video caption when you publish a video to your TikTok account via [/business/video/publish/](https://ads.tiktok.com/marketing_api/docs?id=1762228496095234).

This is a new endpoint in version 1.3.

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/business/hashtag/suggestion/

**Method** GET

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Access token authorized by TikTok creators. <br>To obtain an access token, use [/tt_user/oauth2/token/](https://business-api.tiktok.com/portal/docs?id=1833997638479041).|
```

**Parameters**

``` xtable
|Field{30%}|Data Type{15%}| Description{55%}|
|--- |--- |--- |--- |
| business_id {Required} | string |Application specific unique identifier for the TikTok account. <br>Pass the value of the `open_id` field returned in the response of [/tt_user/oauth2/token/](https://business-api.tiktok.com/portal/docs?id=1833997638479041) to this field.|
| keyword {Required} | string | The keyword that you want to get recommended hashtags for. |
| language | string | Keyword language. The language of the hashtag recommendations will match the keyword language. <br><br><strong>Default value</strong>: `en` (English).<br><br><strong>Enum values</strong>:<ul><li>`en`: English</li><li>`ar`: Arabic</li><li>`bn-IN`: Bengali</li><li>`ceb-PH`: Cebuano</li><li>`cs-CZ`: Czech</li><li>`de-DE`: German</li><li>`el-GR`: Greek</li><li>`es`: Spanish</li><li>`fi-FI`: Finnish</li><li>`fil-PH`: Filipino</li><li>`fr`: French</li><li>`he-IL`: Hebrew</li><li>`hi-IN`: Hindi</li><li>`hu-HU`: Hungarian</li><li>`id-ID`: Indonesian</li><li>`it-IT`: Italian</li><li>`ja-JP`: Japanese</li><li>`jv-ID`: Javanese</li><li>`km-KH`: Khmer</li><li>`ko-KR`: Korean</li><li>`ms-MY`: Malay</li><li>`my-MM`: Burmese</li><li>`nl-NL`: Dutch</li><li>`pl-PL`: Polish</li><li>`pt-BR`: Portuguese</li><li>`ro-RO`: Romanian</li><li>`ru-RU`: Russian</li><li>`sv-SE`: Swedish</li><li>`th-TH`: Thai</li><li>`tr-TR`: Turkish</li><li>`uk-UA`: Ukranian</li><li>`ur`: Urdu</li><li>`vi-VN`: Vietnamese</li><li>`zh-Hans`: Chinese (simplified)</li><li>`zh-Hant-TW`: Chinese (traditional)</li></ul>|
```
### Example
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/business/hashtag/suggestion/?business_id={{business_id}}&keyword=summer&language=en' \
  --header 'Access-Token: {{Access-Token}}'
(/code)
```
## Response
**Header**

These are important headers to record for issue reports and troubleshooting. This is not an exhaustive list of response headers.

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
| Date | string | Date and time (GMT) when the response was received. <br><strong>Example</strong>: Fri, 13 Aug 2021 08:04:42 GMT |
| X-Tt-Logid | string | Unique identifier for the API request. |
```
**Body**
``` xtable
|Field{30%}|Type{15%}|Description{55%}|
|-|-|-|
| request_id | string | Unique identifier for the API request. <br><br>Please record this field for all API requests. Important for issue reports and troubleshooting. |
| code | Integer | Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message| string | Response message. For details, see [Appendix - Return Information](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
| data | object | Returned data. |
#|suggestions| object[] | List of hashtag suggestions. 10 recommended hashtags will be provided in total and the hashtags are first ordered by exact match and then by the number of views.<br><p><span style="color:darkred"><b>Note</b></span>: If no hashtag suggestion is available for the `keyword` you specify, we will return the same keyword in `name`, and `view_count` will be 0.|
##|name | string | The name of the recommended hashtag. |
##|view_count | integer | The number of views that the recommended hashtag has received. |
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
        "app_id": "{{app_id}}",
        "creator_id": "{{creator_id}}",
        "scope": "video.publish,user.info.basic,video.list,user.insights,user.insights.creator,tcm.order.update,comment.list.manage,video.insights,comment.list"
    }
}
(/code)
```

