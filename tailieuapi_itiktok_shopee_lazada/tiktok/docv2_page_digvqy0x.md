# For US market: Mandatory Size Chart Quality Validation

> Source: https://partner.tiktokshop.com/docv2/page/digvqy0x
> Section: Changelog
> Scraped: 2026-05-21T00:47:02.081Z

---

# Updates to Product API For Size Chart Quality Validation

**Effective Date:**

-   **Grace Period Start:** 2026-05-07
-   **Full Enforcement Start:** 2026-07-07

### What is changing?

To improve the buyer experience and ensure size chart accuracy, TikTok Shop is introducing a new quality validation mechanism for size charts when editing existing products via the API.  
This change will be rolled out in two phases for existing products:

1.  **Grace Period (Starts 2026-05-07):** The `edit_product` API **will not** be blocked if a size chart has quality issues.
2.  **Full Enforcement (Starts 2026-07-07):** The `edit_product` API will be **blocked** if a size chart fails our quality validation. The API will return an error, and the response will include error message object detailing the specific issues.

### Which APIs are affected?

| API Name | Change Description |
| --- | --- |
| 
`/product/202509/products/{product_id}` (Edit Product)  
&  
/product/202509/products/{product\_id}/partial\_edit

 | 

From **2026-07-07**, this API will return an error and block updates for existing products with poor-quality size charts if no changes are made to the size chart during the edit.

 |
| 

(New) Submit Appeal Task

 | 

A new API will be introduced to allow partners to submit an appeal if they believe a size chart quality warning is incorrect.

 |
| 

（New）Appeal Completed Webhook  

 | 

to receive asynchronous updates on the status of each appeal (approved or rejected)

 |

### How does the validation work?

Validation is triggered only when editing an existing product **and** the size chart (`size_chart.template_id` or `size_chart.image_id`) has not been changed in the current `edit_product` request.  
Our system will asynchronously check for three types of quality issues:

-   **Missing Mandatory Dimensions:** The size chart is missing required dimensions for its category (e.g., a dress missing 'Bust' measurement).
-   **Invalid Value Range:** The measurements provided are outside the expected range for the category.
-   **Incomplete Variation Coverage:** The size chart does not include all available SKU variations (e.g., the product is sold in 'S', 'M', and 'L', but the size chart only lists 'S' and 'M').

### New Error Structure

Starting **2026-07-07**, if an `edit_product` call is blocked due to a size chart quality issue, you will receive the following error response:  
**Error Code:** `5001001`**Error Message:** "Size chart quality check failed. Please check the warning details and either update the size chart or submit an appeal."

### What action is required?

1.  **Handle the Hard Block:** After **2026-07-07**, prepare your system to handle the new error code (`5001001`) from the `edit_product` API. When this error occurs, you must either:
    -   Prompt the seller to update their size chart with corrected information.
    -   OR, provide an interface for the seller to use the new **appeal API** to report an incorrect diagnosis.
2.  **Develop an Appeal Process:**
    -   Build functionality to call the new Submit Appeal Task endpoint. This allows sellers to request an exemption if they believe the validation is incorrect.
    -   Subscribe to the new **Appeal Completed Webhook** to receive asynchronous updates on the status of each appeal (approved or rejected). If an appeal is approved, the seller can then successfully edit the product without changing the size chart.

We appreciate your cooperation in improving the quality of product listings on TikTok Shop.
