(function () {
  function parseDate(value) {
    if (!value) {
      return null;
    }

    var date = new Date(value + "T00:00:00");
    if (isNaN(date.getTime())) {
      return null;
    }

    return date;
  }

  function diffNights(checkin, checkout) {
    if (!checkin || !checkout) {
      return 0;
    }

    var ms = checkout.getTime() - checkin.getTime();
    if (ms <= 0) {
      return 0;
    }

    return Math.round(ms / 86400000);
  }

  function money(value) {
    return Number(value).toLocaleString(undefined, {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    });
  }

  function escapeHtml(value) {
    return String(value || "")
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/\"/g, "&quot;")
      .replace(/'/g, "&#39;");
  }

  function isLocalIcon(iconValue) {
    return String(iconValue || "").indexOf("local:") === 0;
  }

  function getLocalIconUrl(iconValue) {
    var filename = String(iconValue || "").slice(6);
    filename = filename.replace(/[^a-zA-Z0-9_\-\.]/g, "");
    if (!filename) {
      return "";
    }
    var base = (window.cbeConfig && window.cbeConfig.facilityIconsUrl) || "";
    return base + filename;
  }

  function isMaterialSymbolIcon(iconValue) {
    return String(iconValue || "").indexOf("ms:") === 0;
  }

  function isFontAwesomeIcon(iconValue) {
    return String(iconValue || "").indexOf("fa:") === 0;
  }

  function isBootstrapIcon(iconValue) {
    return String(iconValue || "").indexOf("bi:") === 0;
  }

  function isIconifyIcon(iconValue) {
    var icon = String(iconValue || "");
    if (!icon) {
      return false;
    }

    if (
      isMaterialSymbolIcon(icon) ||
      isFontAwesomeIcon(icon) ||
      isBootstrapIcon(icon)
    ) {
      return false;
    }

    return /^[a-z0-9]+:[a-z0-9_-]+$/i.test(icon);
  }

  function getIconifySvgUrl(iconValue) {
    return (
      "https://api.iconify.design/" + encodeURIComponent(iconValue) + ".svg"
    );
  }

  function getMaterialSymbolName(iconValue) {
    return String(iconValue || "")
      .slice(3)
      .toLowerCase()
      .replace(/[^a-z0-9_]/g, "");
  }

  function getFontAwesomeClassName(iconValue) {
    return String(iconValue || "")
      .slice(3)
      .replace(/[^a-z0-9\-\s]/gi, "")
      .trim();
  }

  function getBootstrapIconClassName(iconValue) {
    return String(iconValue || "")
      .slice(3)
      .replace(/[^a-z0-9\-]/gi, "")
      .trim();
  }

  function getPlainMaterialToken(iconValue) {
    var raw = String(iconValue || "")
      .trim()
      .toLowerCase();
    if (!/^[a-z0-9_\-\s]+$/.test(raw)) {
      return "";
    }

    return raw.replace(/[\-\s]+/g, "_").replace(/[^a-z0-9_]/g, "");
  }

  function renderIconifyImageHtml(iconName, className) {
    if (!iconName) {
      return "";
    }

    return (
      '<img class="' +
      escapeHtml((className + " cbe-iconify-icon").trim()) +
      '" src="' +
      escapeHtml(getIconifySvgUrl(iconName)) +
      '" alt="" loading="lazy" decoding="async" />'
    );
  }

  function renderFacilityIconHtml(iconValue, className) {
    var icon = String(iconValue || "");
    var classes = className || "";

    if (isLocalIcon(icon)) {
      var localUrl = getLocalIconUrl(icon);
      if (localUrl) {
        return (
          '<img class="' +
          escapeHtml((classes + " cbe-local-icon").trim()) +
          '" src="' +
          escapeHtml(localUrl) +
          '" alt="" loading="lazy" decoding="async" />'
        );
      }
    }

    if (isMaterialSymbolIcon(icon)) {
      var symbolName = getMaterialSymbolName(icon);
      var msIcon = renderIconifyImageHtml(
        symbolName ? "material-symbols:" + symbolName : "",
        classes,
      );
      if (msIcon) {
        return msIcon;
      }
    }

    if (isFontAwesomeIcon(icon)) {
      var faClass = getFontAwesomeClassName(icon);
      if (faClass) {
        return (
          '<i class="' +
          escapeHtml((classes + " " + faClass).trim()) +
          '" aria-hidden="true"></i>'
        );
      }
    }

    if (isBootstrapIcon(icon)) {
      var biClass = getBootstrapIconClassName(icon);
      if (biClass) {
        return (
          '<i class="' +
          escapeHtml((classes + " bi bi-" + biClass).trim()) +
          '" aria-hidden="true"></i>'
        );
      }
    }

    if (isIconifyIcon(icon)) {
      return renderIconifyImageHtml(icon, classes);
    }

    var plainToken = getPlainMaterialToken(icon);
    if (plainToken) {
      var plainMsIcon = renderIconifyImageHtml(
        "material-symbols:" + plainToken,
        classes,
      );
      if (plainMsIcon) {
        return plainMsIcon;
      }
    }

    return (
      '<span class="' +
      escapeHtml(classes) +
      '">' +
      escapeHtml(icon || "•") +
      "</span>"
    );
  }

  function updateFormPrice(form) {
    var pricePerNight = Number(form.getAttribute("data-price-per-night") || 0);
    var checkinInput = form.querySelector('input[name="checkin_date"]');
    var checkoutInput = form.querySelector('input[name="checkout_date"]');
    var nightsEl = form.querySelector(".cbe-total-nights");
    var totalEl = form.querySelector(".cbe-total-price");

    if (!checkinInput || !checkoutInput || !nightsEl || !totalEl) {
      return;
    }

    var nights = diffNights(
      parseDate(checkinInput.value),
      parseDate(checkoutInput.value),
    );
    var total = nights * pricePerNight;

    nightsEl.textContent = String(nights);
    totalEl.textContent = money(total);
  }

  function setupMinDate(form) {
    var now = new Date();
    var yyyy = now.getFullYear();
    var mm = String(now.getMonth() + 1).padStart(2, "0");
    var dd = String(now.getDate()).padStart(2, "0");
    var today = yyyy + "-" + mm + "-" + dd;

    var checkinInput = form.querySelector('input[name="checkin_date"]');
    var checkoutInput = form.querySelector('input[name="checkout_date"]');

    if (checkinInput) {
      checkinInput.setAttribute("min", today);
    }

    if (checkoutInput) {
      checkoutInput.setAttribute("min", today);
    }
  }

  function bindForm(form) {
    var checkinInput = form.querySelector('input[name="checkin_date"]');
    var checkoutInput = form.querySelector('input[name="checkout_date"]');

    setupMinDate(form);
    updateFormPrice(form);

    if (checkinInput) {
      checkinInput.addEventListener("change", function () {
        if (
          checkoutInput &&
          checkoutInput.value &&
          checkoutInput.value <= checkinInput.value
        ) {
          checkoutInput.value = "";
        }
        if (checkoutInput) {
          checkoutInput.setAttribute(
            "min",
            checkinInput.value || checkoutInput.getAttribute("min"),
          );
        }
        updateFormPrice(form);
      });
    }

    if (checkoutInput) {
      checkoutInput.addEventListener("change", function () {
        updateFormPrice(form);
      });
    }
  }

  document.addEventListener("DOMContentLoaded", function () {
    var forms = document.querySelectorAll(".cbe-booking-form");
    forms.forEach(bindForm);

    initStayGalleryAutoScroll();
    initStayGalleryImageViewer();
    initModal();
    initCabinDetails();
  });

  function initStayGalleryImageViewer() {
    var imageViewerOverlay = document.getElementById(
      "cbe-image-viewer-overlay",
    );
    var imageViewerImage = imageViewerOverlay
      ? imageViewerOverlay.querySelector("#cbe-image-viewer-image")
      : null;
    var imageViewerCounter = imageViewerOverlay
      ? imageViewerOverlay.querySelector("#cbe-image-viewer-counter")
      : null;
    var imageViewerCloseBtn = imageViewerOverlay
      ? imageViewerOverlay.querySelector(".cbe-image-viewer-close")
      : null;

    if (!imageViewerOverlay || !imageViewerImage) {
      return;
    }

    document.addEventListener("click", function (e) {
      var thumb = e.target.closest(".cbe-custom-stay-gallery-thumb");
      if (!thumb) {
        return;
      }

      var gallery = thumb.closest(".cbe-custom-stay-gallery");
      if (!gallery) {
        return;
      }

      e.preventDefault();
      e.stopPropagation();

      var thumbs = gallery.querySelectorAll(".cbe-custom-stay-gallery-thumb");
      var viewerState = {
        images: [],
        index: 0,
      };

      thumbs.forEach(function (item) {
        var fullImageUrl = item.getAttribute("href") || "";
        if (fullImageUrl) {
          viewerState.images.push(fullImageUrl);
        }
      });

      if (!viewerState.images.length) {
        return;
      }

      var targetImageUrl = thumb.getAttribute("href") || "";
      var selectedIndex = viewerState.images.indexOf(targetImageUrl);
      viewerState.index = selectedIndex >= 0 ? selectedIndex : 0;

      imageViewerOverlay._cbeViewerState = viewerState;
      syncViewerControls(imageViewerOverlay, viewerState);
      renderImageViewer(imageViewerImage, imageViewerCounter, viewerState);
      imageViewerOverlay.hidden = false;
      document.body.style.overflow = "hidden";

      if (imageViewerCloseBtn) {
        imageViewerCloseBtn.focus();
      }
    });
  }

  function initStayGalleryAutoScroll() {
    if (
      window.matchMedia &&
      window.matchMedia("(prefers-reduced-motion: reduce)").matches
    ) {
      return;
    }

    var galleries = document.querySelectorAll(".cbe-custom-stay-gallery");
    galleries.forEach(function (gallery) {
      var thumbs = gallery.querySelectorAll(".cbe-custom-stay-gallery-thumb");
      if (thumbs.length <= 1) {
        return;
      }

      var timerId = null;
      var gap = parseFloat(window.getComputedStyle(gallery).gap || "16") || 16;

      function getStep() {
        var firstThumb = gallery.querySelector(
          ".cbe-custom-stay-gallery-thumb",
        );
        if (!firstThumb) {
          return 280;
        }
        return firstThumb.getBoundingClientRect().width + gap;
      }

      function tick() {
        var maxScrollLeft = gallery.scrollWidth - gallery.clientWidth;
        if (maxScrollLeft <= 0) {
          return;
        }

        var nextLeft = gallery.scrollLeft + getStep();
        if (nextLeft >= maxScrollLeft - 2) {
          nextLeft = 0;
        }

        gallery.scrollTo({
          left: nextLeft,
          behavior: "smooth",
        });
      }

      function start() {
        if (timerId !== null) {
          return;
        }
        timerId = window.setInterval(tick, 3200);
      }

      function stop() {
        if (timerId === null) {
          return;
        }
        window.clearInterval(timerId);
        timerId = null;
      }

      gallery.addEventListener("mouseenter", stop);
      gallery.addEventListener("mouseleave", start);
      gallery.addEventListener("focusin", stop);
      gallery.addEventListener("focusout", start);
      gallery.addEventListener("touchstart", stop, { passive: true });
      gallery.addEventListener("pointerdown", stop);
      gallery.addEventListener("wheel", stop, { passive: true });

      start();
    });
  }

  function initCabinDetails() {
    var detailOverlay = document.getElementById("cbe-detail-overlay");
    if (!detailOverlay) {
      return;
    }

    var imageViewerOverlay = document.getElementById(
      "cbe-image-viewer-overlay",
    );
    var imageViewerImage = imageViewerOverlay
      ? imageViewerOverlay.querySelector("#cbe-image-viewer-image")
      : null;
    var imageViewerCounter = imageViewerOverlay
      ? imageViewerOverlay.querySelector("#cbe-image-viewer-counter")
      : null;
    var imageViewerPrevBtn = imageViewerOverlay
      ? imageViewerOverlay.querySelector(".cbe-image-viewer-prev")
      : null;
    var imageViewerNextBtn = imageViewerOverlay
      ? imageViewerOverlay.querySelector(".cbe-image-viewer-next")
      : null;
    var imageViewerCloseBtn = imageViewerOverlay
      ? imageViewerOverlay.querySelector(".cbe-image-viewer-close")
      : null;

    var detailTitle = detailOverlay.querySelector("#cbe-detail-title");
    var detailBody = detailOverlay.querySelector("#cbe-detail-body");
    var detailCloseBtn = detailOverlay.querySelector(".cbe-detail-close");
    var viewerState = {
      images: [],
      index: 0,
    };

    document.addEventListener("click", function (e) {
      var btn = e.target.closest(".cbe-detail-btn");
      if (!btn) {
        return;
      }

      var cabinId = btn.getAttribute("data-cbe-detail-id");
      if (!cabinId) {
        return;
      }

      var detailPanel = document.getElementById("cbe-cabin-detail-" + cabinId);
      if (!detailPanel) {
        return;
      }

      var title = btn.getAttribute("data-cbe-detail-title") || "Cabin Details";

      if (detailTitle) {
        detailTitle.textContent = title;
      }
      if (detailBody) {
        detailBody.innerHTML = detailPanel.innerHTML;
      }

      viewerState.images = collectViewerImages(detailBody);
      viewerState.index = 0;

      detailOverlay.hidden = false;
      document.body.style.overflow = "hidden";
      btn.setAttribute("aria-expanded", "true");

      if (detailCloseBtn) {
        detailCloseBtn.focus();
      }
    });

    detailOverlay.addEventListener("click", function (e) {
      var thumb =
        e.target.closest(".cbe-cabin-detail-thumb") ||
        e.target.closest(".cbe-detail-gallery-thumb");
      if (thumb && imageViewerOverlay && imageViewerImage) {
        e.preventDefault();

        var targetImageUrl =
          thumb.getAttribute("data-full-image") ||
          thumb.getAttribute("href") ||
          "";
        if (!targetImageUrl) {
          return;
        }

        openImageViewer(
          imageViewerOverlay,
          imageViewerImage,
          imageViewerCounter,
          viewerState,
          targetImageUrl,
        );
        if (imageViewerCloseBtn) {
          imageViewerCloseBtn.focus();
        }
        return;
      }

      if (e.target === detailOverlay) {
        hideDetailModal(detailOverlay, imageViewerOverlay, imageViewerImage);
      }
    });

    if (detailCloseBtn) {
      detailCloseBtn.addEventListener("click", function () {
        hideDetailModal(detailOverlay, imageViewerOverlay, imageViewerImage);
      });
    }

    if (imageViewerOverlay) {
      imageViewerOverlay.addEventListener("click", function (e) {
        if (e.target === imageViewerOverlay) {
          hideImageViewer(imageViewerOverlay, imageViewerImage);
        }
      });
    }

    if (imageViewerCloseBtn) {
      imageViewerCloseBtn.addEventListener("click", function () {
        hideImageViewer(imageViewerOverlay, imageViewerImage);
      });
    }

    if (imageViewerPrevBtn) {
      imageViewerPrevBtn.addEventListener("click", function () {
        var activeViewerState =
          (imageViewerOverlay && imageViewerOverlay._cbeViewerState) ||
          viewerState;
        moveViewer(
          imageViewerOverlay,
          imageViewerImage,
          imageViewerCounter,
          activeViewerState,
          -1,
        );
      });
    }

    if (imageViewerNextBtn) {
      imageViewerNextBtn.addEventListener("click", function () {
        var activeViewerState =
          (imageViewerOverlay && imageViewerOverlay._cbeViewerState) ||
          viewerState;
        moveViewer(
          imageViewerOverlay,
          imageViewerImage,
          imageViewerCounter,
          activeViewerState,
          1,
        );
      });
    }

    document.addEventListener("keydown", function (e) {
      if (e.key !== "Escape") {
        if (
          imageViewerOverlay &&
          !imageViewerOverlay.hidden &&
          (e.key === "ArrowLeft" || e.key === "ArrowRight")
        ) {
          e.preventDefault();
          var activeViewerState =
            imageViewerOverlay._cbeViewerState || viewerState;
          moveViewer(
            imageViewerOverlay,
            imageViewerImage,
            imageViewerCounter,
            activeViewerState,
            e.key === "ArrowLeft" ? -1 : 1,
          );
        }
        return;
      }

      if (imageViewerOverlay && !imageViewerOverlay.hidden) {
        hideImageViewer(imageViewerOverlay, imageViewerImage);
        return;
      }

      if (!detailOverlay.hidden) {
        hideDetailModal(detailOverlay, imageViewerOverlay, imageViewerImage);
      }
    });
  }

  function collectViewerImages(detailBody) {
    if (!detailBody) {
      return [];
    }

    var thumbs = detailBody.querySelectorAll(
      ".cbe-cabin-detail-thumb, .cbe-detail-gallery-thumb",
    );
    var images = [];

    thumbs.forEach(function (thumb) {
      var fullImageUrl =
        thumb.getAttribute("data-full-image") ||
        thumb.getAttribute("href") ||
        "";
      if (fullImageUrl) {
        images.push(fullImageUrl);
      }
    });

    return images;
  }

  function openImageViewer(
    imageViewerOverlay,
    imageViewerImage,
    imageViewerCounter,
    viewerState,
    targetImageUrl,
  ) {
    if (
      !imageViewerOverlay ||
      !imageViewerImage ||
      !viewerState.images.length
    ) {
      return;
    }

    var index = viewerState.images.indexOf(targetImageUrl);
    viewerState.index = index >= 0 ? index : 0;
    imageViewerOverlay._cbeViewerState = viewerState;
    syncViewerControls(imageViewerOverlay, viewerState);
    renderImageViewer(imageViewerImage, imageViewerCounter, viewerState);
    imageViewerOverlay.hidden = false;
    document.body.style.overflow = "hidden";
  }

  function moveViewer(
    imageViewerOverlay,
    imageViewerImage,
    imageViewerCounter,
    viewerState,
    direction,
  ) {
    if (
      !imageViewerOverlay ||
      !imageViewerImage ||
      !viewerState.images.length
    ) {
      return;
    }

    var total = viewerState.images.length;
    viewerState.index = (viewerState.index + direction + total) % total;
    renderImageViewer(imageViewerImage, imageViewerCounter, viewerState);
  }

  function renderImageViewer(
    imageViewerImage,
    imageViewerCounter,
    viewerState,
  ) {
    var currentImageUrl = viewerState.images[viewerState.index] || "";
    if (!currentImageUrl) {
      return;
    }

    imageViewerImage.setAttribute("src", currentImageUrl);
    imageViewerImage.setAttribute(
      "alt",
      "Cabin image " + (viewerState.index + 1),
    );

    if (imageViewerCounter) {
      imageViewerCounter.textContent =
        viewerState.index + 1 + " / " + viewerState.images.length;
    }
  }

  function syncViewerControls(imageViewerOverlay, viewerState) {
    if (!imageViewerOverlay) {
      return;
    }

    var prevBtn = imageViewerOverlay.querySelector(".cbe-image-viewer-prev");
    var nextBtn = imageViewerOverlay.querySelector(".cbe-image-viewer-next");
    var hasMultipleImages = viewerState && viewerState.images.length > 1;

    if (prevBtn) {
      prevBtn.disabled = !hasMultipleImages;
    }

    if (nextBtn) {
      nextBtn.disabled = !hasMultipleImages;
    }

    imageViewerOverlay.classList.toggle(
      "cbe-image-viewer-single",
      !hasMultipleImages,
    );
  }

  function hideDetailModal(
    detailOverlay,
    imageViewerOverlay,
    imageViewerImage,
  ) {
    detailOverlay.hidden = true;
    hideImageViewer(imageViewerOverlay, imageViewerImage);
    document.body.style.overflow = "";

    var detailButtons = document.querySelectorAll(
      ".cbe-detail-btn[aria-expanded='true']",
    );
    detailButtons.forEach(function (btn) {
      btn.setAttribute("aria-expanded", "false");
    });
  }

  function hideImageViewer(imageViewerOverlay, imageViewerImage) {
    if (!imageViewerOverlay) {
      return;
    }

    imageViewerOverlay.hidden = true;
    imageViewerOverlay.classList.remove("cbe-image-viewer-single");
    imageViewerOverlay._cbeViewerState = null;
    if (imageViewerImage) {
      imageViewerImage.setAttribute("src", "");
    }

    var detailOverlay = document.getElementById("cbe-detail-overlay");
    var modalOverlay = document.getElementById("cbe-modal-overlay");
    var isDetailOpen = detailOverlay && !detailOverlay.hidden;
    var isModalOpen = modalOverlay && !modalOverlay.hidden;

    if (!isDetailOpen && !isModalOpen) {
      document.body.style.overflow = "";
    }
  }

  /* ── Modal ─────────────────────────────────────────────────── */
  function initModal() {
    var overlay = document.getElementById("cbe-modal-overlay");
    var detailOverlay = document.getElementById("cbe-detail-overlay");
    if (!overlay && !detailOverlay) {
      return;
    }

    /* Open modal when any .cbe-book-now-btn or .cbe-view-details-btn is clicked */
    document.addEventListener("click", function (e) {
      var bookBtn = e.target.closest(".cbe-book-now-btn");
      var detailBtn = e.target.closest(".cbe-view-details-btn");

      if (bookBtn && overlay) {
        var cabinId = bookBtn.getAttribute("data-cbe-cabin-id");
        showModal(overlay, cabinId);
      } else if (detailBtn) {
        var cabinId = detailBtn.getAttribute("data-cbe-cabin-id");
        showDetailModal(cabinId, detailBtn);
      }
    });

    /* Close on overlay background click */
    if (overlay) {
      overlay.addEventListener("click", function (e) {
        if (e.target === overlay) {
          hideModal(overlay);
        }
      });
    }

    if (detailOverlay) {
      detailOverlay.addEventListener("click", function (e) {
        if (e.target === detailOverlay) {
          hideDetailModal(detailOverlay, null, null);
        }
      });
    }

    /* Close button */
    if (overlay) {
      var closeBtn = overlay.querySelector(".cbe-modal-close");
      if (closeBtn) {
        closeBtn.addEventListener("click", function () {
          hideModal(overlay);
        });
      }
    }

    if (detailOverlay) {
      var detailCloseBtn = detailOverlay.querySelector(".cbe-detail-close");
      if (detailCloseBtn) {
        detailCloseBtn.addEventListener("click", function () {
          hideDetailModal(detailOverlay, null, null);
        });
      }
    }

    /* Handle Book Now button inside detail modal */
    document.addEventListener("click", function (e) {
      var detailBookBtn = e.target.closest(".cbe-detail-book-btn");
      if (detailBookBtn) {
        var cabinId = detailBookBtn.getAttribute("data-cbe-cabin-id");
        hideDetailModal(detailOverlay, null, null);
        if (overlay) {
          showModal(overlay, cabinId);
        }
      }
    });

    /* Close on Escape key */
    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape") {
        if (overlay && !overlay.hidden) {
          hideModal(overlay);
        } else if (detailOverlay && !detailOverlay.hidden) {
          hideDetailModal(detailOverlay, null, null);
        }
      }
    });
  }

  function showDetailModal(cabinId, triggerEl) {
    var detailOverlay = document.getElementById("cbe-detail-overlay");
    if (!detailOverlay) {
      return;
    }

    // Prefer the closest card from the clicked button to avoid ID mismatch edge cases.
    var card = triggerEl
      ? triggerEl.closest(".cbe-stay-page-cabin-card")
      : null;
    if (!card && cabinId) {
      card =
        document.querySelector(
          '.cbe-stay-page-cabin-card[data-cabin-id="' + cabinId + '"]',
        ) || document.querySelector('[data-cabin-id="' + cabinId + '"]');
    }
    var detailData = null;

    if (card) {
      var dataAttr = card.getAttribute("data-cabin-details");
      if (dataAttr) {
        try {
          detailData = JSON.parse(dataAttr);
        } catch (e) {
          console.error("Failed to parse cabin details:", e);
        }
      }
    }

    var title = detailOverlay.querySelector(".cbe-detail-title");
    var body = detailOverlay.querySelector(".cbe-detail-body");

    if (!detailData) {
      return;
    }

    if (title) {
      title.textContent = detailData.title || "";
    }

    if (body) {
      var overviewText = detailData.content || detailData.excerpt || "";
      var availableText =
        detailData.totalUnits > 0
          ? detailData.totalUnits + " cabins available"
          : "Availability on request";
      var facilities =
        detailData.facilities && Array.isArray(detailData.facilities)
          ? detailData.facilities.filter(function (facility) {
              return facility && (facility.label || "") !== "";
            })
          : [];
      var leftFacilities = facilities.filter(function (_, idx) {
        return idx % 2 === 0;
      });
      var rightFacilities = facilities.filter(function (_, idx) {
        return idx % 2 === 1;
      });

      var html = '<div class="cbe-detail-layout">';

      // ── Gallery (full-bleed, no card wrapper) ───────────────
      html += '<div class="cbe-detail-gallery-wrap">';
      if (detailData.galleryImages && detailData.galleryImages.length > 0) {
        html += '<div class="cbe-detail-gallery">';
        detailData.galleryImages.forEach(function (img, idx) {
          html +=
            '<a class="cbe-detail-gallery-thumb" href="' +
            img.full +
            '" data-full-image="' +
            img.full +
            '" data-view-index="' +
            idx +
            '"><img src="' +
            img.thumb +
            '" alt="' +
            escapeHtml(detailData.title || "Room") +
            '" loading="lazy" /></a>';
        });
        html += "</div>";
      } else {
        html +=
          '<div class="cbe-detail-no-photo"><span>No photos available</span></div>';
      }
      html += "</div>";

      // ── Name + Price ─────────────────────────────────────────
      html += '<div class="cbe-detail-info-row">';
      html +=
        '<div class="cbe-detail-name-row"><strong class="cbe-detail-room-name">' +
        escapeHtml(detailData.title || "Room") +
        '</strong><span class="cbe-detail-room-price">' +
        (detailData.pricePerNight
          ? Number(detailData.pricePerNight).toLocaleString()
          : "0") +
        " /night</span></div>";
      html += '<div class="cbe-detail-room-specs">';
      if (detailData.bedType) {
        html +=
          '<div class="cbe-detail-spec-card">' +
          '<span class="cbe-spec-icon">🛏️</span>' +
          '<span class="cbe-spec-label">' +
          escapeHtml(detailData.bedType) +
          "</span></div>";
      }
      if (detailData.maxGuests > 0) {
        html +=
          '<div class="cbe-detail-spec-card">' +
          '<span class="cbe-spec-icon">👥</span>' +
          '<span class="cbe-spec-label">Max ' +
          detailData.maxGuests +
          " guests</span></div>";
      }
      if (detailData.totalUnits > 0) {
        html +=
          '<div class="cbe-detail-spec-card">' +
          '<span class="cbe-spec-icon">🏠</span>' +
          '<span class="cbe-spec-label">' +
          detailData.totalUnits +
          " cabins available</span></div>";
      }
      html += "</div>";
      html += "</div>";

      // ── Overview ─────────────────────────────────────────────
      if (overviewText !== "") {
        html += '<div class="cbe-detail-row cbe-detail-row-overview">';
        html += '<span class="cbe-detail-row-label">Cabin Overview</span>';
        html +=
          '<div class="cbe-detail-overview">' +
          escapeHtml(overviewText).replace(/\n/g, "<br />") +
          "</div>";
        html += "</div>";
        html += '<hr class="cbe-detail-divider" />';
      }

      // ── Facilities ────────────────────────────────────────────
      html += '<div class="cbe-detail-row cbe-detail-row-facilities">';
      html += '<span class="cbe-detail-row-label">In Cabin Amenities</span>';
      if (facilities.length > 0) {
        html += '<div class="cbe-detail-facilities-grid">';

        html += '<ul class="cbe-detail-facilities-list">';
        leftFacilities.forEach(function (facility) {
          html +=
            "<li>" +
            renderFacilityIconHtml(
              facility.icon || "",
              "cbe-detail-facility-icon",
            ) +
            '<span class="cbe-detail-facility-label">' +
            escapeHtml(facility.label || "") +
            "</span></li>";
        });
        html += "</ul>";

        html += '<ul class="cbe-detail-facilities-list">';
        rightFacilities.forEach(function (facility) {
          html +=
            "<li>" +
            renderFacilityIconHtml(
              facility.icon || "",
              "cbe-detail-facility-icon",
            ) +
            '<span class="cbe-detail-facility-label">' +
            escapeHtml(facility.label || "") +
            "</span></li>";
        });
        html += "</ul>";

        html += "</div>";
      } else {
        html += '<p class="cbe-detail-empty">Belum ada fasilitas.</p>';
      }
      html += "</div>";
      html += '<hr class="cbe-detail-divider" />';

      // ── Book Now ──────────────────────────────────────────────
      html += '<div class="cbe-detail-row cbe-detail-row-action">';
      html +=
        '<button type="button" class="cbe-detail-book-btn" data-cbe-cabin-id="' +
        detailData.id +
        '">Book Now</button>';
      html += "</div>";

      html += "</div>";

      body.innerHTML = html;

      var imageViewerOverlay = document.getElementById(
        "cbe-image-viewer-overlay",
      );
      var imageViewerImage = imageViewerOverlay
        ? imageViewerOverlay.querySelector("#cbe-image-viewer-image")
        : null;
      var imageViewerCounter = imageViewerOverlay
        ? imageViewerOverlay.querySelector("#cbe-image-viewer-counter")
        : null;

      if (imageViewerOverlay && detailData.galleryImages) {
        var detailViewerState = {
          images: [],
          index: 0,
        };

        detailData.galleryImages.forEach(function (img) {
          detailViewerState.images.push(img.full);
        });

        var galleryThumbs = body.querySelectorAll(".cbe-detail-gallery-thumb");
        galleryThumbs.forEach(function (thumb) {
          thumb.addEventListener("click", function (e) {
            e.preventDefault();
            if (detailViewerState.images.length === 0) {
              return;
            }

            var idx = parseInt(thumb.getAttribute("data-view-index"), 10);
            if (
              isNaN(idx) ||
              idx < 0 ||
              idx >= detailViewerState.images.length
            ) {
              idx = 0;
            }

            detailViewerState.index = idx;
            imageViewerOverlay._cbeViewerState = detailViewerState;
            syncViewerControls(imageViewerOverlay, detailViewerState);
            renderImageViewer(
              imageViewerImage,
              imageViewerCounter,
              detailViewerState,
            );
            imageViewerOverlay.hidden = false;
            document.body.style.overflow = "hidden";
          });
        });

        var imageViewerPrevBtn = imageViewerOverlay.querySelector(
          ".cbe-image-viewer-prev",
        );
        var imageViewerNextBtn = imageViewerOverlay.querySelector(
          ".cbe-image-viewer-next",
        );

        if (imageViewerPrevBtn) {
          imageViewerPrevBtn.onclick = function () {
            moveViewer(
              imageViewerOverlay,
              imageViewerImage,
              imageViewerCounter,
              detailViewerState,
              -1,
            );
          };
        }

        if (imageViewerNextBtn) {
          imageViewerNextBtn.onclick = function () {
            moveViewer(
              imageViewerOverlay,
              imageViewerImage,
              imageViewerCounter,
              detailViewerState,
              1,
            );
          };
        }
      }
    }

    detailOverlay.hidden = false;
    document.body.style.overflow = "hidden";
  }

  function showModal(overlay, cabinId) {
    /* Hide all panels first */
    var panels = overlay.querySelectorAll(".cbe-modal-panel");
    panels.forEach(function (p) {
      p.hidden = true;
    });

    /* Show the matching panel */
    var panel = overlay.querySelector(
      '.cbe-modal-panel[data-modal-cabin-id="' + cabinId + '"]',
    );
    if (panel) {
      panel.hidden = false;

      /* Bind form inputs inside the modal */
      var forms = panel.querySelectorAll(".cbe-booking-form");
      forms.forEach(bindForm);
    }

    overlay.hidden = false;
    document.body.style.overflow = "hidden";

    /* Focus close button for accessibility */
    var closeBtn = overlay.querySelector(".cbe-modal-close");
    if (closeBtn) {
      closeBtn.focus();
    }
  }

  function hideModal(overlay) {
    overlay.hidden = true;
    document.body.style.overflow = "";
  }
})();
