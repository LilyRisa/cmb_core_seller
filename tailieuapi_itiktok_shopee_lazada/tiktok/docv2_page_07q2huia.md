# US/SEA（POP）Market：Support product weight and dimensions at the SKU level

> Source: https://partner.tiktokshop.com/docv2/page/07q2huia
> Section: Changelog
> Scraped: 2026-05-21T00:47:16.832Z

---

# What is changing?

TikTok Shop is introducing **SKU-level dimension and weight support** in Partner APIs. This update allows product weight and dimensions to be maintained and synchronized at the SKU level, which improves shipping-related accuracy for products with multiple variants.  
Previously, weight and dimension data were mainly handled at the product level. With this release, APIs now support dedicated SKU-level fields so partners can send and retrieve more precise package information for each SKU.  
Two areas of the API are affected:

1.  **Product query APIs — new response fields**The following fields are now returned in product query responses:
    -   `sku_weight`
    -   `sku_dimensions`

Applicable APIs:

-   `Get Product`
-   `Check Product Listing`

1.  **Product create and update APIs — new request fields**The following fields are now supported in product write requests:
    -   `sku_weight`
    -   `sku_dimensions`

Applicable APIs:

-   `Create Product`
    
-   `Edit Product`
    
-   `Partial Edit Product`
    

## Field details

### `sku_weight`

| Field | Type | Required | Example | Description |
| --- | --- | --- | --- | --- |
| 
`sku_weight`

 | 

Struct

 | 

No

 | 

\-

 | 

Weight information for a SKU package.

 |
| 

`value`

 | 

String

 | 

Yes

 | 

`1.32`

 | 

Package weight. Must be a positive number. Formatting depends on the selected unit.

 |
| 

`unit`

 | 

String

 | 

Yes

 | 

`KILOGRAM`

 | 

Allowed values vary by region. For example, US supports `KILOGRAM` and `POUND`, while other regions may support `KILOGRAM` or `GRAM` depending on market rules.

 |
| 

**Additional notes:**

 | 

 | 

 | 

 | 

 |

-   Package weight is generally required except for virtual product categories.
-   Weight should reflect the packaged item.
-   If the fee calculated by weight is higher than the fee calculated by dimensions, weight takes precedence in fee calculation.

### `sku_dimensions`

| Field | Type | Required | Example | Description |
| --- | --- | --- | --- | --- |
| 
`sku_dimensions`

 | 

Struct

 | 

No

 | 

\-

 | 

Dimension information for a SKU package, including length, width, height, and unit.

 |
| 

`length`

 | 

String

 | 

Yes

 | 

`10`

 | 

Package length.

 |
| 

`width`

 | 

String

 | 

Yes

 | 

`10`

 | 

Package width.

 |
| 

`height`

 | 

String

 | 

Yes

 | 

`10`

 | 

Package height.

 |
| 

`unit`

 | 

String

 | 

Yes

 | 

`CENTIMETER`

 | 

Allowed values vary by region. For example, US supports `CENTIMETER` and `INCH`, while other regions support `CENTIMETER`.

 |
| 

**Additional notes:**

 | 

 | 

 | 

 | 

 |

-   Dimensions should reflect the packaged item.
    
-   `sku_weight.unit` and `sku_dimensions.unit` must use the same measurement system.
    

## How does the product logic work?

When SKU-level data is available, the system reads **SKU-level values first**.  
When SKU-level data is not available, the system falls back to **product-level values**.  
If a product-level value is requested while only SKU-level data exists, the system uses the **maximum SKU value** as the fallback.  
If SKU-level data is requested while only product-level data exists, each SKU inherits the **product-level value** by default.

## Which markets are affected?

This capability is currently aligned with the existing launch scope described in the reference GTM materials, with priority support for:

-   **US** (`L2L + POP`)
-   **SEA** (`POP`)

## Who is affected?

This change affects developers and integration partners whose applications are used for:

-   Product creation and editing
-   Product data synchronization
-   Shipping-related attribute management
-   Product query and listing validation

It is also relevant to internal Partner Product, Engineering, Ops, PSO, and Support teams.

## What action is required?

1.  **Update product query handling**Read and parse the new response fields:
    -   `sku_weight`
    -   `sku_dimensions`
2.  **Update product create and edit logic**Support sending the new request fields for each SKU:
    -   `sku_weight`
    -   `sku_dimensions`
3.  **Validate unit and data rules**Ensure:
    -   Weight and dimension units follow regional rules
    -   Weight and dimension units use the same measurement system
    -   Values reflect packaged items instead of unpackaged products
4.  **Align seller-facing and support messaging**Prepare guidance for cases where:
    -   Shipping estimation may be shown as a range for multi-SKU products
    -   SKU-level values override product-level values
    -   Product-level fallback still applies when SKU-level data is not provided
