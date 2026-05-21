# EU market: Adding compliance required fields to Products API

> Source: https://partner.tiktokshop.com/docv2/page/eu-market-adding-compliance-required-fields-to-products-api
> Section: Changelog
> Scraped: 2026-05-21T00:42:47.143Z

---

# Summary

To comply with the law and regulation of the EU market, TikTok Shop requires sellers to submit the following information:

1.  Manufacturer Information: the manufacturer of the product
2.  Responsible Person (RP): the responsible person who ensures a seller's products comply with EU regulations.
3.  Extracode: Optional field that can be used if the product SKU includes multiple combined items. Additional IDs in format standards of GTIN, EAN, UPC, or ISBN are accepted.

Developers need to support and guide sellers entering the fields for correct product listings. **The new required fields and API changes are applicable to local sellers in the EU market.**

# Timelines and required actions

| Date | API Changes | Required Actions |
| --- | --- | --- |
| 
**09/30**

 | 

API will support creating manufacturer information and RPs on behalf of sellers, and adding them to products.  
**Note**: Fields optional from 9/30/24 to 12/13/24

 | 

Integrate the new APIs and start guiding sellers to provide manufacturer and RP information to products

 |
| 

**12/13**

 | 

Manufacturers and Responsible Persons required in API

 | 

Ensure sellers submit the required fields when creating and editing products

 |

# API changes

| API endpoints | Parameters | Response |
| --- | --- | --- |
| 
\[New\] Create Responsible Person

 | 

`"responsible_person": {`  
`"name": "John Doe",`  
`"phone_number": {`  
`"country_code": "+34",`  
`"local_number": "313516642"`  
`},`  
`"email": "johndoe@gmail.com",`  
`"address": {`  
`"street_address_line1": "Av. de los Poblados",`  
`"city": "Latina",`  
`"province": "Madrid",`  
`"postal_code": "28044",`  
`"country": "Spain"`  
`}`  
`}`

 | 

`{`  
`"code": 0,`  
`"message": "success",`  
`"request_id": "202203070749000101890810281E8C70B7",`  
`"data": {`  
`"responsible_person_id": "66d3cbe4d9c8b09ddca932a7"`  
`}`  
`}`

 |
| 

\[New\] Partial Edit Responsible Person

 | 

`{`  
`"code": 0,`  
`"message": "success",`  
`"request_id": "202203070749000101890810281E8C70B7"`  
`}`

 | 

`{`  
`"responsible_person": {`  
`"name": "John Doe",`  
`"phone_number": {`  
`"country_code": "+34",`  
`"local_number": "313516642"`  
`}`  
`}`  
`}`

 |
| 

\[New\] Search Responsible Person

 | 

`{`  
`"responsible_person_ids": [`  
`"66d3cbe4d9c8b09ddca932a7",`  
`"66d3cbe3d9c8b09ddca932a1"`  
`],`  
`"keyword": "john"`  
`}`

 | 

Returns the properties of Responsible Person entity

 |
| 

\[New\] Create Manufacturer

 | 

`{`  
`"name": "John Doe",`  
`"phone_number": {`  
`"country_code": "+65",`  
`"local_number": "81234567"`  
`},`  
`"email": "johndoe@gmail.com",`  
`"address": "One Raffles Quay, 1 Raffles Quay, Singapore 048583"`  
`}`

 | 

`{`  
`"code": 0,`  
`"message": "success",`  
`"request_id": "202203070749000101890810281E8C70B7",`  
`"data": {`  
`"manufacturer_id": "66d3cbe4d9c8b09ddca932a7"`  
`}`  
`}`

 |
| 

\[New\] Edit Manufacturer

 | 

`{`  
`"registered_trade_name": "TikTok Shop Co."`  
`}`

 | 

`{`  
`"code": 0,`  
`"message": "success",`  
`"request_id": "202203070749000101890810281E8C70B7"`  
`}`

 |
| 

\[New\] Search Manufacturer

 | 

`{`  
`"manufacturer_ids": [`  
`"66d3cbe4d9c8b09ddca932a7",`  
`"66d3cbe3d9c8b09ddca932a1"`  
`]`  
`}`

 | 

Returns the properties of manufacturer entity

 |
| 

