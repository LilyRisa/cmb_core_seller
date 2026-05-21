# For all EU markets (EU5) Get Shipping Providers API

> Source: https://partner.tiktokshop.com/docv2/page/7z0wsn3s
> Section: Changelog
> Scraped: 2026-05-21T00:34:47.255Z

---

# What is changing?

Starting **March 26, 2026**, the **Get Shipping Providers API** has been enhanced to support accurate **EU shipping provider resolution**.  
Due to interconnected logistics services within the EU, a single delivery option can correspond to **multiple cross-border shipping routes**. To return the correct shipping provider for a specific route, ISVs are now required to specify both:

-   the **shipping origin country** (warehouse), and
-   the **destination country** (buyer).

Two new input parameters have been introduced to enable this behavior:

-   `warehouse_region`
-   `buyer_region`

# **API endpoint changes**

-   **Get Shipping Providers**

# **New parameters added:**

-   `warehouse_region` – ISO 3166-1 alpha-2 country code of the warehouse (shipping origin)
-   `buyer_region` – ISO 3166-1 alpha-2 country code of the buyer (delivery destination)

When these parameters are provided, the API will return the **shipping provider ID and name** that corresponds to the specified delivery option and cross-border route.

# **Which markets are affected?**

-   All **EU markets**
-   **Intra-EU cross-border shipping** scenarios

# Who is affected?

Developers and ISVs whose applications:

-   Manage logistics, fulfillment, or shipping configuration
-   Support **intra-EU delivery flows**
-   Resolve shipping providers programmatically using delivery options

# What action is required?

1.  Update integrations to pass `warehouse_region` and `buyer_region` when calling **Get Shipping Providers** for EU shipments.
2.  Ensure shipping provider resolution logic accounts for **origin–destination combinations**, rather than assuming a single provider per delivery option.

# **General recommendation**

-   This API **can be called at parcel level** to determine the correct shipping provider in real time.
-   Alternatively, ISVs may **cache shipping provider mappings** and refresh them periodically to stay aligned with the latest logistics configuration.
-   If warehouse locations or shipping destinations change frequently, calling the API in real time is recommended to avoid incorrect provider selection.
