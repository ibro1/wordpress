# Wookiee Decor — Fix Workflow v1

**Source:** Stakeholder (Muhammad Maaz) feedback via WhatsApp, 2026-07-21, reviewing the site after the color palette / product gallery update delivered same morning.

**Purpose:** Single source of truth for the next round of fixes. Each item below is grouped by theme, includes what's actually wrong (verified against the codebase where possible, not just assumed from the feedback wording), a proposed fix, and anything that needs a decision before work starts.

---

## 1. Business model change: shipping

**Feedback:** "Remove shipping from banner" → "It's repetitive" → "Don't use free delivery" → "Use flat rate" → "5.99" / "Or" / "6.99"

**What this means:** The stakeholder is moving away from "free delivery over £50" messaging entirely, in favor of a flat shipping rate. He proposed two candidate prices (£5.99 or £6.99) without settling on one — **this is a decision needed before implementation**, not something to guess at.

**Where "free delivery" messaging currently lives (all need updating once the rate is decided):**
- Announcement bar (`header.php`): "Free UK delivery on orders over £50 · 30-day hassle-free returns · Secure checkout"
- Homepage trust bar (`front-page.php`): "Free delivery — On orders over £50"
- Homepage hero stat badge (`front-page.php`): "3-5 day UK delivery, dispatched from Cowdenbeath"
- Shipping policy page content (`inc/static-content.php`)
- Possibly WooCommerce shipping zone/method configuration itself (not yet inspected — need to check whether a "free shipping over £50" method is actually configured in WooCommerce settings, separate from the marketing copy)

**"It's repetitive"** most likely refers to delivery/returns messaging appearing in this many places at once (banner + trust bar + hero badge) rather than being a single, clear statement — worth consolidating once the new rate is settled, not just find-and-replacing the number in four places.

**Decision needed:** £5.99 or £6.99 flat rate (or a different number — the stakeholder may have continued this conversation past what's visible in the screenshot).

**Proposed fix (once rate is decided):**
- Update WooCommerce shipping settings to a flat-rate method at the agreed price
- Rewrite the announcement bar, trust bar, and hero badge copy around the flat rate instead of a threshold
- Update the Shipping policy page copy accordingly (see also §2, handling/transit time)

---

## 2. Policy page content gaps

**Feedback:** "Mention handling and transit time in shipping" / "Mention return address in refund policy"

**What's actually there now:** Both pages exist (`inc/static-content.php`, `'shipping'` and `'returns'` keys) but were written as generic policy boilerplate without these specifics.

**Proposed fix:**
- Shipping policy: add explicit handling time (e.g. "orders dispatched within 24 hours") and transit time (e.g. "3-5 working days" — already used elsewhere on the site, so at least internally consistent) alongside the new flat-rate shipping cost from §1.
- Returns/refunds policy: add the actual return address (**registered office address is already established elsewhere on the site as "28 Johnston Park, Cowdenbeath, Scotland, KY4 9AZ" — need to confirm with stakeholder whether returns go to this same address or a different warehouse/returns address**).

**Decision needed:** confirm the returns address (same as registered office, or different).

---

## 3. Product catalog

**Feedback:** "Products are duplicate" / "And go for one niche only"

**Root cause (verified):** The 6 dummy products span mismatched categories and, more importantly, include a **"Compact Mobility Scooter (Foldable)"** — a mobility aid product that has nothing to do with a home storage/organization brand. This is almost certainly what reads as "duplicate/repetitive" (two bamboo-themed products, two bathroom-themed products, one unrelated scooter) rather than an actual code bug creating duplicate database entries — the product-creation code already guards against literal duplicates by title.

**Proposed fix:** Replace the current 6-product placeholder set with a smaller, coherent single-niche catalog (home storage & organization only — drop the scooter entirely). Exact product lineup is a content decision, not something to invent unilaterally.

**Decision needed:** what the real product lineup should be (real products the business will actually sell), or at minimum, confirmation that the placeholder set should just drop the scooter and be reworded to feel less repetitive within the existing storage niche.

---

## 4. Empty category: Shoe storage

**Feedback:** "Remove the shoe storage collection or add any product in it"

**Root cause (verified):** `wookiee_create_dummy_products()` creates a `shoe-storage` product category but never assigns any product to it. It's linked from the footer ("Shoe storage") and would resolve to an empty archive page.

**Decision needed:** remove the category + footer link, or add a product to it. (Likely resolved together with §3 if the catalog gets reworked.)

---

## 5. About page — image and layout

**Feedback:** "About page overlay issue" / "Broken image" (screenshot showed the "Our Range and Approach" section, with a broken image icon where a bathroom shelf photo should be, and a separate screenshot showed the floating stat card overlapping the hero image awkwardly)

**Root cause:**
- **Broken image:** the About page's stored content was baked into the database before a hardcoded-path fix landed in the theme code earlier this session. Static page content doesn't auto-update from theme code changes — it needs the page deleted and regenerated. **This may already be stale again** after the most recent palette/image work, so it needs re-verification after redeploy, not just a repeat of the old fix.
- **Overlay issue:** the floating "UK Private-Label Retailer / Wookiee / Operated by Wookiee Decor Ltd" card is positioned with `position: absolute; bottom: 20px; right: 20px` against the hero image, sized `max-width: 250px`. At the viewport/image size shown in the stakeholder's screenshot, the card overlaps awkwardly rather than sitting cleanly at the corner — this is a real CSS positioning issue, not just a stale-content problem.

**Proposed fix:** delete + regenerate the About page (routine at this point), and separately fix the floating card's CSS to size/position more robustly across viewport widths (likely needs to move from raw inline absolute positioning to a proper responsive pattern, matching how the homepage's hero stat badge already handles this better).

