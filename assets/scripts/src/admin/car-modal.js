(function (document) {
  "use strict";

  const dialog = document.getElementById("my-car-car-modal");

  function fillModal(data) {
    const title = document.getElementById("my-car-car-modal-title");
    const image = document.getElementById("my-car-car-modal-image");
    const fields = document.getElementById("my-car-car-modal-fields");
    title.textContent = data.title || "";
    if (data.image) {
      image.src = data.image;
      image.hidden = false;
    } else {
      image.hidden = true;
    }
    while (fields.firstChild) {
      fields.removeChild(fields.firstChild);
    }
    data.passport.forEach(function (row) {
      const tr = document.createElement("tr");
      const th = document.createElement("th");
      const td = document.createElement("td");
      th.scope = "row";
      th.textContent = row.label;
      td.textContent = row.value;
      tr.appendChild(th);
      tr.appendChild(td);
      fields.appendChild(tr);
    });
    Object.keys(data.taxonomies).forEach(function (key) {
      const group = data.taxonomies[key];
      if (!group.terms || !group.terms.length) {
        return;
      }
      const tr = document.createElement("tr");
      const th = document.createElement("th");
      const td = document.createElement("td");
      const links = [];
      th.scope = "row";
      th.textContent = group.label;
      group.terms.forEach(function (term) {
        const a = document.createElement("a");
        a.href = term.link;
        a.textContent = term.label;
        links.push(a);
      });
      links.forEach(function (a, index) {
        if (index) {
          td.appendChild(document.createTextNode(", "));
        }
        td.appendChild(a);
      });
      tr.appendChild(th);
      tr.appendChild(td);
      fields.appendChild(tr);
    });
  }

  function renderMileageLog(entries) {
    const tbody = document.getElementById("my-car-mileage-log");
    const table = document.getElementById("my-car-mileage-table");
    const config = window.iworks_my_car && window.iworks_my_car.show_car;
    while (tbody.firstChild) {
      tbody.removeChild(tbody.firstChild);
    }
    if (!entries.length) {
      table.hidden = true;
      return;
    }
    table.hidden = false;
    const unit = (config && config.unit) || "km";
    entries.forEach(function (entry) {
      const tr = document.createElement("tr");
      const tdDate = document.createElement("td");
      const tdMileage = document.createElement("td");
      tdDate.textContent = entry.date;
      tdMileage.textContent = entry.mileage + " " + unit;
      tr.appendChild(tdDate);
      tr.appendChild(tdMileage);
      tbody.appendChild(tr);
    });
  }

  function renderServiceRecords(records) {
    const tbody = document.getElementById("my-car-service-log");
    const table = document.getElementById("my-car-service-table");
    const empty = document.getElementById("my-car-service-empty");
    const config = window.iworks_my_car && window.iworks_my_car.show_car;
    const unit = (config && config.unit) || "km";
    while (tbody.firstChild) {
      tbody.removeChild(tbody.firstChild);
    }
    if (!records || !records.length) {
      table.hidden = true;
      empty.hidden = false;
      return;
    }
    table.hidden = false;
    empty.hidden = true;
    records.forEach(function (record) {
      const tr = document.createElement("tr");
      const cells = [
        record.date,
        record.mileage ? record.mileage + " " + unit : "—",
        (record.types || []).join(", ") || "—",
      ];
      cells.forEach(function (value) {
        const td = document.createElement("td");
        td.textContent = value;
        tr.appendChild(td);
      });
      tbody.appendChild(tr);
    });
  }

  const tabCache = new Map();

  function loadTab(carId, tab, callback) {
    const config = window.iworks_my_car && window.iworks_my_car.show_car;
    if (!config) {
      return;
    }
    const body = new FormData();
    body.append("action", "my-car-get-car-tab");
    body.append("nonce", config.nonce);
    body.append("car_id", carId);
    body.append("tab", tab);
    fetch(config.ajax_url, {
      method: "POST",
      body: body,
      credentials: "same-origin",
    })
      .then(function (response) {
        return response.json();
      })
      .then(function (result) {
        if (!result || !result.success) {
          window.console && console.error("my-car tab:", result);
          return;
        }
        tabCache.set(carId + ":" + tab, result.data);
        callback(result.data);
      })
      .catch(function (error) {
        window.console && console.error("my-car tab:", error);
      });
  }

  function ensureTabLoaded(carId, tab) {
    if ("data" === tab || !carId) {
      return;
    }
    const key = carId + ":" + tab;
    if (tabCache.has(key)) {
      return;
    }
    loadTab(carId, tab, function (data) {
      if ("mileage" === tab) {
        renderMileageLog(data.mileage_log || []);
      } else if ("service" === tab) {
        renderServiceRecords(data.service_records || []);
      }
    });
  }

  function switchTab(name) {
    ensureTabLoaded(dialog.getAttribute("data-car-id"), name);
    dialog.querySelectorAll(".my-car-car-modal-tab").forEach(function (tab) {
      const active = tab.getAttribute("data-tab") === name;
      tab.classList.toggle("is-active", active);
      tab.setAttribute("aria-selected", active ? "true" : "false");
    });
    dialog.querySelectorAll("[data-panel]").forEach(function (panel) {
      panel.hidden = panel.getAttribute("data-panel") !== name;
    });
  }

  function openMileageForm() {
    switchTab("mileage");
    const form = document.getElementById("my-car-mileage-form");
    form.hidden = false;
    document.getElementById("my-car-mileage-date").value = new Date()
      .toISOString()
      .slice(0, 10);
    document.getElementById("my-car-mileage-value").value = "";
    document.getElementById("my-car-mileage-value").focus();
  }

  function closeMileageForm() {
    document.getElementById("my-car-mileage-form").hidden = true;
  }

  function submitMileage(carId) {
    const config = window.iworks_my_car && window.iworks_my_car.show_car;
    const date = document.getElementById("my-car-mileage-date").value;
    const mileage = document.getElementById("my-car-mileage-value").value;
    const body = new FormData();
    body.append("action", "my-car-add-mileage");
    body.append("nonce", config.nonce);
    body.append("car_id", carId);
    body.append("date", date);
    body.append("mileage", mileage);
    fetch(config.ajax_url, {
      method: "POST",
      body: body,
      credentials: "same-origin",
    })
      .then(function (response) {
        return response.json();
      })
      .then(function (result) {
        if (!result || !result.success) {
          window.alert((config.l10n && config.l10n.error) || "Error");
          return;
        }
        renderMileageLog(result.data.mileage_log);
        tabCache.set(carId + ":mileage", {
          mileage_log: result.data.mileage_log,
        });
        closeMileageForm();
      })
      .catch(function (error) {
        window.console && console.error("my-car mileage:", error);
      });
  }

  function openModal(carId) {
    const config = window.iworks_my_car && window.iworks_my_car.show_car;
    if (!config) {
      return;
    }
    dialog.setAttribute("data-car-id", carId);
    switchTab("data");
    document.getElementById("my-car-mileage-form").hidden = true;
    document.getElementById("my-car-mileage-table").hidden = false;
    const body = new FormData();
    body.append("action", "my-car-get-car");
    body.append("nonce", config.nonce);
    body.append("car_id", carId);
    fetch(config.ajax_url, {
      method: "POST",
      body: body,
      credentials: "same-origin",
    })
      .then(function (response) {
        return response.json();
      })
      .then(function (result) {
        if (!result || !result.success) {
          window.console && console.error("my-car modal:", result);
          window.alert((config.l10n && config.l10n.error) || "Error");
          return;
        }
        fillModal(result.data);
        for (const key of Array.from(tabCache.keys())) {
          if (0 === key.indexOf(carId + ":")) {
            tabCache.delete(key);
          }
        }
        dialog.showModal();
      })
      .catch(function (error) {
        window.console && console.error("my-car modal:", error);
        window.alert((config.l10n && config.l10n.error) || "Error");
      });
  }

  document.addEventListener("click", function (event) {
    const button = event.target.closest(".my-car-show-car");
    if (button) {
      event.preventDefault();
      openModal(button.getAttribute("data-car-id"));
      return;
    }
    if (event.target.closest(".my-car-car-modal-tab")) {
      event.preventDefault();
      switchTab(
        event.target.closest(".my-car-car-modal-tab").getAttribute("data-tab"),
      );
      return;
    }
    if (event.target.closest(".my-car-mileage-toggle")) {
      event.preventDefault();
      openMileageForm();
      return;
    }
    if (event.target.closest(".my-car-mileage-cancel")) {
      event.preventDefault();
      closeMileageForm();
      return;
    }
    if (event.target.closest(".my-car-car-modal-close")) {
      dialog.close();
    }
  });
  document.addEventListener("submit", function (event) {
    const form = event.target.closest("#my-car-mileage-form");
    if (!form || !dialog.open) {
      return;
    }
    event.preventDefault();
    submitMileage(dialog.getAttribute("data-car-id"));
  });
})(document);
