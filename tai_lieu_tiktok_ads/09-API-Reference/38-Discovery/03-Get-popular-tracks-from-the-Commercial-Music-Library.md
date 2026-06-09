---
title: "Get popular tracks from the Commercial Music Library"
doc_id: 1825119063013505
path: "API Reference / Discovery / Get popular tracks from the Commercial Music Library"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get popular tracks from the Commercial Music Library

Use this endpoint to retrieve a list of up to 100 popular tracks that are pre-cleared for use in organic content and ad creation from the Commercial Music Library.


## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/discovery/cml/trending_list/

**Method** GET

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Access token authorized by TikTok creators.<br><br>To obtain an access token, use [/tt_user/oauth2/token/](https://business-api.tiktok.com/portal/docs?id=1738084387220481#item-link-Get%20a%20TikTok%20account%20access%20token).|
```

**Parameters**

``` xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|business_id {Required}|string|Application specific unique identifier for the TikTok account.<br><br>Pass the value of the `open_id` field returned in the response of [/tt_user/oauth2/token/](https://business-api.tiktok.com/portal/docs?id=1738084387220481#item-link-Get%20a%20TikTok%20account%20access%20token) to this field.|
|genre|string|The specific genre to filter the results by.<br><br>Default value: `ALL`. <br><br>For enum values, see <a href="#item-link-List of values for genre">List of values for `genre`</a>.|
|country_code|string|The code of the location to filter the results by.<br><br>Default value: `US`. <br><br>See [Location code](https://business-api.tiktok.com/portal/docs?id=1737585867307010) for the supported values.|
|date_range|string|The timeframe in which the tracks have been popular.<br><br>Enum values:<ul><li>`1DAY`: last one day.</li><li>`7DAY`: last seven days.</li><li>`30DAY`: last 30 days.</li><li>`90DAY`: last 90 days.</li></ul>Default value: `7DAY`.|
```

### List of values for `genre`
``` xtable
| Value{30%} | Description{70%} |
|---|---|
| `ALL` | All |
| `ROCK` | Rock |
| `POP` | Pop |
| `LATIN` | Latin |
| `METAL` | Metal |
| `ELECTRONIC` | Electronic |
| `HIP_HOP/RAP` | Hip Hop/Rap |
| `ALTERNATIVE/INDIE` | Alternative/Indie |
| `FOLK` | Folk |
| `R&B/SOUL` | R&B/Soul |
| `COUNTRY` | Country |
| `CLASSICAL` | Classical |
| `JAZZ` | Jazz |
| `REGGAE` | Reggae |
| `CHILDHOOD` | Childhood |
| `BLUES` | Blues |
| `EASY_LISTENING` | Easy Listening |
| `NEW_AGE` | New Age |
| `WORLD_MUSIC` | World Music |
| `EXPERIMENTAL` | Experimental |
| `DEVOTIONAL` | Devotional |
| `CHINESE_TRADITION` | Chinese Tradition |
| `8_BIT` | 8 Bit |
| `A_CAPPELLA` | A capella |
| `AFRO-POP` | Afro-Pop |
| `ALTERNATIVE_HIP_HOP` | Alternative Hip Hop |
| `ALTERNATIVE_ROCK` | Alternative Rock |
| `AMBIENT` | Ambient |
| `ARABIC_POP` | Arabic Pop |
| `BASS_HOUSE` | Bass House |
| `BGM` | BGM |
| `BOOMBAP` | Boombap |
| `BOSSA_NOVA` | Bossa Nova |
| `BRAZILIAN_FUNK_STYLE` | Brazilian Funk Style |
| `BUDDHIST_MUSIC` | Buddhist Music |
| `CANTOPOP` | Cantopop |
| `CELTIC_POP` | Celtic Pop |
| `CHAMBER_MUSIC` | Chamber Music |
| `CHILL_BEATS` | Chill Beats |
| `CHILLOUT` | Chillout |
| `CHINESE_FOLK` | Chinese Folk |
| `CHINESE_OPERA` | Chinese Opera |
| `CHINESE_POP` | Chinese Pop |
| `CHINESE_STYLE` | Chinese Style |
| `CHINOISERIE_ELECTRONIC` | Chinoiserie Electronic |
| `CHINOISERIE_RAP` | Chinoiserie Rap |
| `CHRISTIAN_MUSIC` | Christian Music |
| `CONTEMPORARY_R&B` | Contemporary R&B |
| `COUNTRY_POP` | Country Pop |
| `DANCE_POP` | Dance Pop |
| `DISCO` | Disco |
| `DJ` | DJ |
| `DRUM&BASS` | Drum&Bass |
| `DUBSTEP` | Dubstep |
| `EDM` | EDM |
| `EDM_TRAP` | EDM Trap |
| `ELECTRO_POP` | Electro Pop |
| `EPIC` | Epic |
| `FOLK_POP` | Folk Pop |
| `FUNK` | Funk |
| `FUTURE_BASS` | Future Bass |
| `GOSPEL` | Gospel |
| `GUFENG_MUSIC` | Gufeng Music |
| `HARD_ROCK` | Hard Rock |
| `HIP_HOUSE` | Hip House |
| `HOLIDAY_MUSIC` | Holiday Music |
| `HOUSE` | House |
| `INDIAN_POP` | Indian Pop |
| `INDIE_FOLK` | Indie Folk |
| `INDIE_POP` | Indie Pop |
| `INDIE_ROCK` | Indie Rock |
| `INSTRUMENTAL_HIP_HOP` | Instrumental Hip Hop |
| `INSTRUMENTAL_ROCK` | Instrumental Rock |
| `IRISH_FOLK` | Irish Folk |
| `J_ROCK` | J-Rock |
| `JAPANESE_TRADITIONAL_MUSIC` | Japanese Traditional Music |
| `JAZZ_FUSION` | Jazz Fusion |
| `JAZZ_HIP_HOP` | Jazz Hip Hop |
| `JAZZ_POP` | Jazz Pop |
| `J-POP` | J-Pop |
| `K-POP` | K-Pop |
| `LATIN_POP` | Latin Pop |
| `LO-FI` | Lo-fi |
| `MC` | MC |
| `NOISE` | Noise |
| `OLD_SCHOOL` | Old School |
| `OTHERS` | Others |
| `POP_RAP` | Pop Rap |
| `POP_ROCK` | Pop Rock |
| `POP_SOUL` | Pop Soul |
| `PSYCHEDELIC_ROCK` | Psychedelic Rock |
| `PUNK` | Punk |
| `R&B_RAP` | R&B/Rap |
| `REGGAETON` | Reggaeton |
| `RUSSIAN_POP` | Russian Pop |
| `SERTANEJO` | Sertanejo |
| `SON_CUBANO` | Son Cubano |
| `SOUL` | Soul |
| `SOUNDTRACK` | Soundtrack |
| `SYMPHONY` | Symphony |
| `SYNTH_POP` | Synth Pop |
| `TANGO` | Tango |
| `TECHNO` | Techno |
| `TEEN_POP` | Teen Pop |
| `TRADITIONAL_CHINESE_FOLK` | Traditional Chinese Folk |
| `TRANCE` | Trance |
| `TRAP_RAP` | Trap Rap |
| `TRIP_HOP` | Trip Hop |
| `TROPICAL_HOUSE` | Tropical House |
| `TURKISH_POP` | Turkish Pop |
```


### Example
```xcodeblock
(code curl http)
curl --location  --request GET 'https://business-api.tiktok.com/open_api/v1.3/discovery/cml/trending_list/?business_id={{business_id}}&genre=FOLK&country_code=US&date_range=7DAY' \
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
#|list|object[]|The list of the trending tracks.|
##|commercial_music_id|string|The ID of the track.<br><br>Example: `1234567890`.|
##|commercial_music_name|string|The name of the track.|
##|duration|integer|The duration of the track in seconds.<br><br><p><span style="color:darkred"><b>Note</b></span>: For a track that is only a part (clips) of the full piece of music and not the entire piece, the duration might be recorded as 0.|
##|thumbnail_url|string|The URL of the thumbnail image associated with the track.|
##|artist|string|The name of the artist who performed the track.|
##|preview_url|string|The URL for a preview of the track.<br><br>The URL won't expire.|
##|genres|string[]|A list of genres associated with the track.|
##|rank_position|string|Current rank position of the track in the selected region (`country_code`).<br><br>Example: `100`.|
##|trending_history|object[]|The historical ranking data for the track, spanning up to the past 30 days.|
###|date|string|Date, in the format of `YYYY-MM-DD`.<br><br>Example: `2024-01-01`.|
###|rank_position_daily|string|The rank position of the track in the selected region (`country_code`) on the date.<br><br>Example: `73`.<br><br><p><span style="color:darkred"><b>Note</b></span>: If the track does not perform well compared to the last time period, the track will not be considered a popular track and the value of this field will be `NULL`.|
##| full_duration_song_clip | object | Information related to the track, including the full duration of the track. |
###| preview_url | string | The track preview URL.<br>The URL won't expire.<br><br><p><span style="color:darkred"><b>Note</b></span>: This is the same as the `preview_url` parameter  outside `full_duration_song_clip`. |
###| duration | integer | The track duration in seconds.<br><br><p><span style="color:darkred"><b>Note</b></span>: This value is the same as the existing duration value. |
###| song_clip_id | string | The track ID.<br><br><p><span style="color:darkred"><b>Note</b></span>: This value can be passed to the `music_sound_id` in [/business/video/publish/](https://business-api.tiktok.com/portal/docs?id=1762228496095234). |
##| trending_song_clip | object | Information related to the most popular track in the last 30 days. |
###| preview_url | string | The track preview URL.<br>The URL won't expire.<br><br><p><span style="color:darkred"><b>Note</b></span>: This is the same as the `preview_url` parameter  outside `trending_song_clip`. |
###| duration | integer | The track duration in seconds.<br><br><p><span style="color:darkred"><b>Note</b></span>: This value is the same as the existing duration value. |
###| song_clip_id | string | The track ID.<br><br><p><span style="color:darkred"><b>Note</b></span>: This value can be passed to the `music_sound_id` in [/business/video/publish/](https://business-api.tiktok.com/portal/docs?id=1762228496095234). |
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
                "artist": "{{artist}}",
                "commercial_music_id": "{{commercial_music_id}}",
                "commercial_music_name": "{{commercial_music_name}}",
                "duration": 123,
                "genres": [
                    "Folk"
                ],
                "preview_url": "{{preview_url}}",
                "rank_position": "1",
                "thumbnail_url": "{{thumbnail_url}}",
                "trending_history": [
                    {
                        "date": "2024-09-24",
                        "rank_position_daily": "1"
                    },
                    {
                        "date": "2024-09-23",
                        "rank_position_daily": "1"
                    },
                    {
                        "date": "2024-09-22",
                        "rank_position_daily": "1"
                    },
                    {
                        "date": "2024-09-21",
                        "rank_position_daily": "1"
                    },
                    {
                        "date": "2024-09-20",
                        "rank_position_daily": "1"
                    },
                    {
                        "date": "2024-09-19",
                        "rank_position_daily": "1"
                    },
                    {
                        "date": "2024-09-18",
                        "rank_position_daily": "1"
                    }
                ]
            },
            ...,
            {
                "artist": "{{artist}}",
                "commercial_music_id": "{{commercial_music_id}}",
                "commercial_music_name": "{{commercial_music_name}}",
                "duration": 148,
                "genres": [
                    "Folk",
                    "Indie_Folk"
                ],
                "preview_url": "{{preview_url}}",
                "rank_position": "3",
                "thumbnail_url": "{{thumbnail_url}}",
                "trending_history": [
                    {
                        "date": "2024-09-24",
                        "rank_position_daily": "3"
                    },
                    {
                        "date": "2024-09-23",
                        "rank_position_daily": "2"
                    },
                    {
                        "date": "2024-09-22",
                        "rank_position_daily": "2"
                    },
                    {
                        "date": "2024-09-21",
                        "rank_position_daily": "2"
                    },
                    {
                        "date": "2024-09-20",
                        "rank_position_daily": "2"
                    },
                    {
                        "date": "2024-09-19",
                        "rank_position_daily": "3"
                    },
                    {
                        "date": "2024-09-18",
                        "rank_position_daily": "3"
                    }
                ]
            }
        ]
    }
}
(/code)
```
