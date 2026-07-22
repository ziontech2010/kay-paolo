document.addEventListener('DOMContentLoaded', () => {
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
  const config = window.KayPaolo || {};
  const tokenKey = 'kayPaoloZionToken';
  const userKey = 'kayPaoloZionUser';
  const pendingShipmentKey = 'kayPaoloPendingShipment';
  const shipmentResponseKey = 'kayPaoloShipmentResponse';
  const trackingResponseKey = 'kayPaoloTrackingResponse';
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
    } else {
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
      const adminAccess = document.getElementById('dashboardAdminAccess');
      if (adminAccess) adminAccess.hidden = Number(user.role_id) !== 1;
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
        result.textContent = 'Searching Zion customer records...';
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
      showMessage('Loading existing consignees from Zion...');

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
    setNote('Loading flat rate items from Zion...');

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
      const fallbackOptions = flatRateCache.get('fallback') || fallbackFlatRateOptions();
      flatRateCache.set('fallback', fallbackOptions);
      populateFlatRateSelect(select, fallbackOptions);
      select.dataset.loadedFor = cacheKey;
      select.disabled = false;
      setNote('Showing standard flat rate items until Zion returns live options.', true);
    }
  }

  function normalizeFlatRateOptions(response) {
    const options = [
      response?.all_options,
      response?.data?.all_options,
      response?.options,
      response?.data?.options
    ].find(Array.isArray) || [];

    return options.map((option) => normalizeFlatRateOption(option)).filter((option) => option.slug);
  }

  function normalizeFlatRateOption(option) {
    const defaults = option.default_dimensions || option.dimensions || {};

    return {
      slug: option.slug || option.value || option.id || '',
      label: option.label || option.name || option.title || option.slug || 'Flat Rate Item',
      group: option.group || option.category || 'Flat Rate',
      price: option.price || option.amount || option.rate || option.total || '',
      readonly: option.readonly_dimensions !== false,
      defaults: {
        package_count_ind: defaults.package_count_ind || defaults.count || 1,
        weight: defaults.weight || option.weight || '',
        length: defaults.length || option.length || '',
        width: defaults.width || option.width || '',
        height: defaults.height || option.height || ''
      }
    };
  }

  function fallbackFlatRateOptions() {
    const flatRate = (slug, label, group, weight, length, width, height) => ({
      slug,
      label,
      group,
      defaults: { package_count_ind: 1, weight, length, width, height },
      readonly: true
    });

    return [
      flatRate('blender', 'BLENDER', 'MOTHER`S DAY SPECIAL', 5, 14, 6, 6),
      flatRate('juicer', 'JUICER', 'MOTHER`S DAY SPECIAL', 7, 12, 10, 8),
      flatRate('toaster', 'TOASTER', 'MOTHER`S DAY SPECIAL', 12, 16, 14, 12),
      flatRate('microwave', 'MICROWAVE', 'MOTHER`S DAY SPECIAL', 14, 14, 14, 14),
      flatRate('dryer', 'DRYER', 'MOTHER`S DAY SPECIAL', 45, 20, 20, 20),
      flatRate('washer', 'WASHER', 'MOTHER`S DAY SPECIAL', 50, 20, 20, 20),
      flatRate('contains_document', 'DOCUMENT', 'DOCUMENT', 0.5, 12, 8, 1),
      flatRate('phone_new', 'NEW PHONE', 'PHONE', 2, 9, 6, 2),
      flatRate('phone_used', 'USED PHONE', 'PHONE', 2, 9, 6, 2),
      flatRate('tablet_new', 'NEW TABLET', 'TABLET', 2, 12, 9, 3),
      flatRate('tablet_used', 'USED TABLET', 'TABLET', 2, 12, 9, 3),
      flatRate('laptop_new', 'NEW LAPTOP', 'LAPTOP', 5, 18, 12, 4),
      flatRate('laptop_used', 'USED LAPTOP', 'LAPTOP', 5, 18, 12, 4),
      flatRate('tv_24', 'TV 24"', 'TV', 12, 22, 15, 4),
      flatRate('tv_32', 'TV 32"', 'TV', 14, 29, 19, 4),
      flatRate('tv_40', 'TV 40"', 'TV', 16, 37, 22, 6),
      flatRate('tv_42', 'TV 42"', 'TV', 20, 38, 24, 6),
      flatRate('tv_50', 'TV 50"', 'TV', 25, 44, 27, 7),
      flatRate('tv_55', 'TV 55"', 'TV', 35, 49, 30, 7),
      flatRate('tv_60', 'TV 60"', 'TV', 40, 54, 33, 8),
      flatRate('tv_65', 'TV 65"', 'TV', 50, 58, 35, 8),
      flatRate('tv_70', 'TV 70"', 'TV', 60, 62, 37, 9),
      flatRate('tv_75', 'TV 75"', 'TV', 70, 66, 40, 9),
      flatRate('tv_80', 'TV 80"', 'TV', 80, 70, 45, 10),
      flatRate('tv_85', 'TV 85"', 'TV', 95, 75, 45, 10),
      flatRate('luggage_checked', 'LUGGAGE (CHECKED BAG)', 'SPECIAL', 50, 30, 20, 12),
      flatRate('bins', 'BINS', 'SPECIAL', 80, 36, 16, 16),
      flatRate('barrel_55_gal', 'BARREL 55 GAL', 'SPECIAL', 200, 34, 24, 24),
      flatRate('barrel_77_gallons', 'BARREL 77 GAL', 'SPECIAL', 250, 20, 20, 45),
      flatRate('econtainer', 'E-CONTAINER', 'SPECIAL', 250, 42, 29, 25),
      flatRate('hub_14_14_14', 'HUB20 14X14X14', 'HUB20', 20, 14, 14, 14),
      flatRate('hub_18_18_16', 'HUB20 18X18X16', 'HUB20', 40, 18, 18, 16),
      flatRate('hub_18_18_24', 'HUB20 18X18X24', 'HUB20', 60, 18, 18, 24),
      flatRate('hub_18_24_24', 'HUB20 18X24X24', 'HUB20', 80, 18, 24, 24),
      flatRate('bag_of_food_100', 'BAG OF FOOD 100 LBS', 'OTHER', 100, 24, 14, 10),
      flatRate('bag_of_food_50', 'BAG OF FOOD 50 LBS', 'OTHER', 50, 18, 12, 8),
      flatRate('regular_tires', 'REGULAR TIRES', 'OTHER', 20, 24, 24, 8),
      flatRate('suv_pickup_tires', 'SUV OR PICKUP TIRES', 'OTHER', 30, 30, 30, 10),
      flatRate('gallon_5_litter', '5 LITTER GALLON', 'OTHER', 10, 5, 6, 12),
      flatRate('gallons_5', '5 GALLONS', 'OTHER', 50, 14, 16, 18),
      flatRate('foldable_table', 'FOLDABLE TABLE', 'OTHER', 15, 4, 20, 40),
      flatRate('foldable_chair', 'FOLDABLE CHAIR', 'OTHER', 10, 3, 14, 34),
      flatRate('one_door_fridge', 'ONE DOOR FRIDGE', 'OTHER', 100, 24, 24, 70),
      flatRate('two_door_fridge', 'TWO DOORS FRIDGE', 'OTHER', 150, 36, 36, 70),
      flatRate('twin_bed', 'TWIN BED', 'OTHER', 150, 10, 60, 110),
      flatRate('twin_bedroom', 'TWIN BEDROOM', 'OTHER', 250, 80, 80, 20),
      flatRate('queen_bed', 'QUEEN BED', 'OTHER', 200, 20, 60, 110),
      flatRate('queen_bedroom', 'QUEEN BEDROOM', 'OTHER', 300, 80, 80, 40),
      flatRate('king_bed', 'KING BED', 'OTHER', 300, 30, 60, 110),
      flatRate('king_bedroom', 'KING BEDROOM', 'OTHER', 400, 80, 80, 60),
      flatRate('oven', 'OVEN', 'OTHER', 100, 40, 40, 50),
      flatRate('box_truck_tires', 'BOX TRUCK TIRES', 'OTHER', 50, 40, 40, 14),
      flatRate('car_battery', 'CAR BATTERY (SUDDEN)', 'OTHER', 50, 10, 8, 6),
      flatRate('truck_battery', 'TRUCK BATTERY', 'OTHER', 60, 12, 10, 8),
      flatRate('inverter_battery', 'INVERTER BATTERY', 'OTHER', 70, 12, 10, 8),
      flatRate('bucket_5', 'BUCKET (5 GALLONS)', 'OTHER', 50, 20, 12, 12),
      flatRate('regular_door', 'REGULAR DOOR', 'OTHER', 75, 80, 30, 2),
      flatRate('kid_bicycle', 'KID BICYCLE', 'OTHER', 25, 30, 14, 8),
      flatRate('adult_bicycle', 'ADULT BICYCLE', 'OTHER', 35, 50, 18, 12),
      flatRate('regular_chair', 'REGULAR CHAIR', 'OTHER', 20, 24, 24, 18),
      flatRate('solar_panel_200w', 'SOLAR PANEL 200W', 'OTHER', 50, 40, 30, 4),
      flatRate('solar_panel_400w', 'SOLAR PANEL 400W', 'OTHER', 75, 60, 40, 4),
      flatRate('generator_0_2_5kw', 'GENERATOR 0KW - 2.5KW', 'OTHER', 100, 24, 18, 20),
      flatRate('generator_2_5_5kw', 'GENERATOR 2.5KW - 5KW', 'OTHER', 150, 30, 24, 24),
      flatRate('generator_5_10kw', 'GENERATOR 5KW - 10KW', 'OTHER', 250, 40, 30, 30)
    ];
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
        partner: String(quoteCardCarrier(card) || 'zion').toUpperCase(),
        payment_type: 'PAID AT AGENT',
        deliveryEstimatePrice: quoteCardTotal(card) || undefined,
        deliveryEstimateDate: quoteCardEta(card) || undefined,
        delivery_option: quoteCardService(card) || undefined
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
        const payload = await ensureConsigneeForShipment(mergeShipmentFormPayload(pending.payload || {}));
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
      packageCount: payload.package_count || items.reduce((sum, item) => sum + numberValue(item.count, 0), 0) || 1,
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
    const packageDescription = firstValue('packageDescription', 'package_description');

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
      package_count: dimensions.package_count_ind.reduce((sum, item) => sum + Number(item || 0), 0) || 1,
      total_value: numberValue(firstValue('totalValue', 'package_value'), 10),
      package_value: numberValue(firstValue('totalValue', 'package_value'), 10),
      dimensions,
      flat_rate: flatRate,
      shipment_type: shipmentType,
      delivery_location: deliveryLocation,
      deliveryLocation,
      coupon_code: firstValue('couponCode'),
      extra_service_charge: firstValue('extraServiceCharge'),
      fragile_shipment: document.getElementById('fragileShipment')?.checked ? 1 : 0,
      package_description: packageDescription
    };
  }

  function applyCustomerToQuoteForm(customer) {
    if (!customer || typeof customer !== 'object') return;

    const name = customer.name || customer.business_name || '';
    setText('fromCardTitle', `From: ${name || 'Customer'}`);
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
    return {
      ...basePayload,
      from_name: value('shipmentFromName') || basePayload.from_name,
      from_email: value('shipmentFromEmail') || basePayload.from_email,
      from_phone: value('shipmentFromPhone') || basePayload.from_phone,
      from_country_name: value('shipmentFromCountry') || basePayload.from_country_name,
      from_country: countryCode(value('shipmentFromCountry') || basePayload.from_country_name || basePayload.from_country),
      from_address: value('shipmentFromAddress') || basePayload.from_address,
      from_apt: value('shipmentFromApt') || basePayload.from_apt,
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
      delivery_location: normalizeDeliveryLocation(value('shipmentDeliveryLocation') || basePayload.delivery_location),
      package_description: value('shipmentPackageDescription') || basePayload.package_description || '',
      fragile_shipment: document.getElementById('shipmentFragile')?.checked ? 1 : basePayload.fragile_shipment,
      payment_type: value('shipmentPaymentType') || basePayload.payment_type || 'PAID AT AGENT'
    };
  }

  async function ensureConsigneeForShipment(payload) {
    if (payload.consignee_id || payload.consignees_id) {
      return payload;
    }

    const response = await postJson(route('saveConsignee', '/api/kay-paolo/save-consignee'), {
      ...payload,
      consignee_name: payload.consignee_name || payload.to_name,
      consignee_phone: payload.consignee_phone || payload.to_phone_1,
      consignee_homephone: payload.consignee_homephone || payload.to_phone_2
    });

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

  function fillCreateShipmentPage() {
    const pending = storedJson(pendingShipmentKey, { payload: {}, card: {}, quote: {} });
    const payload = pending.payload || {};
    const card = pending.card || {};
    const quote = pending.quote || {};
    const hasSelection = Boolean(payload.quote_id || quote.quote_id || quote.quoteId || quote.id || Object.keys(card).length);
    const service = quoteCardService(card) || payload.delivery_option || 'Selected service';
    const total = quoteCardTotal(card) || payload.deliveryEstimatePrice || payload.total || '0.00';
    const carrier = quoteCardCarrier(card) || payload.partner || 'Zion';
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
    const logo = card.logo
      || card.logo_url
      || card.carrier_logo
      || card.carrier_logo_url
      || card.image
      || card.image_url
      || card.icon
      || '';

    if (logo) {
      return `<img src="${escapeHtml(resolveAssetUrl(logo))}" alt="${escapeHtml(carrier)} logo" width="100" height="50">`;
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
        ? 'Checking live Kay Paolo rates through the Zion Shipping API.'
        : 'Completing the shipment through the Zion Shipping API.';
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
