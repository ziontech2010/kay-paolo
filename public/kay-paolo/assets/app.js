document.addEventListener('DOMContentLoaded', () => {
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
  const config = window.KayPaolo || {};
  const tokenKey = 'kayPaoloZionToken';
  const userKey = 'kayPaoloZionUser';
  const pendingShipmentKey = 'kayPaoloPendingShipment';
  const shipmentResponseKey = 'kayPaoloShipmentResponse';
  const trackingResponseKey = 'kayPaoloTrackingResponse';

  const route = (name, fallback) => config.routes?.[name] || fallback;
  const storedToken = () => window.localStorage.getItem(tokenKey) || '';
  const storedJson = (key, fallback = {}) => {
    try {
      return JSON.parse(window.localStorage.getItem(key) || JSON.stringify(fallback));
    } catch (error) {
      return fallback;
    }
  };
  const storedUser = () => storedJson(userKey, {});
  const value = (id) => document.getElementById(id)?.value?.trim() || '';
  const setValue = (id, nextValue) => {
    const element = document.getElementById(id);
    if (element && nextValue !== undefined && nextValue !== null && nextValue !== '') {
      element.value = nextValue;
    }
  };
  const setText = (id, nextValue) => {
    const element = document.getElementById(id);
    if (element) element.textContent = String(nextValue ?? '-');
  };
  const firstValue = (...ids) => ids.map(value).find((item) => item !== '') || '';
  const firstElement = (...ids) => ids.map((id) => document.getElementById(id)).find(Boolean) || null;
  const numberValue = (raw, fallback) => {
    const parsed = Number(String(raw ?? '').replace(/[^0-9.-]/g, ''));
    return Number.isFinite(parsed) && parsed > 0 ? parsed : fallback;
  };
  const currentPath = () => window.location.pathname + window.location.search;
  const loginUrl = (redirectPath = '') => {
    const base = route('loginPage', '/login');
    return redirectPath ? `${base}?redirect=${encodeURIComponent(redirectPath)}` : base;
  };

  if (!storedToken() && config.sessionToken) {
    window.localStorage.setItem(tokenKey, config.sessionToken);
  }

  if (!Object.keys(storedUser()).length && config.sessionUser && Object.keys(config.sessionUser).length) {
    window.localStorage.setItem(userKey, JSON.stringify(config.sessionUser));
  }

  const header = document.getElementById('siteHeader');
  if (header) {
    window.addEventListener('scroll', () => {
      header.classList.toggle('scrolled', window.scrollY > 10);
    });
  }

  const burger = document.getElementById('burgerBtn');
  const mainNav = document.getElementById('mainNav');
  if (burger && mainNav) {
    burger.addEventListener('click', () => mainNav.classList.toggle('open'));
    document.querySelectorAll('nav.main > ul > li.has-dd > a').forEach((link) => {
      link.addEventListener('click', (event) => {
        if (window.innerWidth <= 760) {
          event.preventDefault();
          link.parentElement.classList.toggle('dd-open');
        }
      });
    });
  }

  document.querySelectorAll('.qc-tabs button').forEach((btn) => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.qc-tabs button').forEach((item) => item.classList.remove('active'));
      document.querySelectorAll('.tab-panel').forEach((panel) => panel.classList.remove('active'));
      btn.classList.add('active');
      document.getElementById('panel-' + btn.dataset.tab)?.classList.add('active');
    });
  });

  document.querySelectorAll('form[data-inline-confirm]').forEach((form) => {
    form.addEventListener('submit', (event) => {
      event.preventDefault();
      form.querySelector('.form-note')?.classList.add('show');
      form.reset();
    });
  });

  initContactModal();
  initCounters();
  initLogin();
  initSessionPanels();
  initLogoutForms();
  initPullCustomer();
  initPackageBlocks();
  initQuoteForm();
  initCreateShipmentForm();
  initTrackingForm();
  initTrackingDetailPage();
  initReceiptPages();
  initShipmentHistoryFilters();

  async function postJson(url, payload, options = {}) {
    const headers = {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': csrf
    };

    const token = options.token === undefined ? storedToken() : options.token;
    if (token) {
      headers.Authorization = `Bearer ${token}`;
    }

    const response = await fetch(url, {
      method: 'POST',
      headers,
      body: JSON.stringify(payload || {})
    });

    const text = await response.text();
    let data = {};
    try {
      data = text ? JSON.parse(text) : {};
    } catch (error) {
      data = { status: 'error', message: text || 'Unexpected response.' };
    }

    if (!response.ok) {
      const message = data.message || data.error || 'Request failed.';
      throw Object.assign(new Error(message), { response: data, status: response.status });
    }

    return data;
  }

  function initLogin() {
    const loginForm = document.getElementById('loginForm');
    if (!loginForm || loginForm.dataset.apiLogin === undefined) return;

    loginForm.addEventListener('submit', async (event) => {
      event.preventDefault();

      const errorBox = document.getElementById('loginApiError');
      const successBox = document.getElementById('loginApiSuccess');
      const submitButton = loginForm.querySelector('button[type="submit"]');
      if (errorBox) errorBox.hidden = true;
      if (successBox) successBox.hidden = true;
      if (submitButton) {
        submitButton.disabled = true;
        submitButton.textContent = 'Logging in...';
      }

      try {
        const payload = {
          email: loginForm.querySelector('[name="email"]')?.value.trim(),
          password: loginForm.querySelector('[name="password"]')?.value,
          role_id: loginForm.querySelector('[name="role_id"]')?.value || undefined
        };
        const response = await postJson(loginForm.dataset.apiEndpoint || route('login', '/api/kay-paolo/login'), payload, { token: '' });

        if (response.error === 'true' || !response.access_token) {
          throw new Error(response.message || 'Unable to login with Zion Shipping.');
        }

        window.localStorage.setItem(tokenKey, response.access_token);
        window.localStorage.setItem(userKey, JSON.stringify(response.user || {}));
        if (successBox) {
          successBox.textContent = response.message || 'Logged in successfully.';
          successBox.hidden = false;
        }

        const redirectInput = loginForm.querySelector('[name="redirect"]')?.value || '';
        window.location.href = redirectInput.startsWith('/') && !redirectInput.startsWith('//')
          ? redirectInput
          : route('account', '/account');
      } catch (error) {
        if (errorBox) {
          errorBox.textContent = error.message;
          errorBox.hidden = false;
        }
      } finally {
        if (submitButton) {
          submitButton.disabled = false;
          submitButton.textContent = 'Login';
        }
      }
    });
  }

  function initSessionPanels() {
    const authNotice = document.getElementById('authNotice');
    if (authNotice && storedToken()) {
      authNotice.hidden = true;
    }

    const dashboardName = document.getElementById('dashboardUserName');
    if (!dashboardName) return;

    const user = storedUser();
    if (user && Object.keys(user).length) {
      dashboardName.textContent = user.name || 'Zion user';
      setText('dashboardRole', user.role?.name || 'User');
      setText('dashboardRoleId', user.role_id || '-');
      setText('dashboardEmail', user.email || '-');
      setText('dashboardAccount', user.account_number || user.id || '-');
    }
  }

  function initLogoutForms() {
    document.querySelectorAll('form[action$="/logout"]').forEach((form) => {
      form.addEventListener('submit', () => {
        window.localStorage.removeItem(tokenKey);
        window.localStorage.removeItem(userKey);
        window.localStorage.removeItem(pendingShipmentKey);
      });
    });
  }

  function initPullCustomer() {
    const pullCustomerBtn = document.getElementById('pullCustomerBtn');
    if (!pullCustomerBtn) return;

    pullCustomerBtn.addEventListener('click', async () => {
      const lookup = value('qCustomerLookup');
      const result = document.getElementById('customerLookupResult');
      const nextUrl = pullCustomerBtn.dataset.next || route('quoteDetails', '/quote-details');
      const moveNext = (customerId = '') => {
        const params = new URLSearchParams();
        if (lookup) params.set('lookup', lookup);
        if (customerId) params.set('customer', customerId);
        window.location.href = params.toString() ? `${nextUrl}?${params}` : nextUrl;
      };

      if (!storedToken()) {
        window.location.href = loginUrl(window.location.pathname);
        return;
      }

      if (!lookup) {
        moveNext();
        return;
      }

      if (result) {
        result.className = 'api-inline-result';
        result.textContent = 'Searching Zion customer records...';
      }

      try {
        const response = await postJson(route('fetchUserForQuote', '/api/kay-paolo/fetch-user-for-quote'), {
          phone_or_account: lookup,
          customer: lookup
        });
        if (result) {
          result.className = 'api-inline-result success';
          result.textContent = response.message || 'Customer ready for quote.';
        }
        moveNext(response.quote_user_id || response.user_id || response.id || lookup);
      } catch (error) {
        if (result) {
          result.className = 'api-inline-result api-alert error';
          result.textContent = error.message;
        }
      }
    });
  }

  function initPackageBlocks() {
    const container = document.getElementById('packagesContainer');
    if (!container) return;

    const refreshPackages = () => {
      container.querySelectorAll('.package-block').forEach((block, index) => {
        const number = index + 1;
        block.id = `packageBlock${number}`;
        block.querySelector('.package-title').textContent = `Package ${number}`;
        const removeButton = block.querySelector('.remove-package-btn');
        if (removeButton) removeButton.style.display = index === 0 ? 'none' : 'inline-flex';

        const flat = block.querySelector('.pkg-flat-rate');
        const count = block.querySelector('.pkg-count');
        if (flat) flat.id = `pkgFlatRate${number}`;
        if (count) count.id = `pkgCount${number}`;
        const flatLabel = block.querySelector('label[for^="pkgFlatRate"]');
        const countLabel = block.querySelector('label[for^="pkgCount"]');
        if (flatLabel) flatLabel.setAttribute('for', `pkgFlatRate${number}`);
        if (countLabel) countLabel.setAttribute('for', `pkgCount${number}`);
      });
    };

    container.addEventListener('click', (event) => {
      const addButton = event.target.closest('.add-package-btn');
      const removeButton = event.target.closest('.remove-package-btn');

      if (addButton) {
        const lastBlock = container.querySelector('.package-block:last-child');
        const clone = lastBlock.cloneNode(true);
        clone.querySelectorAll('input').forEach((input) => {
          if (input.type === 'checkbox') input.checked = false;
          else input.value = '';
        });
        clone.querySelectorAll('select').forEach((select) => {
          select.selectedIndex = 0;
        });
        container.appendChild(clone);
        refreshPackages();
      }

      if (removeButton) {
        removeButton.closest('.package-block')?.remove();
        refreshPackages();
      }
    });

    refreshPackages();
  }

  function initQuoteForm() {
    const quoteForm = document.getElementById('quoteForm');
    if (!quoteForm) return;

    if (!storedToken()) {
      window.location.replace(loginUrl(currentPath()));
      return;
    }

    quoteForm.addEventListener('submit', async (event) => {
      event.preventDefault();
      const container = document.getElementById('quoteResult');
      const shippingResult = document.getElementById('shippingResult');
      if (container) container.innerHTML = '';
      if (shippingResult) shippingResult.innerHTML = '';
      showLoader('quoteLoader', true);

      try {
        const payload = buildQuotePayload();
        const response = await postJson(route('quote', '/api/kay-paolo/quote'), payload);
        window.localStorage.setItem('kayPaoloLastQuotePayload', JSON.stringify(payload));
        window.localStorage.setItem('kayPaoloLastQuoteResponse', JSON.stringify(response));
        renderQuoteCards(response, payload);
      } catch (error) {
        showError(container, error.message);
      } finally {
        showLoader('quoteLoader', false);
      }
    });

    document.addEventListener('click', (event) => {
      const button = event.target.closest('.create-shipment-btn');
      if (!button) return;

      const quoteResponse = storedJson('kayPaoloLastQuoteResponse', {});
      const quotePayload = storedJson('kayPaoloLastQuotePayload', {});
      const cards = normalizeQuoteCards(quoteResponse);
      const card = cards[Number(button.dataset.cardIndex)] || {};
      const quoteId = quoteResponse.quote_id || quoteResponse.quoteId || quoteResponse.id || card.quote_id || '';
      const pendingPayload = {
        ...quotePayload,
        quote_id: quoteId,
        partner: String(card.carrier || card.carrier_name || 'zion').toUpperCase(),
        payment_type: 'PAID AT AGENT',
        deliveryEstimatePrice: card.total || card.price || card.amount || undefined,
        deliveryEstimateDate: card.arrives_on || card.arrival_date || card.eta || undefined,
        delivery_option: card.service_name || card.service || card.name || undefined
      };

      window.localStorage.setItem(pendingShipmentKey, JSON.stringify({
        payload: pendingPayload,
        card,
        quote: quoteResponse
      }));
      window.location.href = route('createShipmentPage', '/create-shipment');
    });
  }

  function initCreateShipmentForm() {
    const form = document.getElementById('createShipmentForm');
    if (!form) return;

    if (!storedToken()) {
      window.location.replace(loginUrl(currentPath()));
      return;
    }

    fillCreateShipmentPage();

    form.addEventListener('submit', async (event) => {
      event.preventDefault();
      const result = document.getElementById('shippingResult');
      if (result) result.innerHTML = '';
      showLoader('shippingLoader', true);

      try {
        const pending = storedJson(pendingShipmentKey, { payload: {}, card: {}, quote: {} });
        const payload = mergeShipmentFormPayload(pending.payload || {});
        const response = await postJson(route('shipping', '/api/kay-paolo/shipping'), payload);
        window.localStorage.setItem(shipmentResponseKey, JSON.stringify({ response, payload, selected: pending.card || {} }));
        window.location.href = route('receipt', '/receipt');
      } catch (error) {
        showError(result, error.message);
      } finally {
        showLoader('shippingLoader', false);
      }
    });
  }

  function initTrackingForm() {
    const trackingForm = document.getElementById('trackingForm');
    if (trackingForm) {
      trackingForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        const result = document.getElementById('trackingResult');
        if (result) result.innerHTML = '';
        showLoader('trackingLoader', true);

        try {
          const trackingNumber = firstValue('tracking_number', 'trackNumInput');
          const response = await postJson(route('tracking', '/api/kay-paolo/validate-tracking'), {
            tracking_number: trackingNumber,
            id: trackingNumber
          }, { token: storedToken() });
          window.localStorage.setItem(trackingResponseKey, JSON.stringify(response));
          window.location.href = `${route('trackingDetail', '/tracking-detail')}?id=${encodeURIComponent(trackingNumber)}`;
        } catch (error) {
          showError(result, error.message);
        } finally {
          showLoader('trackingLoader', false);
        }
      });
    }

    const trackAnotherBtn = document.getElementById('trackAnotherBtn');
    if (trackAnotherBtn) {
      trackAnotherBtn.addEventListener('click', async () => {
        const input = value('trackAnotherInput');
        if (!input) return;
        trackAnotherBtn.disabled = true;
        trackAnotherBtn.textContent = 'Tracking...';
        try {
          const response = await postJson(route('tracking', '/api/kay-paolo/validate-tracking'), {
            tracking_number: input,
            id: input
          }, { token: storedToken() });
          window.localStorage.setItem(trackingResponseKey, JSON.stringify(response));
          window.history.replaceState({}, '', `${route('trackingDetail', '/tracking-detail')}?id=${encodeURIComponent(input)}`);
          populateTrackingDetail(response, input);
        } catch (error) {
          window.alert(error.message);
        } finally {
          trackAnotherBtn.disabled = false;
          trackAnotherBtn.textContent = 'Track Shipment';
        }
      });
    }
  }

  function initTrackingDetailPage() {
    if (!document.getElementById('trackingTitle') && !document.getElementById('shipmentTitle')) return;

    const params = new URLSearchParams(window.location.search);
    const trackingNumber = params.get('id') || params.get('tracking_number') || '';
    const stored = storedJson(trackingResponseKey, {});
    if (Object.keys(stored).length) {
      populateTrackingDetail(stored, trackingNumber);
      return;
    }

    if (!trackingNumber) return;

    postJson(route('tracking', '/api/kay-paolo/validate-tracking'), {
      tracking_number: trackingNumber,
      id: trackingNumber
    }, { token: storedToken() })
      .then((response) => {
        window.localStorage.setItem(trackingResponseKey, JSON.stringify(response));
        populateTrackingDetail(response, trackingNumber);
      })
      .catch(() => {
        populateTrackingDetail({}, trackingNumber);
      });
  }

  function initReceiptPages() {
    const receiptSummary = document.getElementById('receiptSummary');
    const receiptA4Payload = document.getElementById('receiptA4Payload');
    const invoicePayload = document.getElementById('invoicePayload');
    if (!receiptSummary && !receiptA4Payload && !invoicePayload) return;

    const shipment = storedJson(shipmentResponseKey, {});
    const response = shipment.response || {};
    const payload = shipment.payload || {};
    const selected = shipment.selected || {};
    const tracking = response.tracking_number || response.invoice_num || response.awb || payload.tracking_number || 'Pending';
    const total = selected.total || selected.price || payload.deliveryEstimatePrice || response.total || '0.00';
    const status = response.status_name || response.status || response.message || 'Booked';

    setText('receiptTrackingNumber', tracking);
    setText('receiptStatus', status);
    setText('receiptTotal', `USD ${total}`);
    setText('receiptA4TrackingNumber', tracking);
    setText('receiptA4Shipper', [payload.from_address, payload.from_city, payload.from_state, payload.from_zip].filter(Boolean).join(', ') || 'Kay Paolo Shipping');
    setText('receiptA4Receiver', [payload.to_name, payload.to_address, payload.to_city, payload.to_state, payload.to_zip].filter(Boolean).join(', ') || 'Destination customer');
    setText('invoiceNumber', response.invoice_num || tracking);
    setText('invoiceAmount', `USD ${total}`);
    setText('invoiceStatus', status);

    const pretty = JSON.stringify({ response, payload, selected }, null, 2);
    if (receiptSummary) receiptSummary.textContent = pretty;
    if (receiptA4Payload) receiptA4Payload.textContent = pretty;
    if (invoicePayload) invoicePayload.textContent = pretty;
  }

  function initShipmentHistoryFilters() {
    const list = document.getElementById('historyCardList');
    if (!list) return;

    const filter = () => {
      const query = value('searchInput').toLowerCase();
      const statuses = Array.from(document.querySelectorAll('.status-filter:checked')).map((item) => item.value);
      const categories = Array.from(document.querySelectorAll('.category-filter:checked')).map((item) => item.value);
      list.querySelectorAll('.shipment-card[data-status]').forEach((card) => {
        const matchesQuery = !query || String(card.dataset.searchPool || card.textContent).toLowerCase().includes(query);
        const matchesStatus = !statuses.length || statuses.includes(card.dataset.status);
        const matchesCategory = !categories.length || categories.includes(card.dataset.category);
        card.style.display = matchesQuery && matchesStatus && matchesCategory ? '' : 'none';
      });
    };

    document.getElementById('searchInput')?.addEventListener('input', filter);
    document.querySelectorAll('.status-filter,.category-filter').forEach((input) => input.addEventListener('change', filter));
  }

  function buildQuotePayload() {
    const fromCountry = firstElement('from_country');
    const toCountry = firstElement('toCountry', 'to_country');
    const packageBlocks = Array.from(document.querySelectorAll('#packagesContainer .package-block'));
    const dimensions = {
      package_count_ind: [],
      weight: [],
      length: [],
      width: [],
      height: []
    };
    const flatRate = [];
    const shipmentType = [];

    if (packageBlocks.length) {
      packageBlocks.forEach((block) => {
        const count = numberValue(block.querySelector('.pkg-count')?.value, 1);
        const weight = numberValue(block.querySelector('.pkg-weight')?.value, 1);
        const length = numberValue(block.querySelector('.pkg-length')?.value, 1);
        const width = numberValue(block.querySelector('.pkg-width')?.value, 1);
        const height = numberValue(block.querySelector('.pkg-height')?.value, 1);
        const isFlat = Boolean(block.querySelector('.pkg-flat-rate')?.checked);
        dimensions.package_count_ind.push(count);
        dimensions.weight.push(weight);
        dimensions.length.push(length);
        dimensions.width.push(width);
        dimensions.height.push(height);
        if (isFlat) {
          flatRate.push('on');
          shipmentType.push('flat_rate');
        }
      });
    } else {
      dimensions.package_count_ind.push(1);
      dimensions.weight.push(numberValue(firstValue('package_weight'), 1));
      dimensions.length.push(numberValue(firstValue('package_length'), 1));
      dimensions.width.push(numberValue(firstValue('package_width'), 1));
      dimensions.height.push(numberValue(firstValue('package_height'), 1));
    }

    const toCountryName = selectedCountryName(toCountry);
    const fromCountryName = selectedCountryName(fromCountry);
    const deliveryLocation = firstValue('deliveryLocation', 'delivery_location');
    const packageDescription = firstValue('packageDescription', 'package_description') || 'General merchandise';

    return {
      user_id: firstValue('quoteUserId') || undefined,
      quote_user_id: firstValue('quoteUserId') || undefined,
      from_country_name: fromCountryName,
      from_country: countryCode(fromCountry?.value || fromCountryName),
      from_address: firstValue('from_address'),
      from_zip: firstValue('from_zip'),
      from_city: firstValue('from_city'),
      from_state: firstValue('from_state'),
      to_country_name: toCountryName,
      to_country: countryCode(toCountry?.value || toCountryName),
      to_address: firstValue('toAddress', 'to_address'),
      to_apt: firstValue('toApt', 'to_apt'),
      to_zip_input: firstValue('toZip', 'to_zip'),
      to_city_dropdown: firstValue('toCity', 'to_city'),
      to_zip: firstValue('toZip', 'to_zip'),
      to_city: firstValue('toCity', 'to_city'),
      to_state: firstValue('toState', 'to_state'),
      to_name: firstValue('toName', 'to_name'),
      consignee_name: firstValue('toName', 'to_name'),
      consignee_id: firstValue('consignee_id') || undefined,
      consignees_id: firstValue('consignee_id') || undefined,
      to_phone_1: firstValue('toPhone', 'to_phone_1'),
      to_phone_2: firstValue('toHomePhone', 'to_phone_2'),
      consignee_phone: firstValue('toPhone', 'to_phone_1'),
      package_count: dimensions.package_count_ind.reduce((sum, item) => sum + Number(item || 0), 0) || 1,
      total_value: numberValue(firstValue('totalValue', 'package_value'), 10),
      package_value: numberValue(firstValue('totalValue', 'package_value'), 10),
      dimensions,
      flat_rate: flatRate,
      shipment_type: shipmentType.length ? shipmentType : (firstValue('shipment_type') ? [firstValue('shipment_type')] : []),
      delivery_location: deliveryLocation,
      deliveryLocation,
      coupon_code: firstValue('couponCode'),
      extra_service_charge: firstValue('extraServiceCharge'),
      fragile_shipment: document.getElementById('fragileShipment')?.checked ? 1 : 0,
      package_description: packageDescription
    };
  }

  function mergeShipmentFormPayload(basePayload) {
    return {
      ...basePayload,
      from_address: value('shipmentFromAddress') || basePayload.from_address,
      from_city: value('shipmentFromCity') || basePayload.from_city,
      from_state: value('shipmentFromState') || basePayload.from_state,
      from_zip: value('shipmentFromZip') || basePayload.from_zip,
      to_name: value('shipmentToName') || basePayload.to_name,
      consignee_name: value('shipmentToName') || basePayload.consignee_name,
      to_phone_1: value('shipmentToPhone') || basePayload.to_phone_1,
      to_phone_2: value('shipmentToHomePhone') || basePayload.to_phone_2,
      to_country_name: value('shipmentToCountry') || basePayload.to_country_name,
      to_country: countryCode(value('shipmentToCountry') || basePayload.to_country_name || basePayload.to_country),
      to_address: value('shipmentToAddress') || basePayload.to_address,
      to_apt: value('shipmentToApt') || basePayload.to_apt,
      to_city: value('shipmentToCity') || basePayload.to_city,
      to_state: value('shipmentToState') || basePayload.to_state,
      to_zip: value('shipmentToZip') || basePayload.to_zip,
      delivery_location: value('shipmentDeliveryLocation') || basePayload.delivery_location,
      package_description: value('shipmentPackageDescription') || basePayload.package_description || 'General merchandise',
      fragile_shipment: document.getElementById('shipmentFragile')?.checked ? 1 : basePayload.fragile_shipment,
      payment_type: value('shipmentPaymentType') || basePayload.payment_type || 'PAID AT AGENT'
    };
  }

  function fillCreateShipmentPage() {
    const pending = storedJson(pendingShipmentKey, { payload: {}, card: {}, quote: {} });
    const payload = pending.payload || {};
    const card = pending.card || {};
    const quote = pending.quote || {};

    setText('selectedServiceName', card.service_name || card.service || card.name || 'Selected service');
    setText('selectedServiceTotal', `USD ${card.total || card.price || card.amount || '0.00'}`);
    setText('selectedQuoteId', quote.quote_id || quote.quoteId || quote.id || payload.quote_id || '-');
    setText('selectedCarrier', card.carrier_name || card.carrier || payload.partner || 'Zion');
    setText('selectedArrivesOn', card.arrives_on || card.arrival_date || card.eta || '-');
    setText('selectedDeliveredBy', card.delivered_by || card.delivery_time || '-');

    setValue('shipmentToName', payload.to_name || payload.consignee_name);
    setValue('shipmentToPhone', payload.to_phone_1 || payload.consignee_phone);
    setValue('shipmentToHomePhone', payload.to_phone_2);
    setValue('shipmentToCountry', payload.to_country_name || payload.to_country);
    setValue('shipmentToZip', payload.to_zip);
    setValue('shipmentToAddress', payload.to_address);
    setValue('shipmentToApt', payload.to_apt);
    setValue('shipmentToCity', payload.to_city);
    setValue('shipmentToState', payload.to_state);
    setValue('shipmentDeliveryLocation', payload.delivery_location);
    setValue('shipmentPackageDescription', payload.package_description);

    const summary = document.getElementById('shipmentPackageSummary');
    if (summary && payload.dimensions) {
      const pieces = payload.package_count || payload.dimensions.weight?.length || 1;
      const weight = (payload.dimensions.weight || []).join(', ') || '1';
      summary.textContent = `${pieces} package(s), weight: ${weight} lbs, declared value: USD ${payload.total_value || payload.package_value || '0.00'}.`;
    }
  }

  function renderQuoteCards(response, originalPayload) {
    const container = document.getElementById('quoteResult');
    if (!container) return;

    const cards = normalizeQuoteCards(response);
    const quoteId = response.quote_id || response.quoteId || response.id || '';

    if (!cards.length) {
      showError(container, response.message || 'No quote cards returned from Zion.');
      return;
    }

    container.innerHTML = `
      <div class="quote-results-header">
        <h3>Quote Results</h3>
        <span class="quote-number mono">${escapeHtml(quoteId ? `Quote #${quoteId}` : 'Quote Ready')}</span>
      </div>
      ${cards.map((card, index) => renderQuoteCard(card, index, quoteId)).join('')}
    `;

    window.localStorage.setItem('kayPaoloLastQuotePayload', JSON.stringify(originalPayload || {}));
    window.localStorage.setItem('kayPaoloLastQuoteResponse', JSON.stringify(response || {}));
  }

  function renderQuoteCard(card, index) {
    if (card.type === 'message' || card.message_only) {
      return `
        <div class="carrier-unavailable-card">
          <div class="carrier-logo-container"><div class="carrier-logo-img"><img src="" alt=""></div></div>
          <div class="carrier-details"><p>${escapeHtml(card.message || 'This shipping option is unavailable.')}</p></div>
        </div>
      `;
    }

    const carrier = card.carrier_name || card.carrier || 'Kay Paolo Shipping';
    const service = card.service_name || card.service || card.name || 'Shipping Service';
    const total = card.total || card.price || card.amount || '0.00';
    const freight = card.freight || card.base_rate || '0.00';
    const insurance = card.insurance || '0.00';
    const homeDelivery = card.home_delivery || card.delivery || '0.00';
    const tax = card.tax || '0.00';

    return `
      <div class="quote-rate-card">
        <div class="rate-brand">
          <img src="${escapeHtml(assetUrl('logo'))}" alt="Kay Paolo Shipping Logo" width="100" height="50">
        </div>
        <div class="rate-info">
          <h4>${escapeHtml(service)}</h4>
          <div class="rate-delivery">
            <span class="delivery-date">Arrives on ${escapeHtml(card.arrives_on || card.arrival_date || card.eta || '-')}</span>
            <span class="delivery-time">Delivered by ${escapeHtml(card.delivered_by || card.delivery_time || '-')}</span>
          </div>
          <div class="mono" style="font-size: 12px; color: var(--ink-400); margin-top: 6px">${escapeHtml(carrier)}</div>
        </div>
        <div class="rate-breakdown">
          <div class="breakdown-item"><span>Freight:</span> <span class="mono">${escapeHtml(freight)}</span></div>
          <div class="breakdown-item"><span>Insurance:</span> <span class="mono">${escapeHtml(insurance)}</span></div>
          <div class="breakdown-item"><span>Home Delivery:</span> <span class="mono">${escapeHtml(homeDelivery)}</span></div>
          <div class="breakdown-item"><span>Tax:</span> <span class="mono">${escapeHtml(tax)}</span></div>
        </div>
        <div class="rate-price-action">
          <div class="price-box">
            <span class="price-label">TOTAL</span>
            <span class="price-currency">USD</span>
            <span class="price-amount">${escapeHtml(total)}</span>
          </div>
          <button type="button" class="btn btn-gold create-shipment-btn" data-card-index="${index}">BOOK NOW</button>
        </div>
      </div>
    `;
  }

  function normalizeQuoteCards(response) {
    const candidates = [
      response?.cards,
      response?.data?.cards,
      response?.quotes,
      response?.rates,
      response?.rate_data,
      response?.data?.quotes,
      response?.data?.rates
    ].find((item) => Array.isArray(item) || (item && typeof item === 'object'));

    if (!candidates) return [];
    return Array.isArray(candidates) ? candidates : Object.values(candidates);
  }

  function populateTrackingDetail(response, requestedTrackingNumber = '') {
    const shipping = response.shipping_data || response.shipping || response.data?.shipping_data || {};
    const tracking = response.tracking_data || response.tracking || response.data?.tracking_data || {};
    const status = tracking.status_name || tracking.status || shipping.status_name || shipping.status || response.status || 'Tracking received';
    const invoice = shipping.invoice_num || shipping.tracking_number || tracking.tracking_number || requestedTrackingNumber || value('trackAnotherInput') || '-';
    const from = [shipping.shipper_address, shipping.shipper_city, shipping.shipper_state, shipping.shipper_zip, shipping.shipper_country].filter(Boolean).join(', ');
    const to = [shipping.consignee_address, shipping.consignee_address_city, shipping.consignee_address_state, shipping.consignee_address_zip, shipping.consignee_address_country].filter(Boolean).join(', ');
    const routeText = [shipping.shipper_country || shipping.from_country, shipping.consignee_address_country || shipping.to_country].filter(Boolean).join(' to ') || response.message || 'Pending';
    const amount = shipping.balance_due || shipping.payment_due || shipping.amount_due || '';

    setText('shipmentTitle', `Shipment #${invoice}`);
    setText('expectedArrivalDate', shipping.expected_arrival_date || tracking.expected_arrival_date || shipping.arrives_on || 'Pending');
    setText('carrierTrackNum', shipping.carrier_tracking_number || shipping.tracking_numbers || invoice);
    setText('routeText', routeText);
    setText('serviceName', shipping.delivery_option || shipping.service_name || 'Not available');
    setText('statusCardTitle', status);
    setText('statusCardTime', tracking.status_date || shipping.created_at || 'Pending');
    setText('shippingAddressText', from || 'Pending');
    setText('receiverAddressText', to || 'Pending');
    setText('trackingStatusSummary', status);
    setText('trackingInvoiceSummary', invoice);
    setText('trackingWeightSummary', shipping.weight || shipping.total_weight || '-');
    setText('trackingPiecesSummary', shipping.package_count || shipping.pieces || '-');
    setText('paymentAmount', amount ? `$${amount}` : '$0.00');

    const paymentBanner = document.getElementById('paymentRequiredBanner');
    if (paymentBanner && amount) paymentBanner.style.display = '';

    const events = tracking.history || tracking.events || response.history || [];
    const timeline = document.getElementById('verticalTimeline');
    if (timeline && Array.isArray(events) && events.length) {
      timeline.innerHTML = events.map((event, index) => `
        <div class="timeline-event ${index === 0 ? 'current-event' : ''}">
          <div class="timeline-event-date">${escapeHtml(event.date || event.created_at || '-')}</div>
          <div class="timeline-event-content">
            <h5>${escapeHtml(event.status || event.status_name || 'Shipment update')}</h5>
            <p>${escapeHtml(event.description || event.message || '')}</p>
          </div>
        </div>
      `).join('');
    }
  }

  function initContactModal() {
    const contactForm = document.getElementById('contactForm');
    const confirmOverlay = document.getElementById('confirmOverlay');
    const confirmClose = document.getElementById('confirmClose');
    if (!contactForm || !confirmOverlay || !confirmClose) return;

    const showModal = () => {
      confirmOverlay.classList.add('active');
      document.body.style.overflow = 'hidden';
      confirmClose.focus();
    };
    const hideModal = () => {
      confirmOverlay.classList.remove('active');
      document.body.style.overflow = '';
    };

    contactForm.addEventListener('submit', (event) => {
      event.preventDefault();
      showModal();
      contactForm.reset();
    });
    confirmClose.addEventListener('click', hideModal);
    confirmOverlay.addEventListener('click', (event) => {
      if (event.target === confirmOverlay) hideModal();
    });
    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') hideModal();
    });
  }

  function initCounters() {
    const counters = document.querySelectorAll('.stat b[data-count]');
    const animateCounter = (el) => {
      const target = parseInt(el.dataset.count, 10);
      const suffix = el.dataset.suffix || '';
      const duration = 1400;
      const start = performance.now();
      const tick = (now) => {
        const pct = Math.min((now - start) / duration, 1);
        const eased = 1 - Math.pow(1 - pct, 3);
        el.textContent = Math.floor(target * eased).toLocaleString() + suffix;
        if (pct < 1) requestAnimationFrame(tick);
      };
      requestAnimationFrame(tick);
    };

    if ('IntersectionObserver' in window && counters.length) {
      const observer = new IntersectionObserver((entries, obs) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            animateCounter(entry.target);
            obs.unobserve(entry.target);
          }
        });
      }, { threshold: 0.35 });
      counters.forEach((counter) => observer.observe(counter));
    } else {
      counters.forEach((counter) => {
        counter.textContent = Number(counter.dataset.count || 0).toLocaleString() + (counter.dataset.suffix || '');
      });
    }
  }

  function selectedCountryName(select) {
    return select?.options?.[select.selectedIndex]?.text || select?.value || '';
  }

  function countryCode(country) {
    const raw = String(country || '').trim();
    if (!raw) return '';
    if (raw.length <= 3 && raw.toUpperCase() === raw) return raw;

    const map = {
      'united states': 'US',
      haiti: 'HT',
      'dominican republic': 'DO',
      canada: 'CA',
      jamaica: 'JM',
      'trinidad and tobago': 'TT',
      'united kingdom': 'GB',
      germany: 'DE',
      france: 'FR',
      nigeria: 'NG',
      japan: 'JP',
      australia: 'AU',
      brazil: 'BR',
      peru: 'PE',
      china: 'CN',
      india: 'IN',
      mexico: 'MX',
      singapore: 'SG',
      spain: 'ES',
      vietnam: 'VN',
      'united arab emirates': 'AE',
      'south africa': 'ZA',
      philippines: 'PH'
    };

    return map[raw.toLowerCase()] || raw;
  }

  function assetUrl(type) {
    if (type === 'logo') {
      const icon = document.querySelector('link[rel="icon"]')?.href || '';
      return icon || '/kay-paolo/assets/logo/kay-paolo.svg';
    }

    return '';
  }

  function showLoader(id, visible) {
    const loader = document.getElementById(id);
    if (loader) loader.hidden = !visible;
  }

  function showError(container, message) {
    if (!container) return;
    container.innerHTML = `<div class="api-alert error">${escapeHtml(message)}</div>`;
  }

  function escapeHtml(nextValue) {
    return String(nextValue ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }
});
