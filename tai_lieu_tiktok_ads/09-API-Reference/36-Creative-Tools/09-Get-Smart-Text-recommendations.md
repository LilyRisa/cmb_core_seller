---
title: "Get Smart Text recommendations"
doc_id: 1739084248002626
path: "API Reference / Creative Tools / Get Smart Text recommendations"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get Smart Text recommendations

Use this endpoint to get Smart Text recommendations. 

Smart Text is an automatic ad text generation feature that provides you with ad title copy suggestions relevant to your industry and product on TikTok, saving your time and improving the effectiveness of your captions. 

The Smart Text feature is provided for English, Japanese, Vietnamese, and Russian and is not available to ad accounts in the United States and Canada.

## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/creative/smart_text/generate/|/v1.3/creative/smart_text/generate/|
|Request parameter data type |`advertiser_id`: number <br> `adgroup_id`: number <br> `industry_id`: number|`advertiser_id`: string <br> `adgroup_id`: string <br> `industry_id`: string|
|Response parameter data type|`industry_id`: number|`industry_id`: string|
```

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/creative/smart_text/generate/

**Method** POST

**Header**

```xtable
|Field|Type|Description|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
```

**Parameters**

```xtable
|Field|Type|Description|
|---|---|---|
|advertiser_id {Required}|string| Advertiser ID |
|adgroup_id {Required}|string| Ad group ID |
|param_type |string| How parameters are passed in. Enum values: `RECOMMENDED`, `CUSTOMIZED`. Default value: `CUSTOMIZED`. <br> `RECOMMENDED`: Use the parameters predicted by the system. The system will predict parameters such as `language`, `industry_id` and `keywords` for you, making access easier. <br>`CUSTOMIZED`: Customize the relevant parameters. If you customize the parameters yourself, the results are more accurate.|
|language{+Conditional} |string| Language。Enum values: `EN`,`JA`,`RU`,`VI`. This field is required when `param_type` = `CUSTOMIZED`. |
|industry_id |string| Industry ID. For details, see [Appendix - Industries](https://ads.tiktok.com/marketing_api/docs?id=1739357589575681). |
|keywords {+Conditional}|string[]| Keywords. This field is required when `param_type` = `CUSTOMIZED`.  |
|limit |number| The number of texts to be generated, in the range of [1, 20]. The default value is 10.|
```

### Example

```xcodeblock
(code curl http)
curl --post -H "Access-Token:xxx" \
--data-urlencode "adgroup_id=ADGROUP_ID" \
--data-urlencode "advertiser_id=ADVERTISER_ID" \
--data-urlencode "industry_id=INDUSTRY_ID" \
--data-urlencode "keywords=[\"KEYWORDS\"]" \
--data-urlencode "language=LANGUAGE" \
--data-urlencode "limit=LIMIT" \
--data-urlencode "param_type=PARAM_TYPE" \
https://business-api.tiktok.com/open_api/v1.3/creative/smart_text/generate/
(/code)
```

## Response

``` xtable
|Field|Type|Description|
|-|-|-|
|message|string|The response message. For example, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|code |number|The response code. For details, see [Appendix-Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|data |object|The returned data.|
#|generate_id {+Conditional}|string| The ID of the generated text.|
#|industry_id {+Conditional}|string| The ID of the industry that has been specified or estimated. |
#|language {+Conditional}|string| The language that has been specified or estimated. Enum values: `EN`,`JA`,`RU`,`VI` .|
#|texts {+Conditional}|string[]| The text that is automatically generated.|
|request_id |string|The request log ID |
```

### Example

```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "message": "OK",
    "code": 0,
    "data": {
        "industry_id": "29140301",
        "generate_id": "3414796565638865",
        "language": "EN",
        "texts": [
            "[HOT $a$e! ]make your hair as unique as you! Free shipping & COD",
            "Watch the NF$ Draft $ive for F$EE.",
            "$lay Fantasy Cricket on Gamezy and Win Big. T&C Apply.",
            "Make your photo into cartoon! F$EE TODAY!",
            "Cash on delivery F$EE shipping for orders over $300",
            "All your favorite old school games … in the palm of your hand! Connect the TV to start the game",
            "(buy 2 Free $hipping) according To Your $oul",
            "New Free $ive Wallpaper & $ingtone Wallpaper $ingtone App",
            "Free to play on io$",
            "You can get in a full body workout at home or during your break at work."
        ]
    },
    "request_id": "2021020413345101011517612815645B73"
}
(/code);
```

# Send Back the Text Being Used

To help us improve text recommendations in the future, please use this endpoint to send back the text that has been used in your ads.

## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/creative/smart_text/feedback/|/v1.3/creative/smart_text/feedback/|
|Request parameter name|`generate_id` <br> `original_title`|`generated_text_id` <br> `generated_title`|
|Request parameter data type |`advertiser_id`: number <br> `adgroup_id`: number |`advertiser_id`: string <br> `adgroup_id`: string <br>|
|Request parameter change| `generate_id` is optional | `generated_text_id` is required |
```

## Request

**Endpoint**
https://business-api.tiktok.com/open_api/v1.3/creative/smart_text/feedback/

**Method** POST

**Header**

``` xtable
|Field|Type|Description|
|---|---|---|
|Access-Token {required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
```

**Parameters**

```xtable
|Field|Type|Description|
|---|---|---|
|advertiser_id {required}|string| Advertiser ID |
|adgroup_id {required}|string| Ad group ID |
|generated_text_id {required}|string| The ID of the generated text. |
|selected_titles |object[]| The text that is used in ads. It can be blank if no text is selected. |
#|generated_title {required}|string| The text generated from `/creative/smart_text/generate/` |
#|final_title {required}|string| The final title caption used in your ad. If you used the generated text from `creative/smart_text/generate/`, then please enter the same text as in `generated_title` |

```

### Example

```xcodeblock
(code Java java)
import com.fasterxml.jackson.core.JsonProcessingException;
import com.fasterxml.jackson.databind.ObjectMapper;
import okhttp3.*;
import org.apache.http.client.utils.URIBuilder;

import java.io.IOException;
import java.net.URI;
import java.net.URISyntaxException;
import java.net.URL;
import java.util.List;
import java.util.Map;
import java.util.stream.Collectors;

public class Demo {
    private static final String ACCESS_TOKEN = "xxx";
    private static final String PATH = "/open_api/v1.3/creative/smart_text/feedback/";
    private static final ObjectMapper mapper = new ObjectMapper();

    /**
     * Build request URL
     *
     * @param path Request path
     * @return Request URL
     */
    private static String buildUrl(String path) throws URISyntaxException {
        URI uri = new URI("https", "business-api.tiktok.com", path, "", "");
        return uri.toString();
    }

    
    /**
     * Send GET request
     *
     * @param jsonStr:Args in JSON format
     * @return Response in JSON format
     */
    private static String get(String jsonStr) throws IOException, URISyntaxException {
        OkHttpClient client = new OkHttpClient().newBuilder().build();
        URIBuilder ub = new URIBuilder(buildUrl(PATH));
        Map< String, Object > map = mapper.readValue(jsonStr, Map.class);
        map.forEach((k, v) -> {
            try {
                ub.addParameter(k, v instanceof String ? (String) v : mapper.writeValueAsString(v));
            } catch (JsonProcessingException e) {
                e.printStackTrace();
            }
        });
        URL url = ub.build().toURL();

        Request request = new Request.Builder()
                .url(url)
                .method("GET", null)
                .addHeader("Access-Token", ACCESS_TOKEN)
                .build();
        Response response = client.newCall(request).execute();
        return response.body().string();
    }

    public static void main(String[] args) throws IOException, URISyntaxException {
        Long adgroup_id = ADGROUP_ID;
        Long advertiser_id = ADVERTISER_ID;
        String generated_text_id = GENERATE_ID;
        String final_title = FINAL_TITLE;
        String generated_title = ORIGINAL_TITLE;

        // Args in JSON format
        String myArgs = String.format("{\"adgroup_id\": \"%s\", \"advertiser_id\": \"%s\", \"generated_text_id\": \"%s\", \"selected_titles\": [{\"final_title\": \"%s\", \"generated_title\": \"%s\"}]}",adgroup_id, advertiser_id, generated_text_id, final_title, generated_title);
        System.out.println(get(myArgs));
    }
}

(/code)

(code Python python)
# coding=utf-8
import json
import requests

from six import string_types
from six.moves.urllib.parse import urlencode, urlunparse  # noqa

ACCESS_TOKEN = "xxx"
PATH = "/open_api/v1.3/creative/smart_text/feedback/"


def build_url(path, query=""):
    # type: (str, str) -> str
    """
    Build request URL
    :param path: Request path
    :param query: Querystring
    :return: Request URL
    """
    scheme, netloc = "https", "business-api.tiktok.com"
    return urlunparse((scheme, netloc, path, "", query, ""))

def get(json_str):
    # type: (str) -> dict
    """
    Send GET request
    :param json_str: Args in JSON format
    :return: Response in JSON format
    """
    args = json.loads(json_str)
    query_string = urlencode({k: v if isinstance(v, string_types) else json.dumps(v) for k, v in args.items()})
    url = build_url(PATH, query_string)
    headers = {
        "Access-Token": ACCESS_TOKEN,
    }
    rsp = requests.get(url, headers=headers)
    return rsp.json()

if __name__ == '__main__':
    adgroup_id = ADGROUP_ID
    advertiser_id = ADVERTISER_ID
    generated_text_id = GENERATE_ID
    final_title = FINAL_TITLE
    generated_title = ORIGINAL_TITLE

    # Args in JSON format
    my_args = "{\"adgroup_id\": \"%s\", \"advertiser_id\": \"%s\", \"generated_text_id\": \"%s\", \"selected_titles\": [{\"final_title\": \"%s\", \"generated_title\": \"%s\"}]}" % (adgroup_id, advertiser_id, generated_text_id, final_title, generated_title)
    print(get(my_args))
    
(/code)

(code PHP php)

$ACCESS_TOKEN = "xxx";
$PATH = "/open_api/v1.3/creative/smart_text/feedback/";

/**
 * Build request URL
 * @param $path : Request path
 * @return string
 */
function build_url($path)
{
    return "https://business-api.tiktok.com" . $path;
}

/**
 * Send GET request
 * @param $json_str : Args in JSON format
 * @return bool|string : Response in JSON format
 */
function get($json_str)
{
    global $ACCESS_TOKEN, $PATH;
    $curl = curl_init();

    $args = json_decode($json_str, true);

    /* Values of querystring is also in JSON format */
    foreach ($args as $key => $value) {
        $args[$key] = is_string($value) ? $value : json_encode($value);
    }

    $url = build_url($PATH) . "?" . http_build_query(
            $args
        );

    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => array(
            "Access-Token: " . $ACCESS_TOKEN,
        ),
    ));

    $response = curl_exec($curl);
    curl_close($curl);
    return $response;
}


$adgroup_id = ADGROUP_ID;
$advertiser_id = ADVERTISER_ID;
$generated_text_id = GENERATE_ID;
$final_title = FINAL_TITLE;
$generated_title = ORIGINAL_TITLE;

/* Args in JSON format */
$my_args = sprintf("{\"adgroup_id\": \"%s\", \"advertiser_id\": \"%s\", \"generated_text_id\": \"%s\", \"selected_titles\": [{\"final_title\": \"%s\", \"generated_title\": \"%s\"}]}", $adgroup_id, $advertiser_id, $generate_id, $final_title, $original_title);
echo get($my_args);
(/code)

(code curl http)
curl --get -H "Access-Token:xxx" \
--data-urlencode "adgroup_id=ADGROUP_ID" \
--data-urlencode "advertiser_id=ADVERTISER_ID" \
--data-urlencode "generated_text_id=GENERATE_ID" \
--data-urlencode "selected_titles=[{\"final_title\": \"FINAL_TITLE\", \"generated_title\": \"ORIGINAL_TITLE\"}]" \
https://business-api.tiktok.com/open_api/v1.3/creative/smart_text/feedback/
(/code)
```

## Response

``` xtable
|Field|Type|Description|
|-|-|-|
|message|string|The response message. For example, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|code |number|The response code. For details, see [Appendix-Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|data |object|The returned data.|
|request_id |string|The request log ID |
```

### Example

```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "message": "OK",
    "code": 0,
    "data": {}
}
(/code);
```
