(function ($) {
  function attachmentThumbUrl(attachment) {
    if (!attachment) {
      return "";
    }

    if (
      attachment.sizes &&
      attachment.sizes.thumbnail &&
      attachment.sizes.thumbnail.url
    ) {
      return attachment.sizes.thumbnail.url;
    }

    return attachment.url || "";
  }

  function renderFeaturedPreview(attachment) {
    var preview = $("#cbe-featured-preview");
    if (!preview.length) {
      return;
    }

    var url = attachmentThumbUrl(attachment);
    if (!url) {
      preview.empty();
      return;
    }

    preview.html('<img src="' + url + '" alt="" />');
  }

  function renderSingleImagePreview(previewSelector, attachment) {
    var preview = $(previewSelector);
    if (!preview.length) {
      return;
    }

    var url = attachmentThumbUrl(attachment);
    if (!url) {
      preview.empty();
      return;
    }

    preview.html('<img src="' + url + '" alt="" />');
  }

  function renderGalleryByIds(ids) {
    var preview = $("#cbe-gallery-preview, #cbe-page-gallery-preview").first();
    if (!preview.length) {
      return;
    }

    preview.empty();
    ids.forEach(function (id) {
      var attachment = wp.media.attachment(id);
      attachment.fetch().then(function () {
        var data = attachment.attributes || {};
        var url = attachmentThumbUrl(data);
        if (url) {
          preview.append(
            '<span class="cbe-gallery-thumb"><img src="' +
              url +
              '" alt="" /></span>',
          );
        }
      });
    });
  }

  $(function () {
    var featuredInput = $("#cbe_featured_image_id");
    var pageHeaderDesktopInput = $("#cbe_page_header_desktop_image_id");
    var pageHeaderMobileInput = $("#cbe_page_header_mobile_image_id");
    var galleryInput = $("#cbe_gallery_ids, #cbe_page_gallery_ids").first();
    var galleryPreview = $(
      "#cbe-gallery-preview, #cbe-page-gallery-preview",
    ).first();

    if (
      featuredInput.length ||
      pageHeaderDesktopInput.length ||
      pageHeaderMobileInput.length ||
      galleryInput.length
    ) {
      var selectFeaturedBtn = $("#cbe-select-featured");
      var removeFeaturedBtn = $("#cbe-remove-featured");
      var selectPageHeaderDesktopBtn = $(
        "#cbe-select-page-header-desktop-image",
      );
      var removePageHeaderDesktopBtn = $(
        "#cbe-remove-page-header-desktop-image",
      );
      var selectPageHeaderMobileBtn = $("#cbe-select-page-header-mobile-image");
      var removePageHeaderMobileBtn = $("#cbe-remove-page-header-mobile-image");
      var selectGalleryBtn = $("#cbe-select-gallery, #cbe-select-page-gallery");
      var clearGalleryBtn = $("#cbe-clear-gallery, #cbe-clear-page-gallery");

      var featuredFrame = null;
      var pageHeaderDesktopFrame = null;
      var pageHeaderMobileFrame = null;
      var galleryFrame = null;

      selectFeaturedBtn.on("click", function (e) {
        e.preventDefault();

        if (featuredFrame) {
          featuredFrame.open();
          return;
        }

        featuredFrame = wp.media({
          title: "Select featured photo",
          button: { text: "Use this photo" },
          library: { type: "image" },
          multiple: false,
        });

        featuredFrame.on("select", function () {
          var attachment = featuredFrame
            .state()
            .get("selection")
            .first()
            .toJSON();
          featuredInput.val(attachment.id);
          renderFeaturedPreview(attachment);
        });

        featuredFrame.open();
      });

      removeFeaturedBtn.on("click", function (e) {
        e.preventDefault();
        featuredInput.val("");
        renderFeaturedPreview(null);
      });

      selectPageHeaderDesktopBtn.on("click", function (e) {
        e.preventDefault();

        if (pageHeaderDesktopFrame) {
          pageHeaderDesktopFrame.open();
          return;
        }

        pageHeaderDesktopFrame = wp.media({
          title: "Select desktop header photo (1920 x 300)",
          button: { text: "Use this photo" },
          library: { type: "image" },
          multiple: false,
        });

        pageHeaderDesktopFrame.on("select", function () {
          var attachment = pageHeaderDesktopFrame
            .state()
            .get("selection")
            .first()
            .toJSON();
          pageHeaderDesktopInput.val(attachment.id);
          renderSingleImagePreview(
            "#cbe-page-header-desktop-preview",
            attachment,
          );
        });

        pageHeaderDesktopFrame.open();
      });

      removePageHeaderDesktopBtn.on("click", function (e) {
        e.preventDefault();
        pageHeaderDesktopInput.val("");
        renderSingleImagePreview("#cbe-page-header-desktop-preview", null);
      });

      selectPageHeaderMobileBtn.on("click", function (e) {
        e.preventDefault();

        if (pageHeaderMobileFrame) {
          pageHeaderMobileFrame.open();
          return;
        }

        pageHeaderMobileFrame = wp.media({
          title: "Select mobile header photo (1280 x 1280)",
          button: { text: "Use this photo" },
          library: { type: "image" },
          multiple: false,
        });

        pageHeaderMobileFrame.on("select", function () {
          var attachment = pageHeaderMobileFrame
            .state()
            .get("selection")
            .first()
            .toJSON();
          pageHeaderMobileInput.val(attachment.id);
          renderSingleImagePreview(
            "#cbe-page-header-mobile-preview",
            attachment,
          );
        });

        pageHeaderMobileFrame.open();
      });

      removePageHeaderMobileBtn.on("click", function (e) {
        e.preventDefault();
        pageHeaderMobileInput.val("");
        renderSingleImagePreview("#cbe-page-header-mobile-preview", null);
      });

      selectGalleryBtn.on("click", function (e) {
        e.preventDefault();

        if (galleryFrame) {
          galleryFrame.open();
          return;
        }

        galleryFrame = wp.media({
          title: "Select gallery photos",
          button: { text: "Use selected photos" },
          library: { type: "image" },
          multiple: true,
        });

        galleryFrame.on("select", function () {
          var selection = galleryFrame.state().get("selection");
          var ids = [];

          selection.each(function (model) {
            ids.push(model.get("id"));
          });

          galleryInput.val(ids.join(","));
          renderGalleryByIds(ids);
        });

        galleryFrame.open();
      });

      clearGalleryBtn.on("click", function (e) {
        e.preventDefault();
        galleryInput.val("");
        galleryPreview.empty();
      });

      var existingGalleryIds = (galleryPreview.attr("data-gallery-ids") || "")
        .split(",")
        .map(function (item) {
          return parseInt(item, 10);
        })
        .filter(function (id) {
          return id > 0;
        });

      if (existingGalleryIds.length) {
        renderGalleryByIds(existingGalleryIds);
      }
    }

    initStayPageRoomOrdering();
    initFacilitiesBuilder();
    initFacilityCatalogBuilder();
  });

  function initStayPageRoomOrdering() {
    var root = $(".cbe-stay-page-manager");
    if (!root.length) {
      return;
    }

    var orderList = root.find("#cbe-selected-room-order");
    var orderedInput = root.find("#cbe_ordered_cabin_ids");
    var roomItems = root.find(".cbe-room-picker-item");
    var counter = root.find("#cbe-selected-room-count");

    if (!orderList.length || !orderedInput.length || !roomItems.length) {
      return;
    }

    var selectedOrder = String(orderedInput.val() || "")
      .split(",")
      .map(function (item) {
        return parseInt(item, 10);
      })
      .filter(function (id) {
        return id > 0;
      });

    var draggedId = 0;

    function getItemById(id) {
      return root.find('.cbe-room-picker-item[data-cabin-id="' + id + '"]');
    }

    function getSelectedIdsInListOrder() {
      var ids = [];
      roomItems.each(function () {
        var item = $(this);
        var checkbox = item.find(".cbe-room-checkbox").first();
        var id = parseInt(String(item.attr("data-cabin-id") || "0"), 10);
        if (checkbox.prop("checked") && id > 0) {
          ids.push(id);
        }
      });
      return ids;
    }

    function normalizeSelectedOrder() {
      var selectedIds = getSelectedIdsInListOrder();
      var selectedMap = {};
      selectedIds.forEach(function (id) {
        selectedMap[id] = true;
      });

      selectedOrder = selectedOrder.filter(function (id) {
        return !!selectedMap[id];
      });

      selectedIds.forEach(function (id) {
        if (selectedOrder.indexOf(id) === -1) {
          selectedOrder.push(id);
        }
      });

      orderedInput.val(selectedOrder.join(","));
    }

    function moveInOrder(id, direction) {
      var index = selectedOrder.indexOf(id);
      if (index === -1) {
        return;
      }

      var targetIndex = direction === "up" ? index - 1 : index + 1;
      if (targetIndex < 0 || targetIndex >= selectedOrder.length) {
        return;
      }

      var current = selectedOrder[index];
      selectedOrder[index] = selectedOrder[targetIndex];
      selectedOrder[targetIndex] = current;

      orderedInput.val(selectedOrder.join(","));
      renderOrderList();
    }

    function moveBefore(dragId, targetId) {
      if (dragId <= 0 || targetId <= 0 || dragId === targetId) {
        return;
      }

      var dragIndex = selectedOrder.indexOf(dragId);
      var targetIndex = selectedOrder.indexOf(targetId);
      if (dragIndex === -1 || targetIndex === -1) {
        return;
      }

      selectedOrder.splice(dragIndex, 1);
      targetIndex = selectedOrder.indexOf(targetId);
      selectedOrder.splice(targetIndex, 0, dragId);

      orderedInput.val(selectedOrder.join(","));
      renderOrderList();
    }

    function renderOrderList() {
      orderList.empty();

      if (!selectedOrder.length) {
        orderList.append(
          '<div class="cbe-selected-room-order-empty">Select at least one room to set custom order.</div>',
        );
        return;
      }

      selectedOrder.forEach(function (id) {
        var item = getItemById(id);
        var title = String(item.attr("data-cabin-title") || "Room #" + id);

        orderList.append(
          '<div class="cbe-selected-room-order-item" draggable="true" data-cabin-id="' +
            id +
            '">' +
            '<span class="cbe-room-order-handle" aria-hidden="true">↕</span>' +
            "<strong>" +
            title +
            "</strong>" +
            '<span class="cbe-room-order-actions">' +
            '<button type="button" class="cbe-room-order-btn" data-room-order-action="up" aria-label="Move up">↑</button>' +
            '<button type="button" class="cbe-room-order-btn" data-room-order-action="down" aria-label="Move down">↓</button>' +
            "</span>" +
            "</div>",
        );
      });
    }

    function syncSelectionState() {
      var selectedCount = 0;

      roomItems.each(function () {
        var item = $(this);
        var checked = item.find(".cbe-room-checkbox").first().prop("checked");
        item.toggleClass("is-selected", checked);
        if (checked) {
          selectedCount += 1;
        }
      });

      counter.text(String(selectedCount));
      normalizeSelectedOrder();
      renderOrderList();
    }

    root.on("change", ".cbe-room-checkbox", function () {
      syncSelectionState();
    });

    orderList.on("click", ".cbe-room-order-btn", function (event) {
      event.preventDefault();

      var button = $(this);
      var action = String(button.attr("data-room-order-action") || "");
      var row = button.closest(".cbe-selected-room-order-item");
      var id = parseInt(String(row.attr("data-cabin-id") || "0"), 10);
      if (id <= 0) {
        return;
      }

      moveInOrder(id, action === "up" ? "up" : "down");
    });

    orderList.on(
      "dragstart",
      ".cbe-selected-room-order-item",
      function (event) {
        var row = $(this);
        draggedId = parseInt(String(row.attr("data-cabin-id") || "0"), 10);
        row.addClass("is-dragging");

        if (event.originalEvent && event.originalEvent.dataTransfer) {
          event.originalEvent.dataTransfer.effectAllowed = "move";
        }
      },
    );

    orderList.on("dragend", ".cbe-selected-room-order-item", function () {
      $(this).removeClass("is-dragging");
    });

    orderList.on("dragover", ".cbe-selected-room-order-item", function (event) {
      event.preventDefault();
      if (event.originalEvent && event.originalEvent.dataTransfer) {
        event.originalEvent.dataTransfer.dropEffect = "move";
      }
    });

    orderList.on("drop", ".cbe-selected-room-order-item", function (event) {
      event.preventDefault();
      var target = $(this);
      var targetId = parseInt(String(target.attr("data-cabin-id") || "0"), 10);
      moveBefore(draggedId, targetId);
    });

    syncSelectionState();
  }

  function initFacilitiesBuilder() {
    var builder = $("#cbe-facilities-builder");
    if (!builder.length) {
      return;
    }

    var hiddenInput = $("#cbe_facilities_items");
    var form = builder.closest("form");
    var iconOptions = {};

    try {
      iconOptions = JSON.parse(builder.attr("data-icon-options") || "{}");
    } catch (err) {
      iconOptions = {};
    }

    function syncFacilities() {
      var items = [];

      builder.find(".cbe-facility-checkbox:checked").each(function () {
        var checkbox = $(this);
        var iconKey = String(checkbox.val() || "");
        var meta = iconOptions[iconKey] || {};

        if (!iconKey || !meta.label) {
          return;
        }

        items.push({
          icon_key: iconKey,
          label: meta.label,
        });
      });

      hiddenInput.val(JSON.stringify(items));
    }

    builder.on("change", ".cbe-facility-checkbox", function () {
      syncFacilities();
    });

    form.on("submit", function () {
      syncFacilities();
    });

    syncFacilities();
  }

  function initFacilityCatalogBuilder() {
    var builder = $("#cbe-facility-catalog-builder");
    if (!builder.length) {
      return;
    }

    var list = $("#cbe-facility-catalog-list");
    var addButton = $("#cbe-add-facility-catalog-item");
    var tableSearchInput = $("#cbe-facility-table-search");
    var localIconsBase = String(builder.attr("data-local-icons-base") || "");
    var iconPool = [];
    var iconifyQueryCache = {};
    var iconOptionLabelMap = {};
    var iconifyDebounceTimer = null;
    var iconifyAbortController = null;

    try {
      iconPool = JSON.parse(builder.attr("data-icon-pool") || "[]");
    } catch (err) {
      iconPool = [];
    }

    function escapeAttribute(value) {
      return String(value || "")
        .replace(/&/g, "&amp;")
        .replace(/"/g, "&quot;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;");
    }

    function isLocalIcon(iconValue) {
      return String(iconValue || "").indexOf("local:") === 0;
    }

    function getLocalIconUrl(iconValue) {
      var filename = String(iconValue || "").slice(6);
      filename = filename.replace(/[^a-zA-Z0-9_\-\.]/g, "");
      if (!filename || !localIconsBase) {
        return "";
      }

      return localIconsBase + filename;
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
      var value = String(iconValue || "").trim();
      if (!value) {
        return false;
      }

      if (
        isMaterialSymbolIcon(value) ||
        isFontAwesomeIcon(value) ||
        isBootstrapIcon(value)
      ) {
        return false;
      }

      return /^[a-z0-9]+:[a-z0-9\-_]+$/i.test(value);
    }

    function getIconifySvgUrl(iconValue) {
      return (
        "https://api.iconify.design/" +
        encodeURIComponent(String(iconValue || "")) +
        ".svg"
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

    function renderIconPreview(iconValue, className) {
      var classes = className || "";

      if (isLocalIcon(iconValue)) {
        var localUrl = getLocalIconUrl(iconValue);
        if (localUrl) {
          return (
            '<img class="' +
            escapeAttribute((classes + " cbe-local-icon").trim()) +
            '" src="' +
            escapeAttribute(localUrl) +
            '" alt="" loading="lazy" decoding="async" />'
          );
        }
      }

      if (isMaterialSymbolIcon(iconValue)) {
        return (
          '<span class="' +
          escapeAttribute(
            (classes + " cbe-material-symbol material-symbols-outlined").trim(),
          ) +
          '">' +
          escapeAttribute(getMaterialSymbolName(iconValue)) +
          "</span>"
        );
      }

      if (isFontAwesomeIcon(iconValue)) {
        var faClass = getFontAwesomeClassName(iconValue);
        if (faClass) {
          return (
            '<i class="' +
            escapeAttribute((classes + " " + faClass).trim()) +
            '" aria-hidden="true"></i>'
          );
        }
      }

      if (isBootstrapIcon(iconValue)) {
        var biClass = getBootstrapIconClassName(iconValue);
        if (biClass) {
          return (
            '<i class="' +
            escapeAttribute((classes + " bi bi-" + biClass).trim()) +
            '" aria-hidden="true"></i>'
          );
        }
      }

      if (isIconifyIcon(iconValue)) {
        return (
          '<img class="' +
          escapeAttribute((classes + " cbe-iconify-icon").trim()) +
          '" src="' +
          escapeAttribute(getIconifySvgUrl(iconValue)) +
          '" alt="" loading="lazy" decoding="async" />'
        );
      }

      return (
        '<span class="' +
        escapeAttribute(classes) +
        '">' +
        escapeAttribute(iconValue || "•") +
        "</span>"
      );
    }

    function getIconOptionLabel(option) {
      var label = String((option && option.label) || "").trim();
      var value = String((option && option.value) || "").trim();
      if (label) {
        return label;
      }

      return value;
    }

    function getIconOptionByValue(iconValue) {
      var value = String(iconValue || "").trim();
      if (value && iconOptionLabelMap[value]) {
        return {
          value: value,
          label: iconOptionLabelMap[value],
        };
      }

      var found = null;

      iconPool.some(function (option) {
        var optionValue = String((option && option.value) || "").trim();
        if (optionValue && optionValue === value) {
          found = option;
          return true;
        }
        return false;
      });

      return found;
    }

    function getAutocompleteDisplayValue(iconValue) {
      var option = getIconOptionByValue(iconValue);
      if (option) {
        return getIconOptionLabel(option);
      }

      return String(iconValue || "");
    }

    function findIconMatches(query) {
      var q = String(query || "")
        .trim()
        .toLowerCase();

      return iconPool
        .filter(function (option) {
          var optionValue = String((option && option.value) || "")
            .trim()
            .toLowerCase();
          var optionLabel = getIconOptionLabel(option).toLowerCase();

          if (!q) {
            return true;
          }

          return optionValue.indexOf(q) !== -1 || optionLabel.indexOf(q) !== -1;
        })
        .slice(0, 30);
    }

    function humanizeIconifyName(name) {
      return String(name || "")
        .replace(/[-_]+/g, " ")
        .replace(/\s+/g, " ")
        .trim()
        .replace(/\b\w/g, function (ch) {
          return ch.toUpperCase();
        });
    }

    function normalizeIconOptions(options) {
      var normalized = [];

      options.forEach(function (option) {
        var value = String((option && option.value) || "").trim();
        var label = String((option && option.label) || "").trim();
        if (!value) {
          return;
        }

        if (!label) {
          label = value;
        }

        iconOptionLabelMap[value] = label;
        normalized.push({
          value: value,
          label: label,
        });
      });

      return normalized;
    }

    function fetchIconifyMatches(query, callback) {
      var q = String(query || "")
        .trim()
        .toLowerCase();

      if (q.length < 2) {
        callback([]);
        return;
      }

      if (iconifyQueryCache[q]) {
        callback(iconifyQueryCache[q]);
        return;
      }

      if (iconifyDebounceTimer) {
        clearTimeout(iconifyDebounceTimer);
      }

      iconifyDebounceTimer = setTimeout(function () {
        if (iconifyAbortController) {
          iconifyAbortController.abort();
        }

        if (typeof AbortController !== "undefined") {
          iconifyAbortController = new AbortController();
        } else {
          iconifyAbortController = null;
        }

        var url =
          "https://api.iconify.design/search?query=" +
          encodeURIComponent(q) +
          "&limit=40";

        fetch(url, {
          signal: iconifyAbortController
            ? iconifyAbortController.signal
            : undefined,
        })
          .then(function (response) {
            if (!response.ok) {
              throw new Error("iconify-search-failed");
            }
            return response.json();
          })
          .then(function (payload) {
            var icons = Array.isArray(payload && payload.icons)
              ? payload.icons
              : [];

            var mapped = icons.map(function (name) {
              var value = String(name || "").trim();
              var parts = value.split(":");
              var iconName = parts.length > 1 ? parts[1] : value;
              return {
                value: value,
                label: "Iconify: " + humanizeIconifyName(iconName),
              };
            });

            var normalized = normalizeIconOptions(mapped);
            iconifyQueryCache[q] = normalized;
            callback(normalized);
          })
          .catch(function () {
            callback([]);
          });
      }, 180);
    }

    function mergeIconMatches(localMatches, remoteMatches) {
      var merged = [];
      var seen = {};

      localMatches.concat(remoteMatches).forEach(function (item) {
        var value = String((item && item.value) || "").trim();
        if (!value || seen[value]) {
          return;
        }

        seen[value] = true;
        merged.push(item);
      });

      return merged.slice(0, 40);
    }

    function buildAutocompleteHtml(matches) {
      var html = "";

      matches.forEach(function (option) {
        var optionValue = String((option && option.value) || "").trim();
        if (!optionValue) {
          return;
        }

        html +=
          '<button type="button" class="cbe-facility-icon-result" data-icon-value="' +
          escapeAttribute(optionValue) +
          '">' +
          renderIconPreview(optionValue, "cbe-icon-result-preview") +
          '<span class="cbe-icon-result-text">' +
          escapeAttribute(getIconOptionLabel(option)) +
          "</span>" +
          "</button>";
      });

      if (!html) {
        return (
          '<div class="cbe-facility-icon-empty">' +
          escapeAttribute("No icon found") +
          "</div>"
        );
      }

      return html;
    }

    function renderAutocompleteResults(row, query) {
      var results = row.find(".cbe-facility-icon-autocomplete-results");
      var q = String(query || "");
      var localMatches = normalizeIconOptions(findIconMatches(q));

      row.attr("data-cbe-icon-query", q.toLowerCase());
      results.html(buildAutocompleteHtml(localMatches)).show();

      fetchIconifyMatches(q, function (remoteMatches) {
        if (row.attr("data-cbe-icon-query") !== q.toLowerCase()) {
          return;
        }

        if (!row.hasClass("is-editing")) {
          return;
        }

        var merged = mergeIconMatches(localMatches, remoteMatches || []);
        results.html(buildAutocompleteHtml(merged)).show();
      });
    }

    function hideAutocompleteResults(row) {
      row.find(".cbe-facility-icon-autocomplete-results").hide().empty();
    }

    function selectRowIcon(row, iconValue) {
      var value = String(iconValue || "ms:room_service").trim();
      var iconInput = row.find(".cbe-facility-catalog-icon-input");
      var searchInput = row.find(".cbe-facility-icon-autocomplete-input");

      iconInput.val(value);
      searchInput.val(getAutocompleteDisplayValue(value));
      refreshRowPreview(row);
      hideAutocompleteResults(row);
    }

    function resolveTypedIconValue(rawText, fallbackValue) {
      var text = String(rawText || "").trim();
      var fallback = String(fallbackValue || "ms:room_service").trim();

      if (!text) {
        return fallback;
      }

      var lowerText = text.toLowerCase();
      var exactMatch = null;

      iconPool.some(function (option) {
        var optionValue = String((option && option.value) || "").trim();
        var optionLabel = getIconOptionLabel(option).trim();
        if (!optionValue) {
          return false;
        }

        if (
          optionValue.toLowerCase() === lowerText ||
          optionLabel.toLowerCase() === lowerText
        ) {
          exactMatch = optionValue;
          return true;
        }

        return false;
      });

      if (exactMatch) {
        return exactMatch;
      }

      if (/^(ms:|fa:|bi:)/i.test(text)) {
        return text;
      }

      if (/^local:[a-z0-9_.-]+$/i.test(text)) {
        return text;
      }

      if (/^[a-z0-9]+:[a-z0-9\-_]+$/i.test(text)) {
        return text;
      }

      return fallback;
    }

    function slugify(value) {
      return String(value || "")
        .toLowerCase()
        .trim()
        .replace(/[^a-z0-9\s-]/g, "")
        .replace(/\s+/g, "-")
        .replace(/-+/g, "-");
    }

    function createRow(item) {
      var key = (item && item.key) || "";
      var icon = (item && item.icon) || "ms:room_service";
      var label = (item && item.label) || "";

      return $(
        '<tr class="cbe-facility-catalog-row">' +
          '<td class="cbe-facility-row-no"></td>' +
          "<td>" +
          renderIconPreview(icon, "cbe-facility-catalog-icon-preview") +
          '<input type="hidden" name="cbe_facility_catalog_icons[]" class="cbe-facility-catalog-icon-input" value="' +
          escapeAttribute(icon) +
          '" />' +
          '<div class="cbe-facility-icon-autocomplete">' +
          '<input type="search" class="cbe-facility-icon-autocomplete-input" placeholder="Search icon (wifi, pool, shower)..." autocomplete="off" />' +
          '<div class="cbe-facility-icon-autocomplete-results"></div>' +
          "</div></td>" +
          "<td>" +
          '<input type="hidden" name="cbe_facility_catalog_keys[]" class="cbe-facility-catalog-key-input" value="' +
          escapeAttribute(key) +
          '" />' +
          '<input type="text" name="cbe_facility_catalog_labels[]" class="cbe-facility-catalog-name-input" placeholder="Facility name" value="' +
          escapeAttribute(label) +
          '" />' +
          "</td>" +
          '<td class="cbe-facility-actions-cell">' +
          '<button type="button" class="button cbe-edit-facility-catalog-item" aria-label="Edit facility">✎</button> ' +
          '<button type="button" class="button cbe-remove-facility-catalog-item" aria-label="Delete facility">🗑</button>' +
          "</td>" +
          "</tr>",
      );
    }

    function updateRowNumbers() {
      list.find(".cbe-facility-catalog-row").each(function (idx) {
        $(this)
          .find(".cbe-facility-row-no")
          .text(String(idx + 1));
      });
    }

    function refreshRowPreview(row) {
      var iconValue = String(
        row.find(".cbe-facility-catalog-icon-input").val() || "",
      );
      row
        .find(".cbe-facility-catalog-icon-preview")
        .replaceWith(
          renderIconPreview(iconValue, "cbe-facility-catalog-icon-preview"),
        );
    }

    function setRowEditState(row, isEditing) {
      var nameInput = row.find(".cbe-facility-catalog-name-input");
      var iconAutocompleteInput = row.find(
        ".cbe-facility-icon-autocomplete-input",
      );
      var editButton = row.find(".cbe-edit-facility-catalog-item");

      if (isEditing) {
        row.addClass("is-editing");
        nameInput.prop("readonly", false);
        iconAutocompleteInput.prop("readonly", false);
        editButton.text("✔");
      } else {
        row.removeClass("is-editing");
        nameInput.prop("readonly", true);
        iconAutocompleteInput.prop("readonly", true);
        hideAutocompleteResults(row);
        editButton.text("✎");
      }
    }

    function initExistingRows() {
      list.find(".cbe-facility-catalog-row").each(function () {
        var row = $(this);
        var iconValue = String(
          row.find(".cbe-facility-catalog-icon-input").val() ||
            "ms:room_service",
        );
        row
          .find(".cbe-facility-icon-autocomplete-input")
          .val(getAutocompleteDisplayValue(iconValue));
        setRowEditState(row, false);
      });
    }

    function syncRowKeyWithLabel(row) {
      var keyInput = row.find(".cbe-facility-catalog-key-input");
      var nameInput = row.find(".cbe-facility-catalog-name-input");
      if (!keyInput.val()) {
        keyInput.val(slugify(nameInput.val()));
      }
    }

    function filterRows(term) {
      var q = String(term || "")
        .trim()
        .toLowerCase();

      list.find(".cbe-facility-catalog-row").each(function () {
        var row = $(this);
        var nameText = String(
          row.find(".cbe-facility-catalog-name-input").val() || "",
        ).toLowerCase();
        var keyText = String(
          row.find(".cbe-facility-catalog-key-input").val() || "",
        ).toLowerCase();
        var visible =
          !q || nameText.indexOf(q) !== -1 || keyText.indexOf(q) !== -1;
        row.toggle(visible);
      });
    }

    addButton.on("click", function (e) {
      e.preventDefault();
      var row = createRow();
      list.append(row);
      selectRowIcon(row, "ms:room_service");
      setRowEditState(row, true);
      updateRowNumbers();
    });

    list.on("change input", ".cbe-facility-catalog-icon-input", function () {
      var row = $(this).closest(".cbe-facility-catalog-row");
      refreshRowPreview(row);
    });

    list.on(
      "focus input",
      ".cbe-facility-icon-autocomplete-input",
      function () {
        var row = $(this).closest(".cbe-facility-catalog-row");
        if (!row.hasClass("is-editing")) {
          return;
        }

        renderAutocompleteResults(row, $(this).val());
      },
    );

    list.on("keydown", ".cbe-facility-icon-autocomplete-input", function (e) {
      if (e.key !== "Enter") {
        return;
      }

      var row = $(this).closest(".cbe-facility-catalog-row");
      var firstResult = row.find(".cbe-facility-icon-result").first();
      if (firstResult.length) {
        e.preventDefault();
        selectRowIcon(row, firstResult.attr("data-icon-value") || "");
      }
    });

    list.on("blur", ".cbe-facility-icon-autocomplete-input", function () {
      var input = $(this);
      var row = input.closest(".cbe-facility-catalog-row");

      if (row.data("cbeSkipBlurResolve") === true) {
        row.removeData("cbeSkipBlurResolve");
        return;
      }

      var currentValue = String(
        row.find(".cbe-facility-catalog-icon-input").val() || "ms:room_service",
      );
      var resolvedValue = resolveTypedIconValue(input.val(), currentValue);

      selectRowIcon(row, resolvedValue);

      setTimeout(function () {
        hideAutocompleteResults(row);
      }, 120);
    });

    list.on("mousedown", ".cbe-facility-icon-result", function (e) {
      e.preventDefault();
      var row = $(this).closest(".cbe-facility-catalog-row");
      row.data("cbeSkipBlurResolve", true);
      selectRowIcon(row, $(this).attr("data-icon-value") || "");
    });

    list.on("click", ".cbe-facility-icon-result", function (e) {
      e.preventDefault();
      var row = $(this).closest(".cbe-facility-catalog-row");
      selectRowIcon(row, $(this).attr("data-icon-value") || "");
    });

    list.on("input", ".cbe-facility-catalog-name-input", function () {
      syncRowKeyWithLabel($(this).closest(".cbe-facility-catalog-row"));
    });

    list.on("click", ".cbe-edit-facility-catalog-item", function (e) {
      e.preventDefault();
      var row = $(this).closest(".cbe-facility-catalog-row");
      var editing = !row.hasClass("is-editing");
      setRowEditState(row, editing);
      if (!editing) {
        syncRowKeyWithLabel(row);
      }
    });

    list.on("click", ".cbe-remove-facility-catalog-item", function (e) {
      e.preventDefault();
      $(this).closest(".cbe-facility-catalog-row").remove();
      updateRowNumbers();
    });

    tableSearchInput.on("input", function () {
      filterRows($(this).val());
    });

    list.find(".cbe-facility-catalog-row").each(function () {
      syncRowKeyWithLabel($(this));
    });

    iconPool.forEach(function (option) {
      var value = String((option && option.value) || "").trim();
      if (!value) {
        return;
      }

      iconOptionLabelMap[value] = getIconOptionLabel(option);
    });

    initExistingRows();
    updateRowNumbers();
    filterRows("");
  }
})(jQuery);
