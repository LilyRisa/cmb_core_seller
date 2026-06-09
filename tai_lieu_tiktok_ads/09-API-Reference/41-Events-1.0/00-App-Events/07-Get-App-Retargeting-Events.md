---
title: "Get App Retargeting Events"
doc_id: 1740859371033601
path: "API Reference / Events 1.0 / App Events / Get App Retargeting Events"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get App Retargeting Events

Use this endpoint to obtain information about an App Retargeting Event, which can be used to define Audience Rules. See [Audience rules](https://ads.tiktok.com/marketing_api/docs?id=1739566532187138).
> <span style="color:DarkRed">**Important**</span><br>
> By using Events API endpoints, you agree to the [TikTok Business Products (Data) Terms](https://ads.tiktok.com/i18n/official/policy/business-products-terms) and that you will not share sensitive data with TikTok.
## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
|Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path| /v1.2/app/external_action/retargeting/| /v1.3/app/optimization_event/retargeting/|
|Request parameter data type|`advertiser_id`: number <br>`app_id`: number |`advertiser_id`: string <br>`app_id`: string |
|Response parameter name |`external_actions` |`optimization_events` |
```

## Request

**Endpoint**
https://business-api.tiktok.com/open_api/v1.3/app/optimization_event/retargeting/

**Method** GET

**Header**

```xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
```

**Parameters**

```xtable
|Field|Data Type|Description|
|---|---|---|
|advertiser_id {Required}|string|Advertiser ID |
|app_id {Required}|string|Your App ID, obtained after successfully creating your app. |
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
    private static final String PATH = "/open_api/v1.3/app/optimization_event/retargeting/";
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
        Long advertiser_id = ADVERTISER_ID;
        Long app_id = APP_ID;

        // Args in JSON format
        String myArgs = String.format("{\"advertiser_id\": \"%s\", \"app_id\": \"%s\"}",advertiser_id, app_id);
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
PATH = "/open_api/v1.3/app/optimization_event/retargeting/"


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
    advertiser_id = ADVERTISER_ID
    app_id = APP_ID

    # Args in JSON format
    my_args = "{\"advertiser_id\": \"%s\", \"app_id\": \"%s\"}" % (advertiser_id, app_id)
    print(get(my_args))
    
(/code)

(code PHP php)

$ACCESS_TOKEN = "xxx";
$PATH = "/open_api/v1.3/app/optimization_event/retargeting/";

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


$advertiser_id = ADVERTISER_ID;
$app_id = APP_ID;

/* Args in JSON format */
$my_args = sprintf("{\"advertiser_id\": \"%s\", \"app_id\": \"%s\"}", $advertiser_id, $app_id);
echo get($my_args);
(/code)

(code curl http)
curl --get -H "Access-Token:xxx" \
--data-urlencode "advertiser_id=ADVERTISER_ID" \
--data-urlencode "app_id=APP_ID" \
https://business-api.tiktok.com/open_api/v1.3/app/optimization_event/retargeting/
(/code)

```

## Response

```xtable
|Field|Data Type|Description|
|---|---|---|
|code |number|Return Data, see [Appendix-Return code](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|message |string|Return message, see [Appendix-Return information](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).  |
|data |object|Return data|
#|optimization_events|string[]| App retargeting event which can be used to define Audience Rule, <br>see [Audience rules](https://ads.tiktok.com/marketing_api/docs?id=1739566532187138) and[Appendix-Conversion events-App event types](https://ads.tiktok.com/marketing_api/docs?id=1739361474981889)<br>for more details.|
|request_id |string|The log id of a request, which uniquely identifies the request.|
```

### Example

```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
  "message": "OK",
  "code": 0,
  "data": {
    "external_actions": [
      "ACTIVE",
      "LOGIN",
      "LAUNCH_APP"
    ]
  },
  "request_id": "2020052214483801023125009011089"
}
(/code);
```
