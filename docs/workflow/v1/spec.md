# Wookiee Decor — Fix Workflow v1

**Source:** Stakeholder (Muhammad Maaz) feedback via WhatsApp, 2026-07-21, reviewing the site after the color palette / product gallery update delivered same morning.

**Purpose:** Single source of truth for the next round of fixes. Each item below is grouped by theme, includes what's actually wrong (verified against the codebase where possible, not just assumed from the feedback wording), a proposed fix, and anything that needs a decision before work starts.

---

## 1. Business model change: shipping — ✅ DONE

**Feedback:** "Remove shipping from banner" → "It's repetitive" → "Don't use free delivery" → "Use flat rate" → "5.99" / "Or" / "6.99"

**Resolution:** went with **£5.99** (Abubakar's call, on Claude's recommendation — more standard UK flat-rate price point than £6.99). Implemented as a **setting**, not hardcoded copy: `wookiee_setting_shipping_rate`, editable at Appearance → Wookiee Settings, read live by the announcement bar, homepage trust bar, hero stat badge, and the Shipping policy page (via `[wookiee_field key="shipping_rate"]`). Changing the number in one place now updates everywhere, including already-baked pages.

**Not yet done:** the actual WooCommerce shipping zone/method configuration (cart/checkout calculation) still needs to be set to a flat £5.99 rate — this spec item covered the *messaging*, not the checkout math. Needs a pass through WooCommerce → Settings → Shipping.

---

## 2. Policy page content gaps — ✅ DONE (shipping/handling), self-serve (returns address)

**Feedback:** "Mention handling and transit time in shipping" / "Mention return address in refund policy"

**Resolution:**
- Shipping policy: rewrote the delivery cost table around the flat rate + a "Handling & Transit Time" column, both pulled live from settings (`shipping_rate`, `shipping_dispatch`).
- Returns address: rather than guess or block on an answer, this (and every other contact detail across all policy pages — email, phone, company number, registered address, ~30 instances total) now reads from the new **Appearance → Wookiee Settings** admin page via shortcode. **Returns address defaults to the registered office address if left blank** — go to the settings page and fill in a different address there if returns actually go somewhere else (a separate warehouse, etc.), otherwise no action needed.

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

## 5. About page — image and layout — ✅ DONE

**Feedback:** "About page overlay issue" / "Broken image"

**Resolution:** the floating stat card's CSS now caps its width to the container (`max-width: min(230px, calc(100% - 40px))`) instead of a bare fixed 250px, and drops to static flow below the image entirely under 480px instead of overlapping it. The broken image itself was confirmed fixed after the About page was deleted/regenerated earlier in this session; since company details on this page (registered address, company number) now also read from Wookiee Settings via shortcode, this page won't need another delete/regenerate cycle if those details ever change again.

---

## 6. Footer — ✅ mostly done

**Feedback:** "Change the pallet of footer" / "Payment icons not rendered properly"

**Resolution:**
- **Clutter:** consolidated the newsletter block (was: kicker + heading + lead + 3 bullets + separate signup card) into a single slim inline band; merged the 4-column info section down; tightened spacing throughout.
- **Payment icons:** the Visa and PayPal icons were crude hand-approximated shape blobs that didn't actually resemble the real logos (unlike Mastercard's recognizable two-circle mark, or Amex/Apple Pay's clean text style) — likely the actual "not rendered properly" issue. Replaced with clean text-based badges matching the Amex/Apple Pay treatment already in use. **Still worth a look after redeploy** to confirm this was the actual problem, since it was diagnosed from the code rather than a screenshot.

---

## 7. Social media links — ✅ DONE (mechanism), needs your input (URLs)

**Feedback:** "Remove social media if you don't have pages" / "Atleast make fb"

**Resolution:** social icons are now driven by the Wookiee Settings admin page — each of Facebook/Instagram/LinkedIn/Pinterest only renders in the footer if a URL is actually filled in there; blank fields hide their icon entirely. No code changes needed to add or remove one going forward.

**Still needed:** the actual Facebook URL (or whichever accounts exist) entered into the settings page — nothing shows until that's filled in.

---

## 8. Navigation

**Feedback:** "If possible make breadcrumbs"

**Resolution:** ✅ DONE. Regular pages (About, Contact, etc.) get a simple "Home > Page Title" breadcrumb via a new `wookiee_breadcrumb()` function called from `index.php`. Shop/product pages already had WooCommerce's own breadcrumb firing automatically (it was just unstyled/easy to miss) — now styled to match the rest of the site.

---

## Execution order (updated)

1. ~~Footer decluttering, breadcrumbs, About page CSS fix~~ — ✅ done
2. ~~Shipping rate + messaging~~ — ✅ done (£5.99, now a setting)
3. ~~Theme settings admin panel~~ — ✅ done, resolves the returns-address and social-URL blockers as self-serve rather than needing a code change
4. Remaining: WooCommerce shipping method configuration (checkout math, not just messaging — see §1)
5. Remaining: product catalog rework (§3, §4) — waiting on real product data or confirmation to just shrink the placeholder set
6. Remaining: reconfirm the payment icon fix actually resolved the "not rendered properly" complaint (§6)

## Open questions summary (blocking items)

| # | Question | Blocks |
|---|---|---|
| 1 | Real product lineup, or just confirm shrinking the placeholder set to whatever the photo pool can support without repeats? | §3, §4 |
| 2 | Facebook page URL (and any other real social accounts) — no longer blocks any code work, just needs entering at Appearance → Wookiee Settings whenever ready | §7 |
| 3 | Confirm on the live site whether the payment icon fix actually resolved "not rendered properly" | §6 |

~~What specifically is wrong with the footer palette?~~ — resolved: it was never about color, it's about density (see §6).
