(function () {
	"use strict";

	var reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

	/* Reveal on scroll — one orchestrated entrance, not per-section fatigue */
	var revealEls = document.querySelectorAll(".reveal");
	if (revealEls.length) {
		if (reduceMotion || !("IntersectionObserver" in window)) {
			revealEls.forEach(function (el) { el.classList.add("is-in"); });
		} else {
			var io = new IntersectionObserver(
				function (entries) {
					entries.forEach(function (entry) {
						if (entry.isIntersecting) {
							entry.target.classList.add("is-in");
							io.unobserve(entry.target);
						}
					});
				},
				{ threshold: 0.15 }
			);
			revealEls.forEach(function (el) { io.observe(el); });
		}
	}

	/* Code-card type-in — plays once, then static */
	document.querySelectorAll(".code-card[data-typein]").forEach(function (card) {
		var lines = card.querySelectorAll(".code-line");
		if (!lines.length) return;

		if (reduceMotion) {
			lines.forEach(function (line) { line.classList.add("is-typed"); });
			return;
		}

		var revealLines = function () {
			lines.forEach(function (line, i) {
				setTimeout(function () { line.classList.add("is-typed"); }, i * 160);
			});
		};

		if ("IntersectionObserver" in window) {
			var typeIo = new IntersectionObserver(
				function (entries) {
					entries.forEach(function (entry) {
						if (entry.isIntersecting) {
							revealLines();
							typeIo.unobserve(entry.target);
						}
					});
				},
				{ threshold: 0.4 }
			);
			typeIo.observe(card);
		} else {
			revealLines();
		}
	});

	/* Command palette (N13) */
	var pill = document.getElementById("searchpill");
	var cmdk = document.getElementById("cmdk");
	if (!pill || !cmdk) return;

	var input = document.getElementById("cmdk-input");
	var resultsEl = document.getElementById("cmdk-results");
	var destinations = window.dbtDestinations || [];
	var activeIndex = 0;
	var lastFocused = null;

	function itemEls() {
		return Array.prototype.slice.call(resultsEl.querySelectorAll(".cmdk__item"));
	}

	function render(filter) {
		var q = (filter || "").trim().toLowerCase();
		var groups = {};
		destinations.forEach(function (d) {
			if (q && d.label.toLowerCase().indexOf(q) === -1) return;
			groups[d.group] = groups[d.group] || [];
			groups[d.group].push(d);
		});

		resultsEl.innerHTML = "";
		var groupNames = Object.keys(groups);
		if (!groupNames.length) {
			var empty = document.createElement("p");
			empty.className = "cmdk__group";
			empty.textContent = "No matches";
			resultsEl.appendChild(empty);
			return;
		}

		groupNames.forEach(function (groupName) {
			var label = document.createElement("p");
			label.className = "cmdk__group";
			label.textContent = groupName;
			resultsEl.appendChild(label);

			groups[groupName].forEach(function (d) {
				var item = document.createElement("button");
				item.type = "button";
				item.className = "cmdk__item";
				item.textContent = d.label;
				item.dataset.url = d.url;
				resultsEl.appendChild(item);
			});
		});

		activeIndex = 0;
		setActive(0);
	}

	function setActive(i) {
		var items = itemEls();
		if (!items.length) return;
		activeIndex = (i + items.length) % items.length;
		items.forEach(function (el, idx) { el.classList.toggle("is-active", idx === activeIndex); });
	}

	function goToActive() {
		var items = itemEls();
		var el = items[activeIndex];
		if (el && el.dataset.url) window.location.href = el.dataset.url;
	}

	function openCmdk() {
		lastFocused = document.activeElement;
		cmdk.classList.add("is-open");
		cmdk.setAttribute("aria-hidden", "false");
		document.body.style.overflow = "hidden";
		render("");
		if (input) {
			input.value = "";
			requestAnimationFrame(function () { input.focus(); });
		}
	}

	function closeCmdk() {
		cmdk.classList.remove("is-open");
		cmdk.setAttribute("aria-hidden", "true");
		document.body.style.overflow = "";
		if (lastFocused && lastFocused.focus) lastFocused.focus();
	}

	pill.addEventListener("click", openCmdk);

	cmdk.querySelectorAll("[data-close]").forEach(function (el) {
		el.addEventListener("click", closeCmdk);
	});

	document.addEventListener("keydown", function (e) {
		var isOpen = cmdk.classList.contains("is-open");
		var meta = e.metaKey || e.ctrlKey;

		if (meta && e.key.toLowerCase() === "k") {
			e.preventDefault();
			isOpen ? closeCmdk() : openCmdk();
			return;
		}

		if (!isOpen) return;

		if (e.key === "Escape") { closeCmdk(); return; }
		if (e.key === "ArrowDown") { e.preventDefault(); setActive(activeIndex + 1); return; }
		if (e.key === "ArrowUp") { e.preventDefault(); setActive(activeIndex - 1); return; }
		if (e.key === "Enter") { e.preventDefault(); goToActive(); return; }
	});

	resultsEl.addEventListener("click", function (e) {
		var item = e.target.closest(".cmdk__item");
		if (item && item.dataset.url) window.location.href = item.dataset.url;
	});

	if (input) {
		input.addEventListener("input", function () { render(input.value); });
	}
})();
