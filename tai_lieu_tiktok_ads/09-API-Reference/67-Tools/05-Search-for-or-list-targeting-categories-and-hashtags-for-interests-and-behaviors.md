---
title: "Search for or list targeting categories and hashtags for interests and behaviors"
doc_id: 1796638342793218
path: "API Reference / Tools / Search for or list targeting categories and hashtags for interests and behaviors"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Search for or list targeting categories and hashtags for interests and behaviors

Use this endpoint to search for targeting categories and hashtags related to interests and behaviors based on seed keywords. You can also use it to list all available targeting categories and hashtags for interests and behaviors without specifying seed keywords.

<!-- ## Workflow Overview
To effectively find the targeting categories or hashtags tailored to your specific use case, follow the workflow outlined in the following diagram:

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/d9a6f61de79e5858178cfdc7cd6b05ab" alt="Workflow for search foring or listing targeting categories and hashtags for interests and behaviors " width="100%" style="color:#808080" />-->


## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/targeting/search/

**Method** GET

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
```

**Parameters**

``` xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|--- |--- |--- |
| advertiser_id {Required} | string | Advertiser ID. |
| targeting_type {Required} | string | The primary targeting type. <br><br>Enum value: `INTEREST_AND_BEHAVIOR` (Interests & Behaviors). |
| sub_targeting_types {+Conditional} | string[] | Required when `targeting_type` is `INTEREST_AND_BEHAVIOR` and multiple keywords are passed to `search_keywords`. <br><br>The secondary targeting types. <br><br>Enum values: <ul><li>`GENERAL_INTEREST`: general interests. </li><li>`ADDITIONAL_INTEREST`: additional interests. </li><li> `PURCHASE_INTENTION`: purchase intention. </li><li> `VIDEO_INTERACTION`: video interactions. </li><li>`CREATOR_INTERACTION`: creator interactions. </li><li>`HASHTAG_INTERACTION`: hashtag interactions.</li></ul> <br><ul><li> If `search_keywords` is not passed, the allowed values are `GENERAL_INTEREST`, `PURCHASE_INTENTION`, `VIDEO_INTERACTION`, `CREATOR_INTERACTION`, `HASHTAG_INTERACTION`.<ul><li> If you don't pass `sub_targeting_types` in such cases, the default value for `sub_targeting_types` will be ` ["GENERAL_INTEREST","PURCHASE_INTENTION","VIDEO_INTERACTION","CREATOR_INTERACTION","HASHTAG_INTERACTION"]`.</li></ul></li><li> If `search_keywords` is passed and contains one single keyword, the allowed values are `GENERAL_INTEREST`, `ADDITIONAL_INTEREST`, `PURCHASE_INTENTION`, `VIDEO_INTERACTION`, `CREATOR_INTERACTION`, `HASHTAG_INTERACTION`. <ul><li> If you don't pass `sub_targeting_types` in such cases, the default value for `sub_targeting_types` will be ` ["GENERAL_INTEREST","ADDITIONAL_INTEREST","PURCHASE_INTENTION","VIDEO_INTERACTION","CREATOR_INTERACTION","HASHTAG_INTERACTION"]`.</li></ul></li><li> If `search_keywords` is passed and contains more than one keyword, the allowed values are `ADDITIONAL_INTEREST`, `HASHTAG_INTERACTION`.<ul><li> In such cases, you need to manually pass `sub_targeting_types`. </li></ul></li></ul>|
| search_keywords | string[] | A list of seed keywords that you provide to search for targeting categories or hashtags. <br><br>Max size: 10. The length limit for each keyword is 100 characters. <br><br>If you pass multiple seed keywords to this field, ensure the value of `sub_targeting_types` includes only `ADDITIONAL_INTEREST` and `HASHTAG_INTERACTION`. <br><br><p><span style="color:darkred"><b>Note</b></span>: Searching for hashtag bundles is not supported. Therefore, if you include `HASHTAG_INTERACTION` in the value of `sub_targeting_types` and pass `search_keywords`, you can only get hashtags from the returned `search_result` within the `hashtag_interaction` object. To retrieve hashtag bundles, include `HASHTAG_INTERACTION` in the value of `sub_targeting_types` and do not pass `search_keywords`. |
| language | string | The language of the seed keywords. <br><br> Default value: `en`. <br><br>To find the allowed values, see <a href="#item-link-List of values for language">List of values for `language`</a>. <br><br><p><span style="color:darkred"><b>Note</b></span>: If you include `HASHTAG_INTERACTION` in the value of `sub_targeting_types` and pass one or more non-English seed keywords to `search_keywords`, hashtags in multiple languages might be returned, even if you specify `language`. This is because searching for hashtags based on a single language is not supported and some characters or letters are shared among certain languages. |
| filtering | object | Filtering conditions. |
#| special_industries | string[] | Special ad categories. <br>By selecting a housing, employment, or credit category for your campaign, you certify not to use TikTok to discriminate based on protected characteristics in ads relating to housing, employment, or credit and to adhere to [TikTok's special ad category policy](https://ads.tiktok.com/help/article/housing-employment-credit-hec-ad-policy?redirected=2). <br><br> Enum values: <ul><li>`HOUSING`: Ads for real estate listings, homeowners’ insurance, mortgage loans or other related opportunities. </li><li> `EMPLOYMENT`: Ads for job offers, internships, professional certification programs or other related opportunities. </li><li>`CREDIT`: Ads for credit card offers, auto loans, long-term financing or other related opportunities.</li></ul><br>Default value: `[]`. By default, special ad categories are not filtered. <br><br>If you pass multiple ad categories to this field, the results will include targeting categories or hashtags that support any of the specified ad categories. |
```

### List of values for `language`
```xtable
| Value{20%} | Description{60%}  |
|---|---|
| `ar` | Arabic |
| `cs` | Czech |
| `de` | German |
| `en` | English |
| `es` | Spanish |
| `fr` | French |
| `id` | Indonesian |
| `it` | Italian |
| `ja` | Japanese |
| `ko` | Korean |
| `ms` | Malay |
| `pl` | Polish |
| `pt` | Portuguese |
| `ru` | Russian |
| `sv` | Swedish |
| `th` | Thai |
| `tr` | Turkish |
| `vi` | Vietnamese |
| `zh` | Chinese |
```

### Example
```xcodeblock
(code curl http)
curl --location  --request GET 'https://business-api.tiktok.com/open_api/v1.3/targeting/search/?advertiser_id={{advertiser_id}}&targeting_type=INTEREST_AND_BEHAVIOR' \
--header 'Access-Token: {{Access-Token}}' \
(/code)
```
## Response

``` xtable
|Field{30%}|Type{17%}|Description{53%}|
|-|-|-|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix-Return Code](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|message|string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|request_id |string|The log ID of a request, which uniquely identifies the request.  |
|data |object|Returned data.|
#| general_interest | object | The list of general interest categories. |
##| list_result | object[] | The list of all available general interest categories. <br><br>If `search_keywords` is specified and the value of `sub_targeting_types` includes `GENERAL_INTEREST` in the request, the value of this field will be null. |
###| sub_targeting_type| string | The secondary targeting type. <br><br>Enum value: `GENERAL_INTEREST` (general interests). |
###| id | string | The ID of the general interest category. <br><br>You can pass this value to `interest_category_ids` when creating an ad group using [/adgroup/create/](https://business-api.tiktok.com/portal/docs?id=1739499616346114). |
###| name | string | The name of the general interest category. |
###|supported_special_industries | string[] | The special ad categories that the general interest category can be used together with. <br><br>Enum values: <ul><li> `HOUSING`: Ads for real estate listings, homeowners’ insurance, mortgage loans or other related opportunities.</li><li> `EMPLOYMENT`: Ads for job offers, internships, professional certification programs or other related opportunities.</li> <li>`CREDIT`: Ads for credit card offers, auto loans, long-term financing or other related opportunities. </li></ul> |
###| level | integer | The level of the general interest category. |
###|children_ids | string[] | The IDs of the children general interest categories for the current category. |
###|hashtag_type | string | Hashtag type. <br><br>This field will always be null because the hashtag type is for hashtag interactions scenarios only. |
##| search_result | map(string<>object[]) | The list of general interest categories that are generated based on the specified `search_keywords`. <br><br><ul><li> Key: A seed keyword in the `search_keywords` of the request. <ul><li> Example: `"child"`.</li></ul></li><li> Value: An object array representing the generated general interest categories, following the structure of `list_result`.<ul><li> Example: `&#91;&#123;"sub_targeting_type": "GENERAL_INTEREST", "id": "10100","name": "Early Childhood & Preschool Education", "supported_special_industries": &#91;"HOUSING", "EMPLOYMENT", "CREDIT"&#93;"children_ids": null,"level": null, "hashtag_type": null&#125;&#93;`. </li></ul></li></ul> You can pass the value of id to `interest_category_ids` when creating an ad group using [/adgroup/create/](https://business-api.tiktok.com/portal/docs?id=1739499616346114). |
#| additional_interest | object | The list of additional interest categories. <br><br>If `search_keywords` is not passed in the request, the value of this field will be null. |
##| search_result | map(string<>object[]) | The list of additional interest categories that are generated based on the specified `search_keywords`. <br><br><ul><li> Key: A seed keyword in the `search_keywords` of the request. <ul><li> Example: `"child"`.</li></ul></li><li> Value: An object array representing the generated additional interest categories, following the structure of `list_result` within the `general_interest` object. Note that the value of `sub_targeting_type` will always be `ADDITIONAL_INTEREST`.<ul><li> Example: `&#91;&#123;"sub_targeting_type": "ADDITIONAL_INTEREST", "id": "1915593069","name": "Childhood", "supported_special_industries": &#91;&#93;,"children_ids": null,"level": null, "hashtag_type": null&#125;&#93;`.</li></ul></li></ul>You can pass the value of id to `interest_keyword_ids` when creating an ad group using [/adgroup/create/](https://business-api.tiktok.com/portal/docs?id=1739499616346114). |
#| purchase_intention | object | The list of purchase intention categories. |
##| list_result | object[] | The list of all available purchase intention categories. <br><br> If `search_keywords` is specified and the value of `sub_targeting_types` includes `PURCHASE_INTENTION` in the request, the value of this field will be null. |
###| sub_targeting_type | string | The secondary targeting type. <br><br> Enum value: `PURCHASE_INTENTION` (purchase intention). |
###| id | string | The ID of the purchase intention category. <br><br>You can pass this value to `purchase_intention_keyword_ids` when creating an ad group using [/adgroup/create/](https://business-api.tiktok.com/portal/docs?id=1739499616346114). |
###| name | string | The name of the purchase intention category. |
###|supported_special_industries | string[] | The special ad categories that the purchase intention category can be used together with. <br><br>Enum values: <ul><li> `HOUSING`: Ads for real estate listings, homeowners’ insurance, mortgage loans or other related opportunities.</li><li> `EMPLOYMENT`: Ads for job offers, internships, professional certification programs or other related opportunities.</li> <li>`CREDIT`: Ads for credit card offers, auto loans, long-term financing or other related opportunities. </li></ul> |
###| level | integer | The level of the purchase intention category. |
###| children_ids | string[] | The IDs of the children purchase intention categories for the current category. |
###| hashtag_type | string | Hashtag type. <br><br> This field will always be null because the hashtag type is for hashtag interactions scenarios only. |
##| search_result | map(string<>object[]) | The list of purchase intention categories that are generated based on the specified `search_keywords`. <br><br><ul><li> Key: A seed keyword in the `search_keywords` of the request.<ul><li> Example: `"child"`.</li></ul></li><li> Value: An object array representing the generated purchase intention categories, following the structure of `list_result`.<ul><li> Example: `&#91;&#123; "sub_targeting_type": "PURCHASE_INTENTION", "id": "12109","name": "Children's Apparel", "supported_special_industries": &#91;&#93;,"children_ids": null, "level": null, "hashtag_type": null&#125;&#93;`. </li></ul></li></ul>You can pass the value of id to `purchase_intention_keyword_ids` when creating an ad group using [/adgroup/create/](https://business-api.tiktok.com/portal/docs?id=1739499616346114). |
#| video_interaction | object | The list of video interactions categories. |
##| list_result | object[] | The list of all available video interactions categories. <br><br>If `search_keywords` is specified and the value of `sub_targeting_types` includes `VIDEO_INTERACTION` in the request, the value of this field will be null. |
###| sub_targeting_type| string | The secondary targeting type. <br><br>Enum values: `VIDEO_INTERACTION` (video interactions). |
###| id | string | The ID of the video interactions category. <br><br>You can pass this value to `action_category_ids` when creating an ad group using [/adgroup/create/](https://business-api.tiktok.com/portal/docs?id=1739499616346114) and `action_scene` is set to `VIDEO_RELATED`. |
###| name | string | The name of the video interactions category. |
###|supported_special_industries | string[] | The special ad categories that the video interactions category can be used together with. <br><br>Enum values: <ul><li> `HOUSING`: Ads for real estate listings, homeowners’ insurance, mortgage loans or other related opportunities.</li><li> `EMPLOYMENT`: Ads for job offers, internships, professional certification programs or other related opportunities.</li> <li>`CREDIT`: Ads for credit card offers, auto loans, long-term financing or other related opportunities. </li></ul> |
###| level | integer | The level of the video interactions category. |
###| children_ids | string[] | The IDs of the children video interactions categories for the current category. |
###| hashtag_type | string | Hashtag type. <br><br>This field will always be null because the hashtag type is for hashtag interactions scenarios only. |
##| search_result | map(string<>object[]) | The list of video interactions categories that are generated based on the specified `search_keywords`. <br><br><ul> Key: A seed keyword in the `search_keywords` of the request. <li><ul><li> Example: `"fashion"`. </li></ul></li><li> Value: An object array representing the generated video interactions categories, following the structure of `list_result`. <ul><li> Example: `&#91;&#123;"sub_targeting_type": "VIDEO_INTERACTION", "id": "1612","name": "Other Fashion", "supported_special_industries": &#91;"HOUSING","EMPLOYMENT","CREDIT"&#93;,"children_ids": null,"level": null, "hashtag_type": null&#125;&#93;`. </li></ul></li></ul>You can pass the value of id to `action_category_ids` when creating an ad group using [/adgroup/create/](https://business-api.tiktok.com/portal/docs?id=1739499616346114) and `action_scene` is set to `VIDEO_RELATED`. |
#| creator_interaction | object | The list of creator interactions categories. |
##| list_result | object[] | The list of all available creator interactions categories. <br><br>If `search_keywords` is specified and the value of `sub_targeting_types` includes `CREATOR_INTERACTION` in the request, the value of this field will be null. |
###| sub_targeting_type | string | The secondary targeting type. <br><br>Enum value: `CREATOR_INTERACTION` (creator interactions). |
###| id | string | The ID of the creator interactions category. <br><br>You can pass this value to `action_category_ids` when creating an ad group using [/adgroup/create/](https://business-api.tiktok.com/portal/docs?id=1739499616346114) and `action_scene` is set to `CREATOR_RELATED`. |
###| name | string | The name of the creator interactions category. |
###|supported_special_industries | string[] | The special ad categories that the creator interactions category can be used together with. <br><br>Enum values: <ul><li> `HOUSING`: Ads for real estate listings, homeowners’ insurance, mortgage loans or other related opportunities.</li><li> `EMPLOYMENT`: Ads for job offers, internships, professional certification programs or other related opportunities.</li> <li>`CREDIT`: Ads for credit card offers, auto loans, long-term financing or other related opportunities. </li></ul> |
###| level | integer | The level of the creator interactions category. |
###| children_ids | string[] | The IDs of the children creator interactions categories for the current category. |
###| hashtag_type | string | Hashtag type. <br><br>This field will always be null because the hashtag type is for hashtag interactions scenarios only. |
##| search_result | map(string<>object[]) | The list of creator interactions categories that are generated based on the specified `search_keywords`. <br><br><ul><li> Key: A seed keyword in the `search_keywords` of the request.<ul><li> Example: `"fashion"`. </li></ul></li><li> Value: An object array representing the generated creator interactions categories, following the structure of `list_result`.<ul><li> Example: `&#91;&#123;"sub_targeting_type": "CREATOR_INTERACTION", "id": "10","name": "Fashion & Beauty", "supported_special_industries": &#91;"HOUSING","EMPLOYMENT","CREDIT"&#93;,"children_ids": null,"level": null, "hashtag_type": null&#125;&#93;`. </li></ul></li></ul>You can pass the value of id to `action_category_ids` when creating an ad group using [/adgroup/create/](https://business-api.tiktok.com/portal/docs?id=1739499616346114) and `action_scene` is set to `CREATOR_RELATED`. |
#| hashtag_interaction | object | The list of hashtags or hashtag bundles related to hashtag interactions. <br><br> If the value of `sub_targeting_types` includes `HASHTAG_INTERACTION` in the request: <ul><li> When `search_keywords` is passed, only hashtags will be returned. </li><li> When `search_keywords` is not passed, only hashtag bundles will be returned.</li></ul>|
##| list_result | object[] | The list of all available hashtag bundles. <br><br>If `search_keywords` is specified and the value of `sub_targeting_types` includes `HASHTAG_INTERACTION` in the request, the value of this field will be null. |
###| sub_targeting_type | string | The secondary targeting type. <br><br>Enum values: `HASHTAG_INTERACTION` (hashtag interactions). |
###| id | string | The ID of the hashtag bundle. <br><br>You can pass this value to `action_category_ids` when creating an ad group using [/adgroup/create/](https://business-api.tiktok.com/portal/docs?id=1739499616346114) and `action_scene` is set to `HASHTAG_RELATED`. |
###| name | string | The name of the hashtag bundle. |
###|supported_special_industries | string[] | The special ad categories that the hashtag bundle can be used together with. <br><br>Enum values: <ul><li> `HOUSING`: Ads for real estate listings, homeowners’ insurance, mortgage loans or other related opportunities.</li><li> `EMPLOYMENT`: Ads for job offers, internships, professional certification programs or other related opportunities.</li> <li>`CREDIT`: Ads for credit card offers, auto loans, long-term financing or other related opportunities. </li></ul> |
###| level | integer | The level of the hashtag bundle. |
###| children_ids| string[] | The IDs of the children hashtag bundles for the current bundle. |
###| hashtag_type| string | Hashtag type. <br><br>Enum value: `HASHTAG_BUNDLE` (A pre-defined bundle of related hashtags focused on a common theme or event. For example, the "christmas" hashtag bundle can be used to target seasonal hashtags related to Christmas.) |
##| search_result | map(string<>object[]) | The list of hashtags that are generated based on the specified `search_keywords`.<br><br><ul><li> Key: A seed keyword in the `search_keywords` of the request.<ul><li> Example: `"child"`. </li></ul></li><li> Value: An object array representing the generated hashtags, following the structure of `list_result`. Note that the value of `hashtag_type` will always be `HASHTAG` (A standalone hashtag that can be used to target users interested in content associated with that specific hashtag).<ul><li> Example: `&#91;&#123;"sub_targeting_type": "HASHTAG_INTERACTION", "id": "67137517581","name": "#childbirtheducation", "supported_special_industries": &#91; &#93;,"children_ids": null,"level": null, "hashtag_type": "HASHTAG"&#125;&#93;,`.</li></ul></li></ul>You can pass the value of id to `action_category_ids` when creating an ad group using [/adgroup/create/](https://business-api.tiktok.com/portal/docs?id=1739499616346114) and `action_scene` is set to `HASHTAG_RELATED`. |
```


### Example
```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "video_interaction": {
            "list_result": [
                {
                    "id": "1311100",
                    "sub_targeting_type": "VIDEO_INTERACTION",
                    "name": "Scenery & Plants",
                    "children_ids": [],
                    "level": 3,
                    "supported_special_industries": [
                        "HOUSING",
                        "EMPLOYMENT",
                        "CREDIT"
                    ],
                    "hashtag_type": null
                },
                ...
                ],
            "search_result": null
        },
        "general_interest": {
            "list_result": [
                {
                    "id": "18110",
                    "sub_targeting_type": "GENERAL_INTEREST",
                    "name": "Cleaning Supplies",
                    "children_ids": [],
                    "level": 2,
                    "supported_special_industries": [],
                    "hashtag_type": null
                },
                ...
                ],
            "search_result": null
        },
        "creator_interaction": {
            "list_result": [
                {
                    "id": "11",
                    "sub_targeting_type": "CREATOR_INTERACTION",
                    "name": "DIY & Life Hacks",
                    "children_ids": [
                        "11001"
                    ],
                    "level": 1,
                    "supported_special_industries": [
                        "HOUSING",
                        "EMPLOYMENT",
                        "CREDIT"
                    ],
                    "hashtag_type": null
                },
                ...
                ],
            "search_result": null
        },
        "hashtag_interaction": {
            "list_result": [
                {
                    "id": "11",
                    "sub_targeting_type": "HASHTAG_INTERACTION",
                    "name": "cybermonday",
                    "children_ids": [],
                    "level": 1,
                    "supported_special_industries": [],
                    "hashtag_type": "HASHTAG_BUNDLE"
                },
                ...
                ],
            "search_result": null
        },
        "purchase_intention": {
            "list_result": [
                {
                    "id": "26101",
                    "sub_targeting_type": "PURCHASE_INTENTION",
                    "name": "Photography",
                    "children_ids": [],
                    "level": 2,
                    "supported_special_industries": [
                        "HOUSING",
                        "EMPLOYMENT",
                        "CREDIT"
                    ],
                    "hashtag_type": null
                },
                ...
                ],
            "search_result": null
        },
        "additional_interest": null
    },
    "code": 0
}
(/code)
```
