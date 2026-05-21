# Rate limits

> Source: https://partner.tiktokshop.com/docv2/page/rate-limits
> Section: Developer Guide
> Scraped: 2026-05-21T00:23:56.821Z

---

# **Overview**

-   The TTS Open Platform uses a **Dynamic QPS Allocation** mechanism: your app's QPS quota is calculated dynamically based on **authorized shop scale × API resource characteristics**. **The more shops authorized, the higher your quota.**
-   The platform does **not** expose a fixed QPS value for query. Build a self-adaptive client that throttles itself based on `429` responses, combined with **exponential backoff + jitter + batch APIs + caching**.
-   Receiving a `429` does **not** necessarily mean your app exceeded its rate limit — it can also be a platform-level protective throttle on the API. The handling strategy is the same: **back off and retry**.

# 1\. Why API Rate Limiting Exists

API rate limiting is a foundational mechanism that keeps the platform stable and fair. It serves two goals:

-   **Platform stability** — prevents traffic spikes from overloading the service and degrading access quality for everyone.
-   **Fair resource allocation** — distributes platform capacity equitably among developers, so a few apps cannot monopolize resources at the expense of others.

We strongly recommend every developer adopt industry-standard **throttling, caching, and retry** patterns to build resilient applications.

# 2\. Dynamic QPS Allocation

## 2.1 Two Dimensions of Quota Calculation

Instead of a one-size-fits-all fixed quota, the platform computes the QPS ceiling for each app dynamically along two dimensions:

| **Dimension** | **Effect on Your Quota** |
| --- | --- |
| 
**App Authorization Scale**(number of authorized shops)

 | 

The authorized shop count is the base of your quota. **The more shops your app is authorized for, the higher the total QPS allocated.** If you anticipate business growth and need higher QPS, **the most direct path is to expand your shop authorization scale** — no separate application required.

 |
| 

**API Resource Characteristics**(per-endpoint tiering)

 | 

Each API is graded by read/write nature, lightweight vs. heavy load, and real-time platform load:  
  
\* Lightweight read APIs (e.g. product info lookup) → relatively higher quota  
\* Heavy or write APIs (e.g. order creation, complex analytics) → relatively lower quotaThe ceiling for any single API is adjusted dynamically based on platform conditions.

 |

## 2.2 Rate Limit Isolation Unit

The minimum isolation unit for rate limiting is the **App ID × Authorized Shop** combination:

-   The same shop authorized to different apps → quotas are independent across apps.
-   The same app authorized to multiple shops → quotas are independent across shops.

The QPS quota of each isolation unit is computed dynamically per §2.1; there is no unified fixed value.  
**Implementation tip**: Do not hard-code a QPS threshold in your code. Building a client that adapts to `429` responses is far more robust than wiring in a static value.

# 3\. Response Codes & Handling When Throttled

Distinguishing `429` from `503` correctly is the prerequisite for a robust client. The two have different root causes and different actions:

| **HTTP Status** | **Root Cause & Action** |
| --- | --- |
| 
**429 Too Many Requests**(Rate limited)

 | 

**Two possible root causes**:  
  
1\. Your app exceeded the dynamically allocated QPS within the current isolation unit.  
2\. The API hit a platform-level protective throttle (independent of your app's request rate).**Both cases share the same handling**:  
3\. Stop sending new requests immediately.  
4\. Retry using **exponential backoff + random jitter** (see §4.5).  
5\. ⛔ Never retry immediately at high frequency — it will worsen the throttle and may extend the restriction.

 |
| 

**503 Service Unavailable**

 | 

**Not a rate limit.** It indicates the underlying service is overloaded, in transient failure, or under maintenance.  
  
1\. Usually transient — retry after a short wait.  
2\. If it persists, check the developer announcement page or contact technical support to verify whether a known incident is in progress.

 |

# 4\. Best Practices (5 items, ordered by priority)

**1\. Use Batch APIs (top priority)​**The platform offers **batch endpoints** for many scenarios. Wherever a batch endpoint exists, **do not loop the single-item endpoint** — collapsing N calls into one is the single most effective way to stay below the rate limit.  
**2\. Request Queue & Throttling**Maintain an internal request queue and dispatch at a smooth, controlled rate. Avoid bursts where many requests fire within a single second.  
**3\. Fetch Only What You Need**Trim API requests to the minimum fields and volume your business actually requires. Avoid full-table pulls when an incremental sync would do.  
**4\. Cache Hot Data**Cache low-churn, high-read data (e.g. product master data, shop config, category mappings) on your side. This drastically reduces unnecessary API traffic.  
**5\. Idempotency + Tiered Error Handling**

-   Make all write operations (create/update) idempotent so retries cannot produce duplicates or dirty data.
    
-   Distinguish `429` / `503` / business errors / network errors and apply the right response to each class.
    

## 4.5 Exponential Backoff + Random Jitter (the standard `429` handler)

When you receive a `429`, you must retry with **Exponential Backoff + Jitter**:

PLAIN

Word Wrap

```
wait = base_delay * (2 ** retry_count) + random_jitter
```

**Key parameters**:

-   `base_delay` — start at 1 second.
-   **Double the wait time on each failure** (1s → 2s → 4s → 8s …) and cap it (e.g. 60s).
-   `random_jitter` — add a small random offset (e.g. 0–500ms) on top of the wait time to **prevent multiple instances from synchronizing their retries and creating a new spike**.
-   Set a max retry count (e.g. 5). Beyond that, surface an alert or fall back to a degraded path.

**Python reference implementation**:

PYTHON

Word Wrap

```
import random, time  
  
def backoff_sleep(retry: int, base: float = 1.0, cap: float = 60.0):  
    wait = min(base * (2 ** retry) + random.uniform(0, 0.5), cap)  
    time.sleep(wait)
```

# 5\. FAQ

**Q1: How do I know exactly how much QPS quota my app gets?**  
A: Quotas are computed dynamically. The platform **does not expose a fixed quota query API**. The correct approach is to make your app self-adaptive: react to `429` responses to throttle dynamically, rather than relying on a hard-coded threshold.

**Q2: My business is growing fast. How do I increase my QPS quota?**  
A: The core idea of the dynamic allocation mechanism is that **your quota grows automatically with your authorized shop scale**. The most direct and recommended path is:

-   **Expand your shop authorization scale** — the more shops your app is authorized for, the higher the total QPS the platform allocates. No separate application needed.
    
-   For large promotions or special peak events, if you have already implemented every best practice and still anticipate hitting bottlenecks, contact your Account Manager (AM) or platform technical support in advance to evaluate options together.
    

**Q3: I rarely send requests — why do I still see 429 occasionally?**  
A: There are three common causes:

1.  **Sub-second bursts**: even when overall QPS is low, sending several requests within the same second can trigger throttling. Use a request queue to smooth dispatch.
    
2.  **Platform-level protective throttling on the API**: in rare cases, when an API's platform-wide load reaches a protective threshold, the platform temporarily lowers the available quota for all callers. This is independent of your app's request rate, and the handling is the same — back off and retry per §4.5.
    
3.  **Concurrency contention within the same isolation unit**: although rate limiting is scoped to `App × Shop`, multiple instances of your app can still stack requests within millisecond windows.
    

**Q4: How is QPS counted for batch endpoints vs. single-item endpoints?**  
A: Batch endpoints are typically counted as **one call per request** (not per item in the batch). Whenever a batch endpoint is available, prefer it. Refer to each endpoint's documentation for its exact counting rule.
