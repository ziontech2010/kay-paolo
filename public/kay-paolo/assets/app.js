document.addEventListener('DOMContentLoaded', () => {
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
  const config = window.KayPaolo || {};
  const tokenKey = 'kayPaoloZionToken';
  const userKey = 'kayPaoloZionUser';
  const pendingShipmentKey = 'kayPaoloPendingShipment';
  const shipmentResponseKey = 'kayPaoloShipmentResponse';
  const trackingResponseKey = 'kayPaoloTrackingResponse';
  const countryCacheKey = 'kayPaoloCountries:v3';
  const paymentOptionsCacheKey = 'kayPaoloPaymentOptions:v3';
  const flatRateCache = new Map();

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
  const setSelectValue = (id, nextValue) => {
    const element = document.getElementById(id);
    if (!element || nextValue === undefined || nextValue === null || nextValue === '') return;

    const raw = String(nextValue).trim();
    const normalized = raw.toLowerCase();
    const normalizedCode = countryCode(raw).toLowerCase();
    const match = Array.from(element.options).find((option) => {
      return String(option.value).trim().toLowerCase() === normalized
        || String(option.textContent).trim().toLowerCase() === normalized
        || countryCode(option.value).toLowerCase() === normalizedCode
        || countryCode(option.textContent).toLowerCase() === normalizedCode;
    });

    if (match) {
      element.value = match.value;
      delete element.dataset.previousValue;
    } else {
      element.dataset.previousValue = raw;
      element.value = raw;
    }
  };
  const setText = (id, nextValue) => {
    const element = document.getElementById(id);
    if (element) element.textContent = String(nextValue ?? '-');
  };
  const firstValue = (...ids) => ids.map(value).find((item) => item !== '') || '';
  const firstElement = (...ids) => ids.map((id) => document.getElementById(id)).find(Boolean) || null;
  const queryParam = (name) => new URLSearchParams(window.location.search).get(name) || '';
  const numberValue = (raw, fallback) => {
    const parsed = Number(String(raw ?? '').replace(/[^0-9.-]/g, ''));
    return Number.isFinite(parsed) && parsed > 0 ? parsed : fallback;
  };
  const currentPath = () => window.location.pathname + window.location.search;
  const adminRoleIds = [1, 12, 13, 14, 15];
  const isAdminRole = (roleId) => adminRoleIds.includes(Number(roleId));
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

  initDeliveryLocationSelects();
  initCountrySelects();
  initPaymentOptions();
  initBackButtons();
  initContactModal();
  initCounters();
  initLogin();
  initSessionPanels();
  initAuthNav();
  initLogoutForms();
  initPullCustomer();
  initPackageBlocks();
  initConsigneePicker();
  initQuoteForm();
  initCreateShipmentForm();
  initTrackingForm();
  initTrackingDetailPage();
  initShipmentConfirmationPage();
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

  async function getJson(url, query = {}, options = {}) {
    const headers = {
      Accept: 'application/json',
      'X-CSRF-TOKEN': csrf
    };

    const token = options.token === undefined ? storedToken() : options.token;
    if (token) {
      headers.Authorization = `Bearer ${token}`;
    }

    const params = new URLSearchParams();
    Object.entries(query || {}).forEach(([key, nextValue]) => {
      if (nextValue !== undefined && nextValue !== null && nextValue !== '') {
        params.set(key, nextValue);
      }
    });

    const response = await fetch(params.toString() ? `${url}?${params}` : url, { headers });
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

  function initBackButtons() {
    document.querySelectorAll('[data-go-back]').forEach((button) => {
      button.addEventListener('click', () => {
        if (window.history.length > 1) {
          window.history.back();
          return;
        }

        window.location.href = route('quotePage', '/quote');
      });
    });
  }

  function initDeliveryLocationSelects() {
    const selectors = [
      '#deliveryLocation',
      '#shipmentDeliveryLocation',
      'select[name="delivery_location"]',
      'select.delivery_location'
    ];
    const selects = new Set(selectors.flatMap((selector) => Array.from(document.querySelectorAll(selector))));

    selects.forEach((select) => {
      const previous = normalizeDeliveryLocation(select.value);
      select.innerHTML = '';

      [
        { value: '', label: '-- Select Delivery Location --' },
        { value: 'Pickup in Office', label: 'Pickup in Office' },
        { value: 'Home Delivery', label: 'Home Delivery' }
      ].forEach((option) => {
        const optionElement = document.createElement('option');
        optionElement.value = option.value;
        optionElement.textContent = option.label;
        select.appendChild(optionElement);
      });

      select.value = ['Pickup in Office', 'Home Delivery'].includes(previous) ? previous : '';
      select.dataset.kayDeliveryLocked = '1';
    });
  }

  function initCountrySelects() {
    const selects = Array.from(document.querySelectorAll('[data-country-select], #destCountry, #from_country, #toCountry, #shipmentFromCountry, #shipmentToCountry'));
    if (!selects.length) return;

    const cached = normalizeCountries(storedJson(countryCacheKey, []));
    if (cached.length) {
      populateCountrySelects(selects, cached);
    }

    getJson(route('countries', '/api/kay-paolo/countries'), {}, { token: storedToken() })
      .then((response) => {
        const countries = normalizeCountries(response.countries || response.data?.countries || response.data || []);
        if (!countries.length) return;

        window.localStorage.setItem(countryCacheKey, JSON.stringify(countries));
        populateCountrySelects(selects, countries);
      })
      .catch(() => {
        if (!cached.length) {
          selects.forEach((select) => {
            if (select.options.length === 0) {
              select.innerHTML = '<option value="">Countries unavailable</option>';
            }
          });
        }
      });
  }

  function normalizeCountries(rawCountries) {
    const rows = Array.isArray(rawCountries)
      ? rawCountries.map((country) => ['', country])
      : rawCountries && typeof rawCountries === 'object'
        ? Object.entries(rawCountries)
        : [];

    if (!rows.length && !Array.isArray(rawCountries)) {
      if (!rawCountries || typeof rawCountries !== 'object') return [];
    }

    const seen = new Set();
    return rows
      .map(([key, country]) => {
        if (typeof country === 'string') {
          const code = String(key || '').trim().toUpperCase();
          const name = country.trim();
          if (!code || !name || seen.has(code)) return null;
          seen.add(code);
          return { code, name };
        }

        if (!country || typeof country !== 'object') return null;
        const code = String(country.code || country.alpha_2_code || country.value || key || '').trim().toUpperCase();
        const name = String(country.name || country.country_name || country.label || country.title || '').trim();
        if (!code || !name || seen.has(code)) return null;
        seen.add(code);
        return { code, name };
      })
      .filter(Boolean)
      .sort((a, b) => a.name.localeCompare(b.name));
  }

  function populateCountrySelects(selects, countries) {
    selects.forEach((select) => {
      const previous = select.value || select.dataset.previousValue || '';
      const selectedText = select.options?.[select.selectedIndex]?.text || '';
      const placeholder = firstPlaceholderOption(select);

      select.innerHTML = '';
      if (placeholder) {
        select.appendChild(placeholder);
      }

      countries.forEach((country) => {
        const option = document.createElement('option');
        option.value = country.code;
        option.textContent = country.name;
        select.appendChild(option);
      });

      setSelectValue(select.id, previous || selectedText);
      select.dataset.kayCountriesLoaded = '1';
    });
  }

  function firstPlaceholderOption(select) {
    const first = select.options?.[0];
    if (!first || String(first.value || '').trim() !== '') return null;

    const option = document.createElement('option');
    option.value = '';
    option.textContent = first.textContent || 'Select Country';
    return option;
  }

  function initPaymentOptions() {
    const selects = Array.from(document.querySelectorAll('[data-payment-options], #shipmentPaymentType'));
    if (!selects.length) return;

    const cached = normalizePaymentOptions(storedJson(paymentOptionsCacheKey, []));
    if (cached.length) {
      populatePaymentOptions(selects, cached);
    }

    getJson(route('paymentOptions', '/api/kay-paolo/payment-options'), {}, { token: storedToken() })
      .then((response) => {
        const options = normalizePaymentOptions(response.options || response.payment_options || response.data?.options || response.data || []);
        if (!options.length) return;

        window.localStorage.setItem(paymentOptionsCacheKey, JSON.stringify(options));
        populatePaymentOptions(selects, options);
      })
      .catch(() => {
        if (!cached.length) {
          populatePaymentOptions(selects, [{ value: 'PAID AT AGENT', label: 'Paid at Store' }]);
        }
      });
  }

  function normalizePaymentOptions(rawOptions) {
    const rows = Array.isArray(rawOptions)
      ? rawOptions
      : rawOptions && typeof rawOptions === 'object'
        ? Object.entries(rawOptions).map(([key, option]) => {
          if (typeof option === 'string') return { value: key, label: option };
          if (option && typeof option === 'object') return { value: option.value || option.code || key, ...option };
          return option;
        })
        : [];

    const seen = new Set();
    return rows
      .map((option) => {
        if (typeof option === 'string') {
          return { value: option, label: paymentLabel(option) };
        }

        if (!option || typeof option !== 'object') return null;
        const value = String(option.value || option.code || option.type || option.name || '').trim();
        if (!value || seen.has(value)) return null;

        seen.add(value);
        return { value, label: String(option.label || option.name || paymentLabel(value)).trim() };
      })
      .filter(Boolean);
  }

  function populatePaymentOptions(selects, options) {
    const pending = storedJson(pendingShipmentKey, { payload: {} });
    selects.forEach((select) => {
      const previous = select.value || pending.payload?.payment_type || '';
      select.innerHTML = '';

      options.forEach((option) => {
        const optionElement = document.createElement('option');
        optionElement.value = option.value;
        optionElement.textContent = option.label;
        select.appendChild(optionElement);
      });

      setSelectValue(select.id, previous || 'PAID AT AGENT');
      select.dataset.kayPaymentsLoaded = '1';
    });
  }

  function paymentLabel(rawValue) {
    const labels = {
      'PAID AT AGENT': 'Paid at Store',
      COLLECT: 'Collect',
      'CREDIT OR DEBIT CARD': 'Credit or Debit Card',
      PAYPAL: 'PayPal',
      SQUARE: 'Square',
      SPLIT: 'Split Payment',
      'PARTIAL PAYMENT': 'Partial Payment'
    };
    const value = String(rawValue || '').trim();
    return labels[value] || value.toLowerCase().replace(/(^|\s|-|_)\S/g, (match) => match.toUpperCase()).replace(/[_-]/g, ' ');
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
          throw new Error(response.message || 'Unable to login to Kay Paolo.');
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
          : route('home', '/');
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
      dashboardName.textContent = user.name || 'Kay Paolo user';
      setText('dashboardRole', user.role?.name || 'User');
      setText('dashboardRoleId', user.role_id || '-');
      setText('dashboardEmail', user.email || '-');
      setText('dashboardAccount', user.account_number || user.id || '-');
      const adminAccess = document.getElementById('dashboardAdminAccess');
      if (adminAccess) adminAccess.hidden = !isAdminRole(user.role_id);
      const adminAction = document.getElementById('dashboardAdminAction');
      if (adminAction) adminAction.hidden = !isAdminRole(user.role_id);
    }
  }

  function initAuthNav() {
    const authLink = document.querySelector('[data-auth-link]');
    if (!authLink) return;

    if (storedToken()) {
      authLink.textContent = 'My Profile';
      authLink.href = route('account', '/account');
    } else {
      authLink.textContent = 'Login';
      authLink.href = route('loginPage', '/login');
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
        result.textContent = 'Searching customer records...';
      }

      try {
        const response = await postJson(route('fetchUserForQuote', '/api/kay-paolo/fetch-user-for-quote'), {
          phone_or_account: lookup,
          customer: lookup
        });
        window.localStorage.setItem('kayPaoloQuoteCustomer', JSON.stringify(response));
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

  function initConsigneePicker() {
    const radios = document.querySelectorAll('input[name="consigneeType"]');
    const existingField = document.getElementById('existingConsigneeSelectField');
    const existingSelect = document.getElementById('existingConsignee');
    const hiddenConsigneeId = document.getElementById('consignee_id');
    if (!radios.length || !existingField || !existingSelect) return;

    const result = document.getElementById('existingConsigneeResult');
    const consigneesById = new Map();
    let loaded = false;
    let loading = false;

    const quoteCustomer = storedJson('kayPaoloQuoteCustomer', {});
    const storedCustomerId = String(quoteCustomer.quote_user_id || quoteCustomer.user_id || quoteCustomer.customer?.id || '');
    const requestedCustomerId = queryParam('customer');
    if (quoteCustomer?.customer && (!requestedCustomerId || storedCustomerId === requestedCustomerId)) {
      applyCustomerToQuoteForm(quoteCustomer.customer);
      if (!value('quoteUserId')) {
        setValue('quoteUserId', quoteCustomer.quote_user_id || quoteCustomer.user_id || quoteCustomer.customer.id);
      }
    }

    const clearConsigneeFields = () => {
      ['toName', 'toPhone', 'toHomePhone', 'toCountry', 'toZip', 'toAddress', 'toApt', 'toCity', 'toState'].forEach((id) => {
        const field = document.getElementById(id);
        if (field) field.value = '';
      });
      if (hiddenConsigneeId) hiddenConsigneeId.value = '';
    };

    const showMessage = (message, isError = false) => {
      if (!result) return;
      result.className = isError ? 'api-inline-result api-alert error' : 'api-inline-result success';
      result.textContent = message;
    };

    const buildLookupPayload = () => {
      const customerId = firstValue('quoteUserId') || queryParam('customer');
      const lookup = queryParam('lookup');

      return {
        user_id: customerId || undefined,
        quote_user_id: customerId || undefined,
        phone_or_account: lookup || undefined
      };
    };

    const loadConsignees = async (force = false) => {
      if (loaded && !force) return;
      if (loading) return;
      if (!storedToken()) return;

      const payload = buildLookupPayload();
      if (!payload.user_id && !payload.phone_or_account) {
        showMessage('Pull a customer first, then select an existing consignee.', true);
        return;
      }

      loading = true;
      existingSelect.disabled = true;
      existingSelect.innerHTML = '<option value="">Loading consignees...</option>';
      showMessage('Loading existing consignees...');

      try {
        const response = await postJson(route('consigneeList', '/api/kay-paolo/consignee-list'), payload);
        const customer = response.customer || response.data?.customer;
        if (customer) applyCustomerToQuoteForm(customer);

        const consignees = response.consignees || response.data?.consignees || [];
        consigneesById.clear();
        existingSelect.innerHTML = '<option value="">-- Select Existing Consignee --</option>';

        consignees.forEach((consignee) => {
          const id = String(consignee.id || consignee.consignee_id || '');
          if (!id) return;
          consigneesById.set(id, consignee);
          const option = document.createElement('option');
          option.value = id;
          option.textContent = [
            consignee.consignee_name || consignee.name || 'Unnamed consignee',
            consignee.consignee_phone || consignee.phone,
            consignee.consignee_address_city || consignee.city,
            consignee.consignee_address_country || consignee.country
          ].filter(Boolean).join(' - ');
          existingSelect.appendChild(option);
        });

        loaded = true;
        existingSelect.disabled = false;
        showMessage(consignees.length ? `${consignees.length} consignee(s) loaded.` : 'No consignees found for this customer.', consignees.length === 0);
      } catch (error) {
        existingSelect.innerHTML = '<option value="">-- Select Existing Consignee --</option>';
        existingSelect.disabled = false;
        showMessage(error.message, true);
      } finally {
        loading = false;
      }
    };

    radios.forEach((radio) => {
      radio.addEventListener('change', () => {
        if (radio.value === 'existing' && radio.checked) {
          existingField.style.display = 'block';
          clearConsigneeFields();
          loadConsignees();
        }

        if (radio.value === 'new' && radio.checked) {
          existingField.style.display = 'none';
          existingSelect.value = '';
          clearConsigneeFields();
        }
      });
    });

    existingSelect.addEventListener('change', () => {
      const consignee = consigneesById.get(existingSelect.value);
      if (consignee) {
        applyConsigneeToQuoteForm(consignee);
      } else {
        clearConsigneeFields();
      }
    });

    if (firstValue('quoteUserId') || queryParam('customer') || queryParam('lookup')) {
      loadConsignees();
    }
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
        const type = block.querySelector('.pkg-flat-rate-type');
        if (flat) flat.id = `pkgFlatRate${number}`;
        if (count) count.id = `pkgCount${number}`;
        if (type) type.id = `pkgFlatRateType${number}`;
        const flatLabel = block.querySelector('label[for^="pkgFlatRate"]');
        const countLabel = block.querySelector('label[for^="pkgCount"]');
        const typeLabel = block.querySelector('label[for^="pkgFlatRateType"]');
        if (flatLabel) flatLabel.setAttribute('for', `pkgFlatRate${number}`);
        if (countLabel) countLabel.setAttribute('for', `pkgCount${number}`);
        if (typeLabel) typeLabel.setAttribute('for', `pkgFlatRateType${number}`);
        toggleFlatRateField(block, Boolean(flat?.checked));
      });
    };

    container.addEventListener('click', (event) => {
      const addButton = event.target.closest('.add-package-btn');
      const removeButton = event.target.closest('.remove-package-btn');

      if (addButton) {
        const lastBlock = container.querySelector('.package-block:last-child');
        const clone = lastBlock.cloneNode(true);
        clone.querySelectorAll('input').forEach((input) => {
          if (input.classList.contains('pkg-flat-rate-hidden')) input.value = '0';
          else if (input.classList.contains('pkg-flat-rate-type-hidden')) input.value = '';
          else if (input.type === 'checkbox') input.checked = false;
          else input.value = '';
          input.disabled = false;
        });
        clone.querySelectorAll('select').forEach((select) => {
          select.selectedIndex = 0;
        });
        clone.querySelectorAll('.pkg-flat-rate-note').forEach((note) => {
          note.textContent = '';
          note.className = 'api-inline-result pkg-flat-rate-note';
        });
        container.appendChild(clone);
        refreshPackages();
      }

      if (removeButton) {
        removeButton.closest('.package-block')?.remove();
        refreshPackages();
      }
    });

    container.addEventListener('change', (event) => {
      const flatCheckbox = event.target.closest('.pkg-flat-rate');
      const flatType = event.target.closest('.pkg-flat-rate-type');

      if (flatCheckbox) {
        toggleFlatRateField(flatCheckbox.closest('.package-block'), flatCheckbox.checked);
      }

      if (flatType) {
        applyFlatRateDefaults(flatType.closest('.package-block'), flatType.selectedOptions[0]);
      }
    });

    ['toCountry', 'to_country'].forEach((id) => {
      document.getElementById(id)?.addEventListener('change', () => resetPackageFlatRates(container, true));
    });
    document.getElementById('from_state')?.addEventListener('change', () => reloadVisibleFlatRateFields(container));

    refreshPackages();
  }

  function resetPackageFlatRates(container, clearDimensions = false) {
    container.querySelectorAll('.package-block').forEach((block) => {
      const checkbox = block.querySelector('.pkg-flat-rate');
      if (checkbox) checkbox.checked = false;
      toggleFlatRateField(block, false);

      if (clearDimensions) {
        ['.pkg-weight', '.pkg-length', '.pkg-width', '.pkg-height'].forEach((selector) => {
          const input = block.querySelector(selector);
          if (input) {
            input.value = '';
          }
        });
      }
    });
  }

  function toggleFlatRateField(block, checked) {
    if (!block) return;

    const field = block.querySelector('.pkg-flat-rate-field');
    const select = block.querySelector('.pkg-flat-rate-type');
    const hiddenFlatRate = block.querySelector('.pkg-flat-rate-hidden');
    const hiddenFlatRateType = block.querySelector('.pkg-flat-rate-type-hidden');
    const note = block.querySelector('.pkg-flat-rate-note');
    if (!field || !select) return;

    field.style.display = checked ? 'block' : 'none';
    select.required = checked;
    if (hiddenFlatRate) hiddenFlatRate.disabled = checked;
    if (hiddenFlatRateType) hiddenFlatRateType.disabled = checked;

    if (checked) {
      loadFlatRatesForBlock(block);
      return;
    }

    select.value = '';
    select.disabled = false;
    if (note) {
      note.textContent = '';
      note.className = 'api-inline-result pkg-flat-rate-note';
    }
    setFlatRateDimensionReadonly(block, false);
  }

  function reloadVisibleFlatRateFields(container) {
    container.querySelectorAll('.package-block').forEach((block) => {
      if (block.querySelector('.pkg-flat-rate')?.checked) {
        loadFlatRatesForBlock(block, true);
      }
    });
  }

  async function loadFlatRatesForBlock(block, force = false) {
    const select = block.querySelector('.pkg-flat-rate-type');
    const note = block.querySelector('.pkg-flat-rate-note');
    if (!select) return;

    const toCountry = firstElement('toCountry', 'to_country');
    const toCountryName = selectedCountryName(toCountry);
    const toCountryCode = countryCode(toCountry?.value || toCountryName);
    const fromState = firstValue('from_state');
    const cacheKey = `${toCountryCode || 'any'}:${fromState || 'any'}`;

    if (!force && select.dataset.loadedFor === cacheKey && select.options.length > 1) return;

    const setNote = (message, isError = false) => {
      if (!note) return;
      note.className = isError ? 'api-inline-result api-alert error pkg-flat-rate-note' : 'api-inline-result success pkg-flat-rate-note';
      note.textContent = message;
    };

    select.disabled = true;
    select.innerHTML = '<option value="">Loading flat rate items...</option>';
    setNote('Loading live flat rate items...');

    try {
      const response = await postJson(route('flatRates', '/api/kay-paolo/flat-rates'), {
        to_country: toCountryCode || undefined,
        from_state: fromState || undefined,
        to: {
          country: toCountryCode || undefined,
          country_name: toCountryName || undefined
        },
        from: {
          state: fromState || undefined
        }
      });
      const options = normalizeFlatRateOptions(response);
      flatRateCache.set(cacheKey, options);
      populateFlatRateSelect(select, options);
      select.dataset.loadedFor = cacheKey;
      select.disabled = false;
      setNote(options.length ? `${options.length} flat rate item(s) loaded.` : 'No flat rate items available for this destination.', options.length === 0);
    } catch (error) {
      populateFlatRateSelect(select, []);
      select.dataset.loadedFor = cacheKey;
      select.disabled = false;
      setNote('Unable to load live flat rate items from the shipping API.', true);
    }
  }

  function normalizeFlatRateOptions(response) {
    const options = [
      response?.options,
      response?.data?.options,
      response?.flat_rates,
      response?.data?.flat_rates,
      response?.flat_rate,
      response?.data?.flat_rate,
      response?.flatRates,
      response?.data?.flatRates,
      response?.flatrates,
      response?.data?.flatrates,
      response?.rates,
      response?.data?.rates,
      response?.items,
      response?.data?.items,
      response?.all_options,
      response?.data?.all_options,
      response?.data?.data,
      response?.data
    ].find((candidate) => {
      if (Array.isArray(candidate)) return candidate.length > 0;
      return candidate && typeof candidate === 'object' && Object.keys(candidate).length > 0;
    }) || [];

    const rows = Array.isArray(options)
      ? options.map((option) => ['', option])
      : Object.entries(options);

    return rows
      .map(([key, option]) => normalizeFlatRateOption(option, key))
      .filter((option) => option.slug && !option.restricted);
  }

  function normalizeFlatRateOption(option, key = '') {
    if (typeof option === 'string') {
      return {
        slug: key || option,
        label: option,
        group: 'Flat Rate',
        price: '',
        readonly: true,
        restricted: false,
        defaults: {
          package_count_ind: 1,
          weight: '',
          length: '',
          width: '',
          height: ''
        }
      };
    }

    if (!option || typeof option !== 'object') {
      return {
        slug: '',
        label: '',
        group: 'Flat Rate',
        price: '',
        readonly: true,
        restricted: true,
        defaults: {
          package_count_ind: 1,
          weight: '',
          length: '',
          width: '',
          height: ''
        }
      };
    }

    const defaults = option.default_dimensions || option.dimensions || {};
    const status = String(option.status || option.state || '').toLowerCase();

    return {
      slug: option.slug || option.value || option.typeCode || option.type_code || option.code || option.shipment_type || option.id || key || '',
      label: option.label || option.name || option.title || option.description || option.typeCode || option.slug || 'Flat Rate Item',
      group: option.group || option.category || 'Flat Rate',
      price: option.price || option.amount || option.rate || option.total || '',
      readonly: option.readonly_dimensions !== false,
      restricted: option.restricted === true || option.is_restricted === true || option.disabled === true || ['restricted', 'inactive', 'disabled'].includes(status),
      defaults: {
        package_count_ind: defaults.package_count_ind || defaults.count || 1,
        weight: defaults.weight || option.weight || '',
        length: defaults.length || option.length || '',
        width: defaults.width || option.width || '',
        height: defaults.height || option.height || ''
      }
    };
  }

  function populateFlatRateSelect(select, options) {
    select.innerHTML = '<option value="">-- Select Flat Rate Item --</option>';

    const groups = new Map();
    options.forEach((option) => {
      const groupName = option.group || 'Flat Rate';
      if (!groups.has(groupName)) {
        const group = document.createElement('optgroup');
        group.label = groupName;
        groups.set(groupName, group);
        select.appendChild(group);
      }

      const optionElement = document.createElement('option');
      optionElement.value = option.slug;
      optionElement.textContent = option.price ? `${option.label} - ${moneyText(option.price)}` : option.label;
      optionElement.dataset.weight = option.defaults.weight;
      optionElement.dataset.length = option.defaults.length;
      optionElement.dataset.width = option.defaults.width;
      optionElement.dataset.height = option.defaults.height;
      optionElement.dataset.count = option.defaults.package_count_ind;
      optionElement.dataset.readonlyDimensions = option.readonly ? '1' : '0';
      groups.get(groupName).appendChild(optionElement);
    });
  }

  function applyFlatRateDefaults(block, option) {
    if (!block || !option || !option.value) {
      setFlatRateDimensionReadonly(block, false);
      return;
    }

    setBlockValue(block, '.pkg-count', option.dataset.count);
    setBlockValue(block, '.pkg-weight', option.dataset.weight);
    setBlockValue(block, '.pkg-length', option.dataset.length);
    setBlockValue(block, '.pkg-width', option.dataset.width);
    setBlockValue(block, '.pkg-height', option.dataset.height);
    setFlatRateDimensionReadonly(block, option.dataset.readonlyDimensions === '1');
  }

  function setBlockValue(block, selector, nextValue) {
    const element = block?.querySelector(selector);
    if (element && nextValue !== undefined && nextValue !== null && nextValue !== '') {
      element.value = nextValue;
    }
  }

  function setFlatRateDimensionReadonly(block, readonly) {
    block?.querySelectorAll('.pkg-weight,.pkg-length,.pkg-width,.pkg-height').forEach((input) => {
      input.readOnly = readonly;
      input.classList.toggle('is-readonly', readonly);
    });
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
        partner: quoteCardPartner(card),
        payment_type: 'PAID AT AGENT',
        deliveryEstimatePrice: quoteCardTotal(card) || undefined,
        deliveryEstimateDate: quoteCardEta(card) || undefined,
        delivery_option: quoteCardService(card) || undefined,
        selected_shipper: quoteCardService(card) || undefined
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
        const mergedPayload = await ensureConsigneeForShipment(mergeShipmentFormPayload(pending.payload || {}));
        const payload = buildBocicotShipmentPayload(mergedPayload);
        const response = await postJson(route('shipping', '/api/kay-paolo/shipping'), payload);
        await queueShipmentEmailNotifications(response, payload);
        window.localStorage.setItem(shipmentResponseKey, JSON.stringify({ response, payload, selected: pending.card || {} }));
        window.location.href = route('shipmentConfirmation', '/shipment-confirmation');
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

  function initShipmentConfirmationPage() {
    const confirmationRoot = document.querySelector('[data-shipment-confirmation]');
    if (!confirmationRoot) return;

    const shipment = storedJson(shipmentResponseKey, {});
    const response = shipment.response || {};
    const payload = shipment.payload || {};
    const selected = shipment.selected || {};
    const data = buildShipmentDocumentData(response, payload, selected);
    const params = new URLSearchParams(window.location.search);
    const shipmentNo = params.get('id') || data.tracking || data.documentNumber || 'Pending';

    setText('shipmentNoDisplay', shipmentNo);
    setText('packageAmountDisplay', `${data.packageCount} package${Number(data.packageCount) === 1 ? '' : 's'}`);
    setDocumentHref('openLabelBtn', route('shipmentLabel', '/shipment-label'), data, shipmentNo);
    setDocumentHref('openReceiptBtn', route('shipmentReceipt', '/shipment-receipt'), data, shipmentNo);
  }

  function setDocumentHref(id, baseUrl, data, shipmentNo) {
    const link = document.getElementById(id);
    if (!link) return;

    const params = new URLSearchParams();
    if (data.shipmentId) params.set('shipment_id', data.shipmentId);
    if (data.documentNumber && data.documentNumber !== 'Pending') params.set('invoice', data.documentNumber);
    if (shipmentNo && shipmentNo !== 'Pending') params.set('id', shipmentNo);
    link.href = params.toString() ? `${baseUrl}?${params}` : baseUrl;
  }

  function initReceiptPages() {
    const documentRoot = document.querySelector('[data-shipment-document]');
    const awbSheet = document.querySelector('.awb-sheet');
    if (!documentRoot && !awbSheet) return;

    const shipment = storedJson(shipmentResponseKey, {});
    const response = shipment.response || {};
    const payload = shipment.payload || {};
    const selected = shipment.selected || {};
    const data = buildShipmentDocumentData(response, payload, selected);

    setText('documentNumber', data.documentNumber);
    setText('documentDate', data.date);
    setText('documentStatus', data.status);
    setText('documentPaymentType', data.paymentType);
    setText('documentTracking', data.tracking);
    setText('documentShipperName', data.shipperName);
    setText('documentShipperAddress', data.shipperAddress);
    setText('documentShipperContact', data.shipperContact);
    setText('documentConsigneeName', data.consigneeName);
    setText('documentConsigneeAddress', data.consigneeAddress);
    setText('documentConsigneeContact', data.consigneeContact);
    setText('documentNotes', data.notes);
    setText('documentFreight', moneyText(data.freight));
    setText('documentInsurance', moneyText(data.insurance));
    setText('documentHomeDelivery', moneyText(data.homeDelivery));
    setText('documentTax', moneyText(data.tax));
    setText('documentTotal', moneyText(data.total));
    renderDocumentItems(data.items);

    setText('receiptA4TrackingNumber', data.tracking);
    setText('receiptA4Barcode', `*${data.tracking}*`);
    setText('receiptA4LargeNumber', data.tracking);
    setText('receiptA4Shipper', `${data.shipperName}\n${data.shipperAddress}\n${data.shipperContact}`);
    setText('receiptA4Receiver', `${data.consigneeName}\n${data.consigneeAddress}\n${data.consigneeContact}`);
    setText('receiptA4Package', `${data.description} / ${data.packageCount} package(s) / ${data.totalWeight} lb`);
    setText('receiptA4PaymentType', data.paymentType);
    setText('receiptA4Total', moneyText(data.total));
  }

  function buildShipmentDocumentData(response, payload, selected) {
    const responseData = response.data || {};
    const shipping = response.shipping_data || response.shipping || responseData.shipping_data || responseData.shipping || {};
    const tracking = response.tracking_number
      || response.invoice_num
      || response.awb
      || responseData.tracking_number
      || shipping.tracking_number
      || shipping.invoice_num
      || payload.tracking_number
      || 'Pending';
    const documentNumber = response.invoice_num
      || responseData.invoice_num
      || shipping.invoice_num
      || tracking;
    const description = payload.package_description || shipping.package_description || '';
    const items = packageRowsFromPayload(payload, description);
    const totalWeight = items.reduce((sum, item) => sum + numberValue(item.weight, 0), 0) || 1;

    return {
      shipmentId: response.shipment_id
        || response.shipping_id
        || response.id
        || responseData.shipment_id
        || responseData.shipping_id
        || responseData.id
        || shipping.id
        || shipping.shipment_id
        || '',
      documentNumber,
      tracking,
      date: readableDate(response.created_at || responseData.created_at || shipping.created_at || new Date()),
      status: response.status_name || response.status || response.message || responseData.status || shipping.status || 'Booked',
      paymentType: payload.payment_type || shipping.payment_type || 'PAID AT AGENT',
      shipperName: payload.from_name || shipping.shipper_name || storedUser().name || 'Kay Paolo Customer',
      shipperAddress: [payload.from_address, payload.from_city, payload.from_state, payload.from_zip, payload.from_country_name].filter(Boolean).join(', ') || '414 Main St, Asbury Park, NJ 07712',
      shipperContact: [payload.from_phone, payload.from_email || storedUser().email].filter(Boolean).join(' / ') || 'info@kaypaoloshipping.com',
      consigneeName: payload.to_name || payload.consignee_name || shipping.consignee_name || 'Destination Customer',
      consigneeAddress: [payload.to_address, payload.to_apt, payload.to_city, payload.to_state, payload.to_zip, payload.to_country_name].filter(Boolean).join(', ') || 'Destination address pending',
      consigneeContact: [payload.to_phone_1 || payload.consignee_phone, payload.to_phone_2].filter(Boolean).join(' / ') || 'Phone pending',
      description,
      items,
      packageCount: packagePieceCount(payload) || items.reduce((sum, item) => sum + numberValue(item.count, 0), 0) || 1,
      totalWeight,
      freight: selected.freight || response.freight || responseData.freight || shipping.freight || 0,
      insurance: selected.insurance || response.insurance || responseData.insurance || shipping.insurance || 0,
      homeDelivery: selected.home_delivery || selected.delivery || response.home_delivery || responseData.home_delivery || shipping.home_delivery || 0,
      tax: selected.tax || response.tax || responseData.tax || shipping.tax || 0,
      total: selected.total || selected.price || selected.amount || payload.deliveryEstimatePrice || response.total || responseData.total || shipping.total || 0,
      notes: response.message || responseData.message || 'Thank you for shipping with Kay Paolo Shipping.'
    };
  }

  function packageRowsFromPayload(payload, description) {
    const dimensions = payload.dimensions || {};
    const counts = Array.isArray(dimensions.package_count_ind) ? dimensions.package_count_ind : [payload.package_count || 1];
    const weights = Array.isArray(dimensions.weight) ? dimensions.weight : [payload.package_weight || 1];
    const lengths = Array.isArray(dimensions.length) ? dimensions.length : [payload.package_length || 1];
    const widths = Array.isArray(dimensions.width) ? dimensions.width : [payload.package_width || 1];
    const heights = Array.isArray(dimensions.height) ? dimensions.height : [payload.package_height || 1];
    const declaredValue = payload.total_value || payload.package_value || 0;
    const rowCount = Math.max(counts.length, weights.length, lengths.length, widths.length, heights.length, 1);

    return Array.from({ length: rowCount }, (_, index) => ({
      description,
      count: counts[index] || 1,
      weight: weights[index] || 1,
      dimensions: `${lengths[index] || 1} x ${widths[index] || 1} x ${heights[index] || 1}`,
      value: declaredValue
    }));
  }

  function renderDocumentItems(items) {
    const tbody = document.getElementById('documentItems');
    if (!tbody) return;

    tbody.innerHTML = items.map((item) => `
      <tr>
        <td>${escapeHtml(item.description)}</td>
        <td>${escapeHtml(item.count)}</td>
        <td>${escapeHtml(item.weight)} lb</td>
        <td>${escapeHtml(item.dimensions)}</td>
        <td>${escapeHtml(moneyText(item.value))}</td>
      </tr>
    `).join('');
  }

  function moneyText(amount) {
    if (amount === undefined || amount === null || amount === '') return 'USD 0.00';
    const raw = String(amount).trim();
    if (/^usd\s/i.test(raw)) return raw;
    if (raw.startsWith('$')) return `USD ${raw.slice(1)}`;
    return `USD ${raw}`;
  }

  function readableDate(dateValue) {
    const date = dateValue instanceof Date ? dateValue : new Date(dateValue);
    if (Number.isNaN(date.getTime())) return String(dateValue || '');

    return date.toLocaleDateString(undefined, {
      year: 'numeric',
      month: 'short',
      day: '2-digit'
    });
  }

  function initShipmentHistoryFilters() {
    const list = document.getElementById('historyCardList');
    if (!list) return;

    const result = document.getElementById('historyResult');
    const loader = document.getElementById('historyLoader');

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

    const load = async () => {
      if (!result || !storedToken()) return;

      result.innerHTML = historyNoticeCard('Loading', 'Shipment history', 'Loading shipment history from your account.');
      if (loader) loader.hidden = false;

      try {
        const response = await postJson(route('shippingHistory', '/api/kay-paolo/shipping-history'), {
          limit: firstValue('entriesSelect') || 100,
          created_in: firstValue('timeSelect'),
          search: firstValue('searchInput'),
          user_id: storedUser().id || storedUser().account_number || undefined
        });

        if (response.html) {
          const cards = extractHistoryCards(response.html);
          if (cards) {
            result.innerHTML = cards;
            updateHistoryBadgesFromCards();
          } else {
            renderHistoryRows(result, normalizeHistoryRows(response));
          }
        } else {
          renderHistoryRows(result, normalizeHistoryRows(response));
        }
        filter();
      } catch (error) {
        showError(result, error.message);
      } finally {
        if (loader) loader.hidden = true;
      }
    };

    document.getElementById('searchInput')?.addEventListener('input', filter);
    document.getElementById('entriesSelect')?.addEventListener('change', load);
    document.getElementById('timeSelect')?.addEventListener('change', load);
    document.querySelectorAll('.status-filter,.category-filter').forEach((input) => input.addEventListener('change', filter));

    list.addEventListener('click', (event) => {
      const quickView = event.target.closest('.quick-view-link');
      const moreLink = event.target.closest('.more-link');
      const card = event.target.closest('.shipment-card[data-status]');

      if (quickView && card) {
        event.preventDefault();
        const details = card.querySelector('.history-card-details');
        if (details) {
          details.classList.add('active');
          details.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
      }

      if (moreLink && card) {
        event.preventDefault();
        const tracking = card.dataset.tracking || card.querySelector('.history-card-col h4')?.textContent?.trim() || '';
        window.location.href = `${route('trackingDetail', '/tracking-detail')}${tracking ? `?id=${encodeURIComponent(tracking)}` : ''}`;
      }
    });

    load();
  }

  function normalizeHistoryRows(response) {
    const candidates = [
      response?.shippings,
      response?.shipping_history,
      response?.history,
      response?.data?.shippings,
      response?.data?.shipping_history,
      response?.data?.history,
      response?.data?.data,
      response?.data
    ].find((item) => Array.isArray(item) || (item && typeof item === 'object'));

    if (!candidates) return [];

    return Array.isArray(candidates) ? candidates : Object.values(candidates);
  }

  function renderHistoryRows(container, rows) {
    if (!rows.length) {
      container.innerHTML = historyNoticeCard('No Shipments', 'Shipment history', 'No shipments found for this account.');
      return;
    }

    container.innerHTML = rows.map((row) => {
      const tracking = historyField(row, ['tracking_number', 'trackingNumber', 'tracking', 'invoice_num', 'invoice', 'invoice_number', 'awb', 'id'], '-');
      const status = historyStatus(row);
      const createdBy = historyField(row, ['created_by', 'shipper_name', 'from_name'], 'Kay Paolo Shipping');
      const date = readableDate(historyField(row, ['created_at', 'shipment_date', 'date'], ''));
      const option = historyField(row, ['delivery_option', 'deliveryOption', 'selected_shipper', 'service_name'], 'Shipping service');
      const description = historyField(row, ['package_description', 'packageDescription', 'description'], 'Package details pending');
      const fromAddress = historyAddress(row, 'from');
      const toAddress = historyAddress(row, 'to');
      const category = historyCategory(row);
      const fromName = historyField(row, ['shipper_name', 'from_name'], 'Sender');
      const toName = historyField(row, ['consignee_name', 'to_name'], 'Customer');
      const baseFreight = historyMoney(row, ['base_freight', 'freight', 'freight_amount', 'shipping_cost', 'deliveryEstimatePrice'], 'USD 0.00');
      const insurance = historyMoney(row, ['insurance', 'insurance_amount'], 'USD 0.00');
      const tax = historyMoney(row, ['tax', 'tax_amount'], 'USD 0.00');
      const totalPaid = historyMoney(row, ['total_paid', 'total', 'amount', 'deliveryEstimatePrice'], 'USD 0.00');
      const weight = historyWeight(row);
      const dimensions = historyDimensions(row);
      const packageCount = historyPackageCount(row);
      const agentId = historyField(row, ['agent_id', 'assigned_agent_id', 'created_by_id', 'created_by'], '-');
      const pickupDate = readableDate(historyField(row, ['scheduled_pickup', 'pickup_date', 'plannedShippingDateAndTime'], ''));
      const searchPool = [tracking, status, createdBy, fromName, toName, option, description, fromAddress, toAddress].join(' ');
      const cardStyle = status === 'Delivered' ? 'margin-bottom: 0; border-top-color: #059669' : 'margin-bottom: 0';

      return `
        <div class="shipment-card" data-status="${escapeHtml(status)}" data-category="${escapeHtml(category)}" data-tracking="${escapeHtml(tracking)}" data-search-pool="${escapeHtml(searchPool)}" style="${cardStyle}">
          <div class="history-card-main">
            <div class="history-card-col">
              <h4 style="color: var(--navy-800)">${escapeHtml(tracking)}</h4>
              <span class="status-lbl">${escapeHtml(status)}</span>
              <span class="meta-label">Created By</span>
              <span class="meta-val">${escapeHtml(createdBy)}</span>
            </div>
            <div class="history-card-col">
              <span class="meta-label">Shipment Date</span>
              <span class="meta-val" style="font-weight: 700">${escapeHtml(date || '-')}</span>
              <span class="meta-label">Delivery Option</span>
              <span class="meta-val">${escapeHtml(option)}</span>
              <span class="meta-label">Description</span>
              <span class="meta-val" style="font-weight: 600">${escapeHtml(description)}</span>
            </div>
            <div class="history-card-col">
              <div class="address-block">
                <span class="meta-label">Ship From</span>
                <strong>${escapeHtml(fromName)}</strong>
                ${escapeHtml(fromAddress)}
              </div>
            </div>
            <div class="history-card-col">
              <div class="address-block">
                <span class="meta-label">Ship To</span>
                <strong>${escapeHtml(toName)}</strong>
                ${escapeHtml(toAddress)}
              </div>
              <div class="quick-view-link" role="button" tabindex="0">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block; vertical-align:middle; margin-right:4px"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                Quick View
              </div>
            </div>
          </div>
          <div class="history-card-details">
            <div class="history-detail-grid">
              <div>
                <strong>Detailed Pricing</strong>
                <div style="margin-top:8px; line-height: 1.6">
                  Base Freight: ${escapeHtml(baseFreight)}<br>
                  Insurance: ${escapeHtml(insurance)}<br>
                  Tax: ${escapeHtml(tax)}<br>
                  <strong>Total Paid: ${escapeHtml(totalPaid)}</strong>
                </div>
              </div>
              <div>
                <strong>Package Specs</strong>
                <div style="margin-top:8px; line-height: 1.6">
                  Weight: ${escapeHtml(weight)}<br>
                  Dimensions: ${escapeHtml(dimensions)}<br>
                  Load Type: ${escapeHtml(packageCount)} package${packageCount === '1' ? '' : 's'}
                </div>
              </div>
              <div>
                <strong>Carrier Tracking Details</strong>
                <div style="margin-top:8px; line-height: 1.6">
                  Assigned Agent ID: ${escapeHtml(agentId)}<br>
                  Scheduled Pickup: ${escapeHtml(pickupDate || '-')}<br>
                  Status Log: ${escapeHtml(status)} (${escapeHtml(date || '-')})
                </div>
              </div>
            </div>
          </div>
          <div class="history-card-footer">
            <span class="more-link">
              More
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle; margin-left:2px"><polyline points="6 9 12 15 18 9"/></svg>
            </span>
          </div>
        </div>
      `;
    }).join('');

    updateHistoryBadgesFromRows(rows);
  }

  function historyField(row, keys, fallback = '') {
    for (const key of keys) {
      const value = row?.[key] ?? row?.shipping?.[key] ?? row?.shipping_data?.[key] ?? row?.quote?.[key] ?? row?.details?.[key];
      if (value !== undefined && value !== null && value !== '') {
        return String(value);
      }
    }

    return fallback;
  }

  function historyAddress(row, side) {
    const prefix = side === 'from' ? 'shipper' : 'consignee';
    const alt = side === 'from' ? 'from' : 'to';
    return [
      historyField(row, [`${prefix}_address`, `${alt}_address`]),
      historyField(row, [`${prefix}_city`, `${prefix}_address_city`, `${alt}_city`]),
      historyField(row, [`${prefix}_state`, `${prefix}_address_state`, `${alt}_state`]),
      historyField(row, [`${prefix}_zip`, `${prefix}_address_zip`, `${alt}_zip`]),
      historyField(row, [`${prefix}_country`, `${prefix}_address_country`, `${alt}_country_name`, `${alt}_country`])
    ].filter(Boolean).join(', ') || 'Address pending';
  }

  function historyCategory(row) {
    const from = countryCode(historyField(row, ['shipper_country', 'from_country', 'from_country_name']));
    const to = countryCode(historyField(row, ['consignee_country', 'consignee_address_country', 'to_country', 'to_country_name']));
    return from === 'US' && to === 'US' ? 'Domestic' : 'International';
  }

  function historyStatus(row) {
    const raw = historyField(row, ['status_name', 'shipping_status', 'status'], 'Ready to Ship');
    const status = raw.toLowerCase();

    if (status.includes('void')) return 'Voided';
    if (status.includes('not deliver')) return 'Not Deliverable';
    if (status.includes('deliver')) return 'Delivered';
    if (status.includes('available')) return 'Available';
    if (status.includes('delay')) return 'Delayed';
    if (status.includes('custom')) return 'Customs';
    if (status.includes('transit')) return 'In Transit';
    if (status.includes('pick')) return 'Picked Up';
    if (status.includes('ready')) return 'Ready to Ship';

    return raw;
  }

  function historyMoney(row, keys, fallback) {
    const amount = historyField(row, keys, '');
    return amount === '' ? fallback : moneyText(amount);
  }

  function historyMaybeJson(value) {
    if (!value) return null;
    if (typeof value === 'object') return value;

    try {
      return JSON.parse(value);
    } catch (error) {
      return null;
    }
  }

  function historyPackageList(row) {
    const packages = historyMaybeJson(row?.packages || row?.package || row?.shipping?.packages || row?.shipping?.package);
    if (Array.isArray(packages)) return packages;
    return [];
  }

  function historyPackageCount(row) {
    const explicit = historyField(row, ['package_count', 'packages_count', 'piece_count'], '');
    if (explicit) return explicit;
    const packages = historyPackageList(row);
    return String(packages.length || 1);
  }

  function historyWeight(row) {
    const explicit = historyField(row, ['weight', 'total_weight', 'package_weight'], '');
    if (explicit) return `${explicit} lbs`;

    const dimensions = historyMaybeJson(row?.dimensions || row?.shipping?.dimensions);
    const weights = Array.isArray(dimensions?.weight) ? dimensions.weight : [];
    if (weights.length) {
      const total = weights.reduce((sum, item) => sum + Number(item || 0), 0);
      return `${total || weights[0]} lbs`;
    }

    const packages = historyPackageList(row);
    const total = packages.reduce((sum, item) => sum + Number(item.weight || 0), 0);
    return total ? `${total} lbs` : '-';
  }

  function historyDimensions(row) {
    const explicit = historyField(row, ['dimension', 'dimensions_text', 'package_dimensions'], '');
    if (explicit) return explicit;

    const dimensions = historyMaybeJson(row?.dimensions || row?.shipping?.dimensions);
    const length = Array.isArray(dimensions?.length) ? dimensions.length[0] : dimensions?.length;
    const width = Array.isArray(dimensions?.width) ? dimensions.width[0] : dimensions?.width;
    const height = Array.isArray(dimensions?.height) ? dimensions.height[0] : dimensions?.height;
    if (length && width && height) return `${length}" x ${width}" x ${height}"`;

    const firstPackage = historyPackageList(row)[0];
    if (firstPackage?.dimensions?.length && firstPackage?.dimensions?.width && firstPackage?.dimensions?.height) {
      return `${firstPackage.dimensions.length}" x ${firstPackage.dimensions.width}" x ${firstPackage.dimensions.height}"`;
    }

    return '-';
  }

  function historyNoticeCard(title, status, description) {
    return `
      <div class="shipment-card" style="margin-bottom: 0">
        <div class="history-card-main">
          <div class="history-card-col">
            <h4 style="color: var(--navy-800)">${escapeHtml(title)}</h4>
            <span class="status-lbl">${escapeHtml(status)}</span>
            <span class="meta-label">Created By</span>
            <span class="meta-val">Kay Paolo Shipping</span>
          </div>
          <div class="history-card-col">
            <span class="meta-label">Shipment Date</span>
            <span class="meta-val" style="font-weight: 700">-</span>
            <span class="meta-label">Delivery Option</span>
            <span class="meta-val">-</span>
            <span class="meta-label">Description</span>
            <span class="meta-val" style="font-weight: 600">${escapeHtml(description)}</span>
          </div>
          <div class="history-card-col">
            <div class="address-block"><span class="meta-label">Ship From</span><strong>-</strong>-</div>
          </div>
          <div class="history-card-col">
            <div class="address-block"><span class="meta-label">Ship To</span><strong>-</strong>-</div>
          </div>
        </div>
      </div>
    `;
  }

  function extractHistoryCards(html) {
    if (!html || typeof DOMParser === 'undefined') return '';

    const doc = new DOMParser().parseFromString(html, 'text/html');
    const cards = Array.from(doc.querySelectorAll('#historyCardList .shipment-card[data-status], .shipment-card[data-status]'));
    return cards.map((card) => card.outerHTML).join('');
  }

  function updateHistoryBadgesFromRows(rows) {
    updateHistoryBadges(rows.map((row) => ({
      status: historyStatus(row),
      category: historyCategory(row)
    })));
  }

  function updateHistoryBadgesFromCards() {
    const rows = Array.from(document.querySelectorAll('#historyResult .shipment-card[data-status]')).map((card) => ({
      status: card.dataset.status || '',
      category: card.dataset.category || ''
    }));
    updateHistoryBadges(rows);
  }

  function updateHistoryBadges(rows) {
    if (!rows.length) return;

    const total = rows.length;
    const percent = (count) => `${((count / total) * 100).toFixed(2)}%`;
    document.querySelectorAll('[data-history-status-badge]').forEach((badge) => {
      const key = badge.dataset.historyStatusBadge;
      badge.textContent = percent(rows.filter((row) => row.status === key).length);
    });
    document.querySelectorAll('[data-history-category-badge]').forEach((badge) => {
      const key = badge.dataset.historyCategoryBadge;
      badge.textContent = percent(rows.filter((row) => row.category === key).length);
    });
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
        const flatType = block.querySelector('.pkg-flat-rate-type')?.value || '';
        dimensions.package_count_ind.push(count);
        dimensions.weight.push(weight);
        dimensions.length.push(length);
        dimensions.width.push(width);
        dimensions.height.push(height);
        flatRate.push(isFlat ? 'on' : '0');
        shipmentType.push(isFlat ? flatType : '');
      });
    } else {
      dimensions.package_count_ind.push(1);
      dimensions.weight.push(numberValue(firstValue('package_weight'), 1));
      dimensions.length.push(numberValue(firstValue('package_length'), 1));
      dimensions.width.push(numberValue(firstValue('package_width'), 1));
      dimensions.height.push(numberValue(firstValue('package_height'), 1));
      flatRate.push(firstValue('shipment_type') ? 'on' : '0');
      shipmentType.push(firstValue('shipment_type'));
    }

    const toCountryName = selectedCountryName(toCountry);
    const fromCountryName = selectedCountryName(fromCountry);
    const deliveryLocation = normalizeDeliveryLocation(firstValue('deliveryLocation', 'delivery_location'));
    const fragileShipment = document.getElementById('fragileShipment')?.checked ? 1 : 0;

    const quoteCustomerId = firstValue('quoteUserId') || queryParam('customer');

    return {
      user_id: quoteCustomerId || undefined,
      quote_user_id: quoteCustomerId || undefined,
      phone_or_account: queryParam('lookup') || undefined,
      from_name: firstValue('from_name'),
      from_email: firstValue('from_email'),
      from_phone: firstValue('from_phone'),
      from_account: firstValue('from_account'),
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
      package_count: dimensionRowCountFromDimensions(dimensions),
      total_value: numberValue(firstValue('totalValue', 'package_value'), 10),
      package_value: numberValue(firstValue('totalValue', 'package_value'), 10),
      dimensions,
      flat_rate: flatRate,
      shipment_type: shipmentType,
      delivery_location: deliveryLocation,
      deliveryLocation,
      coupon_code: firstValue('couponCode'),
      extra_service_charge: firstValue('extraServiceCharge'),
      include_in_receipt: document.getElementById('includeReceipt')?.checked ? 1 : 0,
      fragile_shipment: fragileShipment,
      is_fragile_shipment: fragileShipment
    };
  }

  function applyCustomerToQuoteForm(customer) {
    if (!customer || typeof customer !== 'object') return;

    const name = customer.name || customer.business_name || '';
    const account = customer.account_number || customer.id || firstValue('from_account');
    setText('fromCardTitle', `From: ${name || 'Customer'}${account ? ` | Account #${account}` : ''}`);
    setValue('from_name', name);
    setValue('from_email', customer.email);
    setValue('from_phone', customer.shipper_phone || customer.phone || customer.mobile);
    setValue('from_account', customer.account_number);
    setSelectValue('from_country', customer.shipper_country || customer.country || 'US');
    setValue('from_address', customer.shipper_address || customer.address);
    setValue('from_apt', customer.shipper_apt || customer.apt);
    setValue('from_city', customer.shipper_city || customer.city);
    setValue('from_state', customer.shipper_state || customer.state);
    setValue('from_zip', customer.shipper_zip || customer.zip);
  }

  function applyConsigneeToQuoteForm(consignee) {
    if (!consignee || typeof consignee !== 'object') return;

    setValue('consignee_id', consignee.id || consignee.consignee_id);
    setValue('toName', consignee.consignee_name || consignee.name);
    setValue('toPhone', consignee.consignee_phone || consignee.phone);
    setValue('toHomePhone', consignee.consignee_homephone || consignee.home_phone || consignee.homePhone);
    setSelectValue('toCountry', consignee.consignee_address_country || consignee.country);
    setValue('toZip', consignee.consignee_address_zip || consignee.zip);
    setValue('toAddress', consignee.consignee_address || consignee.address);
    setValue('toApt', consignee.consignee_apt || consignee.apt);
    setValue('toCity', consignee.consignee_address_city || consignee.city);
    setValue('toState', consignee.consignee_address_state || consignee.state);
  }

  function mergeShipmentFormPayload(basePayload) {
    const fromCountrySelect = firstElement('shipmentFromCountry');
    const toCountrySelect = firstElement('shipmentToCountry');
    const fromCountryName = selectedCountryName(fromCountrySelect) || basePayload.from_country_name;
    const toCountryName = selectedCountryName(toCountrySelect) || basePayload.to_country_name;

    return {
      ...basePayload,
      from_name: value('shipmentFromName') || basePayload.from_name,
      from_email: value('shipmentFromEmail') || basePayload.from_email,
      from_phone: value('shipmentFromPhone') || basePayload.from_phone,
      from_country_name: fromCountryName,
      from_country: countryCode(fromCountrySelect?.value || basePayload.from_country || fromCountryName),
      from_address: value('shipmentFromAddress') || basePayload.from_address,
      from_apt: value('shipmentFromApt') || basePayload.from_apt,
      from_city: value('shipmentFromCity') || basePayload.from_city,
      from_state: value('shipmentFromState') || basePayload.from_state,
      from_zip: value('shipmentFromZip') || basePayload.from_zip,
      to_name: value('shipmentToName') || basePayload.to_name,
      consignee_name: value('shipmentToName') || basePayload.consignee_name,
      to_phone_1: value('shipmentToPhone') || basePayload.to_phone_1,
      to_phone_2: value('shipmentToHomePhone') || basePayload.to_phone_2,
      to_country_name: toCountryName,
      to_country: countryCode(toCountrySelect?.value || basePayload.to_country || toCountryName),
      to_address: value('shipmentToAddress') || basePayload.to_address,
      to_apt: value('shipmentToApt') || basePayload.to_apt,
      to_city: value('shipmentToCity') || basePayload.to_city,
      to_state: value('shipmentToState') || basePayload.to_state,
      to_zip: value('shipmentToZip') || basePayload.to_zip,
      delivery_location: normalizeDeliveryLocation(value('shipmentDeliveryLocation') || basePayload.delivery_location),
      package_description: value('shipmentPackageDescription') || basePayload.package_description || '',
      fragile_shipment: document.getElementById('shipmentFragile')?.checked ? 1 : basePayload.fragile_shipment,
      payment_type: value('shipmentPaymentType') || basePayload.payment_type || 'PAID AT AGENT'
    };
  }

  function arrayValue(raw) {
    if (Array.isArray(raw)) return raw;
    if (raw === undefined || raw === null || raw === '') return [];
    return [raw];
  }

  function padArray(raw, count, fallback) {
    const values = arrayValue(raw).slice(0, count);
    while (values.length < count) {
      values.push(fallback);
    }
    return values;
  }

  function dimensionRowCountFromDimensions(dimensions) {
    const source = dimensions || {};
    const lengths = ['package_count_ind', 'weight', 'length', 'width', 'height']
      .map((key) => Array.isArray(source[key]) ? source[key].length : 0);
    return Math.max(...lengths, 1);
  }

  function normalizeShipmentDimensions(payload) {
    const dimensions = payload.dimensions || {};
    let counts = arrayValue(dimensions.package_count_ind);
    let weights = arrayValue(dimensions.weight);
    let lengths = arrayValue(dimensions.length);
    let widths = arrayValue(dimensions.width);
    let heights = arrayValue(dimensions.height);

    if (!weights.length && payload.package_weight !== undefined) weights = [payload.package_weight];
    if (!lengths.length && payload.package_length !== undefined) lengths = [payload.package_length];
    if (!widths.length && payload.package_width !== undefined) widths = [payload.package_width];
    if (!heights.length && payload.package_height !== undefined) heights = [payload.package_height];
    if (!counts.length) counts = [payload.package_count_ind || 1];

    const rowCount = Math.max(counts.length, weights.length, lengths.length, widths.length, heights.length, 1);

    return {
      package_count_ind: padArray(counts, rowCount, 1).map((item) => numberValue(item, 1)),
      weight: padArray(weights, rowCount, 1).map((item) => numberValue(item, 1)),
      length: padArray(lengths, rowCount, 1).map((item) => numberValue(item, 1)),
      width: padArray(widths, rowCount, 1).map((item) => numberValue(item, 1)),
      height: padArray(heights, rowCount, 1).map((item) => numberValue(item, 1))
    };
  }

  function packagePieceCount(payload) {
    const counts = arrayValue(payload?.dimensions?.package_count_ind);
    if (counts.length) {
      return counts.reduce((sum, item) => sum + numberValue(item, 1), 0) || 1;
    }

    return numberValue(payload?.total_packages || payload?.pieces || payload?.package_count, 1);
  }

  function plannedShippingDateTime() {
    const date = new Date();
    const day = date.getDay();
    if (day === 6) {
      date.setDate(date.getDate() + 2);
    } else if (day === 0) {
      date.setDate(date.getDate() + 1);
    }

    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const dateNumber = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${dateNumber}T00:00:00`;
  }

  function flatRateIsOn(value) {
    return value === true || value === 1 || ['1', 'true', 'yes', 'on'].includes(String(value || '').toLowerCase());
  }

  function expandedPackagesFromPayload(payload) {
    const dimensions = normalizeShipmentDimensions(payload);
    const rowCount = dimensionRowCountFromDimensions(dimensions);
    const flatRate = padArray(payload.flat_rate, rowCount, '0');
    const shipmentType = padArray(payload.shipment_type, rowCount, '');
    const packages = [];

    for (let index = 0; index < rowCount; index += 1) {
      const repeat = numberValue(dimensions.package_count_ind[index], 1);
      const currentShipmentType = String(shipmentType[index] || '').trim();
      const isFlatRate = flatRateIsOn(flatRate[index]) || currentShipmentType !== '';

      for (let piece = 0; piece < repeat; piece += 1) {
        const packageItem = {
          typeCode: currentShipmentType === 'contains_document' ? '2BP' : '3BX',
          weight: numberValue(dimensions.weight[index], 0),
          dimensions: {
            length: numberValue(dimensions.length[index], 0),
            width: numberValue(dimensions.width[index], 0),
            height: numberValue(dimensions.height[index], 0)
          }
        };

        if (isFlatRate && currentShipmentType) {
          packageItem.flat_rate_type = currentShipmentType;
        }

        packages.push(packageItem);
      }
    }

    return packages;
  }

  function compactPayload(payload) {
    return Object.fromEntries(
      Object.entries(payload).filter(([, nextValue]) => nextValue !== undefined && nextValue !== null)
    );
  }

  function buildBocicotShipmentPayload(payload) {
    payload = payload || {};
    const dimensions = normalizeShipmentDimensions(payload || {});
    const rowCount = dimensionRowCountFromDimensions(dimensions);
    const flatRate = padArray(payload.flat_rate, rowCount, '0');
    const shipmentType = padArray(payload.shipment_type, rowCount, '');
    const deliveryLocation = normalizeDeliveryLocation(payload.delivery_location || payload.deliveryLocation);
    const selectedShipper = payload.selected_shipper || payload.delivery_option || '';
    const declaredValue = numberValue(payload.total_value || payload.package_value, 10);
    const fragileShipment = payload.is_fragile_shipment ?? payload.fragile_shipment ?? 0;

    return compactPayload({
      user_id: payload.user_id || payload.quote_user_id || storedUser().id || undefined,
      quote_user_id: payload.quote_user_id || payload.user_id || storedUser().id || undefined,
      quote_id: payload.quote_id || undefined,
      partner: normalizePartner(payload.partner),
      selected_shipper: selectedShipper || undefined,
      delivery_option: selectedShipper || undefined,
      from_name: payload.from_name || undefined,
      from_email: payload.from_email || undefined,
      from_phone: payload.from_phone || undefined,
      from_country_name: payload.from_country_name || undefined,
      from_country: payload.from_country || countryCode(payload.from_country_name),
      from_address: payload.from_address || undefined,
      from_apt: payload.from_apt || '',
      from_zip: payload.from_zip || undefined,
      from_city: payload.from_city || undefined,
      from_state: payload.from_state || undefined,
      consignee_id: payload.consignee_id || payload.consignees_id || undefined,
      consignees_id: payload.consignees_id || payload.consignee_id || undefined,
      consignee_name: payload.consignee_name || payload.to_name || undefined,
      consignee_phone: payload.consignee_phone || payload.to_phone_1 || undefined,
      consignee_homephone: payload.consignee_homephone || payload.to_phone_2 || '',
      to_name: payload.to_name || payload.consignee_name || undefined,
      to_phone_1: payload.to_phone_1 || payload.consignee_phone || undefined,
      to_phone_2: payload.to_phone_2 || payload.consignee_homephone || '',
      to_country_name: payload.to_country_name || undefined,
      to_country: payload.to_country || countryCode(payload.to_country_name),
      to_address: payload.to_address || undefined,
      to_apt: payload.to_apt || '',
      to_zip: payload.to_zip || undefined,
      to_city: payload.to_city || undefined,
      to_state: payload.to_state || undefined,
      package_count: rowCount,
      package_description: payload.package_description ?? '',
      total_value: declaredValue,
      package_value: declaredValue,
      dimensions,
      flat_rate: flatRate,
      shipment_type: shipmentType,
      packages: expandedPackagesFromPayload({ ...payload, dimensions, flat_rate: flatRate, shipment_type: shipmentType }),
      monetaryAmount: [
        {
          typeCode: 'declaredValue',
          value: declaredValue,
          currency: 'USD'
        }
      ],
      plannedShippingDateAndTime: payload.plannedShippingDateAndTime || plannedShippingDateTime(),
      delivery_location: deliveryLocation,
      deliveryLocation: deliveryLocation,
      delivery_description: payload.delivery_description || '',
      payment_type: payload.payment_type || 'PAID AT AGENT',
      deliveryEstimatePrice: payload.deliveryEstimatePrice || undefined,
      deliveryEstimateDate: payload.deliveryEstimateDate || undefined,
      promo: payload.promo || payload.coupon_code || '',
      coupon_code: payload.coupon_code || payload.promo || '',
      extra_service_charge: payload.extra_service_charge || '',
      include_in_receipt: payload.include_in_receipt ?? payload.include_receipt ?? 0,
      flaterateinside: flatRate.some(flatRateIsOn) || shipmentType.some(Boolean) ? 1 : 0,
      fragile_shipment: fragileShipment,
      is_fragile_shipment: fragileShipment
    });
  }

  async function ensureConsigneeForShipment(payload) {
    if (payload.consignee_id || payload.consignees_id) {
      return payload;
    }

    const response = await postJson(route('saveConsignee', '/api/kay-paolo/save-consignee'), compactPayload({
      user_id: payload.user_id || payload.quote_user_id || storedUser().id || undefined,
      quote_user_id: payload.quote_user_id || payload.user_id || storedUser().id || undefined,
      to_name: payload.to_name || payload.consignee_name,
      to_phone_1: payload.to_phone_1 || payload.consignee_phone,
      to_phone_2: payload.to_phone_2 || payload.consignee_homephone,
      to_country_name: payload.to_country_name,
      to_country: payload.to_country || countryCode(payload.to_country_name),
      to_address: payload.to_address,
      to_apt: payload.to_apt || '',
      to_zip: payload.to_zip,
      to_city: payload.to_city,
      to_state: payload.to_state,
      consignee_name: payload.consignee_name || payload.to_name,
      consignee_phone: payload.consignee_phone || payload.to_phone_1,
      consignee_homephone: payload.consignee_homephone || payload.to_phone_2
    }));

    const consigneeId = response.consignee_id || response.id || response.data?.consignee_id || response.data?.id;
    if (!consigneeId) {
      return payload;
    }

    return {
      ...payload,
      consignee_id: consigneeId,
      consignees_id: consigneeId
    };
  }

  async function queueShipmentEmailNotifications(response, payload) {
    const data = buildShipmentDocumentData(response || {}, payload || {}, {});
    const emails = Array.from(new Set([
      payload?.from_email,
      storedUser().email
    ].filter(Boolean)));

    if (!emails.length) return;

    await Promise.allSettled(emails.map((email) => postJson(route('emailShipment', '/api/kay-paolo/email-shipment'), {
      email,
      shipment_id: data.shipmentId || undefined,
      shipping_id: data.shipmentId || undefined,
      id: data.shipmentId || data.documentNumber || data.tracking || undefined,
      invoice: data.documentNumber || undefined
    })));
  }

  function fillCreateShipmentPage() {
    const pending = storedJson(pendingShipmentKey, { payload: {}, card: {}, quote: {} });
    const payload = pending.payload || {};
    const card = pending.card || {};
    const quote = pending.quote || {};
    const hasSelection = Boolean(payload.quote_id || quote.quote_id || quote.quoteId || quote.id || Object.keys(card).length);
    const service = quoteCardService(card) || payload.delivery_option || 'Selected service';
    const total = quoteCardTotal(card) || payload.deliveryEstimatePrice || payload.total || '0.00';
    const carrier = quoteCardCarrier(card) || payload.partner || 'ZION';
    const eta = quoteCardEta(card) || payload.deliveryEstimateDate || '-';
    const deliveredBy = quoteCardDeliveryTime(card) || payload.delivered_by || '-';

    const notice = document.getElementById('selectedServiceNotice');
    if (notice) notice.hidden = hasSelection;

    setText('selectedServiceName', service);
    setText('selectedServiceTotal', moneyText(total));
    setText('selectedQuoteId', quote.quote_id || quote.quoteId || quote.id || payload.quote_id || '-');
    setText('selectedCarrier', carrier);
    setText('selectedArrivesOn', eta);
    setText('selectedDeliveredBy', deliveredBy);

    setValue('shipmentFromName', payload.from_name);
    setValue('shipmentFromEmail', payload.from_email);
    setValue('shipmentFromPhone', payload.from_phone);
    setSelectValue('shipmentFromCountry', payload.from_country_name || payload.from_country);
    setValue('shipmentFromZip', payload.from_zip);
    setValue('shipmentFromAddress', payload.from_address);
    setValue('shipmentFromApt', payload.from_apt);
    setValue('shipmentFromCity', payload.from_city);
    setValue('shipmentFromState', payload.from_state);
    setValue('shipmentToName', payload.to_name || payload.consignee_name);
    setValue('shipmentToPhone', payload.to_phone_1 || payload.consignee_phone);
    setValue('shipmentToHomePhone', payload.to_phone_2);
    setSelectValue('shipmentToCountry', payload.to_country_name || payload.to_country);
    setValue('shipmentToZip', payload.to_zip);
    setValue('shipmentToAddress', payload.to_address);
    setValue('shipmentToApt', payload.to_apt);
    setValue('shipmentToCity', payload.to_city);
    setValue('shipmentToState', payload.to_state);
    setSelectValue('shipmentDeliveryLocation', normalizeDeliveryLocation(payload.delivery_location));
    setValue('shipmentPackageDescription', payload.package_description);
    setSelectValue('shipmentPaymentType', payload.payment_type || 'PAID AT AGENT');

    const fragileInput = document.getElementById('shipmentFragile');
    if (fragileInput) {
      fragileInput.checked = Boolean(Number(payload.is_fragile_shipment ?? payload.fragile_shipment ?? 0));
    }

    const summary = document.getElementById('shipmentPackageSummary');
    if (summary && payload.dimensions) {
      const pieces = packagePieceCount(payload);
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
      showError(container, response.message || 'No quote cards returned from the shipping API.');
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
          <div class="carrier-logo-container">${carrierLogoMarkup(card)}</div>
          <div class="carrier-details"><p>${escapeHtml(card.message || 'This shipping option is unavailable.')}</p></div>
        </div>
      `;
    }

    const carrier = quoteCardCarrier(card) || 'Kay Paolo Shipping';
    const service = quoteCardService(card) || 'Shipping Service';
    const total = quoteCardTotal(card) || '0.00';
    const freight = card.freight || card.base_rate || '0.00';
    const insurance = card.insurance || '0.00';
    const homeDelivery = card.home_delivery || card.delivery || '0.00';
    const tax = card.tax || '0.00';

    return `
      <div class="quote-rate-card">
        <div class="rate-brand">
          ${carrierLogoMarkup(card)}
        </div>
        <div class="rate-info">
          <h4>${escapeHtml(service)}</h4>
          <div class="rate-delivery">
            <span class="delivery-date">Arrives on ${escapeHtml(quoteCardEta(card) || '-')}</span>
            <span class="delivery-time">Delivered by ${escapeHtml(quoteCardDeliveryTime(card) || '-')}</span>
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

  function quoteCardCarrier(card) {
    return card.carrier_name
      || card.carrier
      || card.partner
      || card.shipper
      || card.shipping_company
      || card.provider
      || '';
  }

  function quoteCardPartner(card) {
    return normalizePartner(card.carrier || card.partner || card.carrier_key || card.carrier_name || card.integration || card.full_integration);
  }

  function normalizePartner(value) {
    const raw = String(value || 'zion').toLowerCase();
    if (raw.includes('full integration')) return 'ZION';
    if (raw.includes('ups')) return 'UPS';
    if (raw.includes('fedex')) return 'FEDEX';
    if (raw.includes('usps')) return 'USPS';
    if (raw.includes('dhl')) return 'DHL';
    return 'ZION';
  }

  function quoteCardService(card) {
    return card.service_name
      || card.service
      || card.name
      || card.delivery_option
      || card.selected_shipper
      || '';
  }

  function quoteCardTotal(card) {
    return card.total
      || card.price
      || card.amount
      || card.deliveryEstimatePrice
      || card.delivery_estimate_price
      || card.rate
      || '';
  }

  function quoteCardEta(card) {
    return card.arrives_on
      || card.arrival_date
      || card.eta
      || card.deliveryEstimateDate
      || card.delivery_estimate_date
      || card.commitment
      || '';
  }

  function quoteCardDeliveryTime(card) {
    return card.delivered_by
      || card.delivery_time
      || card.delivery_by
      || card.transit_time
      || '';
  }

  function carrierLogoMarkup(card) {
    const carrier = quoteCardCarrier(card) || 'Carrier';
    const partner = quoteCardPartner(card);
    const logo = card.logo
      || card.logo_url
      || card.carrier_logo
      || card.carrier_logo_url
      || card.image
      || card.image_url
      || card.icon
      || '';

    if (partner === 'ZION') {
      return `<img src="${escapeHtml(config.assets?.zionCarrierLogo || '/kay-paolo/assets/images/zion-carrier-logo.png')}" alt="Full integration logo" width="100" height="50">`;
    }

    if (logo) {
      return `<img src="${escapeHtml(resolveAssetUrl(logo))}" alt="${escapeHtml(carrier)} logo" width="100" height="50">`;
    }

    if (normalizePartner(carrier) === 'ZION') {
      return `<img src="${escapeHtml(config.assets?.zionCarrierLogo || '/kay-paolo/assets/images/zion-carrier-logo.png')}" alt="Full integration logo" width="100" height="50">`;
    }

    return `<div class="carrier-logo-fallback" aria-label="${escapeHtml(carrier)}">${escapeHtml(carrierInitials(carrier))}</div>`;
  }

  function carrierInitials(name) {
    const normalized = String(name || 'Carrier').replace(/[^a-z0-9\s]/gi, ' ').trim();
    const known = {
      dhl: 'DHL',
      fedex: 'FEDEX',
      ups: 'UPS',
      usps: 'USPS',
      endicia: 'ENDICIA',
      zion: 'ZION',
      'kay paolo': 'KAY'
    };
    const lower = normalized.toLowerCase();
    const knownKey = Object.keys(known).find((key) => lower.includes(key));
    if (knownKey) return known[knownKey];

    return normalized
      .split(/\s+/)
      .filter(Boolean)
      .slice(0, 3)
      .map((part) => part[0])
      .join('')
      .toUpperCase() || 'CAR';
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
    if (!select || !select.value) return '';
    return select.options?.[select.selectedIndex]?.text || select.value || '';
  }

  function countryCode(country) {
    const raw = String(country || '').trim();
    if (!raw) return '';
    if (/^[a-z]{2,3}$/i.test(raw)) return raw.toUpperCase();

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

  function normalizeDeliveryLocation(rawValue) {
    const raw = String(rawValue || '').trim();
    const lower = raw.toLowerCase();
    if (!lower) return '';
    if (lower.includes('office') || lower.includes('pickup')) return 'Pickup in Office';
    if (lower.includes('home')) return 'Home Delivery';
    return raw;
  }

  function resolveAssetUrl(url) {
    const raw = String(url || '').trim();
    if (!raw) return '';
    if (/^(https?:)?\/\//i.test(raw) || raw.startsWith('data:')) return raw;
    if (raw.startsWith('/')) return raw;

    const base = config.zionWebUrl || 'https://dev.zionshipping.com/';
    return `${base.replace(/\/+$/, '')}/${raw.replace(/^\/+/, '')}`;
  }

  function showLoader(id, visible) {
    const loader = document.getElementById(id);
    if (loader) loader.hidden = !visible;
    toggleProcessOverlay(id, visible);
  }

  function toggleProcessOverlay(loaderId, visible) {
    const overlay = document.getElementById('kayProcessOverlay');
    if (!overlay || !['quoteLoader', 'shippingLoader'].includes(loaderId)) return;

    const image = document.getElementById('kayProcessImage');
    const title = document.getElementById('kayProcessTitle');
    const message = document.getElementById('kayProcessMessage');
    const isQuote = loaderId === 'quoteLoader';

    if (image) {
      image.src = isQuote
        ? (config.assets?.generatingQuote || '/kay-paolo/assets/generating-quote.gif')
        : (config.assets?.processingShipping || '/kay-paolo/assets/processing-shipping.gif');
      image.alt = isQuote ? 'Generating quote' : 'Processing shipment';
    }
    if (title) title.textContent = isQuote ? 'Generating Quote' : 'Processing Shipment';
    if (message) {
      message.textContent = isQuote
        ? 'Checking live Kay Paolo rates.'
        : 'Completing the shipment.';
    }

    overlay.hidden = !visible;
    document.body.classList.toggle('kay-process-open', visible);
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
