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

**Root cause (verified, more specific than first thought):** Two compounding issues, not a database bug —

1. The 6 dummy products span mismatched categories and include a **"Compact Mobility Scooter (Foldable)"** — a mobility aid product with nothing to do with a home storage/organization brand.
2. **Cross-product image reuse from the gallery work**: with only ~10 usable stock photos for 6 products × up to 3 gallery images each, several photos are literally reused across *different* products — `lifestyle.png` appears in three separate products' galleries; `bathroom-shelf.png` is one product's main photo and another's gallery image; same pattern for `wookiee-prod-organizer.png` and the two bamboo-themed products. Clicking between products, the same photo shows up attached to what's supposed to be a different item — this is almost certainly what reads as "duplicate."

The product-creation code already guards against literal duplicate database rows by title, so this isn't that.

**Proposed fix:** Replace the current 6-product placeholder set with a smaller, coherent single-niche catalog (home storage & organization only — drop the scooter entirely), sized so each product can have genuinely distinct photography rather than stretching ~10 stock photos across too many products. Reshuffling which stock photo goes where reduces visible overlap somewhat but can't eliminate it at 6 products with this photo pool — the real fix is fewer products and/or real photography, not further shuffling.

**Decision needed:** what the real product lineup should be (real products the business will actually sell), or at minimum, confirmation that the placeholder set should shrink to however many products the existing photo pool can genuinely support without repeats.

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

**Clarified direction (from Abubakar):** not about color specifically — the footer should read as **clean and modern, not cluttered**. This is a density/layout complaint, not a palette complaint.

**Root cause:**
- **Clutter:** the newsletter section currently stacks a kicker, heading, lead paragraph, 3 bullet points, *and* a separate signup card side-by-side with a 4-column link/info section below it and a sub-footer below that — a lot of vertical weight and visual sections before you reach the bottom. Fix: consolidate the newsletter block into a single slim band, reduce info density in the columns, tighten spacing throughout.
- **Payment icons:** checked directly — the Visa/Mastercard/PayPal/Amex/Apple Pay SVGs use their own brand-specific colors and were *not* touched by the recent palette sweep, so this isn't a regression from that work. The actual rendering problem (sizing? wrapping? invisible against the dark background at certain sizes?) hasn't been confirmed visually yet.

**Decision/info needed:** a screenshot of the current payment icons as rendered, to diagnose the actual rendering problem.

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

1. **Unblocked, start now:** footer decluttering (§6, direction confirmed: clean/modern/not cluttered), breadcrumbs (§8, self-contained), About page floating card CSS fix (§5, code fix, no decision needed).
2. **Get the remaining open decisions** (shipping rate; returns address; social accounts; product lineup) — several other fixes depend on these and doing them twice wastes a delete/redeploy cycle each time.
3. Shipping rate change (banner, trust bar, hero badge, shipping policy, WooCommerce config)
4. Returns policy address
5. Product catalog rework (drop scooter, resolve shoe-storage category, reduce cross-product image reuse)
6. About page: delete/regenerate page content (routine, after the CSS fix in step 1)
7. Footer payment icons (needs a current screenshot to diagnose first)
8. Social media links (needs the Facebook URL / account decision first)

## Open questions summary (blocking items)

| # | Question | Blocks |
|---|---|---|
| 1 | Flat shipping rate: £5.99 or £6.99 (or other)? | §1, and downstream copy in §2 |
| 2 | Returns address — same as registered office, or a separate warehouse address? | §2 |
| 3 | Real product lineup, or just "drop the scooter" from the existing placeholders? | §3, §4 |
| 4 | Facebook page URL (if it exists)? Keep/drop Instagram/LinkedIn/Pinterest icons? | §7 |
| 5 | Current screenshot of payment icons issue (to diagnose before fixing) | §6 |

~~What specifically is wrong with the footer palette?~~ — resolved: it was never about color, it's about density (see §6).
