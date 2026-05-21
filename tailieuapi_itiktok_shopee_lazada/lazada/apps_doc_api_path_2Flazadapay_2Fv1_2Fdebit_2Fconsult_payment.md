# GET/POSTConsultPayment

> Source: https://open.lazada.com/apps/doc/api?path=%2Flazadapay%2Fv1%2Fdebit%2Fconsult_payment
> API path: /lazadapay/v1/debit/consult_payment
> Category: LazPay API
> Scraped: 2026-05-20T23:53:52.373Z

---

Latest update2022-12-30 09:17:21

3385

ConsultPayment

GET/POST

/lazadapay/v1/debit/consult\_payment

Authorization Required

Description:The interface is used for consult pay view. Will return pay view info including balance, coupon, credit card etc. If we have no available coupon, we will return pay method view with an empty list of coupon.

## Service Endpoints

| Region | Endpoint |
| --- | --- |
| Vietnam | https://api.lazada.vn/rest |
| Singapore | https://api.lazada.sg/rest |
| Philippines | https://api.lazada.com.ph/rest |
| Malaysia | https://api.lazada.com.my/rest |
| Thailand | https://api.lazada.co.th/rest |
| Indonesia | https://api.lazada.co.id/rest |
## Common Parameters

| Name | Type | Required or not | Description |
| --- | --- | --- | --- |
| app\_key | String | Yes | Unique app ID issued by LAZADA Open Platform console when you apply for an app category |
| timestamp | String | Yes | The time stamp of the request e.g. 1517820392000 (which translates to 5 February 2018 08:46:32) with less than 7200s difference from UTC time |
| access\_token | String | No | API interface call credentials |
| sign\_method | String | Yes | The HMAC hash algorithm you are using to calculate your signature |
| sign | String | Yes | Part of the authentication process that is used for identifying and verifying who is sending a request (click [here](https://open.lazada.com/apps/doc/doc?nodeId=10450&docId=108068) for details) |
## Parameters

| Name | Type | Required or not | Description |
| --- | --- | --- | --- |
| serviceCode | String | Yes | Indentifier for service |
| payFrom | Object | Yes | Where is the money to be received, the receivable details, including the user and payment amount information |
| custIdMercghost | String | Yes | Indentifier in merchant system for customer who need to pay |
| transAmount | Object | Yes | Amount customer need to pay |
| currency | String | Yes | 3-letter currency code, refer to ISO 4217 Standard currency alphabetic code |
| value | String | Yes | Amount |
| additionalInfo | String | No | Additional Information, json format |
| payTos | Object\[\] | No | Details payable, including sellers and amount |
| customerId | String | Yes | Indentifier in lazpay system for customer who need to receipt |
| payToAmount | Object | Yes | Amount customer need to receipt |
| currency | String | Yes | 3-letter currency code, refer to ISO 4217 Standard currency alphabetic code |
| value | String | Yes | Amount |
| additionalInfo | String | No | Additional Information, json format |
| orderGroup | Object | No | Multi Orders Information |
| envInfo | String | No | Environment info from buyer |
| payOptions | String\[\] | No | pay simulate when payOptions is not null |
| productExt | String | No | Additional Info for payment product |
| additionalInfo | String | No | Additional Info |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| responseMessage | String | Response code |
| responseCode | String | Response message |
| errorCode | String | Error Code |
| additionalInfo | String | Additional Info |
| payOptions | Object\[\] | Available payment option to user |
| supportedCurrencies | String\[\] | Supported currency for this payment option |
| payMethod | String | Payment Method Category |
| additionalInfo | String | Additional Information, json format |
| payOption | String | Payment Method Type |
| rank | Number | Rank of payment option |
| payAssetDetails | Object\[\] | Payment assets of the user, can not be null when payCategory is "CHANNELDETAIL". |
| payAssetType | String | Payment asset type |
| card | Object | Card asset detail |
| externalAccount | Object | External asset detail |
| storeValue | Object | Inner account asset detail |
| coupon | Object | Coupon asset detail |
| rebate | Object | Rebate asset detail |
| bankAccount | Object | Bank account asset detail |
| discount | Object | Discount asset detail |
| additionalInfo | String | Additional Info |
| preferred | Boolean | Successful or not in last payment |
| disableReasonCode | String | Code to indicate the reason of disable |
| disableReasonDesc | String | escription to indicate the reason of disable |
| amountLimitMap | Object | Limit information for each currency |
| payOptionInfo | Object | PaymentOption detail information,can not be null when payCategory is "CHANNEL". |
| enabled | Boolean | enable or disable |
| payCategory | String | Indicate this payment method is a payment asset or or not. |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/lazadapay/v1/debit/consult_payment)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/lazadapay/v1/debit/consult\_payment

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/lazadapay/v1/debit/consult_payment");
request.addApiParameter("serviceCode", "01");
request.addApiParameter("payFrom", "{\"transAmount\":{\"currency\":\"IDR\",\"value\":\"100\"},\"additionalInfo\":\"{}\",\"custIdMercghost\":\"{}\"}");
request.addApiParameter("payTos", "[{\"customerId\":\"{}\",\"additionalInfo\":\"{}\",\"payToAmount\":{\"currency\":\"IDR\",\"value\":\"100\"}}]");
request.addApiParameter("orderGroup", "{}");
request.addApiParameter("envInfo", "{}");
request.addApiParameter("payOptions", "{}");
request.addApiParameter("productExt", "{}");
request.addApiParameter("additionalInfo", "{}");
LazopResponse response = client.execute(request);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "code": "0",
  "additionalInfo": "{\"deviceId\": \"12345679237\"}",
  "errorCode": "PARAM_ILLEGAL",
  "payOptions": [
    {
      "disableReasonCode": "USER_BALANCE_NOT_ENOUGH",
      "disableReasonDesc": "Insufficient funds",
      "amountLimitMap": {},
      "payOptionInfo": {},
      "enabled": "false",
      "supportedCurrencies": [],
      "payCategory": "CHANNEL",
      "payMethod": "WALLET",
      "additionalInfo": "{}",
      "payOption": "DANA_WALLET",
      "rank": "101",
      "payAssetDetails": [
        {
          "bankAccount": {},
          "coupon": {},
          "rebate": {},
          "additionalInfo": "{}",
          "externalAccount": {},
          "discount": {},
          "payAssetType": "card",
          "storeValue": {},
          "card": {}
        }
      ],
      "preferred": "false"
    }
  ],
  "responseMessage": "Request has been processed successfully",
  "request_id": "0ba2887315178178017221014",
  "responseCode": "20054000"
}
```
