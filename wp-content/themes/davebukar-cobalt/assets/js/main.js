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

	/* Mobile nav drawer — off-canvas menu below the 768px breakpoint */
	var navToggle = document.getElementById("navToggle");
	var navDrawer = document.getElementById("navDrawer");
	if (navToggle && navDrawer) {
		var drawerLastFocused = null;

		var openDrawer = function () {
			drawerLastFocused = document.activeElement;
			navDrawer.classList.add("is-open");
			navDrawer.setAttribute("aria-hidden", "false");
			navToggle.setAttribute("aria-expanded", "true");
			document.body.style.overflow = "hidden";
			var closeBtn = navDrawer.querySelector(".drawer__close");
			if (closeBtn) requestAnimationFrame(function () { closeBtn.focus(); });
		};

		var closeDrawer = function () {
			navDrawer.classList.remove("is-open");
			navDrawer.setAttribute("aria-hidden", "true");
			navToggle.setAttribute("aria-expanded", "false");
			document.body.style.overflow = "";
			if (drawerLastFocused && drawerLastFocused.focus) drawerLastFocused.focus();
		};

		navToggle.addEventListener("click", function () {
			navDrawer.classList.contains("is-open") ? closeDrawer() : openDrawer();
		});

		navDrawer.querySelectorAll("[data-drawer-close]").forEach(function (el) {
			el.addEventListener("click", closeDrawer);
		});

		// Any real navigation inside the drawer (link or the Book-a-call
		// trigger) should close the drawer first, so it doesn't sit open
		// behind the lead-capture modal or a page navigation.
		navDrawer.querySelectorAll("a, .js-book-call").forEach(function (el) {
			el.addEventListener("click", closeDrawer);
		});

		document.addEventListener("keydown", function (e) {
			if (e.key === "Escape" && navDrawer.classList.contains("is-open")) closeDrawer();
		});
	}

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

	/* Lead-capture modal ("Book a call") */
	var leadform = document.getElementById("leadform");
	if (!leadform) return;

	var lfForm        = document.getElementById("leadform-form");
	var lfSubmit       = document.getElementById("leadform-submit");
	var lfError        = document.getElementById("leadform-error");
	var lfStatus       = document.getElementById("leadform-status");
	var lfFormStep     = leadform.querySelector('[data-leadform-step="form"]');
	var lfSuccessStep  = leadform.querySelector('[data-leadform-step="success"]');
	var lfSuccessMsg   = document.getElementById("leadform-success-message");
	var lfLastFocused  = null;

	function lfShowError(message) {
		lfError.textContent = message;
		lfError.hidden = false;
	}

	function lfClearError() {
		lfError.hidden = true;
		lfError.textContent = "";
	}

	// The chip always shows the real state of the request — DRAFT before
	// send, then the actual HTTP-style status the endpoint returned. Never
	// a decorative label disconnected from what's actually happening.
	function lfSetStatus(text, state) {
		if (!lfStatus) return;
		lfStatus.textContent = text;
		if (state) {
			lfStatus.dataset.state = state;
		} else {
			delete lfStatus.dataset.state;
		}
	}

	function openLeadform() {
		lfLastFocused = document.activeElement;
		leadform.classList.add("is-open");
		leadform.setAttribute("aria-hidden", "false");
		document.body.style.overflow = "hidden";

		lfClearError();
		lfSetStatus("DRAFT", null);
		lfFormStep.hidden = false;
		lfSuccessStep.hidden = true;
		if (lfForm) lfForm.reset();

		var firstField = document.getElementById("lf-name");
		if (firstField) requestAnimationFrame(function () { firstField.focus(); });
	}

	function closeLeadform() {
		leadform.classList.remove("is-open");
		leadform.setAttribute("aria-hidden", "true");
		document.body.style.overflow = "";
		if (lfLastFocused && lfLastFocused.focus) lfLastFocused.focus();
	}

	document.querySelectorAll(".js-book-call").forEach(function (btn) {
		btn.addEventListener("click", openLeadform);
	});

	leadform.querySelectorAll("[data-leadform-close]").forEach(function (el) {
		el.addEventListener("click", closeLeadform);
	});

	document.addEventListener("keydown", function (e) {
		if (e.key === "Escape" && leadform.classList.contains("is-open")) closeLeadform();
	});

	if (lfForm) {
		lfForm.addEventListener("submit", function (e) {
			e.preventDefault();
			lfClearError();

			var name    = document.getElementById("lf-name").value.trim();
			var email   = document.getElementById("lf-email").value.trim();
			var message = document.getElementById("lf-message").value.trim();

			if (!name || !email || !message) {
				lfShowError("Please fill in your name, email, and a short note on what you need.");
				return;
			}

			lfSubmit.disabled = true;
			lfSubmit.dataset.state = "loading";
			lfSetStatus("SENDING…", "sending");

			var formData = new FormData(lfForm);
			formData.append("action", "dbt_book_call");
			formData.append("nonce", window.dbtBookCall ? window.dbtBookCall.nonce : "");

			fetch(window.dbtBookCall ? window.dbtBookCall.ajaxUrl : "/wp-admin/admin-ajax.php", {
				method: "POST",
				credentials: "same-origin",
				body: formData,
			})
				.then(function (res) {
					return res.json().then(function (data) {
						return { status: res.status, data: data };
					});
				})
				.then(function (result) {
					lfSubmit.disabled = false;
					lfSubmit.removeAttribute("data-state");

					if (result.data && result.data.success) {
						lfSetStatus(result.status + " OK", "ok");
						lfSuccessMsg.textContent = (result.data.data && result.data.data.message) || "We read every one ourselves — expect a reply within one business day.";
						lfFormStep.hidden = true;
						lfSuccessStep.hidden = false;
					} else {
						lfSetStatus(result.status + " ERR", "err");
						lfShowError((result.data && result.data.data && result.data.data.message) || "Something went wrong — please try again.");
					}
				})
				.catch(function () {
					lfSubmit.disabled = false;
					lfSubmit.removeAttribute("data-state");
					lfSetStatus("OFFLINE", "err");
					lfShowError("Network error — please check your connection and try again.");
				});
		});
	}
})();
