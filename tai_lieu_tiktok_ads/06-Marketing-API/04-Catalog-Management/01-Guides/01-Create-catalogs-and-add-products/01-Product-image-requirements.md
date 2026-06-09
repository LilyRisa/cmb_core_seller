---
title: "Product image requirements"
doc_id: 1739578479392770
path: "Marketing API / Catalog Management / Guides / Create catalogs and add products / Product image requirements"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Product image requirements

When you upload or update a product, the product images must be equal to or larger than 500x500 px in size. Otherwise, the images will be filtered out and the product will not be approved.

Use [/catalog/product/get/](https://ads.tiktok.com/marketing_api/docs?id=1740564136678402) to find out whether images for a product are filtered out or not. In the response, check the value of the `image_status` field. 

The enum values for the `image_status` field are:

* `PROCESSING`: The image is being stored.
* `SUCCESS`: The image is stored successfully.
* `FAIL`: The image is not stored successfully.
* `FILTERED`: The image is stored successfully but cannot be used because it does not meet the size requirements (must be equal to or larger than 500 px). 
* `NOT_SUPPORTED`: The image format is not supported.
* `NOT_FOUND`: Cannot find the resource via the URL. Please ensure the URL is valid, and the file is publicly accessible. 

For images that do not meet the size requirements (`image_status` is `FILTERED`), the product audit status (`audit_status`) will stay at `processing`. You can find the suggestion actions in the `suggestion` field. For example: 

```xcodeblock
(code JSON JSON)
"audit": {
            "audit_status": "processing",
            "suggestion": "Please upload an image that is at least 500*500 pixels."
	         },
			 ...
"image_status": "FILTERED"
...
(/code)
```
In this case, you need to upload qualified images to your server, and call the same endpoint to upload or update the product again. Remember to replace the old links with the links to the qualified images.

For images that are not stored successfully (`image_status` is `FAIL`), the audit status of the product will also stay at `processing`, and a correponding `suggestion` will be provided. For example: 
```xcodeblock
(code JSON JSON)
"audit": {
            "audit_status": "processing",
            "suggestion": "Retrieving image failed. Please upload the image again."
	         }
			 ...
"image_status": "FAIL"
...
(/code)
```
