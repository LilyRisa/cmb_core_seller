---
title: "Update an ad account"
doc_id: 1739939050770434
path: "API Reference / BC Assets / Update an ad account"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Update an ad account

Use this endpoint to update ad accounts that were created in the Business Centers. You can also use this endpoint to update the budget of the ad accounts owned by a Business Center in auto-allocation mode. Incremental update is supported. You need to be an Admin user of the Business Center. 

## Comparing v1.2 and v1.3
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/advertiser/update/|/v1.3/advertiser/update/|
|Request parameter data type|`advertiser_id`: number|`advertiser_id`: string |
|Request parameter name| `need_submit_task`|`need_submit_certificate` |
|New request parameters| /|`budget_update_type`<br>`request_id` |
|New response parameters| /|`one_click_set_amount` |
```

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/advertiser/update/

**Method** POST

**Header**

``` xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token{Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
|Content-Type {Required}|string|The content type of the request. <br>Allowed value: `"application/json"`  |
```

**Parameters**

``` xtable
|Field|Data Type|Description|
|---|---|---|
| advertiser_id {+Conditional} | string | Either `advertiser_id` or `bc_id` has to be set.<ul><li>To update non-budget settings for a single ad account, use `advertiser_id`.</li><li>To update the budget for one or more ad accounts, use `bc_id`.</li></ul><br>Ad account ID. |
| advertiser_name | string | Ad account name. |
| company | string | Company name. <br><br>To confirm whether the company name of the ad account can be updated via API, call [/advertiser/info/](https://business-api.tiktok.com/portal/docs?id=1739593083610113) and set `fields` to `["company_name_editable"]`. <ul><li>If the returned `company_name_editable` is `true`, you can use this field to update the company name.</li><li> If the returned `company_name_editable` is `false`, you need to contact your TikTok representative to update the company name. </li></ul> |
| contact_name | string | Name of the contact person. |
| contact_email | string | Contact email. |
| contact_number | string | Contact phone number. |
| promotion_link | string | Promotion link. Length cannot exceed 255 characters. |
| license_no | string | Business license number. <br><br><p><span style="color:darkred"><b>Note</b></span>: This field is supported for ad accounts that are registered in the Chinese mainland, Hong Kong, or in countries Brazil and Mexico.|
| license_image_id | string | Image ID (`image_id`) of the business license. |
| qualification_images | object[] | Additional business certificates. |
#| image_id | string | Image ID of the business certificate. |
| address | string | Business address that is shown on receipts. <br><br>Length range: 1-1,024 characters.|
| tax_map | dict[string] | Billing and invoicing tax number. Different countries use different tax number fields. France uses `vat`, and Brazil uses `tax_id`. |
| need_submit_certificate | boolean | Whether you want to submit the new certificate images for review. <br><br>Supported values: `true`,`false`.<br><br><p><span style="color:darkred"><b>Note</b></span>: Set this field to `true` only when you update any of the following fields: <ul><li>`compnay`</li><li>`promotion_link`</li><li>`license_no`</li><li>`license_image_id`</li><li>`qualification_images`</li></ul>If none of these fields change, omit this parameter to avoid re-triggering the certificate review process.|
| bc_id  {+Conditional} | string | Either `advertiser_id` or `bc_id` has to be set.<ul><li>To update non-budget settings for a single ad account, use `advertiser_id`.</li><li>To update the budget for one or more ad accounts, use `bc_id`.</li></ul><br>Business Center ID. <br><br>Only the ad accounts under the specified `bc_id` can be updated.<br><br><p><span style="color:darkred"><b>Note</b></span>: Only users with Finance Manager permission (`finance_role` is `MANAGER`) can update the budget and reset the budget consumption of an ad account. You can use [/bc/member/invite/](https://ads.tiktok.com/marketing_api/docs?id=1739939455765505) and [/bc/member/update/](https://ads.tiktok.com/marketing_api/docs?id=1739696704424961) to configure or update access settings for members. |
| budget_update_type | string | <ul><li>Valid only when `bc_id` is passed.</li><li>Not supported when `advertiser_id` is passed.</li></ul><br>The type of changes you want to perform on your budget. <br><br>Enum values: <ul><li>`UPDATE`: To update the budget mode and budget amount. <ul><li> If you set this field to `UPDATE`, pass in `advertiser_id`, `budget`, and optionally `budget_mode` within the `advertiser_budgets` object. </li></ul></li><li>`RESET`: To reset the budget consumption of the ad account or ad accounts to 0.<ul><li>You can only reset the budget consumption of an ad account with a custom budget (`budget_mode` is `CUSTOM_BUDGET`).</li><li>If you set this field to `RESET`, only `advertiser_id` within the `advertiser_budgets` object is required, and `budget_mode` and `budget`, if passed, will be ignored.</li></ul></li><li>`INCREMENTAL_UPDATE`: To add to or deduct from the current ad account budget.</li><li> `ONE_CLICK_SET`:  To set the ad account budget to the minimum amount. You don't need to specify the `budget` within the `advertiser_budgets` object array. You can obtain the budget after the change from the response parameter `one_click_set_amount`. </li></ul> Default value: `UPDATE`.<br><br><p><span style="color:darkred"><b>Note</b></span>: `UPDATE` and `RESET` are only supported for Auction ad accounts.|
|request_id|string|Valid only when `budget_update_type` is `INCREMENTAL_UPDATE`.<br><br>Request ID.<br>This field supports idempotency to prevent you from sending the same request twice. If you retry requests with the same request ID multiple times, then only one will succeed.<br>It is different from the `request_id` returned in the response parameters, which is used to uniquely identify an HTTP request.<br><br>The value should be a 32-bit string, with idempotency guaranteed for a 30-minute window.<br><br>Example: `123456789`.<br><br><p><span style="color:darkred"><b>Note</b></span>: Idempotency for incremental ad account budget updates using `request_id` is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.</p>|
| advertiser_budgets   {+Conditional} | object[] | Required when `bc_id` is passed.<br><br>Information about the new budget settings of one or more ad accounts.<br><br>  Max size: <ul><li>When `budget_update_type` is `UPDATE` or `RESET`: 50.</li><li>When `budget_update_type` is `INCREMENTAL_UPDATE` or `ONE_CLICK_SET`: 1. </li></ul><br><p><span style="color:darkred"><b>Note</b></span>: The monthly budget, daily budget, and custom budget of each ad account can be updated <b>up to 20 times a calendar day</b>. |
#| advertiser_id  {+Conditional}| string | Required when `advertiser_budgets` is passed.<br><br>Ad account ID. The ad account should belong to the specified `bc_id`.|
#|budget_mode|string|<ul><li>Valid only when `budget_update_type` is `UPDATE`, `RESET`, or `ONE_CLICK_SET`.</li><li>Not supported when `budget_update_type` is `INCREMENTAL_UPDATE`.</li></ul><br>Budget mode of the ad account.<br><br>Enum values:  <ul><li> `UNLIMITED`:  Unlimited budget. No budget limit for the ad account.</li><li> `MONTHLY_BUDGET`: Monthly budget. The ad account consumes BC credit balance within the monthly budget.</li></ul><ul><li> `DAILY_BUDGET`: Daily budget. The ad account consumes BC credit balance within the daily budget.</li><li> `CUSTOM_BUDGET`: Custom budget. The ad account consumes BC credit balance within the one-time custom budget.</li></ul>Default value when `budget_update_type` is `UPDATE`: `MONTHLY_BUDGET`. <br><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>The default budget mode for each ad account is `UNLIMITED` unless you customize your budget mode. </li><li> Reservation ad accounts can not use monthly budget, daily budget or custom budget for Balance Sharing. </li></ul> |
#|budget {+Conditional}|float|Required when `budget_mode` is `MONTHLY_BUDGET`, `DAILY_BUDGET`, or `CUSTOM_BUDGET`. <br><br><ul><li>When `budget_update_type` is `UPDATE` or `RESET`, this field represents the advertiser account budget kept to two decimal places. <ul><li>If `budget_mode` is set to `UNLIMITED`, this field will be ignored.</li><li> If `budget_mode` is set to `MONTHLY_BUDGET`, pass in monthly budget.</li><li>If `budget_mode` is set to `DAILY_BUDGET`, pass in daily budget.</li><li> If `budget_mode` is set to `CUSTOM_BUDGET`, pass in your one-time custom budget.</li></ul></li><li> When `budget_update_type` is `INCREMENTAL_UPDATE`, this field represents the change amount for the ad account budget.<ul><li>If you want to add to the current budget, set `budget` to a positive number such as 100.</li><li>If you want to deduct from the current budget, set `budget` to a negative number such as -10.</li></ul></li><li>When `budget_update_type` is `ONE_CLICK_SET`, this field is ignored.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: If `budget_mode` is set to `MONTHLY_BUDGET`, `DAILY_BUDGET`, or `CUSTOM_BUDGET`, `budget` should be no less than 105% of the monthly/daily/one-time spending. You can obtain the spending (the metric `spend`) by running a report using [/report/integrated/get/](https://ads.tiktok.com/marketing_api/docs?id=1740302848100353). |
```

### Example


#### Update the non-budget setting of a single ad account
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/advertiser/update/' \
--header 'Access-Token: {{access_token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "advertiser_name": "{{advertiser_name}}",
    "address":"{{address}}"
}'
(/code)

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
    private static final String PATH = "/open_api/v1.3/advertiser/update/";
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
     * Send POST request
     *
     * @param jsonStr Args in JSON format
     * @return Response in JSON format
     */
    private static String post(String jsonStr) throws IOException, URISyntaxException {
        OkHttpClient client = new OkHttpClient().newBuilder().build();
        String url = buildUrl(PATH);

        RequestBody body = RequestBody.create(MediaType.parse("application/json"), jsonStr);
        Request request = new Request.Builder()
                .url(url)
                .method("POST", body)
                .addHeader("Content-Type", "application/json")
                .addHeader("Access-Token", ACCESS_TOKEN)
                .build();
        Response response = client.newCall(request).execute();
        return response.body().string();
    }


    public static void main(String[] args) throws IOException, URISyntaxException {
        Long advertiser_id = ADVERTISER_ID;
        String advertiser_name = ADVERTISER_NAME;
        String company = COMPANY;
        String contact_name = CONTACT_NAME;
        String contact_email = CONTACT_EMAIL;
        String contact_number = CONTACT_NUMBER;
        String promotion_link = PROMOTION_LINK;
        String license_no = LICENSE_NO;
        String license_image_id = LICENSE_IMAGE_ID;
        String image_id = IMAGE_ID;
        String address = ADDRESS;
        List < String > tax_map_list = TAX_MAP;
        String tax_map = mapper.writeValueAsString(tax_map_list);
        String need_submit_certificate = NEED_SUBMIT_CERTIFICATE;

        // Args in JSON format
        String myArgs = String.format("{\"advertiser_id\": \"%s\", \"advertiser_name\": \"%s\", \"company\": \"%s\", \"contact_name\": \"%s\", \"contact_email\": \"%s\", \"contact_number\": \"%s\", \"promotion_link\": \"%s\", \"license_no\": \"%s\", \"license_image_id\": \"%s\", \"qualification_images\": [{\"image_id\": \"%s\"}], \"address\": \"%s\", \"tax_map\": %s, \"need_submit_certificate\": \"%s\"}",advertiser_id, advertiser_name, company, contact_name, contact_email, contact_number, promotion_link, license_no, license_image_id, image_id, address, tax_map, need_submit_certificate);
        System.out.println(post(myArgs));
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
PATH = "/open_api/v1.3/advertiser/update/"


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

def post(json_str):
    # type: (str) -> dict
    """
    Send POST request
    :param json_str: Args in JSON format
    :return: Response in JSON format
    """
    url = build_url(PATH)
    args = json.loads(json_str)
    headers = {
        "Access-Token": ACCESS_TOKEN,
        "Content-Type": "application/json",
    }
    rsp = requests.post(url, headers=headers, json=args)
    return rsp.json()


if __name__ == '__main__':
    advertiser_id = ADVERTISER_ID
    advertiser_name = ADVERTISER_NAME
    company = COMPANY
    contact_name = CONTACT_NAME
    contact_email = CONTACT_EMAIL
    contact_number = CONTACT_NUMBER
    promotion_link = PROMOTION_LINK
    license_no = LICENSE_NO
    license_image_id = LICENSE_IMAGE_ID
    image_id = IMAGE_ID
    address = ADDRESS
    tax_map_list = TAX_MAP
    tax_map = json.dumps(tax_map_list)
    need_submit_certificate = NEED_SUBMIT_CERTIFICATE

    # Args in JSON format
    my_args = "{\"advertiser_id\": \"%s\", \"advertiser_name\": \"%s\", \"company\": \"%s\", \"contact_name\": \"%s\", \"contact_email\": \"%s\", \"contact_number\": \"%s\", \"promotion_link\": \"%s\", \"license_no\": \"%s\", \"license_image_id\": \"%s\", \"qualification_images\": [{\"image_id\": \"%s\"}], \"address\": \"%s\", \"tax_map\": %s, \"need_submit_certificate\": \"%s\"}" % (advertiser_id, advertiser_name, company, contact_name, contact_email, contact_number, promotion_link, license_no, license_image_id, image_id, address, tax_map, need_submit_certificate)
    print(post(my_args))

(/code)

(code PHP php)

$ACCESS_TOKEN = "xxx";
$PATH = "/open_api/v1.3/advertiser/update/";

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
 * Send POST request
 * @param $json_str : Args in JSON format
 * @return bool|string : Response in JSON format
 */
function post($json_str)
{
    global $ACCESS_TOKEN, $PATH;
    $curl = curl_init();

    $url = build_url($PATH);

    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => $json_str,
        CURLOPT_HTTPHEADER => array(
            "Content-Type: application/json",
            "Access-Token: " . $ACCESS_TOKEN,
        ),
    ));

    $response = curl_exec($curl);
    curl_close($curl);
    return $response;
}


$advertiser_id = ADVERTISER_ID;
$advertiser_name = ADVERTISER_NAME;
$company = COMPANY;
$contact_name = CONTACT_NAME;
$contact_email = CONTACT_EMAIL;
$contact_number = CONTACT_NUMBER;
$promotion_link = PROMOTION_LINK;
$license_no = LICENSE_NO;
$license_image_id = LICENSE_IMAGE_ID;
$image_id = IMAGE_ID;
$address = ADDRESS;
$tax_map_list = TAX_MAP;
$tax_map = json_encode($tax_map_list);
$need_submit_certificate = NEED_SUBMIT_CERTIFICATE;

/* Args in JSON format */
$my_args = sprintf("{\"advertiser_id\": \"%s\", \"advertiser_name\": \"%s\", \"company\": \"%s\", \"contact_name\": \"%s\", \"contact_email\": \"%s\", \"contact_number\": \"%s\", \"promotion_link\": \"%s\", \"license_no\": \"%s\", \"license_image_id\": \"%s\", \"qualification_images\": [{\"image_id\": \"%s\"}], \"address\": \"%s\", \"tax_map\": %s, \"need_submit_certificate\": \"%s\"}", $advertiser_id, $advertiser_name, $company, $contact_name, $contact_email, $contact_number, $promotion_link, $license_no, $license_image_id, $image_id, $address, $tax_map, $need_submit_certificate);
echo post($my_args);

(/code)
```
#### Update the budget settings of multiple ad accounts
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/advertiser/update/' \
--header 'Access-Token: {{access_token}}' \
--header 'Content-Type: application/json' \
--data '{
    "bc_id": "{{bc_id}}",
    "budget_update_type":"UPDATE",
    "advertiser_budgets": [
        {
            "advertiser_id": "{{advertiser_id}}",
            "budget_mode": "CUSTOM_BUDGET",
            "budget": {{budget}}
        },
        {
            "advertiser_id": "{{advertiser_id}}",
            "budget_mode": "UNLIMITED"
        }
    ]
}'
(/code)
```

#### Reset the budget consumption of an ad account with Custom Budget
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/advertiser/update/' \
--header 'Access-Token: {{access_token}}' \
--header 'Content-Type: application/json' \
--data '{
    "bc_id": "{{bc_id}}",
    "budget_update_type":"RESET",
    "advertiser_budgets": [
        {
            "advertiser_id": "{{advertiser_id}}"
        }
    ]
}'
(/code)
```

#### Increase the ad account budget
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/advertiser/update/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "bc_id": "{{bc_id}}",
    "budget_update_type": "INCREMENTAL_UPDATE",
    "advertiser_budgets": [{
        "advertiser_id": "{{advertiser_id}}"
        "budget": 100
    }]
}'
(/code)
```
#### Decrease the ad account budget
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/advertiser/update/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "bc_id": "{{bc_id}}",
    "budget_update_type": "INCREMENTAL_UPDATE",
    "advertiser_budgets": [{
        "advertiser_id": "{{advertiser_id}}"
        "budget": -12
    }]
}'
(/code)
```
#### Set the ad account budget to the minimum amount
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/advertiser/update/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "bc_id": "{{bc_id}}",
    "budget_update_type": "ONE_CLICK_SET",
    "advertiser_budgets": [{
        "advertiser_id": "{{advertiser_id}}"
    }]
}'
(/code)
```

## Response

``` xtable
|Field|Data Type|Description|
|---|---|---|
|code |number | Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string | Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|request_id|string|The log id of the request, which uniquely identifies a request. |
|data |object| Returned data. |
#|list |object[]| Returned only when `advertiser_budgets` and `bc_id` are specified in the request.<br><br>List of ad accounts of which the budget settings are updated.  |
##| advertiser_id | string|  Ad account ID.  |
##| status|  string |  Status of update. <br><br>Enum values:<ul><li>`SUCCESS`: The update has succeeded.</li><li> `FAILED`: The update has failed.</li></ul>
##|error_msg|string|Error message. <br><br>When `status` is `SUCCESS`, the value of this field will be null.|
##| one_click_set_amount | number | The minimum budget amount that you have set for the ad account.<br><br>This field returns a non-null value only when `budget_update_type` is set to `ONE_CLICK_SET` in the request. |
```


### Example
#### Update the non-budget settings of a single ad account
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

#### Update the budgets of multiple ad accounts
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
                "advertiser_id": "{{advertiser_id}}",
                "error_msg": null,
                "status": "{{status}}"
            },
            {
                "advertiser_id": "{{advertiser_id}}",
                "error_msg": null,
                "status": "{{status}}"
            }
        ]
    }
}
(/code)
```
#### Reset the budget consumption of an ad account with Custom Budget
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
                "advertiser_id": "{{advertiser_id}}",
                "status": "SUCCESS",
                "error_msg": null
            }
        ]
    }
}
(/code)
```

#### Increase the ad account budget
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
                "advertiser_id": "{{advertiser_id}}",
                "error_msg": null,
                "one_click_set_amount": null,
                "status": "SUCCESS"
            }
        ]
    }
}
(/code)
```
#### Decrease the ad account budget
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
                "advertiser_id": "{{advertiser_id}}",
                "error_msg": null,
                "one_click_set_amount": null,
                "status": "SUCCESS"
            }
        ]
    }
}
(/code)
```
#### Set the ad account budget to the minimum amount
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
                "advertiser_id": "{{advertiser_id}}",
                "error_msg": null,
                "one_click_set_amount": 0,
                "status": "SUCCESS"
            }
        ]
    }
}
(/code)
```
