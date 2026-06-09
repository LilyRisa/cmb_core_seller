---
title: "Get the status of an async report task"
doc_id: 1740302781443073
path: "API Reference / Reporting / Get the status of an async report task"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get the status of an async report task

Use this endpoint to check the status of an asynchronous report task.

## Comparing v1.2 and v1.3 

 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 
 ```xtable
|Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
| Endpoint path | /v1.2/reports/task/check/ | /v1.3/report/task/check/ |
| Request parameter data type | `advertiser_id`: number | `advertiser_id`: string |
```

## Request

**Endpoint**
https://business-api.tiktok.com/open_api/v1.3/report/task/check/

**Method** GET

**Header**


```xtable
|Field|Type|Description|
|---|---|---|
|Access-Token {Required}|string| Authorized access token. Please use the same access token as the [/report/task/create/](https://ads.tiktok.com/marketing_api/docs?id=1740302766489602) point.|
```

**Parameters**

```xtable
|Field|Type|Description|
|---|---|---|
|advertiser_id {Required}|string| Advertiser ID.<br><p><span style="color:darkred"><b>Note</b></span>: If you use `advertiser_ids` when calling [/report/task/create/](https://ads.tiktok.com/marketing_api/docs?id=1740302766489602) (POST method), you need to set this field to one of the Advertiser IDs specified via `advertiser_ids`.   |
|task_id {Required}|string| The ID of the asynchronous report task. You can get the ID via the response of [/report/task/create/](https://ads.tiktok.com/marketing_api/docs?id=1740302766489602) endpoint. |
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
    private static final String PATH = "/open_api/v1.3/report/task/check/";
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
        String task_id = TASK_ID;

        // Args in JSON format
        String myArgs = String.format("{\"task_id\": \"%s\"}",task_id);
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
PATH = "/open_api/v1.3/report/task/check/"


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
    task_id = TASK_ID

    # Args in JSON format
    my_args = "{\"task_id\": \"%s\"}" % (task_id)
    print(get(my_args))
    
(/code)

(code PHP php)

$ACCESS_TOKEN = "xxx";
$PATH = "/open_api/v1.3/report/task/check/";

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


$task_id = TASK_ID;

/* Args in JSON format */
$my_args = sprintf("{\"task_id\": \"%s\"}", $task_id);
echo get($my_args);
(/code)

(code curl http)
curl --get -H "Access-Token:xxx" \
--data-urlencode "task_id=TASK_ID" \
https://business-api.tiktok.com/open_api/v1.3/report/task/check/
(/code)

```

## Response

```xtable
|Field|Type|Description|
|---|---|---|
|code |number|The response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|message |string|The response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|data |object|The returned data.  |
#|status |string| The status of the task. <br><br>Enum values: `QUEUING`, `PROCESSING`, `SUCCESS`, `FAILED`, `CANCELED`.<br><br>If you want to cancel a task that is either queued or currently being processed, use [/report/task/cancel/](https://business-api.tiktok.com/portal/docs?id=1803615367145537).|
#|message |string| This field is returned when `status` is `FAILED`.<br><br> The reason why the task failed. |
|request_id |string|The unique ID of the request.  |
```

### Example

```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "message": "OK",
    "code": 0,
    "data": {
        "status": "SUCCESS",
        "message": ""
    },
    "request_id": "2021022606090301011517615608212B1C"
}
(/code);
```

