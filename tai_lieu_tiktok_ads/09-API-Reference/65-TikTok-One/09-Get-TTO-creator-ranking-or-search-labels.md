---
title: "Get TTO creator ranking or search labels"
doc_id: 1816372654109698
path: "API Reference / TikTok One / Get TTO creator ranking or search labels"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get TTO creator ranking or search labels

Use this endpoint to retrieve the creator industry or content tag labels associated with TikTok One (TTO) creators.

Depending on the label type (`label_type`) you get, you can either use the ranking label in [/tto/tcm/rank/](https://business-api.tiktok.com/portal/docs?id=1816378813274113) to obtain a list of up to 100 US creators who rank highest for that label, or use the search label in [/tto/tcm/creator/discover/](https://business-api.tiktok.com/portal/docs?id=1825017307843585) to discover creators.



## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/tto/tcm/category/label/

**Method** GET

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Access token authorized by a TikTok One Creator Marketplace account. <br>For details, see [Authentication](https://business-api.tiktok.com/portal/docs?id=1825117875361793).|
```

**Parameters**

``` xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|--- |--- |--- |
| tto_tcm_account_id {Required} | string | The ID of the TikTok One Creator Marketplace account.<br><br>To obtain the IDs of the accounts that you can access via `Access-Token`, use [/tto/oauth2/tcm/](https://business-api.tiktok.com/portal/docs?id=1815693478150146). |
| label_type {Required} | string | The type of label.<br><br>Enum value:<ul><li>`RANKING`: ranking label. A label that can be used with [/tto/tcm/rank/](https://business-api.tiktok.com/portal/docs?id=1816378813274113) for ranking creators.</li><li>`SEARCH`: search label.  A label that can be used with [/tto/tcm/creator/discover/](https://business-api.tiktok.com/portal/docs?id=1825017307843585) for discovering creators.</li></ul> |
```
### Example
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/tto/tcm/category/label/?tto_tcm_account_id={{tto_tcm_account_id}}&label_type=RANKING' \
--header 'Access-Token: {{Access-Token}}'
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
#| industry_labels | object[] | The list of creator industry labels, which can be used to obtain Branded Content creator rankings on TTO Creator Leaderboard. <br>Creator industry labels are assigned to creators based on the commercial content they produce, including ad creatives and branded content on their TikTok accounts, and represent their commercial experience in certain industries. |
##| label_id | string | The ID of the creator industry label. |
##| label_name | string | The name of the creator industry label. |
#| content_labels | object[] | The list of content tag labels, which can be used to obtain Organic Content creator rankings on TTO Creator Leaderboard. <br>Content tag labels are assigned to creators based on the main focus of the creator’s TikTok account, and represent the direction of the creator’s organic content. |
##| label_id | string | The ID of the content tag label. |
##| label_name| string | The name of the content tag label. |
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
        "content_labels": [
            {
                "label_id": "11007001",
                "label_name": "Fitness"
            },
            {
                "label_id": "11009001",
                "label_name": "Love & Romantic Relationships"
            },
            {
                "label_id": "11009002",
                "label_name": "Babies"
            },
            {
                "label_id": "11014001",
                "label_name": "Automobiles"
            },
            {
                "label_id": "11010004",
                "label_name": "Cooking & Recipes"
            },
            {
                "label_id": "11011007",
                "label_name": "Jobs & Careers"
            },
            {
                "label_id": "11015001",
                "label_name": "Tech Products & Tests"
            },
            {
                "label_id": "11015003",
                "label_name": "Business & Finance"
            },
            {
                "label_id": "11001001",
                "label_name": "Pet Tips & Care"
            },
            {
                "label_id": "11003001",
                "label_name": "Outfits"
            },
            {
                "label_id": "11007004",
                "label_name": "Traditional Sports"
            },
            {
                "label_id": "11009003",
                "label_name": "Family"
            },
            {
                "label_id": "11013006",
                "label_name": "Home & Garden"
            },
            {
                "label_id": "11004003",
                "label_name": "Music"
            },
            {
                "label_id": "11004004",
                "label_name": "Dance"
            },
            {
                "label_id": "11008001",
                "label_name": "Comedy"
            },
            {
                "label_id": "11013005",
                "label_name": "Travel"
            },
            {
                "label_id": "11002002",
                "label_name": "Beauty Tutorials & Tips"
            },
            {
                "label_id": "11002006",
                "label_name": "Hair Design & Care"
            },
            {
                "label_id": "11004001",
                "label_name": "Movies & TV"
            },
            {
                "label_id": "11005001",
                "label_name": "Video Games"
            }
        ],
        "industry_labels": [
            {
                "label_id": "16000000000",
                "label_name": "Appliances"
            },
            {
                "label_id": "18000000000",
                "label_name": "Household Products"
            },
            {
                "label_id": "22000000000",
                "label_name": "Apparel & Accessories"
            },
            {
                "label_id": "15000000000",
                "label_name": "Tech & Electronics"
            },
            {
                "label_id": "14000000000",
                "label_name": "Beauty & Personal Care"
            },
            {
                "label_id": "21000000000",
                "label_name": "Home Improvement"
            },
            {
                "label_id": "25000000000",
                "label_name": "Games"
            },
            {
                "label_id": "28000000000",
                "label_name": "Sports & Outdoor"
            },
            {
                "label_id": "11000000000",
                "label_name": "Vehicle & Transportation"
            }
        ]
    }
}
(/code)
```
