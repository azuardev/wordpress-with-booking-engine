/**
 * CBE Availability Checker
 * Handles real-time availability checks and price estimates
 */
(function () {
  if (typeof window.CBEAvailability !== "undefined") {
    return;
  }

  const CBEAvailability = {
    getCurrentSearchParams: function () {
      return new URLSearchParams(window.location.search || "");
    },

    buildSearchUrl: function (form) {
      const params = new URLSearchParams();
      const checkinInput = form.querySelector(
        'input[name="checkin_date"], input[data-cbe-checkin]',
      );
      const checkoutInput = form.querySelector(
        'input[name="checkout_date"], input[data-cbe-checkout]',
      );
      const cabinIdInput = form.querySelector(
        'input[name="cabin_id"], select[name="cabin_id"], input[data-cbe-cabin-id], select[data-cbe-cabin-id]',
      );
      const groupInput = form.querySelector(
        'input[name="group"], select[name="group"], input[data-cbe-group], select[data-cbe-group]',
      );
      const guestsInput = form.querySelector(
        'select[name="total_guests"], input[name="total_guests"]',
      );
      const onlyAvailableInput = form.querySelector(
        "[data-cbe-results-available]",
      );

      params.set("cbe_search", "1");
      params.set("cbe_checkin", checkinInput ? checkinInput.value : "");
      params.set("cbe_checkout", checkoutInput ? checkoutInput.value : "");

      if (guestsInput && guestsInput.value) {
        params.set("cbe_guests", guestsInput.value);
      }

      if (cabinIdInput && cabinIdInput.value) {
        params.set("cbe_cabin", cabinIdInput.value);
      }

      if (groupInput && groupInput.value) {
        params.set("cbe_group", groupInput.value);
      }

      params.set(
        "cbe_only_available",
        onlyAvailableInput && !onlyAvailableInput.checked ? "0" : "1",
      );

      const baseUrl =
        form.getAttribute("data-cbe-results-page") ||
        form.getAttribute("action") ||
        window.location.href;
      const targetUrl = new URL(baseUrl, window.location.origin);
      targetUrl.search = params.toString();
      return targetUrl.toString();
    },

    maybeAutoSearchFromQuery: function (form) {
      if (!form.classList.contains("cbe-search-form")) {
        return;
      }

      const query = this.getCurrentSearchParams();
      if (query.get("cbe_search") !== "1") {
        return;
      }

      const checkinInput = form.querySelector(
        'input[name="checkin_date"], input[data-cbe-checkin]',
      );
      const checkoutInput = form.querySelector(
        'input[name="checkout_date"], input[data-cbe-checkout]',
      );

      if (checkinInput && !checkinInput.value) {
        checkinInput.value = query.get("cbe_checkin") || "";
      }

      if (checkoutInput && !checkoutInput.value) {
        checkoutInput.value = query.get("cbe_checkout") || "";
      }

      if (
        checkinInput &&
        checkoutInput &&
        checkinInput.value &&
        checkoutInput.value
      ) {
        this.triggerAvailabilityCheck(form);
      }
    },

    apiBase:
      window.wpApiSettings && window.wpApiSettings.root
        ? window.wpApiSettings.root.replace(/\/$/, "") + "/cbe/v1"
        : "/wp-json/cbe/v1",

    /**
     * Parse date from string (Y-m-d format)
     */
    parseDate: function (dateStr) {
      if (!dateStr) return null;
      const parts = dateStr.split("-");
      if (parts.length !== 3) return null;
      const date = new Date(parts[0], parseInt(parts[1]) - 1, parts[2]);
      if (isNaN(date.getTime())) return null;
      return date;
    },

    /**
     * Format date to Y-m-d
     */
    formatDate: function (date) {
      if (!date || !(date instanceof Date)) return "";
      const year = date.getFullYear();
      const month = String(date.getMonth() + 1).padStart(2, "0");
      const day = String(date.getDate()).padStart(2, "0");
      return `${year}-${month}-${day}`;
    },

    /**
     * Calculate nights between two dates
     */
    calculateNights: function (checkinStr, checkoutStr) {
      const checkin = this.parseDate(checkinStr);
      const checkout = this.parseDate(checkoutStr);
      if (!checkin || !checkout) return 0;

      const diffTime = checkout.getTime() - checkin.getTime();
      if (diffTime <= 0) return 0;

      return Math.round(diffTime / (1000 * 60 * 60 * 24));
    },

    /**
     * Format currency
     */
    formatCurrency: function (value) {
      return Number(value).toLocaleString(undefined, {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      });
    },

    formatCompactCurrency: function (value) {
      return Number(value || 0).toLocaleString(undefined, {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
      });
    },

    /**
     * Check availability via REST API
     */
    checkAvailability: async function (params) {
      try {
        const queryString = new URLSearchParams(params).toString();
        const response = await fetch(
          `${this.apiBase}/check-availability?${queryString}`,
          {
            method: "GET",
            headers: {
              "Content-Type": "application/json",
            },
          },
        );

        if (!response.ok) {
          const errorData = await response.json();
          return {
            success: false,
            message: errorData.message || "Failed to check availability",
          };
        }

        return await response.json();
      } catch (error) {
        console.error("Availability check error:", error);
        return {
          success: false,
          message: "Network error while checking availability",
        };
      }
    },

    /**
     * Get price estimate via REST API
     */
    getPriceEstimate: async function (cabinId, checkinDate, checkoutDate) {
      try {
        const params = new URLSearchParams({
          cabin_id: cabinId,
          checkin_date: checkinDate,
          checkout_date: checkoutDate,
        }).toString();

        const response = await fetch(
          `${this.apiBase}/price-estimate?${params}`,
          {
            method: "GET",
            headers: {
              "Content-Type": "application/json",
            },
          },
        );

        if (!response.ok) {
          const errorData = await response.json();
          return {
            success: false,
            message: errorData.message || "Failed to get price estimate",
          };
        }

        return await response.json();
      } catch (error) {
        console.error("Price estimate error:", error);
        return {
          success: false,
          message: "Network error while getting price",
        };
      }
    },

    /**
     * Initialize date pickers with minimum date validation
     */
    initDatePickers: function (element) {
      if (!element) return;

      const isSearchForm = element.classList.contains("cbe-search-form");

      const checkinInput = element.querySelector(
        'input[name="checkin_date"], input[data-cbe-checkin]',
      );
      const checkoutInput = element.querySelector(
        'input[name="checkout_date"], input[data-cbe-checkout]',
      );

      if (!checkinInput || !checkoutInput) return;

      // Set minimum date to today
      const today = this.formatDate(new Date());
      if (!checkinInput.getAttribute("min")) {
        checkinInput.setAttribute("min", today);
      }

      // Update checkout minimum when checkin changes
      checkinInput.addEventListener("change", (e) => {
        const checkinDate = new Date(e.target.value + "T00:00:00");
        if (isNaN(checkinDate.getTime())) return;

        const checkoutDate = new Date(checkinDate.getTime() + 86400000);
        const minCheckout = this.formatDate(checkoutDate);
        checkoutInput.setAttribute("min", minCheckout);

        // Clear checkout if it's before the new minimum
        if (checkoutInput.value && checkoutInput.value <= e.target.value) {
          checkoutInput.value = minCheckout;
        }

        // Only booking forms should auto-check on date changes.
        if (!isSearchForm && checkoutInput.value) {
          this.triggerAvailabilityCheck(element);
        }
      });

      // Trigger check when checkout changes
      checkoutInput.addEventListener("change", () => {
        if (!isSearchForm && checkinInput.value && checkoutInput.value) {
          this.triggerAvailabilityCheck(element);
        }
      });
    },

    /**
     * Trigger availability check for a form
     */
    triggerAvailabilityCheck: async function (element) {
      if (!element) return;

      const checkinInput = element.querySelector(
        'input[name="checkin_date"], input[data-cbe-checkin]',
      );
      const checkoutInput = element.querySelector(
        'input[name="checkout_date"], input[data-cbe-checkout]',
      );
      const cabinIdInput = element.querySelector(
        'input[name="cabin_id"], select[name="cabin_id"], input[data-cbe-cabin-id], select[data-cbe-cabin-id]',
      );
      const groupInput = element.querySelector(
        'input[name="group"], select[name="group"], input[data-cbe-group], select[data-cbe-group]',
      );
      const onlyAvailableInput = element.querySelector(
        "[data-cbe-results-available]",
      );

      if (!checkinInput || !checkoutInput) return;

      const checkinDate = checkinInput.value;
      const checkoutDate = checkoutInput.value;

      if (!checkinDate || !checkoutDate) return;

      const availabilityDisplay = element.querySelector(
        "[data-cbe-availability]",
      );
      const resultsSection = element.querySelector("[data-cbe-search-results]");
      const resultsList = element.querySelector("[data-cbe-results-list]");

      // Show loading state
      if (availabilityDisplay) {
        availabilityDisplay.innerHTML =
          '<span class="cbe-loading">Checking availability...</span>';
        availabilityDisplay.classList.add("loading");
      }

      if (resultsSection && resultsList) {
        resultsSection.hidden = false;
        resultsList.innerHTML =
          '<div class="cbe-search-popup-empty">Checking cabin availability...</div>';
      }

      const params = {
        checkin_date: checkinDate,
        checkout_date: checkoutDate,
      };

      if (cabinIdInput && cabinIdInput.value) {
        params.cabin_id = cabinIdInput.value;
      }

      if (groupInput && groupInput.value) {
        params.group = groupInput.value;
      }

      if (!params.cabin_id && !params.group) {
        params.group = "all";
      }

      const onlyAvailable =
        !onlyAvailableInput || onlyAvailableInput.checked ? "1" : "0";

      if (!params.cabin_id && onlyAvailable === "1") {
        params.only_available = "1";
      }

      const result = await this.checkAvailability(params);

      if (availabilityDisplay) {
        availabilityDisplay.classList.remove("loading");
      }

      if (!result.success) {
        if (resultsSection && resultsList) {
          resultsSection.hidden = false;
          resultsList.innerHTML = `<div class="cbe-search-popup-empty">${this.escapeHtml(result.message)}</div>`;
        }

        if (availabilityDisplay) {
          availabilityDisplay.innerHTML = `<span class="cbe-error">${this.escapeHtml(result.message)}</span>`;
        }
        return;
      }

      // Handle single cabin response by reusing list popup flow.
      if (result.data && !Array.isArray(result.data)) {
        this.displayAvailabilityList(
          availabilityDisplay,
          [result.data],
          checkinDate,
          checkoutDate,
          resultsSection,
          resultsList,
        );
      } else if (Array.isArray(result.data)) {
        // Handle multiple cabins response
        this.displayAvailabilityList(
          availabilityDisplay,
          result.data,
          checkinDate,
          checkoutDate,
          resultsSection,
          resultsList,
        );
      }
    },

    /**
     * Display availability status for a single cabin
     */
    displayAvailabilityStatus: function (
      element,
      availability,
      checkinDate,
      checkoutDate,
    ) {
      const nights = this.calculateNights(checkinDate, checkoutDate);
      const totalPrice = availability.price_per_night * nights;

      if (availability.is_available) {
        element.className = "cbe-availability available";
        element.innerHTML = `
          <div class="cbe-availability-content">
            <div class="cbe-status-icon">✓</div>
            <div class="cbe-status-info">
              <div class="cbe-status-title">Available</div>
              <div class="cbe-status-details">
                <span class="cbe-units">${availability.available_units} unit(s) available</span>
                <span class="cbe-price">${this.formatCurrency(totalPrice)} for ${nights} night(s)</span>
              </div>
            </div>
          </div>
        `;
      } else {
        element.className = "cbe-availability unavailable";
        element.innerHTML = `
          <div class="cbe-availability-content">
            <div class="cbe-status-icon">✕</div>
            <div class="cbe-status-info">
              <div class="cbe-status-title">Not Available</div>
              <div class="cbe-status-details">
                All units are booked for these dates
              </div>
            </div>
          </div>
        `;
      }
    },

    /**
     * Display availability list for multiple cabins
     */
    displayAvailabilityList: function (
      element,
      availabilityList,
      checkinDate,
      checkoutDate,
      resultsSection,
      resultsList,
    ) {
      const nights = this.calculateNights(checkinDate, checkoutDate);

      if (resultsSection && resultsList) {
        const guestsInput = resultsSection.closest("form")
          ? resultsSection
              .closest("form")
              .querySelector(
                'select[name="total_guests"], input[name="total_guests"]',
              )
          : null;
        const prevState = resultsSection.cbeResultsState || {};
        resultsSection.hidden = false;
        resultsSection.cbeResultsState = {
          rawList: Array.isArray(availabilityList) ? availabilityList : [],
          nights,
          checkinDate,
          checkoutDate,
          guests: guestsInput && guestsInput.value ? guestsInput.value : "2",
          selected: prevState.selected || {},
        };
        this.bindInlineResultControls(resultsSection, resultsList);
        this.renderInlineResults(resultsSection, resultsList);
      } else {
        // Do not auto-open popup modal for search results fallback.
        return;
      }

      if (element) {
        const totalAvailable = availabilityList.length;
        element.className = "cbe-availability available";
        element.innerHTML = `<div class="cbe-availability-content"><div class="cbe-status-icon">✓</div><div class="cbe-status-info"><div class="cbe-status-title">${this.escapeHtml(String(totalAvailable))} cabin(s) found</div><div class="cbe-status-details"><span class="cbe-units">Select cabins and then click Book Now</span></div></div></div>`;
      }
    },

    bindInlineResultControls: function (resultsSection, resultsList) {
      if (!resultsSection || resultsSection.dataset.cbeResultsBound === "1") {
        return;
      }

      resultsSection.dataset.cbeResultsBound = "1";

      const sortSelect = resultsSection.querySelector(
        "[data-cbe-results-sort]",
      );
      const availableToggle = resultsSection.querySelector(
        "[data-cbe-results-available]",
      );

      const rerender = () => {
        this.renderInlineResults(resultsSection, resultsList);
      };

      if (sortSelect) {
        sortSelect.addEventListener("change", rerender);
      }

      if (availableToggle) {
        availableToggle.addEventListener("change", rerender);
      }

      resultsSection.addEventListener("click", (event) => {
        const stepBtn = event.target.closest("[data-cbe-room-step]");
        if (stepBtn) {
          const cabinId = stepBtn.getAttribute("data-cbe-room-id");
          const delta = Number(stepBtn.getAttribute("data-cbe-room-step") || 0);
          this.changeSelectedRoomQty(resultsSection, cabinId, delta);
          return;
        }

        const selectBtn = event.target.closest("[data-cbe-select-room]");
        if (selectBtn) {
          const cabinId = selectBtn.getAttribute("data-cbe-select-room");
          const state = resultsSection.cbeResultsState || {};
          const currentQty = Number(
            (state.selected && state.selected[cabinId]) || 0,
          );
          this.setSelectedRoomQty(
            resultsSection,
            cabinId,
            currentQty > 0 ? 0 : 1,
          );
          return;
        }

        const removeBtn = event.target.closest("[data-cbe-remove-room]");
        if (removeBtn) {
          const cabinId = removeBtn.getAttribute("data-cbe-remove-room");
          this.setSelectedRoomQty(resultsSection, cabinId, 0);
          return;
        }

        const bookBtn = event.target.closest("[data-cbe-selection-book]");
        if (bookBtn) {
          this.openSelectionConfirmModal(resultsSection);
        }
      });
    },

    renderInlineResults: function (resultsSection, resultsList) {
      if (!resultsSection || !resultsList || !resultsSection.cbeResultsState) {
        return;
      }

      const state = resultsSection.cbeResultsState;
      const sortSelect = resultsSection.querySelector(
        "[data-cbe-results-sort]",
      );
      const availableToggle = resultsSection.querySelector(
        "[data-cbe-results-available]",
      );
      const countEl = resultsSection.querySelector("[data-cbe-results-count]");

      let list = Array.isArray(state.rawList) ? state.rawList.slice() : [];

      if (availableToggle && availableToggle.checked) {
        list = list.filter((item) => Boolean(item && item.is_available));
      }

      const sortBy = sortSelect ? sortSelect.value : "recommended";
      list = this.sortAvailabilityList(list, sortBy);

      const cardsHtml = this.renderAvailabilityCards(
        list,
        state.nights,
        state.selected || {},
      );
      resultsList.innerHTML =
        cardsHtml ||
        '<div class="cbe-search-popup-empty">No available cabins for selected dates.</div>';

      this.renderSelectionPanel(resultsSection);

      if (countEl) {
        const availableCount = list.length;
        const nightsLabel = state.nights === 1 ? "night" : "nights";
        countEl.textContent = `${availableCount} cabin(s) for ${state.nights} ${nightsLabel}`;
      }
    },

    sortAvailabilityList: function (list, sortBy) {
      const items = Array.isArray(list) ? list.slice() : [];

      if (sortBy === "price_asc") {
        items.sort(
          (a, b) =>
            Number(a.price_per_night || 0) - Number(b.price_per_night || 0),
        );
      } else if (sortBy === "price_desc") {
        items.sort(
          (a, b) =>
            Number(b.price_per_night || 0) - Number(a.price_per_night || 0),
        );
      } else if (sortBy === "name_asc") {
        items.sort((a, b) =>
          String(a.cabin_name || "").localeCompare(String(b.cabin_name || "")),
        );
      }

      return items;
    },

    getSelectedItems: function (resultsSection) {
      const state = resultsSection && resultsSection.cbeResultsState;
      if (!state || !state.selected) {
        return [];
      }

      const byId = {};
      (Array.isArray(state.rawList) ? state.rawList : []).forEach((item) => {
        if (item && item.cabin_id !== undefined) {
          byId[String(item.cabin_id)] = item;
        }
      });

      return Object.keys(state.selected)
        .map((id) => {
          const qty = Number(state.selected[id] || 0);
          const room = byId[String(id)];
          if (!room || qty < 1) {
            return null;
          }
          return {
            room,
            qty,
          };
        })
        .filter(Boolean);
    },

    setSelectedRoomQty: function (resultsSection, cabinId, qty) {
      const state = resultsSection && resultsSection.cbeResultsState;
      if (!state || !cabinId) {
        return;
      }

      if (!state.selected) {
        state.selected = {};
      }

      const roomData = (Array.isArray(state.rawList) ? state.rawList : []).find(
        (item) => String(item.cabin_id) === String(cabinId),
      );
      const maxQty = roomData
        ? Math.max(0, Number(roomData.available_units || 0))
        : 0;
      const safeQty = Math.min(
        maxQty,
        Math.max(0, Math.floor(Number(qty || 0))),
      );

      if (safeQty <= 0) {
        delete state.selected[String(cabinId)];
      } else {
        state.selected[String(cabinId)] = safeQty;
      }

      this.renderInlineResults(
        resultsSection,
        resultsSection.querySelector("[data-cbe-results-list]"),
      );
    },

    changeSelectedRoomQty: function (resultsSection, cabinId, delta) {
      const state = resultsSection && resultsSection.cbeResultsState;
      if (!state || !cabinId) {
        return;
      }

      const currentQty = Number(
        (state.selected && state.selected[String(cabinId)]) || 0,
      );
      const nextQty = currentQty + Number(delta || 0);
      this.setSelectedRoomQty(resultsSection, cabinId, nextQty);
    },

    renderSelectionPanel: function (resultsSection) {
      const panel = resultsSection.querySelector("[data-cbe-selection-panel]");
      const countEl = resultsSection.querySelector(
        "[data-cbe-selection-count]",
      );
      const listEl = resultsSection.querySelector("[data-cbe-selection-list]");
      const totalEl = resultsSection.querySelector(
        "[data-cbe-selection-total]",
      );

      if (!panel || !listEl || !totalEl) {
        return;
      }

      const state = resultsSection.cbeResultsState || {};
      const items = this.getSelectedItems(resultsSection);
      const selectedRooms = items.reduce((sum, item) => sum + item.qty, 0);

      if (selectedRooms < 1) {
        panel.hidden = true;
        listEl.innerHTML = "";
        totalEl.textContent = "";
        if (countEl) {
          countEl.textContent = "";
        }
        return;
      }

      panel.hidden = false;

      if (countEl) {
        countEl.textContent = `${selectedRooms} cabin(s) selected`;
      }

      let grandTotal = 0;
      listEl.innerHTML = items
        .map((item) => {
          const room = item.room;
          const rowTotal =
            Number(room.price_per_night || 0) *
            Number(state.nights || 0) *
            item.qty;
          grandTotal += rowTotal;
          return `
            <div class="cbe-search-selection-item">
              <div class="cbe-search-selection-item-head">
                <strong>${this.escapeHtml(room.cabin_name)}</strong>
                <button type="button" data-cbe-remove-room="${this.escapeHtml(room.cabin_id)}">&times;</button>
              </div>
              <div class="cbe-search-selection-item-meta">
                <span>${item.qty} x ${this.formatCompactCurrency(room.price_per_night)}/night</span>
                <span>${this.formatCompactCurrency(rowTotal)}</span>
              </div>
            </div>
          `;
        })
        .join("");

      totalEl.textContent = `Total ${this.formatCompactCurrency(grandTotal)}`;
    },

    buildRoomPlanText: function (resultsSection) {
      const state = resultsSection.cbeResultsState || {};
      const items = this.getSelectedItems(resultsSection);
      if (!items.length) {
        return "";
      }

      const lines = items.map(
        (item) =>
          `${item.qty}x ${item.room.cabin_name} (${this.formatCompactCurrency(item.room.price_per_night)}/night)`,
      );
      return `Cabin plan: ${lines.join(", ")}. Stay ${state.checkinDate} - ${state.checkoutDate}.`;
    },

    ensureSelectionConfirmModal: function () {
      let modal = document.getElementById("cbe-selection-confirm-modal");
      if (modal) {
        return modal;
      }

      modal = document.createElement("div");
      modal.id = "cbe-selection-confirm-modal";
      modal.className = "cbe-search-results-modal cbe-selection-confirm-modal";
      modal.hidden = true;
      modal.innerHTML = `
        <div class="cbe-search-results-modal-backdrop" data-cbe-selection-close></div>
        <div class="cbe-search-results-modal-wrap" role="dialog" aria-modal="true" aria-label="Selected Cabins">
          <div class="cbe-search-results-modal-header">
            <h3>Booking Summary</h3>
            <button type="button" class="cbe-search-results-modal-close" aria-label="Close" data-cbe-selection-close>&times;</button>
          </div>
          <div class="cbe-search-results-modal-body" data-cbe-selection-body></div>
          <div class="cbe-selection-confirm-footer">
            <button type="button" class="cbe-selection-confirm-cancel" data-cbe-selection-close>Back</button>
            <button type="button" class="cbe-selection-confirm-submit" data-cbe-selection-confirm>Continue Booking</button>
          </div>
        </div>
      `;

      modal.addEventListener("click", (event) => {
        if (event.target.closest("[data-cbe-selection-close]")) {
          modal.hidden = true;
          document.body.classList.remove("cbe-search-modal-open");
        }
      });

      document.body.appendChild(modal);
      return modal;
    },

    openSelectionConfirmModal: function (resultsSection) {
      const items = this.getSelectedItems(resultsSection);
      if (!items.length) {
        return;
      }

      const modal = this.ensureSelectionConfirmModal();
      const body = modal.querySelector("[data-cbe-selection-body]");
      const confirmBtn = modal.querySelector("[data-cbe-selection-confirm]");
      const state = resultsSection.cbeResultsState || {};

      let grandTotal = 0;
      const linesHtml = items
        .map((item) => {
          const rowTotal =
            Number(item.room.price_per_night || 0) *
            Number(state.nights || 0) *
            item.qty;
          grandTotal += rowTotal;
          return `<li><strong>${this.escapeHtml(item.room.cabin_name)}</strong><span>${item.qty} cabin(s)</span><span>${this.formatCompactCurrency(rowTotal)}</span></li>`;
        })
        .join("");

      if (body) {
        body.innerHTML = `
          <div class="cbe-selection-confirm-summary">
            <p>${this.escapeHtml(state.checkinDate || "")} - ${this.escapeHtml(state.checkoutDate || "")} (${this.escapeHtml(String(state.nights || 0))} night(s))</p>
            <ul>${linesHtml}</ul>
            <div class="cbe-selection-confirm-total">Total ${this.formatCompactCurrency(grandTotal)}</div>
          </div>
        `;
      }

      if (confirmBtn) {
        confirmBtn.onclick = () => {
          modal.hidden = true;
          document.body.classList.remove("cbe-search-modal-open");
          this.openMultiBookingModal(state, items);
        };
      }

      modal.hidden = false;
      document.body.classList.add("cbe-search-modal-open");
    },

    openMultiBookingModal: function (state, items) {
      const safeItems = Array.isArray(items) ? items : [];
      if (!safeItems.length) {
        return;
      }

      const modal = this.ensureMultiBookingModal();
      const body = modal.querySelector("[data-cbe-multi-booking-body]");
      if (!body) {
        return;
      }

      const prioritized = safeItems
        .slice()
        .sort((a, b) => Number(b.qty || 0) - Number(a.qty || 0));
      const primaryRoom = prioritized[0] ? prioritized[0].room || {} : {};
      const config = window.cbeAvailabilityConfig || {};
      const paymentMethods = config.paymentMethods || {
        manual: "Pay on Arrival / Manual Confirmation",
      };
      const totalRooms = safeItems.reduce(
        (sum, item) => sum + Number(item.qty || 0),
        0,
      );

      let grandTotal = 0;
      const summaryItemsHtml = safeItems
        .map((item) => {
          const rowTotal =
            Number(item.room.price_per_night || 0) *
            Number(state.nights || 0) *
            Number(item.qty || 0);
          grandTotal += rowTotal;
          return `<li><span>${this.escapeHtml(item.room.cabin_name)}</span><span>${this.escapeHtml(String(item.qty))}x</span><strong>${this.formatCompactCurrency(rowTotal)}</strong></li>`;
        })
        .join("");

      const paymentOptionsHtml = Object.keys(paymentMethods)
        .map(
          (key) =>
            `<option value="${this.escapeHtml(key)}">${this.escapeHtml(paymentMethods[key])}</option>`,
        )
        .join("");

      body.innerHTML = `
        <form class="cbe-multi-booking-form" method="post" action="${this.escapeHtml(config.adminPostUrl || "")}">
          <input type="hidden" name="action" value="cbe_submit_booking" />
          <input type="hidden" name="cabin_id" value="${this.escapeHtml(primaryRoom.cabin_id || "")}" />
          <input type="hidden" name="redirect_url" value="${this.escapeHtml(config.redirectUrl || window.location.href)}" />
          <input type="hidden" name="cbe_selected_rooms" value="${this.escapeHtml(safeItems.map((item) => `${item.room.cabin_id}:${item.qty}`).join(","))}" />
          <input type="hidden" name="${this.escapeHtml(config.nonceField || "_wpnonce")}" value="${this.escapeHtml(config.nonceValue || "")}" />
          <input type="hidden" name="guest_name" value="" />
          <input type="hidden" name="guest_email" value="" />
          <input type="hidden" name="notes" value="" />

          <div class="cbe-multi-booking-grid">
            <section class="cbe-multi-booking-fields">
              <div class="cbe-multi-booking-stephead">
                <span class="cbe-multi-booking-stepnum">1</span>
                <div>
                  <h3>Your details</h3>
                  <p>Complete the primary guest details to finish your multi-cabin booking.</p>
                </div>
              </div>
              <div class="cbe-multi-booking-row cbe-multi-booking-row-split">
                <div>
                  <label>First name</label>
                  <input type="text" name="cbe_first_name" autocomplete="given-name" required />
                </div>
                <div>
                  <label>Last name</label>
                  <input type="text" name="cbe_last_name" autocomplete="family-name" required />
                </div>
              </div>
              <div class="cbe-multi-booking-row cbe-multi-booking-row-split">
                <div>
                  <label>Email</label>
                  <input type="email" name="cbe_email_display" autocomplete="email" required />
                </div>
                <div>
                  <label>Confirm email</label>
                  <input type="email" name="cbe_email_confirm" autocomplete="email" required />
                </div>
              </div>
              <div class="cbe-multi-booking-row cbe-multi-booking-row-split">
                <div>
                  <label>Phone</label>
                  <input type="text" name="guest_phone" autocomplete="tel" />
                </div>
                <div>
                  <label>Address</label>
                  <input type="text" name="cbe_address" autocomplete="street-address" />
                </div>
              </div>
              <div class="cbe-multi-booking-row cbe-multi-booking-row-split">
                <div>
                  <label>Address line 2</label>
                  <input type="text" name="cbe_address_extra" />
                </div>
                <div>
                  <label>City</label>
                  <input type="text" name="cbe_city" autocomplete="address-level2" />
                </div>
              </div>
              <div class="cbe-multi-booking-row cbe-multi-booking-row-split">
                <div>
                  <label>State / Province (optional)</label>
                  <input type="text" name="cbe_province" autocomplete="address-level1" />
                </div>
                <div>
                  <label>Postal code</label>
                  <input type="text" name="cbe_postal_code" autocomplete="postal-code" />
                </div>
              </div>
              <div class="cbe-multi-booking-row cbe-multi-booking-row-split">
                <div>
                  <label>Country / Region</label>
                  <select name="cbe_country">
                    <option value="">Select country</option>
                    <option value="Indonesia">Indonesia</option>
                    <option value="Singapore">Singapore</option>
                    <option value="Malaysia">Malaysia</option>
                    <option value="Australia">Australia</option>
                    <option value="Other">Other</option>
                  </select>
                </div>
                <div>
                  <label>How did you hear about us?</label>
                  <select name="cbe_referral_source">
                    <option value="">Select source</option>
                    <option value="Google">Google</option>
                    <option value="Instagram">Instagram</option>
                    <option value="Travel Agent">Travel Agent</option>
                    <option value="Returning Guest">Returning Guest</option>
                    <option value="Recommendation">Recommendation</option>
                  </select>
                </div>
              </div>
              <div class="cbe-multi-booking-row">
                <label>My arrival from</label>
                <select name="cbe_arrival_from">
                  <option value="">Select arrival source</option>
                  <option value="Singapore via BBT FT">Singapore via BBT FT</option>
                  <option value="Malaysia via BBT FT">Malaysia via BBT FT</option>
                  <option value="Others">Others (please specify)</option>
                </select>
              </div>
              <div class="cbe-multi-booking-row" data-cbe-arrival-from-other-row hidden>
                <label>Please specify your arrival source</label>
                <input type="text" name="cbe_arrival_from_other" placeholder="Type your arrival source" />
              </div>
              <div class="cbe-multi-booking-row">
                <label>Help us prepare a smooth check-in when you arrive.</label>
                <select name="cbe_arrival_plan">
                  <option value="">Select arrival time</option>
                </select>
              </div>
              <div class="cbe-multi-booking-row" data-cbe-arrival-time-other-row hidden>
                <label>Please specify your arrival time</label>
                <input type="text" name="cbe_arrival_plan_other" placeholder="Type your arrival time" />
              </div>
              <div class="cbe-multi-booking-row">
                <label>Special requests</label>
                <textarea name="cbe_special_requests" rows="4" placeholder="Add any special notes or requests here"></textarea>
              </div>
              <div class="cbe-multi-booking-row cbe-multi-booking-row-split">
                <div>
                  <label>Check-in date</label>
                  <input type="date" name="checkin_date" value="${this.escapeHtml(state.checkinDate || "")}" required />
                </div>
                <div>
                  <label>Check-out date</label>
                  <input type="date" name="checkout_date" value="${this.escapeHtml(state.checkoutDate || "")}" required />
                </div>
              </div>
              <div class="cbe-multi-booking-row cbe-multi-booking-row-split">
                <div>
                  <label>Total guests</label>
                  <input type="number" name="total_guests" min="1" step="1" value="${this.escapeHtml(state.guests || "1")}" required />
                </div>
                <div>
                  <label>Payment method</label>
                  <select name="payment_method" required>${paymentOptionsHtml}</select>
                </div>
              </div>
              <div class="cbe-multi-booking-actions">
                <button type="button" class="cbe-selection-confirm-cancel" data-cbe-multi-booking-close>Cancel</button>
                <button type="submit" class="cbe-selection-confirm-submit">Confirm booking</button>
              </div>
            </section>

            <aside class="cbe-multi-booking-summary">
              <div class="cbe-multi-booking-summary-head">
                <h3>${this.formatCompactCurrency(grandTotal)} Total</h3>
                <p>${this.escapeHtml(state.checkinDate || "")} - ${this.escapeHtml(state.checkoutDate || "")}</p>
                <span>${this.escapeHtml(String(totalRooms))} cabins, ${this.escapeHtml(String(state.guests || "1"))} guests</span>
              </div>
              <div class="cbe-multi-booking-staydetails">
                <h4>Stay details</h4>
                <ul>${summaryItemsHtml}</ul>
              </div>
              <div class="cbe-multi-booking-total">Total ${this.formatCompactCurrency(grandTotal)}</div>
            </aside>
          </div>
        </form>
      `;

      const form = body.querySelector(".cbe-multi-booking-form");
      if (form) {
        this.bindMultiBookingForm(form, state, safeItems);
      }

      modal.hidden = false;
      document.body.classList.add("cbe-search-modal-open");
    },

    bindMultiBookingForm: function (form, state, items) {
      if (!form || form.dataset.cbeMultiBound === "1") {
        return;
      }

      form.dataset.cbeMultiBound = "1";

      this.bindArrivalFields(form);

      form.addEventListener("submit", (event) => {
        const firstName = form.querySelector('[name="cbe_first_name"]');
        const lastName = form.querySelector('[name="cbe_last_name"]');
        const emailDisplay = form.querySelector('[name="cbe_email_display"]');
        const emailConfirm = form.querySelector('[name="cbe_email_confirm"]');
        const guestName = form.querySelector('[name="guest_name"]');
        const guestEmail = form.querySelector('[name="guest_email"]');
        const notes = form.querySelector('[name="notes"]');
        const specialRequests = form.querySelector(
          '[name="cbe_special_requests"]',
        );

        const first = firstName ? firstName.value.trim() : "";
        const last = lastName ? lastName.value.trim() : "";
        const email = emailDisplay ? emailDisplay.value.trim() : "";
        const confirm = emailConfirm ? emailConfirm.value.trim() : "";

        if (email !== confirm) {
          event.preventDefault();
          emailConfirm.setCustomValidity("Email confirmation must match.");
          emailConfirm.reportValidity();
          return;
        }

        if (emailConfirm) {
          emailConfirm.setCustomValidity("");
        }

        if (guestName) {
          guestName.value = `${first} ${last}`.trim();
        }
        if (guestEmail) {
          guestEmail.value = email;
        }
        if (notes) {
          notes.value = this.buildMultiBookingNotes(
            form,
            state,
            items,
            specialRequests ? specialRequests.value : "",
          );
        }
      });
    },

    bindArrivalFields: function (form) {
      if (!form) {
        return;
      }

      const arrivalFrom = form.querySelector('[name="cbe_arrival_from"]');
      const arrivalFromOtherRow = form.querySelector(
        "[data-cbe-arrival-from-other-row]",
      );
      const arrivalFromOtherInput = form.querySelector(
        '[name="cbe_arrival_from_other"]',
      );
      const arrivalPlan = form.querySelector('[name="cbe_arrival_plan"]');
      const arrivalPlanOtherRow = form.querySelector(
        "[data-cbe-arrival-time-other-row]",
      );
      const arrivalPlanOtherInput = form.querySelector(
        '[name="cbe_arrival_plan_other"]',
      );

      if (!arrivalFrom || !arrivalPlan) {
        return;
      }

      const toggleArrivalFromOther = () => {
        const isOther = arrivalFrom.value === "Others";
        if (arrivalFromOtherRow) {
          arrivalFromOtherRow.hidden = !isOther;
        }
        if (arrivalFromOtherInput) {
          arrivalFromOtherInput.required = isOther;
          if (!isOther) {
            arrivalFromOtherInput.value = "";
          }
        }
      };

      const toggleArrivalTimeOther = () => {
        const isOther = arrivalPlan.value === "Others";
        if (arrivalPlanOtherRow) {
          arrivalPlanOtherRow.hidden = !isOther;
        }
        if (arrivalPlanOtherInput) {
          arrivalPlanOtherInput.required = isOther;
          if (!isOther) {
            arrivalPlanOtherInput.value = "";
          }
        }
      };

      const optionsByArrivalSource = {
        "Singapore via BBT FT": [
          "08.10",
          "09.10",
          "11.10",
          "14.00",
          "17.00",
          "Others",
        ],
        "Malaysia via BBT FT": ["10.45", "Others"],
        Others: ["Others"],
      };

      const syncArrivalOptions = () => {
        const selectedSource = arrivalFrom.value;
        const previousValue = arrivalPlan.value;
        const options = optionsByArrivalSource[selectedSource] || [];

        const optionHtml = [
          '<option value="">Select arrival time</option>',
          ...options.map((value) => {
            const label =
              value === "Others" ? "Others (please specify)" : value;
            return `<option value="${this.escapeHtml(value)}">${this.escapeHtml(label)}</option>`;
          }),
        ].join("");

        arrivalPlan.innerHTML = optionHtml;
        if (options.includes(previousValue)) {
          arrivalPlan.value = previousValue;
        }

        toggleArrivalTimeOther();
      };

      arrivalFrom.addEventListener("change", () => {
        toggleArrivalFromOther();
        syncArrivalOptions();
      });
      arrivalPlan.addEventListener("change", toggleArrivalTimeOther);

      toggleArrivalFromOther();
      syncArrivalOptions();
    },

    buildMultiBookingNotes: function (form, state, items, specialRequests) {
      const getValue = (name) => {
        const field = form.querySelector(`[name="${name}"]`);
        return field ? String(field.value || "").trim() : "";
      };

      const lines = [this.buildRoomPlanTextFromItems(state, items)];
      const addressParts = [
        getValue("cbe_address"),
        getValue("cbe_address_extra"),
        getValue("cbe_city"),
        getValue("cbe_province"),
        getValue("cbe_postal_code"),
        getValue("cbe_country"),
      ].filter(Boolean);

      if (addressParts.length) {
        lines.push(`Address: ${addressParts.join(", ")}`);
      }

      const referralSource = getValue("cbe_referral_source");
      if (referralSource) {
        lines.push(`Referral source: ${referralSource}`);
      }

      const arrivalFrom = getValue("cbe_arrival_from");
      const arrivalFromOther = getValue("cbe_arrival_from_other");
      if (arrivalFrom) {
        if (arrivalFrom === "Others" && arrivalFromOther) {
          lines.push(`Arrival from: ${arrivalFromOther}`);
        } else {
          lines.push(`Arrival from: ${arrivalFrom}`);
        }
      }

      const arrivalPlan = getValue("cbe_arrival_plan");
      const arrivalPlanOther = getValue("cbe_arrival_plan_other");
      if (arrivalPlan) {
        if (arrivalPlan === "Others" && arrivalPlanOther) {
          lines.push(`Arrival plan: ${arrivalPlanOther}`);
        } else {
          lines.push(`Arrival plan: ${arrivalPlan}`);
        }
      }

      if (String(specialRequests || "").trim() !== "") {
        lines.push(`Special requests: ${String(specialRequests).trim()}`);
      }

      return lines.filter(Boolean).join("\n");
    },

    buildRoomPlanTextFromItems: function (state, items) {
      const safeItems = Array.isArray(items) ? items : [];
      if (!safeItems.length) {
        return this.buildRoomPlanText({ cbeResultsState: state || {} });
      }

      const lines = safeItems.map(
        (item) =>
          `${item.qty}x ${item.room.cabin_name} (${this.formatCompactCurrency(item.room.price_per_night)}/night)`,
      );
      return `Cabin plan: ${lines.join(", ")}. Stay ${state.checkinDate || ""} - ${state.checkoutDate || ""}.`;
    },

    ensureMultiBookingModal: function () {
      let modal = document.getElementById("cbe-multi-booking-modal");
      if (modal) {
        return modal;
      }

      modal = document.createElement("div");
      modal.id = "cbe-multi-booking-modal";
      modal.className = "cbe-search-results-modal cbe-multi-booking-modal";
      modal.hidden = true;
      modal.innerHTML = `
        <div class="cbe-search-results-modal-backdrop" data-cbe-multi-booking-close></div>
        <div class="cbe-search-results-modal-wrap cbe-multi-booking-wrap" role="dialog" aria-modal="true" aria-label="Multi Cabin Booking Form">
          <div class="cbe-search-results-modal-header">
            <h3>Multi Cabin Booking</h3>
            <button type="button" class="cbe-search-results-modal-close" aria-label="Close" data-cbe-multi-booking-close>&times;</button>
          </div>
          <div class="cbe-search-results-modal-body" data-cbe-multi-booking-body></div>
        </div>
      `;

      modal.addEventListener("click", (event) => {
        if (event.target.closest("[data-cbe-multi-booking-close]")) {
          modal.hidden = true;
          document.body.classList.remove("cbe-search-modal-open");
        }
      });

      modal.addEventListener("submit", (event) => {
        const form = event.target.closest(".cbe-multi-booking-form");
        if (!form) {
          return;
        }

        const firstNameInput = form.querySelector(
          'input[name="guest_first_name"]',
        );
        const lastNameInput = form.querySelector(
          'input[name="guest_last_name"]',
        );
        const emailInput = form.querySelector(
          'input[name="guest_email_visible"]',
        );
        const confirmEmailInput = form.querySelector(
          'input[name="guest_email_confirm"]',
        );
        const hiddenNameInput = form.querySelector('input[name="guest_name"]');
        const hiddenEmailInput = form.querySelector(
          'input[name="guest_email"]',
        );
        const hiddenNotesInput = form.querySelector('input[name="notes"]');

        if (
          emailInput &&
          confirmEmailInput &&
          emailInput.value !== confirmEmailInput.value
        ) {
          event.preventDefault();
          confirmEmailInput.setCustomValidity("Email confirmation must match.");
          confirmEmailInput.reportValidity();
          return;
        }

        if (confirmEmailInput) {
          confirmEmailInput.setCustomValidity("");
        }

        if (hiddenNameInput) {
          hiddenNameInput.value =
            `${(firstNameInput && firstNameInput.value) || ""} ${(lastNameInput && lastNameInput.value) || ""}`.trim();
        }
        if (hiddenEmailInput && emailInput) {
          hiddenEmailInput.value = emailInput.value;
        }
        if (hiddenNotesInput) {
          hiddenNotesInput.value = this.composeStructuredBookingNotes(form);
        }
      });

      document.body.appendChild(modal);
      return modal;
    },

    composeStructuredBookingNotes: function (form) {
      if (!form) {
        return "";
      }

      const lines = [];
      const sourceMap = [
        ["guest_address", "Address"],
        ["guest_address_2", "Address line 2"],
        ["guest_city", "City"],
        ["guest_province", "State / Province"],
        ["guest_postal_code", "Postal code"],
        ["guest_country", "Country / Region"],
        ["guest_discovery_source", "How did you hear about us?"],
        ["arrival_plan", "Estimated arrival"],
        ["special_requests", "Special requests"],
      ];

      sourceMap.forEach(([name, label]) => {
        const field = form.querySelector(`[name="${name}"]`);
        if (field && String(field.value || "").trim() !== "") {
          lines.push(`${label}: ${String(field.value).trim()}`);
        }
      });

      const selectedRoomsField = form.querySelector(
        'input[name="cbe_selected_rooms"]',
      );
      if (selectedRoomsField && selectedRoomsField.value) {
        lines.unshift(`Selected cabins: ${selectedRoomsField.value}`);
      }

      return lines.join("\n");
    },

    renderAvailabilityCards: function (availabilityList, nights, selectedMap) {
      const selection = selectedMap || {};
      return (Array.isArray(availabilityList) ? availabilityList : [])
        .map((avail) => {
          const totalPrice = Number(avail.price_per_night || 0) * nights;
          const statusClass = avail.is_available ? "available" : "unavailable";
          const statusText = avail.is_available
            ? `${avail.available_units} cabin(s) left`
            : "Sold out";
          const bedTypeText = avail.bed_type
            ? this.escapeHtml(avail.bed_type)
            : "Comfort bed";
          const guestCount = Number(avail.max_guests || 0);
          const guestText =
            guestCount > 0 ? `${guestCount} guests` : "2 guests";
          //   const groupText = avail.stay_group
          //     ? this.escapeHtml(String(avail.stay_group).replace(/[-_]+/g, " "))
          //     : "Hotel Collection";
          const detailUrl = avail.detail_url
            ? this.escapeHtml(avail.detail_url)
            : "";
          const thumbnailUrl = avail.thumbnail_url
            ? this.escapeHtml(avail.thumbnail_url)
            : "";
          const mediaHtml = thumbnailUrl
            ? `<img src="${thumbnailUrl}" alt="${this.escapeHtml(avail.cabin_name)}" loading="lazy" />`
            : `<div class="cbe-search-card-media-fallback" aria-hidden="true">${this.getNameInitials(avail.cabin_name)}</div>`;

          const selectedQty = Math.max(
            0,
            Math.min(
              Number(avail.available_units || 0),
              Number(selection[String(avail.cabin_id)] || 0),
            ),
          );
          const minusDisabled = selectedQty <= 0 ? "disabled" : "";
          const plusDisabled =
            selectedQty >= Number(avail.available_units || 0) ? "disabled" : "";

          const actionHtml = avail.is_available
            ? `<div class="cbe-search-room-picker-wrap"><span class="cbe-search-room-picker-label">Cabins</span><div class="cbe-search-room-picker"><button type="button" class="cbe-search-room-step" data-cbe-room-id="${this.escapeHtml(avail.cabin_id)}" data-cbe-room-step="-1" ${minusDisabled}>-</button><span>${selectedQty}</span><button type="button" class="cbe-search-room-step" data-cbe-room-id="${this.escapeHtml(avail.cabin_id)}" data-cbe-room-step="1" ${plusDisabled}>+</button></div></div><button type="button" class="cbe-search-popup-book-btn cbe-search-select-btn" data-cbe-select-room="${this.escapeHtml(avail.cabin_id)}">${selectedQty > 0 ? "Unselect" : "Select"}</button>`
            : `<button type="button" class="cbe-search-popup-book-btn is-disabled" disabled>Not Available</button>`;

          const detailAction = detailUrl
            ? `<a class="cbe-search-detail-link" href="${detailUrl}">Cabin details</a>`
            : "";

          return `
            <article class="cbe-cabin-availability cbe-search-hotel-card ${statusClass} ${selectedQty > 0 ? "is-selected" : ""}">
              <div class="cbe-search-hotel-media">
                ${mediaHtml}
              </div>
              <div class="cbe-search-hotel-main">
                <div class="cbe-cabin-header">
                  <div>
                    <h4 class="cbe-cabin-name">${this.escapeHtml(avail.cabin_name)}</h4>
                  </div>
                  <span class="cbe-status-badge">${statusText}</span>
                </div>
                <div class="cbe-search-hotel-features">
                  <span>${bedTypeText}</span>
                  <span>${guestText}</span>
                  <span>${this.escapeHtml(String(avail.available_units || 0))} unit(s)</span>
                </div>
                
              </div>
              <div class="cbe-search-hotel-price-col">
                <div class="cbe-cabin-details">
                  <span class="cbe-price">${this.formatCompactCurrency(avail.price_per_night)}/night</span>
                  <span class="cbe-total">${this.formatCompactCurrency(totalPrice)} total</span>
                </div>
                <div class="cbe-cabin-actions">${actionHtml}${detailAction}</div>
              </div>
            </article>
          `;
        })
        .join("");
    },

    getNameInitials: function (name) {
      const words = String(name || "Cabin")
        .trim()
        .split(/\s+/)
        .filter(Boolean);
      if (!words.length) {
        return "RM";
      }
      if (words.length === 1) {
        return words[0].slice(0, 2).toUpperCase();
      }
      return `${words[0][0] || "R"}${words[1][0] || "M"}`.toUpperCase();
    },

    ensureResultsModal: function () {
      let modal = document.getElementById("cbe-search-results-modal");
      if (modal) {
        return modal;
      }

      modal = document.createElement("div");
      modal.id = "cbe-search-results-modal";
      modal.className = "cbe-search-results-modal";
      modal.hidden = true;
      modal.innerHTML = `
        <div class="cbe-search-results-modal-backdrop" data-cbe-result-close></div>
        <div class="cbe-search-results-modal-wrap" role="dialog" aria-modal="true" aria-label="Search Results">
          <div class="cbe-search-results-modal-header">
            <h3>Available Cabins</h3>
            <button type="button" class="cbe-search-results-modal-close" aria-label="Close" data-cbe-result-close>&times;</button>
          </div>
          <div class="cbe-search-results-modal-body" data-cbe-result-body></div>
        </div>
      `;

      modal.addEventListener("click", (event) => {
        const closeTarget = event.target.closest("[data-cbe-result-close]");
        if (closeTarget) {
          this.closeResultsModal(modal);
        }
      });

      document.addEventListener("keydown", (event) => {
        if (event.key === "Escape" && !modal.hidden) {
          this.closeResultsModal(modal);
        }
      });

      document.body.appendChild(modal);
      return modal;
    },

    openResultsModal: function (modal) {
      if (!modal) return;
      modal.hidden = false;
      document.body.classList.add("cbe-search-modal-open");
    },

    closeResultsModal: function (modal) {
      if (!modal) return;
      modal.hidden = true;
      document.body.classList.remove("cbe-search-modal-open");
    },

    /**
     * Update price display
     */
    updatePriceDisplay: async function (element) {
      const cabinIdInput = element.querySelector(
        'input[name="cabin_id"], input[data-cbe-cabin-id]',
      );
      const checkinInput = element.querySelector(
        'input[name="checkin_date"], input[data-cbe-checkin]',
      );
      const checkoutInput = element.querySelector(
        'input[name="checkout_date"], input[data-cbe-checkout]',
      );
      const priceDisplay = element.querySelector("[data-cbe-price-estimate]");

      if (
        !cabinIdInput ||
        !checkinInput ||
        !checkoutInput ||
        !priceDisplay ||
        !cabinIdInput.value ||
        !checkinInput.value ||
        !checkoutInput.value
      ) {
        return;
      }

      const result = await this.getPriceEstimate(
        cabinIdInput.value,
        checkinInput.value,
        checkoutInput.value,
      );

      if (!result.success) {
        priceDisplay.textContent = "Unable to calculate price";
        return;
      }

      const data = result.data;
      const nights = this.calculateNights(
        data.checkin_date,
        data.checkout_date,
      );
      const totalPrice = data.price_per_night * nights;

      priceDisplay.innerHTML = `
        <div class="cbe-price-breakdown">
          <div class="cbe-price-row">
            <span class="cbe-label">${this.formatCurrency(data.price_per_night)} × ${nights} night(s)</span>
            <span class="cbe-value">${this.formatCurrency(totalPrice)}</span>
          </div>
          <div class="cbe-price-total">
            <span class="cbe-label">Total:</span>
            <span class="cbe-value">${this.formatCurrency(totalPrice)}</span>
          </div>
        </div>
      `;
    },

    /**
     * Escape HTML special characters
     */
    escapeHtml: function (text) {
      const map = {
        "&": "&amp;",
        "<": "&lt;",
        ">": "&gt;",
        '"': "&quot;",
        "'": "&#39;",
      };
      return String(text).replace(/[&<>"']/g, (char) => map[char]);
    },

    /**
     * Initialize all booking forms on page
     */
    initAll: function (selector) {
      selector =
        selector || ".cbe-booking-form, .cbe-search-form, [data-cbe-form]";
      const forms = document.querySelectorAll(selector);
      forms.forEach((node) => {
        const form =
          node.classList.contains("cbe-search-form") ||
          node.classList.contains("cbe-booking-form")
            ? node
            : node.querySelector(".cbe-search-form, .cbe-booking-form");

        if (!form) {
          return;
        }

        this.initDatePickers(form);

        const searchButton = form.querySelector(".cbe-search-button");
        if (searchButton && !searchButton.dataset.cbeBound) {
          searchButton.dataset.cbeBound = "1";
          searchButton.addEventListener("click", (event) => {
            if (form.classList.contains("cbe-search-form")) {
              // For search UX, rely on native GET form submit so redirect is resilient.
              if (
                searchButton.getAttribute("type") === "button" &&
                typeof form.requestSubmit === "function"
              ) {
                form.requestSubmit();
                event.preventDefault();
              }
              return;
            }
            event.preventDefault();
            this.triggerAvailabilityCheck(form);
          });
        }

        if (
          form.classList.contains("cbe-search-form") &&
          !form.dataset.cbeSubmitBound
        ) {
          form.dataset.cbeSubmitBound = "1";
          form.addEventListener("submit", () => {
            const targetUrl =
              form.getAttribute("data-cbe-results-page") ||
              form.getAttribute("action") ||
              "";
            if (targetUrl) {
              form.setAttribute("action", targetUrl);
            }
          });
        }

        // Trigger initial check if dates are already set
        const checkinInput = form.querySelector(
          'input[name="checkin_date"], input[data-cbe-checkin]',
        );
        const checkoutInput = form.querySelector(
          'input[name="checkout_date"], input[data-cbe-checkout]',
        );

        if (
          !form.classList.contains("cbe-search-form") &&
          checkinInput &&
          checkoutInput &&
          checkinInput.value &&
          checkoutInput.value
        ) {
          this.triggerAvailabilityCheck(form);
        }

        this.maybeAutoSearchFromQuery(form);
      });
    },
  };

  window.CBEAvailability = CBEAvailability;

  // Auto-initialize on DOM ready
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", () => {
      CBEAvailability.initAll();
    });
  } else {
    CBEAvailability.initAll();
  }
})();
