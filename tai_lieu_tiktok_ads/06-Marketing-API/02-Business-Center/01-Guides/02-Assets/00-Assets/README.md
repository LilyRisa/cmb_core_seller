---
title: "Assets"
doc_id: 1754705407307777
path: "Marketing API / Business Center / Guides / Assets / Assets"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Assets

You (**Admin users of a Business Center**) can use Business Center API to manage assets including ad accounts, catalogs, pixels, leads, TikTok accounts and stores.  Below outlines the common use cases for assets.

# Create an ad account

Admin users of Agency or Direct Business Center can create both auction and reservation types of ad accounts via the [/bc/advertiser/create/](https://ads.tiktok.com/marketing_api/docs?id=1739939020318721) endpoint.

> <span style="color:DarkRed">**Important**</span><br>
> As of September 26, 2024, to ensure ad account security, we have implemented UnionPay verification for the creation of ad accounts in Business Centers via [/bc/advertiser/create/](https://business-api.tiktok.com/portal/docs?id=1739939020318721) for advertisers registered in the Chinese mainland. Existing API workflows will continue to function until January 8, 2025. By mid-October 2024, we have released the API changes along with detailed API documentation supporting this change. Affected developers need to implement the new workflow before January 8, 2025, after which integrations might no longer function correctly.

> <span style="color: DodgerBlue">**Note**</span><br>Creating reservation ad accounts is only available for agency Business Center accounts, and is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative. 

To create an ad account, follow the following steps. The specific steps vary based on the registration area and the business license type.

## For advertisers in the Chinese Mainland
If the ad account is registered in the Chinese Mainland, you can either use a new business license, or reuse an existing verified business license.

### Option A: Using a new business license
1. Upload images of business licenses using [/bc/image/upload/](https://business-api.tiktok.com/portal/docs?id=1739938996913218). The response will include the `image_id` for each business license.
2. Check the UnionPay verification requirement for the business license using [/bc/advertiser/unionpay_info/check/](https://business-api.tiktok.com/portal/docs?id=1813772238056449). 
	- If the returned `unionpay_verification_required` is `false`, create an ad account by using [/bc/advertiser/create/](https://business-api.tiktok.com/portal/docs?id=1739939020318721) and pass the `image_id` value to the `license_image_id` field in the request.
	- If the returned `unionpay_verification_required` is `true`, follow these steps:
<br>&nbsp;&nbsp;&nbsp;&nbsp; i.  Create an ad account using [/bc/advertiser/create/](https://business-api.tiktok.com/portal/docs?id=1739939020318721) with the same `license_no` and the `license_image_id`. This will associate the ad account with the business license requiring UnionPay verification.
<br>&nbsp;&nbsp;&nbsp;&nbsp; ii. Submit UnionPay verification using [/bc/advertiser/unionpay_info/submit/](https://business-api.tiktok.com/portal/docs?id=1813772274039809).
<br>&nbsp;&nbsp;&nbsp;&nbsp; iii. Wait for around 20 seconds, then use [/advertiser/info/](https://business-api.tiktok.com/portal/docs?id=1739593083610113) to confirm the `status` of the ad account. 
<br>&nbsp;&nbsp;&nbsp;&nbsp; iv. 
<br>&nbsp;&nbsp;&nbsp;&nbsp; ○ If the ad account is rejected due to UnionPay verification failure: Upload the image of the same business license using [/bc/image/upload/](https://business-api.tiktok.com/portal/docs?id=1739938996913218). The response will include a new `image_id` for the business license. Resubmit your license information (same `license_no` and new `license_image_id`) using [/advertiser/update/](https://business-api.tiktok.com/portal/docs?id=1739939050770434). Then resubmit UnionPay verification using [/bc/advertiser/unionpay_info/submit/](https://business-api.tiktok.com/portal/docs?id=1813772274039809). To learn about the specific rejection reasons for UnionPay verification failures and corresponding solutions, see <a href="#item-link-Rejection reasons for UnionPay verification failures">Rejection reasons for UnionPay verification failures</a>.
<br>&nbsp;&nbsp;&nbsp;&nbsp; ○ If the ad account is rejected due to other reasons: Update the ad account information based on the returned rejection reasons using [/advertiser/update/](https://business-api.tiktok.com/portal/docs?id=1739939050770434).
<br>&nbsp;&nbsp;&nbsp;&nbsp; v. If ad account rejection persists, return to Step iv.

> <span style="color:DodgerBlue">**Note**</span><br>
> If you submit UnionPay verification multiple times while the ad account is under review, only the initial submission will be accepted. Subsequent submissions will be ignored.


#### Rejection reasons for UnionPay verification failures
The following table outlines the detailed rejection reasons for UnionPay verification failures and offers guidance on resolving these issues when you resubmit UnionPay verification using [/bc/advertiser/unionpay_info/submit/](https://business-api.tiktok.com/portal/docs?id=1813772274039809).

| Rejection reason | How to fix |
|---|---|
| "Your UnionPay verification failed <b>because your name and ID number do not match</b>." | Specify the correct Chinese name and ID number of the legal representative via `representative_name` and `representative_id` |
| "Your UnionPay verification failed <b>due to invalid ID number</b>." | Specify the correct ID number via `representative_id` |
| "Your UnionPay verification failed <b>because your name does not match the info provided when opening your bank account</b>." | Specify the correct Chinese name of the legal representative that matches the bank account (`unionpay_account`) via `representative_name` |
| "Your UnionPay verification failed <b>due to invalid phone number</b>." | Specify the correct phone number via `representative_phone_number` |
| "Your UnionPay verification failed <b>because your legal representative name does not match the name on your document</b>." | Specify the correct Chinese name of the legal representative that matches the name on the business license via `representative_name` |

### Option B: Reusing an existing verified business license

**Prerequisite**

You have created an ad account with the business license by following the steps in <a href="#item-link-Option A: Using a new business license">Option A: Using a new business license</a>.

**Steps**

1. Retrieve verified qualifications that are associated with your Business Center using [/bc/advertiser/qualification/get/](https://ads.tiktok.com/marketing_api/docs?id=1770118680584194). Include `filtering={"verified": true}` in the request.

Request
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/bc/advertiser/qualification/get/?bc_id={{bc_id}}&filtering={"verified": true}' \
--header 'Access-Token: {{Access-Token}}'
(/code)
```

Response
```xcodeblock
(code curl http)
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "page_info": {
            "page_size": 10,
            "page": 1,
            "total_page": 1,
            "total_number": 2
        },
        "qualifications": [
            {
                "status": "VERIFIED",
                "region_code": "CN",
                "linked_advertiser_count": 1,
                "qualification_id": "{{qualification_id}}",
                "owner_advertiser_id": "{{owner_advertiser_id}}",
                "company_name": "{{company_name}}"
            },
            {
                "status": "VERIFIED",
                "region_code": "CN",
                "linked_advertiser_count": 1,
                "qualification_id": "{{qualification_id}}",
                "owner_advertiser_id": "{{owner_advertiser_id}}",
                "company_name": "{{company_name}}"
            }
        ]
    }
}
(/code)
```
2. Create an ad account using [/bc/advertiser/create/](https://business-api.tiktok.com/portal/docs?id=1739939020318721). Pass the `qualification_id` value obtained from Step 1 to the `qualification_id` field in the request. Do not pass `license_no` and `license_image_id`. You can skip the UnionPay verification process because you're using a verified qualification.

Request
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/bc/advertiser/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "bc_id": "{{bc_id}}",
    "advertiser_info": {
        "name": "{{name}}",
        "currency": "{{currency}}",
        "timezone": "{{timezone}}",
        "type": "AUCTION"
    },
    "customer_info": {
        "company": "{{company}}",
        "industry": "{{industry}}",
        "registered_area": "CN"
    },
    "qualification_info": {
        "qualification_id": "{{qualification_id}}"
    },
    "contact_info": {
        "name": "{{name}}"
    }
}'
(/code)
```

Response
```xcodeblock
(code curl http)
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "advertiser_id": "{{advertiser_id}}"
    }
}
(/code)
```
3. Wait for around 20 seconds, then use [/advertiser/info/](https://business-api.tiktok.com/portal/docs?id=1739593083610113) to confirm the `status` of the ad account. 
4. If the ad account is rejected for any reason, update the ad account information based on the returned rejection reasons using [/advertiser/update/](https://business-api.tiktok.com/portal/docs?id=1739939050770434).
5. If ad account rejection persists, return to Step 4.

## For advertisers in Hong Kong, France, Brazil or Mexico
If the ad account is registered in Hong Kong, France, Brazil or Mexico:
1. Upload images of business licenses or other qualification documents using [/bc/image/upload/](https://business-api.tiktok.com/portal/docs?id=1739938996913218). The response will include the `image_id` for each qualification document.
2. Create an ad account using [/bc/advertiser/create/](https://business-api.tiktok.com/portal/docs?id=1739939020318721). Pass the `image_id` value obtained from Step 1 to the `license_image_id` or `qualification_image_ids` field in the request.

> <span style="color:DodgerBlue">**Note**</span><br>
> You can save time by reusing qualification information from other ad accounts when creating new ad accounts. To do so, pass the `qualification_id` to [/bc/advertiser/create/](https://business-api.tiktok.com/portal/docs?id=1739939020318721). To retrieve the qualification information of an ad account in your Business Center, use [/bc/advertiser/qualification/get/](https://business-api.tiktok.com/portal/docs?id=1770118680584194).

## For advertisers not in the Chinese Mainland, Hong Kong, France, Brazil or Mexico
If the ad account is not registered in the Chinese Mainland, Hong Kong, France, Brazil or Mexico, create an ad account directly using [/bc/advertiser/create/](https://business-api.tiktok.com/portal/docs?id=1739939020318721).

 # Modify a rejected ad account and submit for review 

 If an ad account is rejected, you can modify the ad account via the [/advertiser/update/](https://ads.tiktok.com/marketing_api/docs?id=1739939050770434) endpoint. In the request, set `need_submit_certificate` to `true` to resubmit the ad account for review.
 
# Disable an ad account
Sometimes, it may be necessary to remove your ad account from a TikTok Business Center account. For example, when you have finished working with an agency or vendor, you may need to remove your ad account from their TikTok Business Center account.

Use [/bc/advertiser/disable/](https://ads.tiktok.com/marketing_api/docs?id=1752349244331009) to disable an ad account. Before you start, please check whether the prerequisites described in the endpoint article are all met. 

# Migrate catalogs to a Business Center 
See [Manage catalogs in a Business Center-Migrate catalogs to a Business Center](https://ads.tiktok.com/marketing_api/docs?id=1738964064361474&rid=d112ewj4i8u) to find out how to migrate your existing product catalog data to a Business Center.  

# Manage catalogs in a Business Center
See [Manage catalogs in a Business Center](https://ads.tiktok.com/marketing_api/docs?id=1738964064361474&rid=d112ewj4i8u) to find out how to manage catalogs. 

# Create and Manage stores in a Business Center 
See [TikTok Store](https://ads.tiktok.com/marketing_api/docs?id=1739581901879298) to find out how to create and manage stores in a Business Center.

# Manage pixels in a Business Center
Use the [/bc/pixel/transfer/](https://ads.tiktok.com/marketing_api/docs?id=1753994928261122) endpoint and pass in Pixel Code (`pixel_code`) to transfer the pixel to your Business Center as an asset. 

Use the [/bc/pixel/link/update/](https://ads.tiktok.com/marketing_api/docs?id=1753994950327297) endpoint to link a pixel to or unlink a pixel from your ad accounts. 

> <span style="color: DodgerBlue">**Note**</span><br>To successfully transfer a pixel, you'll need Admin access to a Business Center. See [Pixel Sharing in Business Center](https://ads.tiktok.com/help/article/pixel-sharing-business-center?lang=en) to learn more about user roles and access. 

- **For Non-agency clients**: The Business Center you are going to use should have admin access to the ad account that owns the pixel.
- **For Agency clients**: You can only transfer pixels from ad accounts that were created from Business Center.

 
 # Share assets with a Partner Business Center
 
You can share assets with a Partner Business Center. The assets that are shared to a Partner Business Center cannot be shared by the Partner Business Center to a third Business Center. 
 
 To add a partner and assign assets to this partner, use the [/bc/partner/add/](https://ads.tiktok.com/marketing_api/docs?id=1739662756510721) endpoint. Currently, the only supported `asset_type` is `ADVERTISER`. You can also set the partner's role to the ad accounts being assigned via `advertiser_role` field.
 
If you want to stop the sharing of an asset with a Partner Business Center, use the [/bc/partner/asset/delete/](https://ads.tiktok.com/marketing_api/docs?id=1739662800930817) endpoint. 
 
# Related endpoints
See [API reference](https://ads.tiktok.com/marketing_api/docs?id=1739562438061058) to find out the available endpoints for Business Center **assets**.