---

## 6. Footer

**Feedback:** "Change the pallet of footer" / "Payment icons not rendered properly"

**Root cause:**
- **Palette:** this is feedback given *after* seeing the new Warm Terracotta & Ink palette already applied — the stakeholder wants the footer specifically adjusted further, but didn't say what's wrong with it (too dark? doesn't match? wants a lighter variant?). **Needs clarification before touching anything** — changing it blind risks going back and forth.
- **Payment icons:** checked directly — the Visa/Mastercard/PayPal/Amex/Apple Pay SVGs use their own brand-specific colors and were *not* touched by the recent palette sweep, so this isn't a regression from that work. The actual rendering problem (sizing? wrapping? invisible against the dark background at certain sizes?) hasn't been confirmed visually yet.

**Decision/info needed:** a screenshot of the current payment icons as rendered, and specific direction on what's wrong with the footer palette (not just "change it").

---

## 7. Social media links

**Feedback:** "Remove social media if you don't have pages" / "Atleast make fb"

**Root cause:** footer social icons (Facebook, Instagram, LinkedIn, Pinterest) all link to `#` — placeholders, no real accounts behind them.

**Proposed fix:** at minimum, set up a real Facebook page and link it; remove the other three placeholder icons (Instagram/LinkedIn/Pinterest) until real accounts exist for those too, rather than link to nothing.

**Decision needed:** does a Facebook page exist/get created, and what's the URL? Are Instagram/LinkedIn/Pinterest accounts planned at all, or should those icons come out entirely for now?

---

## 8. Navigation

**Feedback:** "If possible make breadcrumbs"

**Proposed fix:** add breadcrumb navigation (Home > Shop > Product Name, etc.) across shop/product/page templates. WooCommerce has built-in breadcrumb support (`woocommerce_breadcrumb()`) that isn't currently being called anywhere in this theme — straightforward to wire up and style to match the site.

---

## Execution order

Roughly in dependency order — items with open decisions are blocked until those are answered, so the sequence below assumes answers come back in this rough priority:

1. **Get the two open decisions first** (shipping rate; returns address; footer palette direction; social accounts; product lineup) — several other fixes depend on these and doing them twice wastes a delete/redeploy cycle each time.
2. Shipping rate change (banner, trust bar, hero badge, shipping policy, WooCommerce config)
3. Returns policy address
4. Product catalog rework (drop scooter, resolve shoe-storage category)
5. About page: fix floating card CSS (code fix, no decision needed) + delete/regenerate page content
6. Breadcrumbs (self-contained, no decisions needed — can happen anytime)
7. Footer payment icons (needs a current screenshot to diagnose first)
8. Footer palette adjustment (needs direction first)
9. Social media links (needs the Facebook URL / account decision first)

## Open questions summary (blocking items)

| # | Question | Blocks |
|---|---|---|
| 1 | Flat shipping rate: £5.99 or £6.99 (or other)? | §1, and downstream copy in §2 |
| 2 | Returns address — same as registered office, or a separate warehouse address? | §2 |
| 3 | Real product lineup, or just "drop the scooter" from the existing placeholders? | §3, §4 |
| 4 | What specifically is wrong with the footer palette? | §6 |
| 5 | Facebook page URL (if it exists)? Keep/drop Instagram/LinkedIn/Pinterest icons? | §7 |
| 6 | Current screenshot of payment icons issue (to diagnose before fixing) | §6 |
