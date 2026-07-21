document.addEventListener('DOMContentLoaded', () => {
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
  const config = window.KayPaolo || {};
  const tokenKey = 'kayPaoloZionToken';
  const userKey = 'kayPaoloZionUser';

  const storedToken = () => window.localStorage.getItem(tokenKey) || '';
  const storedUser = () => {
    try {
      return JSON.parse(window.localStorage.getItem(userKey) || '{}');
    } catch (error) {
      return {};
    }
  };

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

  const postJson = async (url, payload, options = {}) => {
    const headers = {
      'Accept': 'application/json',
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
      body: JSON.stringify(payload)
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
  };

  const loginForm = document.getElementById('loginForm');
  if (loginForm && loginForm.dataset.apiLogin !== undefined) {
    loginForm.addEventListener('submit', async (event) => {
      event.preventDefault();

      const errorBox = document.getElementById('loginApiError');
      const successBox = document.getElementById('loginApiSuccess');
      const submitButton = loginForm.querySelector('button[type="submit"]');
      errorBox.hidden = true;
      successBox.hidden = true;
      submitButton.disabled = true;
      submitButton.textContent = 'Logging in...';

      try {
        const payload = {
          email: loginForm.querySelector('[name="email"]').value.trim(),
          password: loginForm.querySelector('[name="password"]').value,
          role_id: loginForm.querySelector('[name="role_id"]').value || undefined
        };
        const loginUrl = config.routes?.login || loginForm.action;
        const response = await postJson(loginUrl, payload, { token: '' });

        if (response.error === 'true' || !response.access_token) {
          throw new Error(response.message || 'Unable to login with Zion Shipping.');
        }

        window.localStorage.setItem(tokenKey, response.access_token);
        window.localStorage.setItem(userKey, JSON.stringify(response.user || {}));
        successBox.textContent = response.message || 'Logged in successfully.';
        successBox.hidden = false;
        window.location.href = '/dashboard';
      } catch (error) {
        errorBox.textContent = error.message;
        errorBox.hidden = false;
      } finally {
        submitButton.disabled = false;
        submitButton.textContent = 'Login';
      }
    });
  }

  const authNotice = document.getElementById('authNotice');
  if (authNotice && storedToken()) {
    authNotice.hidden = true;
  }

  const dashboardName = document.getElementById('dashboardUserName');
  if (dashboardName) {
    const user = storedUser();
    if (user && Object.keys(user).length) {
      dashboardName.textContent = user.name || 'Zion user';
      document.getElementById('dashboardRole').textContent = user.role?.name || 'User';
      document.getElementById('dashboardRoleId').textContent = user.role_id || '-';
      document.getElementById('dashboardEmail').textContent = user.email || '-';
      document.getElementById('dashboardAccount').textContent = user.account_number || '-';
    }
  }

  document.querySelectorAll('form[action$="/logout"]').forEach((form) => {
    form.addEventListener('submit', () => {
      window.localStorage.removeItem(tokenKey);
      window.localStorage.removeItem(userKey);
    });
  });

  const selectedCountryName = (select) => select?.options[select.selectedIndex]?.text || select?.value || '';
  const value = (id) => document.getElementById(id)?.value?.trim() || '';
  const numberValue = (id, fallback) => {
    const raw = Number(value(id));
    return Number.isFinite(raw) && raw > 0 ? raw : fallback;
  };

  let lastQuotePayload = null;
  let lastQuoteResponse = null;

  const buildQuotePayload = () => {
    const fromCountry = document.getElementById('from_country');
    const toCountry = document.getElementById('to_country');
    const packageType = value('shipment_type');
    const isFlatRate = packageType !== '';
    const dimensions = {
      package_count_ind: [1],
      weight: [numberValue('package_weight', 1)],
      length: [numberValue('package_length', 1)],
      width: [numberValue('package_width', 1)],
      height: [numberValue('package_height', 1)]
    };

    return {
      user_id: value('quoteUserId') || undefined,
      quote_user_id: value('quoteUserId') || undefined,
      from_country_name: selectedCountryName(fromCountry),
      from_country: value('from_country'),
      from_address: value('from_address'),
      from_zip: value('from_zip'),
      from_city: value('from_city'),
      from_state: value('from_state'),
      to_country_name: selectedCountryName(toCountry),
      to_country: value('to_country'),
      to_address: value('to_address'),
      to_zip_input: value('to_zip'),
      to_city_dropdown: value('to_city'),
      to_zip: value('to_zip'),
      to_city: value('to_city'),
      to_state: value('to_state'),
      to_name: value('to_name'),
      consignee_name: value('to_name'),
      consignee_id: value('consignee_id') || undefined,
      consignees_id: value('consignee_id') || undefined,
      to_phone_1: value('to_phone_1'),
      consignee_phone: value('to_phone_1'),
      package_count: 1,
      total_value: numberValue('package_value', 10),
      dimensions,
      flat_rate: isFlatRate ? ['on'] : [],
      shipment_type: isFlatRate ? [packageType] : [],
      delivery_location: value('delivery_location'),
      package_description: value('package_description') || 'General merchandise'
    };
  };

  const showLoader = (id, visible) => {
    const loader = document.getElementById(id);
    if (loader) loader.hidden = !visible;
  };

  const showError = (container, message) => {
    container.innerHTML = `<div class="api-alert error">${escapeHtml(message)}</div>`;
  };

  const renderQuoteCards = (payload) => {
    const container = document.getElementById('quoteResult');
    if (!container) return;

    const cards = Array.isArray(payload.cards) ? payload.cards : [];
    const quoteId = payload.quote_id || '';

    if (!cards.length) {
      showError(container, payload.message || 'No quote cards returned from Zion.');
      return;
    }

    container.innerHTML = cards.map((card, index) => {
      if (card.type === 'message') {
        return `<div class="api-card"><h3>${escapeHtml(card.carrier_name || 'Carrier')}</h3><p class="muted-text">${escapeHtml(card.message || 'Unavailable')}</p></div>`;
      }

      return `
        <div class="api-card">
          <span class="num">${escapeHtml((card.carrier_name || card.carrier || 'Carrier').toUpperCase())}</span>
          <h3>${escapeHtml(card.service_name || 'Shipping Service')}</h3>
          <div class="price">$${escapeHtml(card.total || '0.00')}</div>
          <dl>
            <div><dt>Quote ID</dt><dd>${escapeHtml(String(quoteId || '-'))}</dd></div>
            <div><dt>Arrives</dt><dd>${escapeHtml(card.arrives_on || '-')}</dd></div>
            <div><dt>Delivered By</dt><dd>${escapeHtml(card.delivered_by || '-')}</dd></div>
            <div><dt>Freight</dt><dd>$${escapeHtml(card.freight || '0.00')}</dd></div>
            <div><dt>Tax</dt><dd>$${escapeHtml(card.tax || '0.00')}</dd></div>
          </dl>
          <button class="btn btn-navy btn-block create-shipment-btn" type="button" data-card-index="${index}" style="margin-top:18px;">Create Shipment</button>
        </div>
      `;
    }).join('');
  };

  const quoteForm = document.getElementById('quoteForm');
  if (quoteForm) {
    quoteForm.addEventListener('submit', async (event) => {
      event.preventDefault();
      const container = document.getElementById('quoteResult');
      const shippingResult = document.getElementById('shippingResult');
      container.innerHTML = '';
      shippingResult.innerHTML = '';
      showLoader('quoteLoader', true);

      try {
        lastQuotePayload = buildQuotePayload();
        lastQuoteResponse = await postJson(config.routes.quote, lastQuotePayload);
        renderQuoteCards(lastQuoteResponse);
      } catch (error) {
        showError(container, error.message);
      } finally {
        showLoader('quoteLoader', false);
      }
    });

    document.addEventListener('click', async (event) => {
      const button = event.target.closest('.create-shipment-btn');
      if (!button) return;

      const resultContainer = document.getElementById('shippingResult');
      resultContainer.innerHTML = '';
      showLoader('shippingLoader', true);

      try {
        const card = lastQuoteResponse.cards[Number(button.dataset.cardIndex)];
        const payload = {
          ...lastQuotePayload,
          quote_id: lastQuoteResponse.quote_id,
          partner: (card.carrier || 'zion').toUpperCase(),
          payment_type: 'PAID AT AGENT',
          deliveryEstimatePrice: card.total || undefined,
          deliveryEstimateDate: card.arrives_on || undefined,
          delivery_option: card.service_name || undefined
        };
        const response = await postJson(config.routes.shipping, payload);
        resultContainer.innerHTML = renderRawResult('Shipment Response', response);
      } catch (error) {
        showError(resultContainer, error.message);
      } finally {
        showLoader('shippingLoader', false);
      }
    });
  }

  const fetchCustomerBtn = document.getElementById('fetchCustomerBtn');
  if (fetchCustomerBtn) {
    fetchCustomerBtn.addEventListener('click', async () => {
      const result = document.getElementById('customerLookupResult');
      result.className = 'api-inline-result';
      result.textContent = 'Searching Zion customer records...';

      try {
        const response = await postJson(config.routes.fetchUserForQuote, {
          phone_or_account: value('customerLookup')
        });
        document.getElementById('quoteUserId').value = response.quote_user_id || response.user_id || '';
        result.className = 'api-inline-result success';
        result.textContent = response.message || 'Customer ready for quote.';
      } catch (error) {
        result.className = 'api-inline-result api-alert error';
        result.textContent = error.message;
      }
    });
  }

  const trackingForm = document.getElementById('trackingForm');
  if (trackingForm) {
    trackingForm.addEventListener('submit', async (event) => {
      event.preventDefault();
      const result = document.getElementById('trackingResult');
      result.innerHTML = '';
      showLoader('trackingLoader', true);

      try {
        const response = await postJson(config.routes.tracking, {
          tracking_number: value('tracking_number')
        });
        result.innerHTML = renderTracking(response);
      } catch (error) {
        showError(result, error.message);
      } finally {
        showLoader('trackingLoader', false);
      }
    });
  }

  function renderTracking(response) {
    const shipping = response.shipping_data || {};
    const tracking = response.tracking_data || {};
    const status = tracking.status_name || tracking.status || shipping.status || response.status || 'Tracking received';
    const invoice = shipping.invoice_num || shipping.tracking_number || value('tracking_number');
    const route = [shipping.shipper_city, shipping.consignee_address_city].filter(Boolean).join(' to ');

    return `
      <div class="contact-form" style="padding:34px;">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:14px;flex-wrap:wrap;">
          <div><span class="mono" style="font-size:12px;color:var(--gold-500);">WAYBILL</span><h3 class="mono" style="font-size:22px;margin-top:4px;">${escapeHtml(invoice || '-')}</h3></div>
          <div style="text-align:right;"><span class="mono" style="font-size:12px;color:var(--gold-500);">STATUS</span><h3 style="font-size:18px;color:var(--teal-600);margin-top:4px;">${escapeHtml(String(status))}</h3></div>
        </div>
        <p class="muted-text">${escapeHtml(route || response.message || 'Tracking response returned from Zion Shipping.')}</p>
        ${renderRawResult('Raw Zion Tracking Payload', response)}
      </div>
    `;
  }

  function renderRawResult(title, payload) {
    return `
      <div class="api-card">
        <h3>${escapeHtml(title)}</h3>
        <pre class="api-raw">${escapeHtml(JSON.stringify(payload, null, 2))}</pre>
      </div>
    `;
  }

  function escapeHtml(value) {
    return String(value ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }
});
