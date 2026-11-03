/**
 * Farmer-facing prediction interaction.
 * The map chooses a district; exact clicked/GPS coordinates are not submitted.
 */

document.addEventListener("DOMContentLoaded", () => {
    const reference = window.NCYPS_REFERENCE_DATA || {};
    const districtsByProvince = reference.districtsByProvince || {};
    const districtCentres = reference.districtCentres || {};
    const cropsByDistrict = reference.cropsByDistrict || {};
    const allCrops = reference.crops || [];

    const provinceSelect = document.getElementById("provinceSelect");
    const districtSelect = document.getElementById("districtSelect");
    const cropSelect = document.getElementById("cropSelect");
    const climateScenario = document.getElementById("climateScenario");
    const advancedPanel = document.getElementById("advancedPanel");
    const predictForm = document.getElementById("predictForm");
    const predictButton = document.getElementById("predictButton");
    const profileStatus = document.getElementById("profileStatus");
    const locationMethod = document.getElementById("locationMethod");
    const climateSourcePeriod = document.getElementById("climateSourcePeriod");
    const climateRecordCount = document.getElementById("climateRecordCount");
    const landArea = document.getElementById("landArea");
    const landUnit = document.getElementById("landUnit");
    const convertedLandArea = document.getElementById("convertedLandArea");
    const useLocationBtn = document.getElementById("useLocationBtn");
    const mapStatus = document.getElementById("mapLocationStatus");
    const technicalInputs = Array.from(document.querySelectorAll(".technical-input"));

    if (!provinceSelect || !districtSelect || !cropSelect || !predictForm) {
        return;
    }

    const FEATURE_RANGES = {
        area_ha: { label: "District cultivated area", unit: "ha", example: "500", min: 1.0, max: 95311.0 },
        rainfall_mm_total: { label: "Total rainfall", unit: "mm", example: "1200", min: 354.55, max: 2888.66 },
        avg_temp_c: { label: "Average temperature", unit: "°C", example: "20", min: -5.54, max: 26.51 },
        max_temp_c: { label: "Mean daily maximum temperature", unit: "°C", example: "26", min: 0.70, max: 32.89 },
        min_temp_c: { label: "Mean daily minimum temperature", unit: "°C", example: "14", min: -11.01, max: 21.48 },
        humidity_pct: { label: "Relative humidity", unit: "%", example: "65", min: 46.37, max: 82.33 },
        spec_humidity_g_kg: { label: "Specific humidity", unit: "g/kg", example: "10", min: 3.93, max: 14.81 },
        pressure_kpa: { label: "Surface pressure", unit: "kPa", example: "85", min: 55.51, max: 99.36 },
        wind_speed_m_s: { label: "Wind speed", unit: "m/s", example: "2.4", min: 1.81, max: 3.28 },
        solar_rad_kwh: { label: "Solar radiation", unit: "kWh/m²/day", example: "5", min: 3.84, max: 6.02 },
        latitude: { label: "District latitude", unit: "°N", example: "27.7", min: 26.58, max: 30.03 },
        longitude: { label: "District longitude", unit: "°E", example: "85.3", min: 80.32, max: 87.92 },
    };

    const LAND_FACTORS = {
        hectare: 1,
        ropani: 0.05087,
        aana: 0.05087 / 16,
        bigha: 0.6773,
        kattha: 0.6773 / 20,
    };

    const MODEL_FIELD_MAP = {
        area_ha: "Area_ha",
        rainfall_mm_total: "Rainfall_mm_total",
        avg_temp_c: "Avg_Temperature_C",
        max_temp_c: "Mean_Daily_Max_Temperature_C",
        min_temp_c: "Mean_Daily_Min_Temperature_C",
        humidity_pct: "Relative_Humidity_pct",
        spec_humidity_g_kg: "Specific_Humidity_g_kg",
        pressure_kpa: "Surface_Pressure_kPa",
        wind_speed_m_s: "Wind_Speed_m_s",
        solar_rad_kwh: "Solar_Radiation_kWh_m2_day",
        latitude: "Latitude",
        longitude: "Longitude",
    };

    let profileReady = technicalInputs.every((input) => input.value.trim() !== "");
    let profileRequest = 0;
    let map = null;
    let boundaryLayer = null;
    let locationMarker = null;
    let boundaryFeatures = [];
    const districtLayers = new Map();

    function removeDescribedById(input, id) {
        const remaining = (input.getAttribute("aria-describedby") || "")
            .split(/\s+/)
            .filter((value) => value && value !== id);
        if (remaining.length) input.setAttribute("aria-describedby", remaining.join(" "));
        else input.removeAttribute("aria-describedby");
    }

    function clearServerWarning(input) {
        const container = input.parentElement;
        const warning = container?.querySelector(".range-warning[data-server-error='true']");
        if (!warning) return;
        removeDescribedById(input, warning.id);
        warning.remove();
        input.classList.remove("is-invalid-input");
        input.removeAttribute("aria-invalid");
    }

    function removeGeneratedWarning(input) {
        const container = input.parentElement;
        const warning = container?.querySelector(".range-warning[data-generated='true']");
        if (warning) {
            removeDescribedById(input, warning.id);
            warning.remove();
        }
    }

    function showGeneratedWarning(input, text) {
        removeGeneratedWarning(input);
        const warning = document.createElement("small");
        warning.id = `client-error-${input.name}`;
        warning.className = "range-warning";
        warning.dataset.generated = "true";
        warning.textContent = text;
        input.parentElement?.appendChild(warning);
        input.setAttribute("aria-invalid", "true");
        const describedBy = new Set((input.getAttribute("aria-describedby") || "").split(/\s+/).filter(Boolean));
        describedBy.add(warning.id);
        input.setAttribute("aria-describedby", Array.from(describedBy).join(" "));
    }

    function formatValidationNumber(value) {
        return Number(value).toLocaleString(undefined, { maximumFractionDigits: 2 });
    }

    function technicalErrorMessage(input, range) {
        const raw = input.value.trim();
        const minimum = formatValidationNumber(range.min);
        const maximum = formatValidationNumber(range.max);
        if (raw === "") {
            return `${range.label} is empty. Enter a number, for example ${range.example} ${range.unit}.`;
        }

        const value = Number(raw);
        if (!Number.isFinite(value)) {
            return `${range.label} “${raw}” is not a number. Enter a number, for example ${range.example} ${range.unit}.`;
        }
        if (value < range.min) {
            return `You entered ${formatValidationNumber(value)} ${range.unit} for ${range.label}. The minimum accepted value is ${minimum} ${range.unit}; enter a value from ${minimum} to ${maximum} ${range.unit}.`;
        }
        if (value > range.max) {
            return `You entered ${formatValidationNumber(value)} ${range.unit} for ${range.label}. The maximum accepted value is ${maximum} ${range.unit}; enter a value from ${minimum} to ${maximum} ${range.unit}.`;
        }
        return "";
    }

    function validateTechnicalField(input, showMessage = true) {
        const range = FEATURE_RANGES[input.name];
        if (!range) return true;
        const message = technicalErrorMessage(input, range);
        const valid = message === "";
        const wasMarkedInvalid = input.classList.contains("is-invalid-input");
        input.classList.toggle("is-invalid-input", !valid && (showMessage || wasMarkedInvalid));
        if (valid) {
            removeGeneratedWarning(input);
            input.removeAttribute("aria-invalid");
        } else if (showMessage) {
            showGeneratedWarning(input, message);
        }
        return valid;
    }

    function removeLandWarning() {
        const warning = document.getElementById("landAreaClientError");
        if (warning) warning.remove();
        removeDescribedById(landArea, "landAreaClientError");
    }

    function clearLandServerWarning() {
        const warning = document.getElementById("landAreaError");
        if (warning) warning.remove();
        removeDescribedById(landArea, "landAreaError");
    }

    function showLandWarning(message) {
        removeLandWarning();
        const warning = document.createElement("small");
        warning.id = "landAreaClientError";
        warning.className = "range-warning";
        warning.dataset.generated = "true";
        warning.textContent = message;
        landArea.closest(".land-input-group")?.insertAdjacentElement("afterend", warning);
        landArea.setAttribute("aria-invalid", "true");
        const describedBy = new Set((landArea.getAttribute("aria-describedby") || "").split(/\s+/).filter(Boolean));
        describedBy.add(warning.id);
        landArea.setAttribute("aria-describedby", Array.from(describedBy).join(" "));
    }

    function validateLandField(showMessage = true) {
        if (!landArea) return false;
        const raw = landArea.value.trim();
        const value = Number(raw);
        const unitLabel = landUnit?.options[landUnit.selectedIndex]?.text?.toLowerCase() || "selected unit";
        let message = "";
        if (raw === "") message = "Land size is empty. Enter the amount of land you plan to cultivate.";
        else if (!Number.isFinite(value)) message = `Land size “${raw}” is not a number. Enter a number such as 5 or 2.5.`;
        else if (value <= 0) message = `You entered ${formatValidationNumber(value)}. Land size must be greater than 0.`;
        else if (value > 100000) message = `You entered ${formatValidationNumber(value)} ${unitLabel}. Enter 100,000 or less for this prototype.`;
        else if (!LAND_FACTORS[landUnit?.value]) message = "Choose a supported land unit.";

        const valid = message === "";
        const wasMarkedInvalid = landArea.classList.contains("is-invalid-input");
        landArea.classList.toggle("is-invalid-input", !valid && (showMessage || wasMarkedInvalid));
        if (valid) {
            removeLandWarning();
            landArea.removeAttribute("aria-invalid");
        } else if (showMessage) {
            showLandWarning(message);
        }
        return valid;
    }

    function updateLandConversion(showMessage = false) {
        const value = Number(landArea?.value);
        const factor = LAND_FACTORS[landUnit?.value];
        if (!validateLandField(showMessage) || !convertedLandArea || !factor) {
            if (convertedLandArea) convertedLandArea.textContent = "";
            updateSubmitState();
            return;
        }
        const hectares = value * factor;
        convertedLandArea.textContent = `Equivalent to ${hectares.toLocaleString(undefined, { maximumFractionDigits: 4 })} hectares. This land size is used only for the production estimate.`;
        landArea.classList.remove("is-invalid-input");
        updateSubmitState();
    }

    function updateDistrictOptions(preferredDistrict = "") {
        const province = provinceSelect.value;
        const existing = preferredDistrict || districtSelect.value || districtSelect.dataset.selected || "";
        districtSelect.innerHTML = '<option value="">Choose a district</option>';
        (districtsByProvince[province] || []).forEach((district) => {
            const option = document.createElement("option");
            option.value = district;
            option.textContent = district;
            option.selected = district === existing;
            districtSelect.appendChild(option);
        });
        districtSelect.removeAttribute("data-selected");
    }

    function updateCropOptions(preferredCrop = "") {
        const district = districtSelect.value;
        const allowed = new Set(cropsByDistrict[district] || []);
        const existing = preferredCrop || cropSelect.value || cropSelect.dataset.selected || "";
        cropSelect.innerHTML = '<option value="">Choose a crop</option>';
        allCrops.forEach((crop) => {
            if (!allowed.has(crop.name)) return;
            const option = document.createElement("option");
            option.value = crop.name;
            option.textContent = crop.displayName;
            option.selected = crop.name === existing;
            cropSelect.appendChild(option);
        });
        cropSelect.removeAttribute("data-selected");
    }

    function setTechnicalEditability() {
        const custom = climateScenario.value === "custom";
        technicalInputs.forEach((input) => {
            input.readOnly = !custom;
            input.setAttribute("aria-readonly", custom ? "false" : "true");
        });
        if (custom) advancedPanel.open = true;
    }

    function updateSubmitState() {
        const landReady = validateLandField(false);
        const basicReady = Boolean(
            provinceSelect.value
            && districtSelect.value
            && cropSelect.value
            && landReady
        );
        const technicalReady = technicalInputs.reduce(
            (allValid, input) => validateTechnicalField(input, false) && allValid,
            true
        );
        predictButton.disabled = !(basicReady && profileReady && technicalReady);
    }

    function setProfileStatus(message, state = "neutral") {
        profileStatus.textContent = message;
        profileStatus.dataset.state = state;
    }

    function applyProfile(data) {
        Object.entries(MODEL_FIELD_MAP).forEach(([formName, apiName]) => {
            const input = predictForm.elements.namedItem(formName);
            if (input && data.averages[apiName] !== undefined) {
                input.value = data.averages[apiName];
                validateTechnicalField(input, false);
            }
        });
        climateSourcePeriod.value = data.source_period || "";
        climateRecordCount.value = String(data.record_count || 0);
        profileReady = true;

        if (climateScenario.value === "custom") {
            setProfileStatus(`Starting values loaded from ${data.record_count} historical record${data.record_count === 1 ? "" : "s"}. You may change them under Advanced options.`, "ready");
        } else if (data.scenario === "recent") {
            setProfileStatus(`Using the most recent completed record: ${data.source_period}.`, "ready");
        } else {
            setProfileStatus(`Using the average of ${data.record_count} completed historical record${data.record_count === 1 ? "" : "s"}.`, "ready");
        }
        updateSubmitState();
    }

    async function loadHistoricalProfile(forceTypicalForCustom = false) {
        const district = districtSelect.value;
        const crop = cropSelect.value;
        if (!district || !crop) {
            profileReady = false;
            setProfileStatus("Choose a district and crop to load the model information.");
            updateSubmitState();
            return;
        }

        const requestId = ++profileRequest;
        profileReady = false;
        predictButton.disabled = true;
        setProfileStatus("Loading the district’s historical profile…", "loading");
        const requestedScenario = climateScenario.value === "recent" && !forceTypicalForCustom ? "recent" : "typical";

        try {
            const response = await fetch(`proxy_averages.php?district=${encodeURIComponent(district)}&crop=${encodeURIComponent(crop)}&scenario=${encodeURIComponent(requestedScenario)}`, {
                headers: { Accept: "application/json" },
            });
            const data = await response.json();
            if (requestId !== profileRequest) return;
            if (!response.ok || !data.ok || !data.averages) {
                throw new Error(data.error || "Historical information is unavailable for this selection.");
            }
            applyProfile(data);
        } catch (error) {
            if (requestId !== profileRequest) return;
            profileReady = false;
            setProfileStatus(error.message || "Historical information could not be loaded.", "error");
            updateSubmitState();
        }
    }

    function setMarker(latlng, zoom = false) {
        if (!map || !window.L) return;
        if (!locationMarker) {
            locationMarker = L.marker(latlng).addTo(map);
        } else {
            locationMarker.setLatLng(latlng);
        }
        if (zoom) map.setView(latlng, Math.max(map.getZoom(), 9));
    }

    function highlightDistrict(name) {
        districtLayers.forEach((layer, districtName) => {
            layer.setStyle({
                color: districtName === name ? "#B85C38" : "#315A47",
                weight: districtName === name ? 3 : 1,
                fillColor: districtName === name ? "#E9B08F" : "#D8E5DB",
                fillOpacity: districtName === name ? 0.5 : 0.22,
            });
        });
        const selectedLayer = districtLayers.get(name);
        if (selectedLayer) selectedLayer.bringToFront();
    }

    function chooseDistrict(province, district, latlng, method) {
        if (!districtsByProvince[province]?.includes(district)) {
            if (mapStatus) mapStatus.textContent = "This map area does not match a district available in the project.";
            return;
        }

        provinceSelect.value = province;
        updateDistrictOptions(district);
        districtSelect.value = district;
        updateCropOptions(cropSelect.value);
        locationMethod.value = method;
        highlightDistrict(district);

        const centre = districtCentres[district];
        const markerPoint = latlng || (centre ? [centre.latitude, centre.longitude] : null);
        if (markerPoint) setMarker(markerPoint, method !== "manual");

        if (mapStatus) {
            mapStatus.innerHTML = `<strong>${district}</strong>, ${province}<br><span>The model will use district-level coordinates and climate information.</span>`;
        }

        loadHistoricalProfile(climateScenario.value === "custom");
    }

    function pointInRing(point, ring) {
        const [x, y] = point;
        let inside = false;
        for (let i = 0, j = ring.length - 1; i < ring.length; j = i++) {
            const [xi, yi] = ring[i];
            const [xj, yj] = ring[j];
            const crosses = ((yi > y) !== (yj > y))
                && (x < ((xj - xi) * (y - yi)) / ((yj - yi) || Number.EPSILON) + xi);
            if (crosses) inside = !inside;
        }
        return inside;
    }

    function pointInPolygon(point, rings) {
        if (!rings.length || !pointInRing(point, rings[0])) return false;
        for (let i = 1; i < rings.length; i += 1) {
            if (pointInRing(point, rings[i])) return false;
        }
        return true;
    }

    function featureContains(feature, latlng) {
        const point = [latlng.lng, latlng.lat];
        const geometry = feature.geometry;
        if (geometry.type === "Polygon") return pointInPolygon(point, geometry.coordinates);
        if (geometry.type === "MultiPolygon") {
            return geometry.coordinates.some((polygon) => pointInPolygon(point, polygon));
        }
        return false;
    }

    function findDistrictAt(latlng) {
        return boundaryFeatures.find((feature) => featureContains(feature, latlng)) || null;
    }

    async function initialiseMap() {
        const mapElement = document.getElementById("locationMap");
        if (!mapElement || typeof window.L === "undefined") {
            if (mapStatus) mapStatus.textContent = "The interactive map could not load. Use the province and district lists instead.";
            return;
        }

        map = L.map(mapElement, { scrollWheelZoom: false }).setView([28.25, 84.1], 7);
        L.tileLayer("https://tile.openstreetmap.org/{z}/{x}/{y}.png", {
            maxZoom: 19,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        }).addTo(map);

        try {
            const response = await fetch("assets/data/nepal-districts.geojson", { headers: { Accept: "application/geo+json" } });
            if (!response.ok) throw new Error("Boundary file unavailable");
            const collection = await response.json();
            if (!Array.isArray(collection.features) || collection.features.length !== 77) {
                throw new Error("The boundary file does not contain all 77 districts");
            }
            boundaryFeatures = collection.features;
            boundaryLayer = L.geoJSON(collection, {
                style: {
                    color: "#315A47",
                    weight: 1,
                    fillColor: "#D8E5DB",
                    fillOpacity: 0.22,
                },
                onEachFeature(feature, layer) {
                    const district = feature.properties.district;
                    districtLayers.set(district, layer);
                    layer.bindTooltip(district, { sticky: true, direction: "top" });
                    layer.on("click", (event) => {
                        chooseDistrict(feature.properties.province, district, event.latlng, "map");
                    });
                },
            }).addTo(map);
            map.fitBounds(boundaryLayer.getBounds(), { padding: [8, 8] });
            if (useLocationBtn) useLocationBtn.disabled = false;

            if (districtSelect.value) {
                highlightDistrict(districtSelect.value);
                const centre = districtCentres[districtSelect.value];
                if (centre) {
                    setMarker([centre.latitude, centre.longitude]);
                    if (mapStatus) {
                        mapStatus.innerHTML = `<strong>${districtSelect.value}</strong>, ${centre.province}<br><span>Selected from the district list.</span>`;
                    }
                }
            }
        } catch (error) {
            if (mapStatus) mapStatus.textContent = "District boundaries could not be loaded. Use the province and district lists instead.";
        }
    }

    if (useLocationBtn) {
        useLocationBtn.addEventListener("click", () => {
            if (!navigator.geolocation) {
                mapStatus.textContent = "This browser does not provide device location. Click the map or use the district list.";
                return;
            }
            useLocationBtn.disabled = true;
            useLocationBtn.textContent = "Finding location…";
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    useLocationBtn.disabled = false;
                    useLocationBtn.textContent = "Use my location";
                    // Keep device-location matching independent of Leaflet so the
                    // province/district lists still work if map tiles or Leaflet fail.
                    const latlng = {
                        lat: position.coords.latitude,
                        lng: position.coords.longitude,
                    };
                    const feature = findDistrictAt(latlng);
                    if (!feature) {
                        mapStatus.textContent = "The detected position is outside the available Nepal district boundaries.";
                        if (map) map.setView(latlng, 8);
                        return;
                    }
                    chooseDistrict(feature.properties.province, feature.properties.district, latlng, "device");
                },
                (error) => {
                    useLocationBtn.disabled = false;
                    useLocationBtn.textContent = "Use my location";
                    const messages = {
                        1: "Location permission was not granted. Click the map or use the district list.",
                        2: "Your device could not determine its location. Click the map or use the district list.",
                        3: "Location detection timed out. Click the map or use the district list.",
                    };
                    mapStatus.textContent = messages[error.code] || "Device location is unavailable.";
                },
                { enableHighAccuracy: true, timeout: 10000, maximumAge: 300000 }
            );
        });
    }

    provinceSelect.addEventListener("change", () => {
        clearServerWarning(provinceSelect);
        clearServerWarning(districtSelect);
        clearServerWarning(cropSelect);
        updateDistrictOptions();
        updateCropOptions();
        profileReady = false;
        locationMethod.value = "manual";
        highlightDistrict("");
        if (locationMarker && map) {
            map.removeLayer(locationMarker);
            locationMarker = null;
        }
        if (mapStatus) mapStatus.textContent = "Choose a district on the map or from the list.";
        setProfileStatus("Choose a district and crop to load the model information.");
        updateSubmitState();
    });

    districtSelect.addEventListener("change", () => {
        clearServerWarning(districtSelect);
        clearServerWarning(cropSelect);
        updateCropOptions();
        const district = districtSelect.value;
        const centre = districtCentres[district];
        if (district && centre) {
            locationMethod.value = "manual";
            highlightDistrict(district);
            setMarker([centre.latitude, centre.longitude], true);
            mapStatus.innerHTML = `<strong>${district}</strong>, ${centre.province}<br><span>Selected from the district list.</span>`;
        }
        loadHistoricalProfile(climateScenario.value === "custom");
    });

    cropSelect.addEventListener("change", () => {
        clearServerWarning(cropSelect);
        loadHistoricalProfile(climateScenario.value === "custom");
    });

    climateScenario.addEventListener("change", () => {
        clearServerWarning(climateScenario);
        setTechnicalEditability();
        loadHistoricalProfile(climateScenario.value === "custom");
    });

    technicalInputs.forEach((input) => {
        input.addEventListener("input", () => {
            clearServerWarning(input);
            if (climateScenario.value === "custom") {
                profileReady = technicalInputs.every((field) => field.value.trim() !== "");
                setProfileStatus("Using a custom climate scenario. Review all Advanced values before estimating.", "ready");
            }
            validateTechnicalField(input);
            updateSubmitState();
        });
        input.addEventListener("blur", () => validateTechnicalField(input));
    });

    landArea?.addEventListener("input", () => {
        clearLandServerWarning();
        updateLandConversion(true);
    });
    landUnit?.addEventListener("change", () => {
        clearServerWarning(landUnit);
        updateLandConversion(true);
    });

    predictForm.addEventListener("submit", (event) => {
        let technicalValid = true;
        let firstInvalidTechnical = null;
        technicalInputs.forEach((input) => {
            const fieldValid = validateTechnicalField(input, true);
            if (!fieldValid && !firstInvalidTechnical) firstInvalidTechnical = input;
            technicalValid = fieldValid && technicalValid;
        });
        const landValid = validateLandField(true);
        if (!provinceSelect.value || !districtSelect.value || !cropSelect.value || !profileReady || !technicalValid || !landValid) {
            event.preventDefault();
            let firstInvalid = null;
            if (!provinceSelect.value) firstInvalid = provinceSelect;
            else if (!districtSelect.value) firstInvalid = districtSelect;
            else if (!cropSelect.value) firstInvalid = cropSelect;
            else if (!landValid) firstInvalid = landArea;
            else if (firstInvalidTechnical) firstInvalid = firstInvalidTechnical;

            if (firstInvalidTechnical) {
                advancedPanel.open = true;
                const range = FEATURE_RANGES[firstInvalidTechnical.name];
                setProfileStatus(`Correct the highlighted Advanced field: ${range?.label || "model input"}.`, "error");
            } else if (!profileReady) {
                setProfileStatus("The district and crop profile has not loaded yet. Choose both fields and wait for the confirmation message.", "error");
            }

            if (firstInvalid) {
                firstInvalid.scrollIntoView({ behavior: "smooth", block: "center" });
                firstInvalid.focus({ preventScroll: true });
            }
        }
    });

    const initialDistrict = districtSelect.dataset.selected || "";
    const initialCrop = cropSelect.dataset.selected || "";
    updateDistrictOptions(initialDistrict);
    updateCropOptions(initialCrop);
    setTechnicalEditability();
    updateLandConversion(false);
    initialiseMap();

    if (districtSelect.value && cropSelect.value) {
        if (climateScenario.value === "custom" && profileReady) {
            setProfileStatus("Using the custom model values shown under Advanced options.", "ready");
            updateSubmitState();
        } else {
            loadHistoricalProfile(climateScenario.value === "custom");
        }
    } else {
        updateSubmitState();
    }

    const firstServerInvalid = document.querySelector(".is-invalid-input");
    if (firstServerInvalid) {
        if (firstServerInvalid.classList.contains("technical-input")) advancedPanel.open = true;
        firstServerInvalid.scrollIntoView({ behavior: "smooth", block: "center" });
        firstServerInvalid.focus({ preventScroll: true });
    }
});