[Check Product Listing](https://partner.tiktokshop.com/docv2/page/check-product-listing)

 | 

Adding the following properties to responses  
  
\* `manufacturer_ids`  
\* `responsible_person_ids`  
\* `sku.extra_identifier_codes`

 | 

For the EU market, this endpoint will validate the required fields to check whether the product information is compliant

 |
| 

[Create Product](https://partner.tiktokshop.com/docv2/page/create-product), [Edit Product](https://partner.tiktokshop.com/docv2/page/edit-product), and [Partial Edit Product](https://partner.tiktokshop.com/docv2/page/partial-edit-product)

 | 

1\. Removing the existing `manufacturer` object and its properties: name, address, phone number, email:  
![Image](https://sf16-sg.tiktokcdn.com/obj/eden-sg/jvK_ylwvslclK_JWZ%5B%5B/ljhwZthlaukjlkulzlp/Changelog/EU%20Products%20API%20One%20Pager/removing_manufacturer.png?x-resource-account=public)  
2\. Adding the following properties to responses  
\* `manufacturer_ids`  
\* `responsible_person_ids`  
\* `sku.extra_identifier_codes`  
3\. Updating the `certification.files` to support up to 10 files

 | 

For the EU market, this endpoint will validate the required fields. Starting from 13-Dec, editing products without correct `manufacturer_ids` and `responsible_person_ids` will fail

 |
| 

[Get Product](https://partner.tiktokshop.com/docv2/page/get-product)

 | 

No Change

 | 

1\. Removing the existing `manufacturer` object and its properties: name, address, phone number, email:  
![Image](https://sf16-sg.tiktokcdn.com/obj/eden-sg/jvK_ylwvslclK_JWZ%5B%5B/ljhwZthlaukjlkulzlp/Changelog/EU%20Products%20API%20One%20Pager/removing_manufacturer.png?x-resource-account=public)  
2\. Adding the following properties to responses  
\* `manufacturer_ids`  
\* `responsible_person_ids`  
\* `sku.extra_identifier_codes`

 |

# How to integrate the changes

## How to create compliance entities and attach them to product listing

Starting from **13-Dec**, manufacturers and responsible person(s) information become **required** for sellers' product listing. Developers should integrate the API changes, as well as upgrade their app(s) logic and UI to support sellers submitting manufacturers and responsible person(s).

### Step 1: Create entities for manufacturers

![Image](https://sf16-sg.tiktokcdn.com/obj/eden-sg/jvK_ylwvslclK_JWZ%5B%5B/ljhwZthlaukjlkulzlp/Changelog/EU%20Products%20API%20One%20Pager/create_entities_manufacturers.png?x-resource-account=public)  
❗**Important**:  
The legacy `manufacturer` will be removed from API parameters from **26-Sep**.

If you are sending the `manufacturer` object to version 202309 of the Products API, you need to adjust the implementation. Use the above new method to get and pass `manufacturer_ids` to Products instead of sending manufacturer information for every product.

### Step 2: Create entities for responsible person(s)

![Image](https://sf16-sg.tiktokcdn.com/obj/eden-sg/jvK_ylwvslclK_JWZ%5B%5B/ljhwZthlaukjlkulzlp/Changelog/EU%20Products%20API%20One%20Pager/create_entities_responsibleperson.png?x-resource-account=public)

### Step 3: Attach manufacturers and responsible person(s) entities to products

From **Step 1** and **Step 2**, you can get `manufacturer_ids` and `responsible_person_ids`. Use them as required parameters for [Check Product Listing](https://bytedance.sg.larkoffice.com/docx/Pu7edRVn8oeWr8xwpKzl2tjMg3f#share-KGBtdMATMovevtxrmUYlY0nrgzc), [Create Product](https://bytedance.sg.larkoffice.com/docx/Pu7edRVn8oeWr8xwpKzl2tjMg3f#share-Sw3ud6lMWouX9FxXWpwlOcPDgkz), [Edit Product](https://bytedance.sg.larkoffice.com/docx/Pu7edRVn8oeWr8xwpKzl2tjMg3f#share-Fzdmd2v6qo350NxJGBDlw4fngkb), and [Partial Edit Product](https://bytedance.sg.larkoffice.com/docx/Pu7edRVn8oeWr8xwpKzl2tjMg3f#share-XnrvdxZGCokzs9xZzgVlIidQgqe).

## How to use `sku.extra_identifier_codes`

This parameter is optional. When the SKU represents a virtual bundle containing multiple individual SKUs, sellers must provide product identifier codes for the virtual bundle SKU.

The parameter is an array of strings (`[]string`) and accepts up to 10 identifier codes. Each identifier code corresponds to a specific SKU within the product bundle.

The supported formats for identifier codes are:

-   **GTIN**: 14 digits
-   **EAN**: 8, 13, or 14 digits
-   **UPC**: 12 digits
-   **ISBN**: 13 digits (supports *X* in uppercase as the last digit)

Each SKU must have a unique identifier coe, and duplicates are not allowed.  
This parameter can be used for [Check Product Listing](https://bytedance.sg.larkoffice.com/docx/Pu7edRVn8oeWr8xwpKzl2tjMg3f#share-KGBtdMATMovevtxrmUYlY0nrgzc), [Create Product](https://bytedance.sg.larkoffice.com/docx/Pu7edRVn8oeWr8xwpKzl2tjMg3f#share-Sw3ud6lMWouX9FxXWpwlOcPDgkz), [Edit Product](https://bytedance.sg.larkoffice.com/docx/Pu7edRVn8oeWr8xwpKzl2tjMg3f#share-Fzdmd2v6qo350NxJGBDlw4fngkb), and [Partial Edit Product](https://bytedance.sg.larkoffice.com/docx/Pu7edRVn8oeWr8xwpKzl2tjMg3f#share-XnrvdxZGCokzs9xZzgVlIidQgqe).
