---
title: "Get device models"
doc_id: 1737172880570369
path: "API Reference / Tools / Get device models"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get device models

Use this endpoint to get device model enumeration values.

## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
 |Changes{23%}|v1.2{42%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/tools/device_model/|/v1.3/tool/device_model/|
|Request parameter data type |`advertiser_id`: number|`advertiser_id`: string|
|Response parameter name|`name` (in the `device_models` object) |`device_model_name`|
|Response parameter name and data type|<ul><li>`id`(in the `device_models` object): number</li><li>`children` (in the `device_models` object): number[]|<ul><li> `device_model_id` : string</li><li> `child_device_ids`: string[] |
```

## Request

**Endpoint**
https://business-api.tiktok.com/open_api/v1.3/tool/device_model/

**Method** GET

**Header**

```xtable
|Field|Type|Description|
|--- |--- |--- |
|Access-Token{Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
```

**Parameters**

```xtable
|Field|Type|Description|
|--- |--- |--- |
|advertiser_id {Required}|string|Advertiser ID|
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
    private static final String PATH = "/open_api/v1.3/tool/device_model/";
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

        // Args in JSON format
        String myArgs = String.format("{\"advertiser_id\": \"%s\"}",advertiser_id);
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
PATH = "/open_api/v1.3/tool/device_model/"


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

    # Args in JSON format
    my_args = "{\"advertiser_id\": \"%s\"}" % (advertiser_id)
    print(get(my_args))
    
(/code)

(code PHP php)

$ACCESS_TOKEN = "xxx";
$PATH = "/open_api/v1.3/tool/device_model/";

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

/* Args in JSON format */
$my_args = sprintf("{\"advertiser_id\": \"%s\"}", $advertiser_id);
echo get($my_args);
(/code)

(code curl http)
curl --get -H "Access-Token:xxx" \
--data-urlencode "advertiser_id=ADVERTISER_ID" \
https://business-api.tiktok.com/open_api/v1.3/tool/device_model/
(/code)

```

## Response

```xtable
|Field|Type|Description|
|--- |--- |--- |
|code|number|The response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message|string|The response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|data|json|The returned data.|
#|device_models |object[]| The list of device model objects |
##|device_model_id |string| Device model ID |
##|device_model_name |string| Device model name|
##|child_device_ids |string[]| List of children device model IDs |
##|is_active |boolean| Whether the device model is active or not. Only active device models can be targeted.|
##|level |string| Device model level. Enum values: `BRAND`,`SERIES`,`MODEL`. |
##|os_type |string| Operating system type. Enum values: `ANDROID`,`IOS` |
|request_id |string| The log ID of a request, which uniquely identifies the request. |
```

### Example

```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "202206302151270102452421061A5D0C58",
    "data": {
        "device_models": [
            {
                "child_device_ids": [
                    "101001",
                    "101002",
                    "101003",
                    "101004",
                    "101005",
                    "101006",
                    "101007",
                    "101008",
                    "101009",
                    "101010",
                    "101011",
                    "101012",
                    "101013",
                    "101014",
                    "101015",
                    "101016",
                    "101017",
                    "101018",
                    "101019",
                    "101020",
                    "101021",
                    "101022",
                    "101023",
                    "101024",
                    "101025",
                    "101026"
                ],
                "os_type": "ANDROID",
                "device_meta_name": "MEIZU",
                "is_active": false,
                "level": "BRAND",
                "device_meta_id": "101"
            },
            ...
        ]
    }
}
(/code);
```
