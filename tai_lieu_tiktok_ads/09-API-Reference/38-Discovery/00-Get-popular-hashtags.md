---
title: "Get popular hashtags"
doc_id: 1825119032526849
path: "API Reference / Discovery / Get popular hashtags"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get popular hashtags

Use this endpoint to get a list of the top 200 popular hashtags on TikTok. 

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/discovery/trending_list/

**Method** GET

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://business-api.tiktok.com/portal/docs?id=1738373164380162).|
```

**Parameters**

``` xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|--- |--- |--- |
|advertiser_id {Required}|string|Advertiser ID.<br><br>Pass one of the advertiser IDs returned via `advertiser_ids` from [/oauth2/access_token/](https://business-api.tiktok.com/portal/docs?id=1738373164380162).|
|discovery_type {Required}|string|The type of discovery item.<br><br>Currently, we only support `HASHTAG`.|
|country_code|string|Location code.<br><br>Default value: `US`. <br><br>See [Location code](https://business-api.tiktok.com/portal/docs?id=1737585867307010) for the supported values.|
|category_name|string|The name of the hashtag industry category.<br><br>To learn about the enum values, see <a href="#item-link-Hashtag industry category names">Hashtag industry category names</a>. You can pass either a level-1 category name or a level-2 category name.<br><br>Default value: `ALL`.<br><br>Example: `FOOD_AND_BEVERAGE`.<br><br>If you pass `category_name` and `industry_id` at the same time, `industry_id` will be ignored.|
|date_range|string|The timeframe in which the items have been popular.<br><br>Enum values:<ul><li>`1DAY`: last 1 day.</li><li>`7DAY`: last 7 days.</li><li>`30DAY`: last 30 days.</li><li>`120DAY`: last 120 days.</li></ul><br>Default value: `7DAY`. |
```
### Hashtag industry category names

The following table lists the level-1 and level-2 hashtag industry category names that you can pass to `category_name`.

<table>
  <thead>
    <tr>
      <th style="width:25%">Level-1 category name</th>
      <th style="width:25%">Description</th>
      <th style="width:25%">Level-2 category name</th>
      <th style="width:25%">Description</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td><code>ALL</code></td>
      <td>Content covering all kinds of topics.</td>
      <td></td>
      <td></td>
    </tr>
    <tr>
      <td rowspan="2"><code>APPAREL_AND_ACCESSORIES</code></td>
      <td rowspan="2">Content showcasing the latest trends, fashion tips, style guides, and recommendations for apparel and accessories.</td>
      <td><code>OTHER_FASHION</code></td>
      <td>Content featuring fashion-related topics that don't fit into specific categories.</td>
    </tr>
    <tr>
      <td><code>OUTFITS</code></td>
      <td>Content featuring different outfits, styling tips, and fashion inspiration.</td>
    </tr>
    <tr>
      <td><code>BABY_KIDS_AND_MATERNITY</code></td>
      <td>Content providing parenting advice, tips for child development, and recommendations for baby and kids' products.</td>
      <td><code>BABY_CARE</code></td>
      <td>Content focusing on baby care, parenting advice, and baby product recommendations.</td>
    </tr>
    <tr>
      <td rowspan="4"><code>BEAUTY_AND_PERSONAL_CARE</code></td>
      <td rowspan="4">Content featuring beauty tutorials, skincare routines, makeup tips, product reviews, and discussions on personal care routines.</td>
      <td><code>BEAUTY</code></td>
      <td>Content related to beauty, including beauty hacks, beauty trends, and beauty transformations.</td>
    </tr>
    <tr>
      <td><code>BEAUTY_SERVICES</code></td>
      <td>Content showcasing beauty services such as spa treatments, salon makeovers, and beauty consultations.</td>
    </tr>
    <tr>
      <td><code>HAIR_CARE</code></td>
      <td>Content related to hair care routines, hairstyles, and hair product recommendations.</td>
    </tr>
    <tr>
      <td><code>OTHER_BEAUTY</code></td>
      <td>Content featuring beauty-related topics that don't fit into specific categories.</td>
    </tr>
    <tr>
      <td><code>BUSINESS_SERVICES</code></td>
      <td>Content relating to various careers and professions, including insights into the daily work of certain professions, requirements to enter a particular field, job hunting and career development tips, and advice for advancing in the workplace.</td>
      <td><code>CAREER_AND_PROFESSIONAL_GROWTH</code></td>
      <td>Content related to the routine of certain professions, career development, professional growth, job search tips, and advice for advancing in the workplace.</td>
    </tr>
    <tr>
      <td rowspan="5"><code>EDUCATION</code></td>
      <td rowspan="5">Content encompassing a wide range of educational topics, including academic resources, college-related discussions, history and literature insights, learning and development strategies, and science-related content.</td>
      <td><code>ACADEMIC_RESOURCES</code></td>
      <td>Content focusing on educational resources, study guides, online courses, and academic tips for various subjects.</td>
    </tr>
    <tr>
      <td><code>COLLEGE</code></td>
      <td>Content related to college life, including study hacks, college admissions, and tips for navigating college experiences.</td>
    </tr>
    <tr>
      <td><code>HISTORY_AND_LITERATURE</code></td>
      <td>Content exploring historical events, literature reviews, book recommendations, and discussions on literary works.</td>
    </tr>
    <tr>
      <td><code>LEARNING_AND_DEVELOPMENT</code></td>
      <td>Content focusing on learning and personal development, including educational videos, self-improvement tips, and lifelong learning resources.</td>
    </tr>
    <tr>
      <td><code>SCIENCE</code></td>
      <td>Content focusing on scientific concepts, experiments, scientific discoveries, and explanations of natural phenomena.</td>
    </tr>
    <tr>
      <td><code>FINANCIAL_SERVICES</code></td>
      <td>Content focusing on personal finance, investment advice, financial planning, and discussions on financial services.</td>
      <td><code>BUSINESS_AND_FINANCE</code></td>
      <td>Content related to financial management, investment advice, and market trends.</td>
    </tr>
    <tr>
      <td rowspan="5"><code>FOOD_AND_BEVERAGE</code></td>
      <td rowspan="5">Content showcasing recipes, cooking tutorials, culinary tips, food culture, restaurant reviews, and discussions on food and beverages.</td>
      <td><code>COOKING</code></td>
      <td>Content showcasing cooking tutorials, recipe demonstrations, and cooking hacks.</td>
    </tr>
    <tr>
      <td><code>DRINKS</code></td>
      <td>Content showcasing different types of beverages, cocktail recipes, brewing techniques, and information about drinks.</td>
    </tr>
    <tr>
      <td><code>EATING_EXPERIENCES</code></td>
      <td>Content highlighting unique eating experiences, food challenges, and culinary adventures.</td>
    </tr>
    <tr>
      <td><code>FOOD</code></td>
      <td>Content related to food, including cooking techniques, food facts, and food culture.</td>
    </tr>
    <tr>
      <td><code>FOOD_RECOMMENDATIONS</code></td>
      <td>Content providing recommendations and reviews of food products, restaurants, food delivery services, and culinary events.</td>
    </tr>
    <tr>
      <td rowspan="2"><code>GAMES</code></td>
      <td rowspan="2">Content focusing on card games, board games, video games, game reviews, gameplay footage, and discussions on gaming industry trends.</td>
      <td><code>CARD_AND_BOARD_GAMES</code></td>
      <td>Content related to card games, board games, game tutorials, strategies, and discussions on gaming culture.</td>
    </tr>
    <tr>
      <td><code>VIDEO_GAMES</code></td>
      <td>Content related to video games, including game reviews, gameplay footage, and gaming tips.</td>
    </tr>
    <tr>
      <td><code>HEALTH</code></td>
      <td>Content promoting health and wellness, fitness tips, disease prevention, mental well-being, and discussions on maintaining a healthy lifestyle.</td>
      <td><code>HEALTH_AND_WELLNESS</code></td>
      <td>Content promoting health and wellness, fitness tips, healthy lifestyle choices, mental well-being, and self-care practices.</td>
    </tr>
    <tr>
      <td><code>HOME_IMPROVEMENT</code></td>
      <td>Content providing home improvement ideas, DIY projects, interior design tips, and discussions on improving living spaces.</td>
      <td><code>HOME_AND_GARDEN</code></td>
      <td>Content focusing on home improvement, interior design ideas, and gardening tips.</td>
    </tr>
    <tr>
      <td><code>HOUSEHOLD_PRODUCTS</code></td>
      <td>Content featuring reviews and recommendations for household products.</td>
      <td><code>TOYS_COLLECTIBLES_AND_MEMORABILIA</code></td>
      <td>Content showcasing toys, collectibles, memorabilia reviews, unboxing videos, and discussions on toy culture.</td>
    </tr>
    <tr>
      <td rowspan="6"><code>LIFE_SERVICES</code></td>
      <td rowspan="6">Content encompassing a wide range of life services, including crafting and creations, diary and vlog content, discussions on the environment, family-related topics, photography tips and techniques, as well as productivity and efficiency strategies for managing daily life tasks.</td>
      <td><code>CRAFTING_AND_CREATIONS</code></td>
      <td>Content focusing on crafting, DIY projects, art techniques, creative ideas, and handmade creations.</td>
</tr>
<tr>
  <td><code>DIARY_AND_VLOG</code></td>
  <td>Content showcasing personal diaries, vlogs, and daily life updates.</td>
</tr>
<tr>
  <td><code>ENVIRONMENT</code></td>
  <td>Content focusing on environmental issues, eco-friendly practices, and sustainability.</td>
</tr>
<tr>
  <td><code>FAMILY</code></td>
  <td>Content related to family life, and family activities.</td>
</tr>
<tr>
  <td><code>PHOTOGRAPHY</code></td>
  <td>Content related to photography, including photography tips, camera reviews, and photo editing techniques.</td>
</tr>
<tr>
  <td><code>PRODUCTIVITY_AND_EFFICIENCY</code></td>
  <td>Content providing productivity hacks, time management strategies, organization tips, and tools for efficiency improvement.</td>
</tr>
<tr>
  <td rowspan="18"><code>NEWS_AND_ENTERTAINMENT</code></td>
  <td rowspan="18">Content covering a wide range of news and entertainment topics, such as popular culture, arts, performances, movies, TV shows, music, and sports.</td>
  <td><code>ANIMATION_AND_COMICS</code></td>
  <td>Content featuring animations, animated shorts, comic book discussions, graphic novel reviews, and animated storytelling.</td>
</tr>
<tr>
  <td><code>ART</code></td>
  <td>Content showcasing artwork, art tutorials, and artistic expressions.</td>
</tr>
<tr>
  <td><code>COMEDY</code></td>
  <td>Content focusing on comedy skits, jokes, and humorous content.</td>
</tr>
<tr>
  <td><code>COMMUNITY_UPDATES_AND_ISSUES</code></td>
  <td>Content focusing on community updates, local news, social issues, and discussions on community matters.</td>
</tr>
<tr>
  <td><code>CULTURE</code></td>
  <td>Content exploring different cultures, traditions, and customs from around the world, including festivals, rituals, and cultural practices.</td>
</tr>
<tr>
  <td><code>DANCE</code></td>
  <td>Content featuring dance performances, choreography, and dance tutorials.</td>
</tr>
<tr>
  <td><code>ENTERTAINMENT_AND_SPORTS_NEWS</code></td>
  <td>Content providing updates on entertainment industry news, celebrity gossip, and sports news and highlights.</td>
</tr>
<tr>
  <td><code>MISCELLANEOUS_PERFORMANCES</code></td>
  <td>Content showcasing various forms of performances, talent shows, live events, and unique artistic expressions.</td>
</tr>
<tr>
  <td><code>MOVIES_AND_TV</code></td>
  <td>Content related to movies and television, including reviews, behind-the-scenes footage, trailers, and discussions on film and TV shows.</td>
</tr>
<tr>
  <td><code>MUSIC</code></td>
  <td>Content related to music, including music performances, covers, and music news.</td>
</tr>
<tr>
  <td><code>PERFORMING_ARTS</code></td>
  <td>Content featuring performances in theater, and other forms of performing arts.</td>
</tr>
<tr>
  <td><code>PERSONAL_PHOTOGRAPHY</code></td>
  <td>Content showcasing personal photography projects, photo journals, and visual storytelling from a personal perspective.</td>
</tr>
<tr>
  <td><code>ROMANCE</code></td>
  <td>Content related to romance, dating tips, relationship advice, and love stories.</td>
</tr>
<tr>
  <td><code>SENSORY_EXPERIENCES</code></td>
  <td>Content providing sensory experiences, ASMR videos, relaxation techniques, and immersive audio-visual content.</td>
</tr>
<tr>
  <td><code>SHOWS_FESTIVALS_AND_EXHIBITIONS</code></td>
  <td>Content featuring shows, festivals, and exhibitions, including coverage and highlights.</td>
</tr>
<tr>
  <td><code>SPECIAL_EFFECTS</code></td>
  <td>Content showcasing special effects in movies, visual effects breakdowns, CGI tutorials, and discussions on film technology.</td>
</tr>
<tr>
  <td><code>SPORTS</code></td>
  <td>Content related to various sports, including sports highlights, match analysis, sports news, and discussions on sporting events.</td>
</tr>
<tr>
  <td><code>SUPERNATURAL_AND_HORROR</code></td>
  <td>Content focusing on supernatural phenomena, paranormal activities, horror stories, ghost hunting, and discussions on the supernatural genre.</td>
</tr>
<tr>
  <td rowspan="3"><code>PETS</code></td>
  <td rowspan="3">Content covering a wide range of pet-related topics, including information, tips, and advice on caring for different types of pets and livestock farming.</td>
  <td><code>PET_CARE_AND_ACCESSORIES</code></td>
  <td>Content providing tips and advice on pet care, grooming techniques, training methods, and recommendations for pet accessories.</td>
</tr>
<tr>
  <td><code>LIVESTOCK_AND_FARMING</code></td>
  <td>Content focusing on livestock farming practices, animal husbandry, agricultural techniques, and discussions on farming-related topics.</td>
</tr>
<tr>
  <td><code>WILDLIFE_AND_EXOTIC_PETS</code></td>
  <td>Content featuring wildlife documentaries, exotic pet care guides, wildlife conservation efforts, and discussions on diverse animal species.</td>
</tr>
<tr>
  <td rowspan="5"><code>SPORTS_AND_OUTDOOR</code></td>
  <td rowspan="5">Content related to sports and outdoor activities, including sports highlights, fitness tips, and discussions on sporting events.</td>
  <td><code>CONVENTIONAL_AND_MAINSTREAM_SPORTS</code></td>
  <td>Content showcasing popular and mainstream sports, including football, basketball, baseball, tennis, golf, rugby, and so on.</td>
</tr>
<tr>
  <td><code>EXERCISE_AND_FITNESS</code></td>
  <td>Content providing exercise routines, workout tips, fitness challenges, and discussions on maintaining a healthy lifestyle.</td>
</tr>
<tr>
  <td><code>EXTREME_AND_ADVENTURE_SPORTS</code></td>
  <td>Content showcasing extreme sports activities, adventure travel, adrenaline-pumping experiences, and discussions on extreme sports culture.</td>
</tr>
<tr>
  <td><code>LEISURE_AND_RECREATION_FACILITIES</code></td>
  <td>Content featuring leisure and recreation facilities, such as amusement parks, water parks, resorts, and discussions on recreational activities.</td>
</tr>
<tr>
  <td><code>OUTDOOR_RECREATION</code></td>
  <td>Content related to outdoor recreational activities such as hiking, camping, hunting, fishing, or water sports, including tips, gear reviews, and adventure stories.</td>
</tr>
<tr>
  <td rowspan="2"><code>TECHNOLOGY</code></td>
  <td rowspan="2">Content featuring gadget reviews, technical tutorials, innovations in technology, and discussions on the technical industry.</td>
  <td><code>GADGETS_AND_INNOVATION</code></td>
  <td>Content showcasing the latest gadgets, tech reviews, innovative inventions, and discussions on technological advancements.</td>
</tr>
<tr>
  <td><code>SOFTWARE_AND_APPS</code></td>
  <td>Content featuring reviews, demonstrations, and tutorials on software and mobile apps, including productivity tools, creative software, and gaming apps.</td>
</tr>
<tr>
  <td><code>TRAVEL</code></td>
  <td>Content providing travel guides, destination recommendations, travel tips, itineraries, and discussions on travel experiences.</td>
  <td><code>GENERAL_TRAVEL</code></td>
  <td>Content focusing on travel, including destination guides, travel tips, and recommendations for accommodation, transportation, and attractions.</td>
</tr>
<tr>
  <td rowspan="2"><code>VEHICLE_AND_TRANSPORTATION</code></td>
  <td rowspan="2">Content related to vehicles and transportation, including car reviews, maintenance tips, road trips, and discussions on transportation options.</td>
  <td><code>AUTOMOTIVE</code></td>
  <td>Content related to the automotive industry, including car reviews, test drives, automotive news, and discussions on automotive technology.</td>
  </tr>
  <tr>
    <td><code>OTHER_TRANSPORTATION</code></td>
    <td>Content related to transportation modes other than conventional automobiles, such as motorcycles, bicycles, public transportation, and discussions on alternative transportation options.</td>
  </tr>
</tbody></table>



### Example
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/discovery/trending_list/?advertiser_id={{advertiser_id}}&discovery_type=HASHTAG&category_name=ALL&country_code=US&date_range=7DAY' \
--header 'Access-Token: {{Access-Token}}' \
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
#|filter_info|object|Filtering information that is specified in your request.<br><br>If you don't include the following parameters in the request, we will return the default values for them, which are `date_range = 7DAY`, `country_code = US`, `industry_id = ALL`, and `category_name = ALL`.|
##|date_range|string|The timeframe in which the items have been popular.<br><br>Enum values:<ul><li>`1DAY`: last 1 day.</li><li>`7DAY`: last 7 days.</li><li>`30DAY`: last 30 days.</li><li>`120DAY`: last 120 days.</li></ul>|
##|country_code|string|Location code.<br><br>See [Location code](https://business-api.tiktok.com/portal/docs?id=1737585867307010) for the enum values.<br><br>Example: `DE`.|
##|category_name|string|The name of the hashtag industry category.<br><br>To learn about the enum values, see <a href="#item-link-Hashtag industry category names">Hashtag industry category names</a>.<br><br>Example: `FOOD_AND_BEVERAGE`.|
#|list|object[]|The list of popular hashtags.|
##|hashtag_id|string|Hashtag ID.<br><br>Example: `4268426520694`.|
##|hashtag_name|string|Hashtag name.<br><br>Example: `yummy`.|
##|rank_position|string|Current rank position of the hashtag among hashtags within the same category (`category_name`) or industry (`industry_id`) in the selected region (`country_code`).<br><br>Example: `100`.|
##|rank_change|string|The change in rank position compared to the rank in the last time period.<br><br><ul><li>A positive `rank_change` indicates an increase in rank. For example, if the rank position shifts from 5 to 1, `rank_change` will be `4`.</li><li>A negative `rank_change` indicates a decrease in rank. For example, if the rank position shifts from 1 to 5, `rank_change` will be `-4`.</li><li>A `rank_change` of `0` indicates no changes in rank.</li><li>A `rank_change` marked as `NEW` indicates that the hashtag was not present in the top 200 ranking list in the last time period.</li></ul> |
##|views|integer|The number of views for related videos that feature this hashtag in the selected region and timeframe.<br><br><p><span style="color:darkred"><b>Note</b></span>: If you fail to specify `country_code` or `date_range` in your request, we will return data based on the default values for these two parameters, which are `date_range = 7DAY` or `country_code = US`.<br><br>Example: 134333716.|
##|views_global_lifetime|integer|The number of views for related videos that feature this hashtag, globally and across their whole lifetime.<br><br>Example: 91639649280.|
##|posts|integer|The number of posts for related videos that feature this hashtag in the selected region and timeframe.<br><br><p><span style="color:darkred"><b>Note</b></span>: If you fail to specify `country_code` or `date_range` in your request, we will return data based on the default values for these two parameters, which are `date_range = 7DAY` or `country_code = US`.<br><br>Example: 21856.|
##|posts_global_lifetime|integer|The number of posts for related videos that feature this hashtag, globally and across their whole lifetime.<br><br>Example: 7846729.|
##|top_country_list|string[]|The location codes of the top five locations in the world where this hashtag is most popular.<br><br>To learn about the location that a location code represents, see [Location code](https://business-api.tiktok.com/portal/docs?id=1737585867307010).<br><br>Example: `["US", "GB", "PH", "CA", "DE"]`.|
##|trending_history|object[]|The historical ranking data for the hashtag, spanning up to the past 30 days..|
###|date|string|Date, in the format of `YYYY-MM-DD`.<br><br>Example: `2024-01-01`.|
###|rank_position_daily|string|The rank position of the hashtag among hashtags across all categories or industries in the selected region (`country_code`) on the date.<br><br>Example: `73`.<br><br><p><span style="color:darkred"><b>Note</b></span>: If the hashtag does not perform well compared to the last time period, the hashtag will not be considered a trending hashtag and the value of this field will be null.|
###|views_daily|integer|The number of views for related videos that feature this hashtag in the selected region (`country_code`) on the date.<br><br>Example: 21215985.<br><br><p><span style="color:darkred"><b>Note</b></span>: If the daily views are less than 1,000, the value of this field will be null.|
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
        "list": [
            {
                "posts": 44585,
                "rank_change": "0",
                "hashtag_id": "{{hashtag_id}}",
                "hashtag_name": "{{hashtag_name}}",
                "views_global_lifetime": 136840398711,
                "posts_global_lifetime": 26055679,
                "rank_position": "1",
                "views": 836896026,
                "top_country_list": [
                    "US",
                    "TH",
                    "ID",
                    "VN",
                    "PH"
                ],
                 "trending_history": [
                {
                "date": "2024-03-09",
                "rank_position_daily": "121",
                "views_daily": 39181186
                },
                ...
                {
                "date": "2024-04-06",
                "rank_position_daily": "85",
                "views_daily": 49419265
                },
                {
                "date": "2024-04-07",
                "rank_position_daily": "128",
                "views_daily": 45524768
                }
             ]
            },
            {
                "posts": 148372,
                "rank_change": "0",
                "hashtag_id": "{{hashtag_id}}",
                "hashtag_name": "fashion",
                "views_global_lifetime": 303784465772,
                "posts_global_lifetime": 39054276,
                "rank_position": "2",
                "views": 508388428,
                "top_country_list": [
                    "US",
                    "VN",
                    "ID",
                    "MX",
                    "GB"
                ],
                "trending_history": [
                {
                "date": "2024-03-09",
                "rank_position_daily": "121",
                "views_daily": 39181186
                },
                ...
                {
                "date": "2024-04-06",
                "rank_position_daily": "85",
                "views_daily": 49419265
                },
                {
                "date": "2024-04-07",
                "rank_position_daily": "128",
                "views_daily": 45524768
                }
             ]
            },
            {
            ...
            }
        ],
        "filter_info": {
            "country_code": "US",
            "date_range": "7DAY",
            "category_name": "ALL"
        }
    }
}
(/code)
```
