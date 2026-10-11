/* Simplify dashboard (Alpine component). Server data arrives through Livewire ($wire) calls;
   everything else is local UI state. Dialogs, toasts and tooltips come from core.js (window.Simplify). */
(() => {
  /** Returned by run() when an action was skipped (busy) or failed (already reported). */
  const SKIPPED = Symbol('skipped');
  const MINECRAFT_VERSION = /(?:^|[^0-9])((?:1\.[0-9]+(?:\.[0-9]+)?|2[6-9]\.[0-9]+(?:\.[0-9]+)?))(?![0-9])/;
  const OVERVIEW_DEFAULTS = { showMotd: false, largeIcon: 'server', largeName: 'alias', showCpu: true, showRam: true, showQuickActions: true, minecraftAlwaysGreen: true };
  const OVERVIEW_SWITCHES = ['showMotd', 'showCpu', 'showRam', 'showQuickActions', 'minecraftAlwaysGreen'];
  const POWER_LABELS = { start: 'Start', stop: 'Stop', restart: 'Restart', kill: 'Force stop' };
  const accentInk = (color) => {
    if (window.SimplifyTheme?.accentInk) return window.SimplifyTheme.accentInk(color);
    const [r, g, b] = color.slice(1).match(/../g).map((v) => {
      const x = parseInt(v, 16) / 255;
      return x <= 0.04045 ? x / 12.92 : ((x + 0.055) / 1.055) ** 2.4;
    });
    return r * 0.2126 + g * 0.7152 + b * 0.0722 > 0.179 ? '#111118' : '#ffffff';
  };
  const seconds = (value, fallback, min) => Math.max(min, Number.isFinite(Number(value)) && Number(value) > 0 ? Number(value) : fallback) * 1000;

  window.pelicanSimplify = function (wire, boot) {
    const performance = boot.performance || {};
    return {
      boot,
      wire,
      server: boot.server,
      intervals: {
        overview: seconds(performance.overview_seconds, 30, 5),
        server: seconds(performance.server_seconds, 20, 5),
        restart: seconds(performance.restart_seconds, 3, 2),
      },
      // Overview
      overviewDockObserver: null,
      appearanceMenu: false,
      notifyMenu: false,
      noticeHistory: [],
      panelNotices: [],
      overviewCompact: false,
      overviewPreferences: { ...OVERVIEW_DEFAULTS },
      powerConfirmation: null,
      healthExpanded: false,
      healthOpenNodes: [],
      hostMetrics: [
        { key: 'cpu', label: 'CPU', icon: 'cpu' },
        { key: 'memory', label: 'RAM', icon: 'ram' },
        { key: 'disk', label: 'Storage', icon: 'disk' },
        { key: 'network', label: 'Network', icon: 'traffic' },
      ],
      overviewPowerBusy: null,
      overviewNetwork: {},
      overviewHistory: {},
      overviewRefreshing: false,
      gameFilter: 'all',
      overviewView: typeof innerWidth !== 'undefined' && innerWidth <= 600 ? 'list' : 'grid',
      // Shell
      accountMenu: false,
      navMenu: false,
      theme: 'system',
      themeDark: false,
      themeListener: null,
      noticeListener: null,
      started: false,
      destroyed: false,
      busy: false,
      notice: '',
      modal: null,
      loading: false,
      closingCreator: false,
      modalRequest: 0,
      createGame: 'minecraft',
      settingsSection: 'settings',
      settingsChanged: false,
      // Server page
      aliasDraft: '',
      sftpOpen: false,
      fileEditorPath: '',
      fileEditorRevision: null,
      fileEditorUrl: null,
      fileEditorDirty: false,
      fileEditorSaving: false,
      fileEditorChecking: false,
      fileEditorExternal: false,
      uploadedBackgrounds: boot.uploaded_backgrounds || [],
      backgroundDraft: 'auto',
      colorMenu: false,
      colorDraft: '#878787',
      colorMenuStyle: {},
      networkRates: { rx_per_second: null, tx_per_second: null },
      connected: false,
      ws: null,
      reconnect: null,
      reconnectDelay: 0,
      refreshTimer: null,
      pendingTimer: null,
      refreshingInfo: false,
      logs: [],
      command: '',
      history: [],
      historyIndex: -1,
      status: boot.server?.status || 'offline',
      stats: { cpu_absolute: null, memory_bytes: null, disk_bytes: null },
      series: { cpu: [], ram: [], disk: [], network: [] },
      metrics: [
        { key: 'cpu', label: 'CPU load', icon: 'cpu' },
        { key: 'ram', label: 'Memory', icon: 'ram' },
        { key: 'disk', label: 'Storage', icon: 'disk' },
        { key: 'network', label: 'Network usage', icon: 'traffic' },
      ],
      widgets: [
        { key: 'settings', title: 'Game settings', description: 'Gameplay, MOTD & server appearance', icon: 'settings' },
        { key: 'players', title: 'Player management', description: 'Whitelist, operators & moderation', icon: 'players' },
        { key: 'plugins', title: 'Plugins', description: 'Install and manage server plugins', icon: 'plugins' },
        { key: 'mods', title: 'Mods', description: 'Install and manage server mods', icon: 'mods' },
        { key: 'files', title: 'Files', description: 'Browse, upload & connect with SFTP', icon: 'files' },
        { key: 'backups', title: 'Backups', description: 'Recovery points & automatic backups', icon: 'backups' },
        { key: 'schedules', title: 'Scheduled tasks', description: 'Planned restarts & console commands', icon: 'clock' },
        { key: 'software', title: 'Server software', description: 'Minecraft version, loader & modpack', icon: 'packages' },
        { key: 'connection', title: 'Network', description: 'Server address & subdomain', icon: 'link' },
        { key: 'performance', title: 'Performance', description: 'CPU, memory & storage limits', icon: 'cpu' },
        { key: 'web', title: 'Website', description: 'Visibility & website presentation', icon: 'web' },
        { key: 'activity', title: 'Activity log', description: 'Review recent server management actions', icon: 'activity' },
        { key: 'advanced', title: 'Advanced', description: 'Runtime details & server management', icon: 'advanced' },
      ],
      settings: {},
      settingsTab: 'general',
      name: boot.server?.name || '',
      playerName: '',
      playerLists: {},
      website: {},
      packageQuery: '',
      packageVersion: '',
      packageResults: [],
      installed: {},
      advanced: {},
      software: { server_type: 'vanilla', egg_id: '', minecraft_version: '', modpack_project: '', modpack_version: '' },
      softwareConfirm: '',
      deleteConfirm: '',
      requestedSubdomain: '',
      packQuery: '',
      packs: [],
      packReleases: {},
      backupSettings: { ...boot.server?.backup_options },
      backups: { items: [] },
      restartMinutes: 15,

      async init() {
        if (this.started) return;
        this.started = true;
        this.widgets = this.widgets.filter((w) => {
          const feature = { performance: 'resources', plugins: 'packages', mods: 'packages', web: 'web' }[w.key] || w.key;
          return feature === 'web' ? this.boot.web : this.boot.features?.[feature] !== false;
        });
        this.restoreOverviewView();
        this.restoreOverviewPreferences();
        this.initTheme();
        this.initNotices();
        this.applyFavicon();
        this.enhanceCopyright();
        if (this.$nextTick) this.$nextTick(() => this.initOverviewDock());
        if (!this.server) {
          this.recordOverview(this.boot.servers);
          this.refreshTimer = setInterval(() => this.modal || this.refreshOverview(), this.intervals.overview);
          return;
        }
        this.fillSettings();
        await this.connect();
        if (this.destroyed) return;
        this.startServerTimers();
      },
      startServerTimers() {
        // A restart that is due is followed closely; otherwise the page refreshes at the normal pace.
        this.pendingTimer = setInterval(() => this.restartDue() && this.refreshServerInfo(), this.intervals.restart);
        this.refreshTimer = setInterval(() => this.modal || this.refreshServerInfo(), this.intervals.server);
      },
      stopServerTimers() {
        clearInterval(this.refreshTimer);
        clearInterval(this.pendingTimer);
      },
      restartDue() {
        const pending = this.server?.pending;
        if (!pending?.restart_at && !pending?.restart_requested) return false;
        return !!pending.restart_requested || Number(pending.restart_at) * 1000 <= Date.now();
      },
      destroy() {
        this.overviewDockObserver?.disconnect();
        this.restoreFavicon();
        this.destroyed = true;
        window.removeEventListener?.('simplify:theme', this.themeListener);
        window.removeEventListener?.('simplify:notice', this.noticeListener);
        clearTimeout(this.reconnect);
        this.stopServerTimers();
        if (this.ws) {
          this.ws.onclose = null;
          this.ws.close();
        }
      },

      /* Colours ---------------------------------------------------------------------------- */
      creationAccent(game) {
        if (game === 'minecraft' && this.overviewPreferences.minecraftAlwaysGreen) {
          const color = this.themeDark ? '#74d69a' : '#16803d';
          return { '--ps-accent': color, '--ps-on-accent': accentInk(color) };
        }
        return this.accentStyle('overview');
      },
      accentStyle(id) {
        const appearance = this.boot.appearance || {};
        const common = appearance.accent_color;
        if (/^#[0-9a-f]{6}$/i.test(common || '') && (id === 'overview' || appearance.individual_colors === false))
          return { '--ps-accent': common, '--ps-on-accent': accentInk(common) };
        if (id === 'overview') return { '--ps-accent': this.themeDark ? '#c0c0c5' : '#55555f', '--ps-on-accent': this.themeDark ? '#111118' : '#ffffff' };
        const color = (this.server?.uuid === id ? this.server : this.boot.servers?.find((s) => s.uuid === id))?.color;
        if (typeof color === 'string' && /^#[0-9a-f]{6}$/i.test(color)) return { '--ps-accent': color, '--ps-on-accent': accentInk(color) };
        let hash = 2166136261;
        for (const c of String(id)) hash = Math.imul(hash ^ c.charCodeAt(0), 16777619) >>> 0;
        return { '--ps-accent': `hsl(${hash % 360} 68% 76%)`, '--ps-on-accent': '#111118' };
      },
      /** Panel name gradient: the common accent, else the page accent (blue on an uncoloured overview). */
      brandStyle() {
        const common = this.boot.appearance?.accent_color;
        if (/^#[0-9a-f]{6}$/i.test(common || '')) return { '--ps-brand': common };
        return this.server ? {} : { '--ps-brand': this.themeDark ? '#60a5fa' : '#2563eb' };
      },
      currentAccentHex() {
        const accent = this.accentStyle(this.server?.uuid || 'overview')['--ps-accent'];
        if (/^#[0-9a-f]{6}$/i.test(accent)) return accent;
        const match = /hsl\((\d+) 42% (\d+)%\)/.exec(accent);
        if (!match) return '#878787';
        const h = +match[1] / 360, s = 0.42, l = +match[2] / 100;
        const channel = (n) => {
          const k = (n + h * 12) % 12, a = s * Math.min(l, 1 - l);
          return Math.round(255 * (l - a * Math.max(-1, Math.min(k - 3, 9 - k, 1)))).toString(16).padStart(2, '0');
        };
        return '#' + channel(0) + channel(8) + channel(4);
      },

      /* Theme, notices and page chrome --------------------------------------------------------- */
      initTheme() {
        this.theme = window.SimplifyTheme?.preference() || 'system';
        this.themeListener = (e) => {
          this.theme = e.detail.preference;
          this.themeDark = e.detail.dark;
        };
        window.addEventListener('simplify:theme', this.themeListener);
        this.applyTheme();
      },
      setTheme(value) {
        window.SimplifyTheme?.set(value);
        this.theme = value;
        this.applyTheme();
      },
      applyTheme() {
        this.themeDark = window.SimplifyTheme?.dark() ?? false;
        this.$root?.querySelectorAll?.('iframe').forEach((frame) => window.SimplifyTheme?.frame(frame, frame.dataset.psFrame, this.currentAccentHex()));
      },
      initNotices() {
        this.noticeHistory = window.Simplify?.history?.() || [];
        this.panelNotices = Array.isArray(this.boot.panel_notifications) ? this.boot.panel_notifications : [];
        this.noticeListener = (event) => (this.noticeHistory = [event.detail, ...this.noticeHistory].slice(0, 20));
        window.addEventListener?.('simplify:notice', this.noticeListener);
      },
      toast(message, tone = 'info') {
        this.notice = String(message || '');
        window.Simplify?.toast(this.notice, tone);
      },
      ask(options) {
        return window.Simplify?.confirm ? window.Simplify.confirm(options) : Promise.resolve(false);
      },
      /** Active alerts for the bell menu: pending changes, host health and plugin updates. */
      notifications() {
        const items = [];
        const pending = this.server?.pending;
        if (pending) {
          items.push({
            id: 'pending',
            tone: pending.error ? 'danger' : 'warn',
            title: pending.error ? 'Changes need attention' : 'Changes are waiting for a restart',
            text: pending.error || (pending.restart_at ? 'A restart is scheduled to apply your changes.' : 'Restart the server to apply your saved changes.'),
            action: this.showWidget('restart') && !pending.restart_requested ? { label: 'Restart now', url: '#', run: () => this.restartChanges(0) } : null,
          });
        }
        const health = this.boot.host_health;
        if (!this.server && ['warning', 'critical'].includes(health?.state)) {
          items.push({ id: 'health', tone: health.state === 'critical' ? 'danger' : 'warn', title: 'Host health: ' + (health.label || 'Attention needed'), text: (health.available ?? 0) + ' of ' + (health.hosts ?? 0) + ' hosts reporting.', action: null });
        }
        const update = this.boot.update;
        if (update?.available) {
          items.push({ id: 'update', tone: 'info', title: 'Simplify ' + update.version + ' is available', text: 'You are running ' + (update.current || 'an older version') + '. Update from the Pelican plugin page.', action: update.url ? { label: 'Open plugins', url: update.url } : null });
        }
        for (const notice of this.boot.notices || []) items.push({ action: null, ...notice });
        for (const notice of this.panelNotices) items.push({ action: null, ...notice, pelican: true });
        return items;
      },
      /** Opening the bell fetches Pelican's latest notifications (no polling). */
      async toggleNotifications() {
        this.notifyMenu = !this.notifyMenu;
        this.appearanceMenu = false;
        this.accountMenu = false;
        if (!this.notifyMenu || typeof wire.panelNotifications !== 'function') return;
        try {
          const items = await wire.panelNotifications();
          if (Array.isArray(items)) this.panelNotices = items;
        } catch {}
      },
      async markPanelNoticesRead(keys) {
        const ids = keys.filter(Boolean);
        if (!ids.length) return;
        this.panelNotices = this.panelNotices.filter((n) => !ids.includes(n.key));
        try {
          const items = await wire.markPanelNotificationsRead(ids);
          if (Array.isArray(items)) this.panelNotices = items;
        } catch {
          this.toast('Notifications could not be marked as read.', 'warn');
        }
      },
      enhanceCopyright() {
        if (!document.querySelectorAll) return;
        for (const footer of document.querySelectorAll('footer,.fi-footer')) {
          if (footer.closest('.ps-modal') || footer.querySelector('[data-ps-attribution]') || !/©|copyright/i.test(footer.textContent)) continue;
          footer.setAttribute('data-ps-footer', '');
          const wrapper = footer.parentElement;
          if (wrapper && wrapper.children.length === 1 && !wrapper.matches('body,.fi-layout,.fi-main-ctn')) wrapper.setAttribute('data-ps-footer-wrapper', '');
          const line = [...(footer.querySelectorAll?.('p,div') || [])].reverse().find((el) => /©|copyright/i.test(el.textContent)) || footer;
          line.setAttribute('data-ps-copyright-line', '');
          const credit = document.createElement('span');
          credit.dataset.psAttribution = '';
          credit.append(document.createTextNode(' - Enhanced by '));
          const link = document.createElement('a');
          link.href = 'https://github.com/jerajerajerajera/PelicanSimplify';
          link.className = 'ps-project-link';
          link.textContent = 'Simplify';
          link.target = '_blank';
          link.rel = 'noopener noreferrer';
          credit.append(link);
          if (this.boot.build_version) credit.append(document.createTextNode(' · Build ' + this.boot.build_version));
          line.append(credit);
        }
      },
      applyFavicon() {
        if (!this.boot.favicon_url || !document.head) return;
        this.previousFavicons = [...document.head.querySelectorAll('link[rel~="icon"]')];
        this.previousFavicons.forEach((el) => el.remove());
        this.favicon = document.createElement('link');
        this.favicon.rel = 'icon';
        this.favicon.type = 'image/svg+xml';
        this.favicon.href = this.boot.favicon_url;
        document.head.append(this.favicon);
      },
      restoreFavicon() {
        if (!this.favicon) return;
        this.favicon.remove();
        this.previousFavicons?.forEach((el) => document.head.append(el));
        this.favicon = null;
      },
      focusModal() {
        if (typeof this.$nextTick !== 'function') return;
        this.$nextTick(() => {
          if (!this.modal || this.destroyed) return;
          const visible = (selector) => [...(this.$root?.querySelectorAll?.(selector) || [])].find((el) => el.getClientRects().length > 0);
          (visible('.ps-modal-body :is(input,select,textarea,button):not([disabled]),.ps-modal-body a[href]') || visible('.ps-modal-head .ps-close'))?.focus();
        });
      },

      /* Overview -------------------------------------------------------------------------------- */
      playerCount(s) {
        if (s?.status === 'offline') return '0 / 0';
        const count = Number(s?.players), max = Number(s?.max_players);
        if (s?.players == null || !Number.isFinite(count) || count < 0) return '—';
        return Math.floor(count) + ' / ' + (s?.max_players != null && Number.isFinite(max) && max >= 0 ? Math.floor(max) : 0);
      },
      openHostHealth() {
        if (this.server || this.busy || this.modal || !this.boot.features?.host_health) return;
        this.healthExpanded = !this.healthExpanded;
        this.healthOpenNodes = [];
      },
      healthNodes() {
        return this.boot.host_health?.nodes || [];
      },
      healthNodeOpen(node) {
        return this.healthOpenNodes.some((id) => String(id) === String(node.id));
      },
      toggleHealthNode(node) {
        if (!this.healthExpanded || this.server || this.busy || this.modal || !this.boot.features?.host_health || !this.healthNodes().some((n) => String(n.id) === String(node.id))) return;
        this.healthOpenNodes = this.healthNodeOpen(node) ? this.healthOpenNodes.filter((id) => String(id) !== String(node.id)) : [...this.healthOpenNodes, node.id];
      },
      nodeServers(node) {
        return (this.boot.servers || []).filter((s) => Number(s.node_id) === Number(node.id));
      },
      hostRate(h = this.boot.host_health) {
        return this.rateLabel(h?.network_rx_per_second, h?.network_tx_per_second);
      },
      rateLabel(rx, tx) {
        return rx == null || tx == null ? '—' : '↓ ' + this.trafficBytes(rx) + '/s / ↑ ' + this.trafficBytes(tx) + '/s';
      },
      serverRate(s) {
        const n = this.overviewNetwork[s.uuid];
        return this.rateLabel(n?.rx_per_second, n?.tx_per_second);
      },
      serverTraffic(s) {
        return s.usage?.network_rx_bytes == null || s.usage?.network_tx_bytes == null ? '—' : '↓ ' + this.trafficBytes(s.usage.network_rx_bytes) + ' / ↑ ' + this.trafficBytes(s.usage.network_tx_bytes);
      },
      networkSample(current, at, previous) {
        const rx = Number(current?.rx_bytes), tx = Number(current?.tx_bytes);
        if (current?.rx_bytes == null || current?.tx_bytes == null || !Number.isFinite(rx) || !Number.isFinite(tx) || rx < 0 || tx < 0) return null;
        const dt = at - (previous?.at ?? at), ready = previous && dt > 0 && dt <= 120;
        const reset = ready && (rx < previous.rx || tx < previous.tx);
        return { rx, tx, at, rx_per_second: ready ? (reset ? 0 : (rx - previous.rx) / dt) : null, tx_per_second: ready ? (reset ? 0 : (tx - previous.tx) / dt) : null };
      },
      recordNetwork(current, at = Date.now() / 1000) {
        this.networkPrevious = this.networkSample(current, at, this.networkPrevious);
        this.networkRates = { rx_per_second: this.networkPrevious?.rx_per_second ?? null, tx_per_second: this.networkPrevious?.tx_per_second ?? null };
        if (this.networkPrevious) {
          this.series.network.push((this.networkRates.rx_per_second ?? 0) + (this.networkRates.tx_per_second ?? 0));
          if (this.series.network.length > 30) this.series.network.shift();
        }
      },
      powerActionLabel(action) {
        return POWER_LABELS[action] || '';
      },
      canOverviewPower(s, action) {
        const permission = { start: 'control.start', stop: 'control.stop', kill: 'control.stop', restart: 'control.restart' }[action];
        return this.overviewPreferences.showQuickActions && !!permission && !!s.linked && !!s.permissions?.[permission]
          && !['installing', 'restoring'].includes(s.status)
          && (action !== 'start' || s.status === 'offline')
          && (!['stop', 'kill', 'restart'].includes(action) || s.status !== 'offline');
      },
      async overviewPower(s, action, approved = false) {
        if (this.overviewPowerBusy || !this.canOverviewPower(s, action)) return;
        if (!approved) {
          this.powerConfirmation = { uuid: s.uuid, action };
          return;
        }
        if (this.powerConfirmation?.uuid !== s.uuid || this.powerConfirmation?.action !== action) return;
        this.powerConfirmation = null;
        this.overviewPowerBusy = s.uuid;
        try {
          await wire.overviewPower(s.uuid, action);
          this.toast(POWER_LABELS[action] + ' sent to ' + (s.alias || s.name), 'ok');
          await this.refreshOverview();
        } catch (error) {
          this.toast(error?.message || 'The power action could not be sent.', 'danger');
        } finally {
          this.overviewPowerBusy = null;
        }
      },
      /** Large and secondary card names, following the "Large name" preference. */
      cardNames(s) {
        if (!s.alias) return { large: s.name, small: '' };
        return this.overviewPreferences.largeName === 'name' ? { large: s.name, small: s.alias } : { large: s.alias, small: s.name };
      },
      fitCardTitle(row, name) {
        const title = row.querySelector('h3');
        if (!title) return;
        const base = this.overviewView === 'list' ? 21 : 23;
        title.style.fontSize = base + 'px';
        const original = row.querySelector('.ps-card-real-name');
        const reserve = this.overviewView === 'list' && typeof innerWidth !== 'undefined' && innerWidth > 700 && original && !original.classList.contains('no-alias') ? original.getBoundingClientRect().width + 8 : 0;
        const available = Math.max(0, row.clientWidth - reserve);
        if (available > 0 && title.scrollWidth > available) title.style.fontSize = Math.max(10, Math.floor((base * available) / title.scrollWidth)) + 'px';
      },
      softwareTooltip(s) {
        const software = String(s.software || 'Server software').trim();
        const raw = String(s.reported_version || s.minecraft_version || '').trim();
        const version = s.game === 'minecraft' ? raw.match(MINECRAFT_VERSION)?.[1] || '' : raw;
        return (s.status === 'running' ? 'Running ' : '') + software + (version ? ' · ' + version : '');
      },
      gameLabel(s) {
        return s.game === 'minecraft' ? 'Minecraft' : String(s.game_label || 'Other game').trim() || 'Other game';
      },
      gameKey(s) {
        return this.gameLabel(s).toLocaleLowerCase();
      },
      gameOptions() {
        const games = new Map();
        for (const s of this.boot.servers || []) games.set(this.gameKey(s), this.gameLabel(s));
        return [...games].map(([key, label]) => ({ key, label })).sort((a, b) => a.label.localeCompare(b.label));
      },
      visibleServers() {
        return (this.boot.servers || []).filter((s) => this.gameFilter === 'all' || this.gameKey(s) === this.gameFilter);
      },
      setGameFilter(key) {
        if (key === 'all' || this.gameOptions().some((g) => g.key === key)) this.gameFilter = key;
      },
      overviewPreferencesKey() {
        return 'pelicanSimplify.overviewAppearance.' + encodeURIComponent(this.boot.user || 'default');
      },
      restoreOverviewPreferences() {
        const preferences = { ...OVERVIEW_DEFAULTS };
        try {
          const saved = JSON.parse(localStorage.getItem(this.overviewPreferencesKey()) || 'null');
          if (saved && typeof saved === 'object') {
            for (const key of OVERVIEW_SWITCHES) if (typeof saved[key] === 'boolean') preferences[key] = saved[key];
            if (['server', 'egg'].includes(saved.largeIcon)) preferences.largeIcon = saved.largeIcon;
            if (['alias', 'name'].includes(saved.largeName)) preferences.largeName = saved.largeName;
          }
        } catch {}
        this.overviewPreferences = preferences;
      },
      setOverviewPreference(key, value) {
        if (OVERVIEW_SWITCHES.includes(key)) {
          if (typeof value !== 'boolean') return;
        } else if (!({ largeIcon: ['server', 'egg'], largeName: ['alias', 'name'] }[key] || []).includes(value)) return;
        this.overviewPreferences = { ...this.overviewPreferences, [key]: value };
        if (key === 'showQuickActions' && !value) this.powerConfirmation = null;
        try {
          localStorage.setItem(this.overviewPreferencesKey(), JSON.stringify(this.overviewPreferences));
        } catch {}
      },
      overviewMetrics() {
        return [
          { key: 'cpu', label: 'CPU', icon: 'cpu', show: this.overviewPreferences.showCpu },
          { key: 'ram', label: 'RAM', icon: 'ram', show: this.overviewPreferences.showRam },
        ].filter((metric) => metric.show);
      },
      overviewViewKey() {
        return 'pelicanSimplify.overviewView.' + encodeURIComponent(this.boot.user || 'default');
      },
      restoreOverviewView() {
        const phone = typeof innerWidth !== 'undefined' && innerWidth <= 600;
        this.overviewCompact = phone;
        this.overviewView = 'grid';
        try {
          const view = localStorage.getItem(this.overviewViewKey());
          if (['grid', 'list'].includes(view)) this.overviewView = view;
        } catch {}
        if (phone) this.overviewView = 'list';
      },
      setOverviewView(view) {
        if (!['grid', 'list'].includes(view)) return;
        if (typeof innerWidth !== 'undefined' && innerWidth <= 600) {
          this.overviewView = 'list';
          return;
        }
        this.overviewView = view;
        try {
          localStorage.setItem(this.overviewViewKey(), view);
        } catch {}
      },
      /** Keeps the health dock aligned with the rendered card grid (including scrollbar width). */
      initOverviewDock() {
        if (this.server || !this.$root || typeof ResizeObserver === 'undefined') return;
        this.overviewDockObserver?.disconnect();
        const overview = this.$root.querySelector('.ps-overview'), grid = overview?.querySelector('.ps-server-grid');
        if (!overview || !grid) return;
        const sync = () => {
          const parent = overview.getBoundingClientRect(), cards = grid.getBoundingClientRect();
          if (cards.width <= 0) return;
          overview.style.setProperty('--ps-health-width', cards.width + 'px');
          overview.style.setProperty('--ps-health-left', cards.left - parent.left + 'px');
        };
        this.overviewDockObserver = new ResizeObserver(sync);
        this.overviewDockObserver.observe(overview);
        this.overviewDockObserver.observe(grid);
        sync();
      },
      overviewColumns() {
        return Math.min(4, Math.max(1, this.visibleServers().length + (this.boot.can_create ? 1 : 0)));
      },
      cpuLabel(s) {
        const n = s.usage?.cpu_percent;
        return typeof n === 'number' && Number.isFinite(n) ? n.toFixed(1) + '%' : '—';
      },
      cpuFill(s) {
        const n = s.usage?.cpu_percent;
        if (typeof n !== 'number' || !Number.isFinite(n)) return 0;
        const limit = s.usage?.cpu_limit > 0 ? s.usage.cpu_limit : 100;
        return Math.min(100, Math.max(0, (n / limit) * 100));
      },
      memoryLabel(s) {
        const n = s.usage?.memory_bytes;
        if (typeof n !== 'number' || !Number.isFinite(n)) return '—';
        const cap = s.usage?.memory_limit_bytes || 0;
        const gb = Math.max(n, cap) >= 1073741824;
        const unit = gb ? 1073741824 : 1048576;
        const number = (v) => (gb ? (v / unit).toFixed(1) : Math.round(v / unit));
        return number(n) + (cap > 0 ? ' / ' + number(cap) : '') + (gb ? ' GB' : ' MB');
      },
      hostPercent(kind, h = this.boot.host_health) {
        if (!h || !h.available) return null;
        if (kind === 'cpu') return h.cpu_percent;
        const total = h[kind + '_total'];
        return total > 0 ? (h[kind + '_used'] / total) * 100 : null;
      },
      hostMetricState(kind, h = this.boot.host_health) {
        if (h?.metric_states?.[kind]) return h.metric_states[kind];
        const n = this.hostPercent(kind, h);
        if (typeof n !== 'number' || !Number.isFinite(n) || h.available < h.hosts) return 'unavailable';
        return n >= 95 ? 'critical' : n >= 85 ? 'warning' : 'healthy';
      },
      hostMetricLabel(kind, h = this.boot.host_health) {
        if (kind === 'network') return this.hostMetricState(kind, h) === 'healthy' ? 'Traffic reported; bandwidth capacity unknown' : 'Unavailable';
        return { healthy: 'Healthy', warning: 'High load', critical: 'Critical load', unavailable: 'Unavailable' }[this.hostMetricState(kind, h)] || 'Unavailable';
      },
      hostFill(kind, h = this.boot.host_health) {
        if (kind === 'network') return (h?.network_rx_bytes ?? 0) + (h?.network_tx_bytes ?? 0) > 0 ? 100 : 0;
        return Math.min(100, Math.max(0, this.hostPercent(kind, h) || 0));
      },
      hostValue(kind, h = this.boot.host_health) {
        if (kind === 'network') return h?.network_rx_bytes == null || h?.network_tx_bytes == null ? '—' : '↓ ' + this.trafficBytes(h.network_rx_bytes) + ' / ↑ ' + this.trafficBytes(h.network_tx_bytes);
        const n = this.hostPercent(kind, h);
        if (typeof n !== 'number' || !Number.isFinite(n)) return '—';
        if (kind === 'cpu') return n.toFixed(1) + '%';
        return this.memoryLabel({ usage: { memory_bytes: h[kind + '_used'], memory_limit_bytes: h[kind + '_total'] } });
      },
      recordOverview(servers) {
        const ids = new Set();
        for (const s of servers || []) {
          ids.add(s.uuid);
          const h = this.overviewHistory[s.uuid] || (this.overviewHistory[s.uuid] = []);
          h.push({ cpu: s.usage?.cpu_percent ?? null, ram: s.usage?.memory_bytes ?? null });
          this.overviewNetwork[s.uuid] = this.networkSample({ rx_bytes: s.usage?.network_rx_bytes, tx_bytes: s.usage?.network_tx_bytes }, s.usage?.network_sampled_at ?? Date.now() / 1000, this.overviewNetwork[s.uuid]);
          if (h.length > 24) h.shift();
        }
        for (const id of Object.keys(this.overviewHistory)) {
          if (ids.has(id)) continue;
          delete this.overviewHistory[id];
          delete this.overviewNetwork[id];
        }
      },
      cardRamLabel(s) {
        return this.memoryLabel({ usage: { memory_bytes: s.usage?.memory_bytes } });
      },
      cardGraphY(s, key, value) {
        if (typeof value !== 'number' || !Number.isFinite(value) || value < 0) return null;
        const limit = key === 'cpu'
          ? (s.usage?.cpu_limit > 0 ? s.usage.cpu_limit : 100)
          : (s.usage?.memory_limit_bytes > 0 ? s.usage.memory_limit_bytes : Math.max(1073741824, ...(this.overviewHistory[s.uuid] || []).map((p) => p.ram || 0)));
        return 26 - Math.min(1, value / limit) * 23;
      },
      cardGraph(s, key) {
        const h = this.overviewHistory[s.uuid] || [];
        let path = '', drawing = false;
        h.forEach((p, i) => {
          const y = this.cardGraphY(s, key, p[key]);
          if (y === null) {
            drawing = false;
            return;
          }
          const x = 2 + ((24 - h.length + i) / 23) * 96;
          path += (drawing ? ' L' : ' M') + x.toFixed(2) + ' ' + y.toFixed(2);
          drawing = true;
        });
        return path;
      },
      cardGraphPoint(s, key) {
        const h = this.overviewHistory[s.uuid] || [];
        return h.length ? this.cardGraphY(s, key, h[h.length - 1][key]) : null;
      },
      async refreshOverview() {
        if (globalThis.document?.hidden || this.server || this.destroyed || this.overviewRefreshing) return;
        this.overviewRefreshing = true;
        try {
          const [servers, health] = await Promise.all([wire.listing(), this.boot.features?.host_health ? wire.hostHealth() : Promise.resolve({})]);
          if (this.destroyed) return;
          this.boot.servers = servers;
          if (this.gameFilter !== 'all' && !this.gameOptions().some((g) => g.key === this.gameFilter)) this.gameFilter = 'all';
          this.boot.host_health = health;
          this.healthOpenNodes = this.healthOpenNodes.filter((id) => health.nodes?.some((n) => String(n.id) === String(id)));
          this.recordOverview(servers);
        } catch {
        } finally {
          this.overviewRefreshing = false;
        }
      },

      /* Creation, settings and account popups (embedded pages) ---------------------------------- */
      /** Same-origin URL with the embed marker, or '' for anything else. */
      frameUrl(href) {
        try {
          const url = new URL(href, location.origin);
          if (url.origin !== location.origin) return '';
          url.searchParams.set('simplify_embed', '1');
          return url.href;
        } catch {
          return '';
        }
      },
      createFrameUrl() {
        const url = this.frameUrl(this.createGame === 'minecraft' ? this.boot.create_url : this.boot.legacy_create_url);
        if (!url) return '';
        const target = new URL(url);
        if (this.createGame !== 'native') target.searchParams.set('game', this.createGame);
        return target.href;
      },
      canCreateGame(game) {
        // 'native' is the only choice when the easy creator is off (Pelican's own form, administrators).
        return !!this.boot.can_create && (game === 'minecraft' || !!this.boot.legacy_create_url) && ['minecraft', 'other', 'native'].includes(game) && (this.boot.create_games || ['minecraft', 'other']).includes(game);
      },
      openCreate(game = 'minecraft') {
        if (!['minecraft', 'other', 'native'].includes(game) || this.busy || this.server || !this.canCreateGame(game)) return;
        this.createGame = game;
        this.openPopup('create');
      },
      openPopup(key) {
        this.accountMenu = false;
        this.navMenu = false;
        this.notifyMenu = false;
        this.appearanceMenu = false;
        this.modalRequest++;
        this.modal = key;
        this.loading = false;
        this.focusModal();
      },
      async createFrameLoaded(frame) {
        if (this.modal !== 'create') return;
        let completed = false, createdUrl = null;
        try {
          const current = new URL(frame.contentWindow.location.href, location.origin);
          const list = new URL(this.boot.list_url, location.origin);
          const creator = new URL(this.createGame === 'minecraft' ? this.boot.create_url : this.boot.legacy_create_url, location.origin);
          if (current.origin === location.origin) {
            completed = current.pathname === list.pathname && /^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/i.test(current.searchParams.get('server') || '');
            if (completed) createdUrl = current.href;
            if (!completed && /\/create\/?$/.test(creator.pathname)) {
              const prefix = creator.pathname.replace(/create\/?$/, '');
              if (current.pathname.startsWith(prefix)) {
                const suffix = current.pathname.slice(prefix.length).split('/').filter(Boolean);
                completed = /^\d+$/.test(suffix[0] || '') && (suffix.length === 1 || (suffix.length === 2 && ['edit', 'view'].includes(suffix[1])));
              }
            }
          }
        } catch {}
        if (completed) {
          this.modal = null;
          this.loading = false;
          if (createdUrl) {
            location.href = createdUrl;
            return;
          }
          await this.refreshOverview();
          this.toast('Server created. Installation has started.', 'ok');
          return;
        }
        this.styleFrame(frame, this.createGame === 'minecraft' ? 'create' : 'native');
      },
      panelSettingsUrl() {
        const url = this.frameUrl(this.boot.settings_url);
        if (!url || this.settingsSection !== 'banners') return url;
        const target = new URL(url);
        target.searchParams.set('section', 'banners');
        return target.href;
      },
      openPanelSettings(section = 'settings') {
        if (!this.boot.settings_url || this.busy || this.modal) return;
        this.settingsSection = section;
        this.openPopup('panel-settings');
      },
      panelSettingsLoaded(frame) {
        this.styleFrame(frame, 'settings');
        try {
          if (frame.contentWindow._psSettingsListener) return;
          frame.contentWindow._psSettingsListener = true;
          frame.contentWindow.addEventListener('simplify-settings-saved', () => {
            if (this.modal !== 'panel-settings' || this.destroyed) return;
            this.settingsChanged = true;
            this.toast('Settings saved', 'ok');
          });
        } catch {
          this.toast('Settings could not be opened.', 'danger');
        }
      },
      openAccount() {
        if (!this.boot.profile_url || this.busy || this.modal) return;
        this.openPopup('account');
      },
      openAlias() {
        if (this.busy || this.modal || !this.server?.permissions?.['settings.rename']) return;
        this.aliasDraft = this.server.alias || '';
        this.openPopup('alias');
      },
      async saveAlias() {
        const result = await this.run(() => wire.saveAlias(this.aliasDraft), 'Alias saved');
        if (result !== SKIPPED && result) Object.assign(this.server, result);
      },

      /* Text helpers ---------------------------------------------------------------------------- */
      minecraftVersion() {
        const match = String(this.server?.reported_version || '').match(MINECRAFT_VERSION);
        if (match) return match[1];
        const saved = String(this.server?.pack?.version || '').trim();
        return /^(?:1\.[0-9]+(?:\.[0-9]+)?|2[6-9]\.[0-9]+(?:\.[0-9]+)?)$/.test(saved) ? saved : 'Not reported';
      },
      motdColors: [
        ['0', 'Black', '#000000'], ['1', 'Dark blue', '#0000aa'], ['2', 'Dark green', '#00aa00'], ['3', 'Dark aqua', '#00aaaa'],
        ['4', 'Dark red', '#aa0000'], ['5', 'Dark purple', '#aa00aa'], ['6', 'Gold', '#ffaa00'], ['7', 'Gray', '#aaaaaa'],
        ['8', 'Dark gray', '#555555'], ['9', 'Blue', '#5555ff'], ['a', 'Green', '#55ff55'], ['b', 'Aqua', '#55ffff'],
        ['c', 'Red', '#ff5555'], ['d', 'Light purple', '#ff55ff'], ['e', 'Yellow', '#ffff55'], ['f', 'White', '#ffffff'],
      ],
      insertMotdCode(code) {
        if (!/^[0-9a-fk-or]$/.test(code)) return;
        const field = this.$refs?.motdInput;
        const text = String(this.settings.motd || ''), start = field?.selectionStart ?? text.length, end = field?.selectionEnd ?? start;
        const prefix = '§' + code, suffix = end > start && code !== 'r' ? '§r' : '';
        const value = text.slice(0, start) + prefix + text.slice(start, end) + suffix + text.slice(end);
        if (value.length > 512) {
          this.toast('MOTD can contain at most 512 characters.', 'warn');
          return;
        }
        this.settings.motd = value;
        this.$nextTick?.(() => {
          field?.focus();
          field?.setSelectionRange(start + prefix.length, end + prefix.length);
        });
      },
      motdPreview(value = this.settings.motd) {
        const colors = Object.fromEntries(this.motdColors.map(([code, , color]) => [code, color]));
        const parts = [];
        let style = { color: '#aaaaaa' }, text = '';
        const flush = () => {
          if (text) {
            parts.push({ text, style: { ...style } });
            text = '';
          }
        };
        value = String(value || '');
        for (let i = 0; i < value.length; i++) {
          if (value[i] === '§' && /^§x(?:§[0-9a-f]){6}/i.test(value.slice(i))) {
            flush();
            style = { color: '#' + value.slice(i + 2, i + 14).replace(/§/g, '') };
            i += 13;
          } else if (value[i] === '§' && /[0-9a-fk-or]/i.test(value[i + 1] || '')) {
            flush();
            const code = value[++i].toLowerCase();
            if (colors[code]) style = { color: colors[code] };
            else if (code === 'r') style = { color: '#aaaaaa' };
            else if (code === 'l') style.fontWeight = 'bold';
            else if (code === 'o') style.fontStyle = 'italic';
            else if (code === 'n') style.textDecoration = (style.textDecoration || '') + ' underline';
            else if (code === 'm') style.textDecoration = (style.textDecoration || '') + ' line-through';
            else if (code === 'k') style.obfuscated = true;
          } else text += value[i];
        }
        flush();
        return parts.map((part) => ({ ...part, text: part.style.obfuscated ? part.text.replace(/[^\s]/g, '▒') : part.text }));
      },
      cleanMotd(s) {
        return String(s).replace(/§[0-9A-FK-OR]/gi, '').replace(/\\n/g, ' ').trim();
      },
      safeImage(s) {
        if (typeof s !== 'string' || !s.trim()) return '';
        if (s.startsWith('data:image/png;base64,') && s.length < 150000) return s;
        try {
          const u = new URL(s, location.origin);
          return ['https:', 'http:'].includes(u.protocol) ? u.href : '';
        } catch {
          return '';
        }
      },
      safeLink(s) {
        try {
          const u = new URL(s);
          return ['https:', 'http:'].includes(u.protocol) ? u.href : '#';
        } catch {
          return '#';
        }
      },
      head(p) {
        return 'https://mc-heads.net/avatar/' + encodeURIComponent(p.id || p.uuid || p.name) + '/48';
      },
      trafficBytes(v) {
        if (!Number.isFinite(Number(v)) || Number(v) < 0) return '—';
        v = Number(v);
        if (v >= 1073741824) return (v / 1073741824).toFixed(1) + ' GB';
        if (v >= 1048576) return (v / 1048576).toFixed(1) + ' MB';
        if (v >= 1024) return (v / 1024).toFixed(1) + ' KB';
        return Math.round(v) + ' B';
      },
      bytes(v) {
        return v >= 1073741824 ? (v / 1073741824).toFixed(1) + ' GB' : (v / 1048576).toFixed(0) + ' MB';
      },

      /* Actions ---------------------------------------------------------------------------------- */
      /** Runs one server action at a time; failures are reported once. Returns SKIPPED when it did not run. */
      async run(fn, message) {
        if (this.busy) return SKIPPED;
        this.busy = true;
        try {
          const result = await fn();
          if (message) this.toast(message, 'ok');
          return result;
        } catch (e) {
          this.toast(e?.message || 'The action could not be completed.', 'danger');
          return SKIPPED;
        } finally {
          this.busy = false;
        }
      },
      async copy(text, message = 'Address copied') {
        try {
          await navigator.clipboard.writeText(text);
        } catch {
          const el = document.createElement('textarea');
          el.value = text;
          document.body.append(el);
          el.select();
          document.execCommand('copy');
          el.remove();
        }
        this.toast(message, 'ok');
      },
      fillSettings() {
        this.name = this.server?.name || '';
        const p = { ...(this.server?.properties || {}), ...(this.server?.pending?.properties || {}) };
        const difficulty = ['peaceful', 'easy', 'normal', 'hard'], modes = ['survival', 'creative', 'adventure', 'spectator'];
        this.settings = {
          motd: p.motd || '',
          max_players: Number(p['max-players'] || 20),
          difficulty: difficulty[Number(p.difficulty)] || p.difficulty || 'normal',
          gamemode: modes[Number(p.gamemode)] || p.gamemode || 'survival',
          view_distance: Number(p['view-distance'] || 10),
          spawn_protection: Number(p['spawn-protection'] ?? 16),
          pvp: p.pvp !== 'false',
          white_list: p['white-list'] === 'true',
          allow_flight: p['allow-flight'] === 'true',
        };
        if (this.server?.can_resources) {
          Object.assign(this.settings, { memory_gb: this.server.memory / 1024, disk_mb: this.server.disk, cpu: this.server.cpu, ...(this.server?.pending?.resources || {}) });
        }
      },
      packageKind() {
        return ['mods', 'plugins'].includes(this.server?.package_kind) ? this.server.package_kind : '';
      },
      isPackageModal() {
        return ['mods', 'plugins'].includes(this.modal);
      },
      showWidget(key) {
        const feature = { performance: 'resources', plugins: 'packages', mods: 'packages' }[key] || key;
        if (this.boot.features?.[feature] === false) return false;
        const p = this.server?.permissions || {};
        switch (key) {
          case 'web': return !!(this.boot.web && this.server?.owner);
          case 'files': return !!p['file.read'];
          case 'backups': return !!p['backup.read'];
          case 'settings': return !!((this.server?.minecraft && p['file.read-content']) || p['settings.rename']);
          case 'performance': return !!this.server;
          case 'restart': return !!(this.server?.pending && p['control.restart'] && p['control.stop'] && p['control.start']);
          case 'players': return !!(this.server?.minecraft && p['file.read-content']);
          case 'mods':
          case 'plugins': return !!(this.packageKind() === key && p['file.read']);
          case 'schedules': return !!(p['schedule.read'] && this.server?.native_sections?.schedules);
          case 'activity': return !!(p['activity.read'] && this.server?.native_sections?.activity);
          case 'software': return !!(p['startup.read'] && (this.server?.minecraft || this.server?.native_sections?.startup));
          case 'advanced':
          case 'connection': return !!p['startup.read'];
          default: return false;
        }
      },
      widgetTitle(widget) {
        if (!this.server?.minecraft) {
          if (widget.key === 'settings') return 'Server settings';
          if (widget.key === 'software') return 'Startup settings';
        }
        return widget.title;
      },
      widgetDescription(widget) {
        if (this.server?.minecraft) return widget.description;
        return {
          settings: 'Server name & appearance',
          software: (this.server?.software || 'Game') + ' startup variables',
          advanced: 'Runtime details & ' + (this.server?.software || 'server') + ' management',
        }[widget.key] || widget.description;
      },
      modalTitle() {
        if (this.modal === 'alias') return 'Change server alias';
        if (this.modal === 'background') return 'Server background';
        if (this.modal === 'panel-settings') return this.settingsSection === 'banners' ? 'Server banners' : 'Simplify settings';
        if (this.modal === 'create') return { other: 'Create other game server', native: 'Create server' }[this.createGame] || 'Create Minecraft server';
        if (this.modal === 'account') return 'Profile';
        const widget = this.widgets.find((w) => w.key === this.modal);
        return (widget ? this.widgetTitle(widget) : null) || (this.modal === 'restart' ? 'Schedule restart' : 'Workspace');
      },
      /** Every popup has a fixed size: small for short forms, large for embedded or list workspaces. */
      modalSize() {
        if (['alias', 'restart'].includes(this.modal)) return 'is-sm';
        if (['create', 'panel-settings', 'account', 'files', 'schedules', 'activity', 'backups', 'software', 'settings', 'plugins', 'mods'].includes(this.modal)) return 'is-lg';
        return 'is-md';
      },
      modalFlush() {
        return ['create', 'panel-settings', 'account', 'files', 'schedules', 'activity'].includes(this.modal) || (this.modal === 'software' && !this.server?.minecraft);
      },
      settingsTabs() {
        return [
          { key: 'general', label: 'General', icon: 'edit' },
          { key: 'gameplay', label: 'Gameplay', icon: 'settings' },
          { key: 'motd', label: 'MOTD', icon: 'image' },
        ];
      },
      backupPolicyLabel() {
        const options = this.server?.backup_options || {};
        if (options.enabled === false) return 'Paused for this server';
        const minutes = Number(options.interval_minutes) || 360;
        const every = minutes % 60 === 0 ? (minutes === 60 ? 'Every hour' : 'Every ' + minutes / 60 + ' hours') : 'Every ' + minutes + ' minutes';
        return every + ' · ' + (options.retention || 2) + ' recovery points' + (options.only_when_used !== false ? ' · skipped while the server has not run' : '');
      },
      async open(key) {
        if (this.busy || !this.showWidget(key)) return;
        const request = ++this.modalRequest;
        this.modal = key;
        this.loading = true;
        const current = () => !this.destroyed && this.modalRequest === request && this.modal === key;
        const load = async (fn) => {
          const value = await fn();
          if (!current()) throw new Error('Workspace closed');
          return value;
        };
        try {
          if (key === 'backups') this.backups = await load(() => wire.backupList());
          if (key === 'web') this.website = await load(() => wire.website());
          if (key === 'players') this.playerLists = await load(() => wire.players());
          if (['mods', 'plugins'].includes(key)) {
            this.packageQuery = '';
            this.packageResults = [];
            this.packageVersion = '';
            this.installed = await load(() => wire.installedPackages());
            if (this.installed.context?.kind !== key) throw new Error('This server does not support ' + key + '.');
            this.packageVersion = this.installed.context?.version || '';
          }
          if (['advanced', 'software', 'connection'].includes(key) && (key !== 'software' || this.server?.minecraft)) {
            this.advanced = await load(() => (key === 'connection' ? wire.connectionData() : wire.advancedData()));
            this.requestedSubdomain = this.advanced.requested_subdomain || '';
          }
          if (['settings', 'performance'].includes(key)) this.fillSettings();
          if (key === 'settings') {
            this.settingsTab = 'general';
            this.aliasDraft = this.server?.alias || '';
          }
          if (key === 'backups') this.backupSettings = { only_when_used: true, ...this.server?.backup_options };
        } catch (e) {
          if (current()) {
            this.toast(e?.message || 'This section is unavailable.', 'danger');
            this.modal = null;
          }
        } finally {
          if (this.modalRequest === request) {
            this.loading = false;
            this.focusModal();
          }
        }
      },
      async close() {
        if (this.busy || this.closingCreator) return;
        if (this.fileEditorDirty && !(await this.ask({ title: 'Discard unsaved file changes?', message: 'Your edits in the file editor have not been saved.', confirmLabel: 'Discard changes', tone: 'danger' }))) return;
        this.fileEditorUrl = null;
        this.fileEditorDirty = false;
        this.sftpOpen = false;
        const creating = this.modal === 'create', changedSettings = this.modal === 'panel-settings' && this.settingsChanged;
        if (creating) {
          this.closingCreator = true;
          try {
            const save = this.$root?.querySelector('iframe[title="Create server"]')?.contentWindow?.sscSaveDraft;
            if (save) await save();
            else if (!(await this.ask({ title: 'Leave the creator?', message: 'Unsaved changes may be lost.', confirmLabel: 'Leave' }))) return;
          } catch {
            this.toast('Draft could not be saved. Please try again before closing.', 'danger');
            return;
          } finally {
            this.closingCreator = false;
          }
        }
        this.modalRequest++;
        this.modal = null;
        this.loading = false;
        if (creating) this.refreshOverview();
        if (changedSettings) location.reload();
      },

      /* Console --------------------------------------------------------------------------------- */
      async connect() {
        if (this.destroyed || !this.server?.permissions['websocket.connect']) return;
        const retry = () => {
          // Back off from 4 seconds up to 5 minutes while Wings is unreachable.
          this.reconnectDelay = Math.min(300000, this.reconnectDelay ? this.reconnectDelay * 2 : 4000);
          if (!this.destroyed) this.reconnect = setTimeout(() => this.connect(), this.reconnectDelay);
        };
        try {
          const auth = await wire.socket();
          if (this.destroyed) return;
          this.networkPrevious = null;
          this.networkRates = { rx_per_second: null, tx_per_second: null };
          const socket = new WebSocket(auth.url);
          this.ws = socket;
          socket.onopen = () => socket.send(JSON.stringify({ event: 'auth', args: [auth.token] }));
          socket.onmessage = async (msg) => {
            let data;
            try {
              data = JSON.parse(msg.data);
            } catch {
              return;
            }
            const { event, args = [] } = data;
            if (event === 'auth success') {
              this.connected = true;
              this.reconnectDelay = 0;
              this.renderLogs();
              socket.send(JSON.stringify({ event: 'send logs', args: [null] }));
              socket.send(JSON.stringify({ event: 'send stats', args: [null] }));
            }
            if (['console output', 'install output', 'daemon error'].includes(event)) {
              this.logs.push(String(args[0] ?? '').replace(/\x1b\[[0-9;?]*[ -/]*[@-~]/g, ''));
              if (this.logs.length > 1200) this.logs.splice(0, this.logs.length - 1200);
              this.renderLogs();
            }
            if (event === 'status') {
              this.status = args[0];
              if (this.status === 'offline') {
                this.networkPrevious = null;
                this.networkRates = { rx_per_second: 0, tx_per_second: 0 };
                this.stats.network = { rx_bytes: 0, tx_bytes: 0 };
                this.stats.cpu_absolute = 0;
                this.stats.memory_bytes = 0;
                this.server.players = 0;
                this.server.player_names = [];
              }
            }
            if (event === 'stats') {
              try {
                const v = typeof args[0] === 'string' ? JSON.parse(args[0]) : args[0];
                Object.assign(this.stats, v);
                this.recordNetwork(v.network);
                if (!this.networkPrevious) this.stats.network = null;
                for (const [key, field] of [['cpu', 'cpu_absolute'], ['ram', 'memory_bytes'], ['disk', 'disk_bytes']]) {
                  this.series[key].push(Number(v[field] || 0));
                  if (this.series[key].length > 30) this.series[key].shift();
                }
              } catch {}
            }
            if (['token expiring', 'token expired'].includes(event)) {
              try {
                const refresh = await wire.socket();
                socket.send(JSON.stringify({ event: 'auth', args: [refresh.token] }));
              } catch {
                socket.close();
              }
            }
            if (event === 'install completed') this.toast('Installation completed. Refresh this dashboard to load the new software details.', 'ok');
          };
          socket.onerror = () => (this.connected = false);
          socket.onclose = () => {
            this.connected = false;
            retry();
          };
        } catch {
          this.connected = false;
          retry();
        }
      },
      renderLogs() {
        this.$nextTick(() => {
          const el = this.$refs.output;
          if (!el) return;
          const text = this.logs.join('\n');
          if (el.textContent === text) return;
          const bottom = el.scrollHeight - el.scrollTop - el.clientHeight < 80;
          el.textContent = text;
          if (bottom) el.scrollTop = el.scrollHeight;
        });
      },
      sendCommand() {
        const cmd = this.command.trim();
        if (!cmd || !this.connected || !this.server.permissions['control.console']) return;
        this.ws.send(JSON.stringify({ event: 'send command', args: [cmd] }));
        this.history = [cmd, ...this.history].slice(0, 50);
        this.historyIndex = -1;
        this.command = '';
      },
      historyBack() {
        this.historyIndex = Math.min(this.historyIndex + 1, this.history.length - 1);
        this.command = this.history[this.historyIndex] || '';
      },
      historyForward() {
        this.historyIndex = Math.max(-1, this.historyIndex - 1);
        this.command = this.history[this.historyIndex] || '';
      },
      metricValue(key) {
        if (key === 'network') return this.rateLabel(this.networkRates.rx_per_second, this.networkRates.tx_per_second);
        const v = this.stats[{ cpu: 'cpu_absolute', ram: 'memory_bytes', disk: 'disk_bytes' }[key]];
        if (v === null || v === undefined) return '—';
        return key === 'cpu' ? Number(v).toFixed(1) + '%' : this.bytes(v);
      },
      metricLimit(key) {
        if (key === 'network') {
          const n = this.stats.network;
          return 'Used ' + (n?.rx_bytes == null || n?.tx_bytes == null ? '—' : '↓ ' + this.trafficBytes(n.rx_bytes) + ' / ↑ ' + this.trafficBytes(n.tx_bytes)) + ' · since server start';
        }
        if (key === 'cpu') return `of ${this.server?.cpu || '∞'}% allocated`;
        return `of ${this.bytes(((key === 'ram' ? this.server?.memory : this.server?.disk) || 0) * 1048576)} allocated`;
      },
      spark(key) {
        const a = this.series[key];
        if (a.length < 2) return 'M0 34 L200 34';
        const max = Math.max(...a, 1);
        return a.map((v, i) => `${i ? 'L' : 'M'}${((i * 200) / (a.length - 1)).toFixed(1)} ${(34 - (v / max) * 30).toFixed(1)}`).join(' ');
      },
      async refreshServerInfo() {
        if (globalThis.document?.hidden || this.busy || this.refreshingInfo || this.destroyed) return;
        this.refreshingInfo = true;
        try {
          const info = await wire.refreshInfo();
          if (this.destroyed) return;
          Object.assign(this.server, info);
          this.renderLogs();
        } catch {
        } finally {
          this.refreshingInfo = false;
        }
      },
      async power(action) {
        if (action === 'kill' && !(await this.ask({ title: 'Force stop this server?', message: 'The server stops immediately. Unsaved game progress may be lost.', confirmLabel: 'Force stop', tone: 'danger' }))) return;
        const result = await this.run(() => wire.power(action), action === 'restart' && this.server.pending ? 'Restart queued' : POWER_LABELS[action] + ' sent');
        if (result !== SKIPPED && ['restart', 'stop'].includes(action) && this.server.pending) Object.assign(this.server, await wire.refreshInfo());
      },

      /* Appearance -------------------------------------------------------------------------------- */
      openBackground() {
        if (this.busy || this.modal || !this.server?.permissions?.['settings.rename']) return;
        this.colorMenu = false;
        this.backgroundDraft = this.server.background_id || 'auto';
        this.openPopup('background');
      },
      async uploadBanner() {
        const result = await this.run(() => wire.uploadServerBanner(), 'Banner uploaded');
        if (result === SKIPPED || !result) return;
        this.uploadedBackgrounds = result.uploaded_backgrounds || [];
        this.backgroundDraft = result.background_id;
        Object.assign(this.server, result);
      },
      async deleteBanner(id) {
        if (this.busy || !(await this.ask({ title: 'Remove this uploaded banner?', message: 'Servers using it return to their automatic background.', confirmLabel: 'Remove banner', tone: 'danger' }))) return;
        const result = await this.run(() => wire.deleteServerBanner(id), 'Banner removed');
        if (result === SKIPPED || !result) return;
        this.uploadedBackgrounds = result.uploaded_backgrounds || [];
        this.backgroundDraft = result.background_id;
        Object.assign(this.server, result);
      },
      async saveBackground() {
        if (this.busy || !this.server?.permissions?.['settings.rename']) return;
        const result = await this.run(() => wire.saveServerBackground(this.backgroundDraft), 'Server background saved');
        if (result === SKIPPED || !result || !Object.hasOwn(result, 'background')) return;
        Object.assign(this.server, result);
      },
      toggleColorMenu(button) {
        if (this.colorMenu) {
          this.colorMenu = false;
          return;
        }
        this.colorDraft = this.server?.color || this.currentAccentHex();
        const box = button.getBoundingClientRect();
        this.colorMenuStyle = { left: Math.max(12, Math.min(box.left - 200, innerWidth - 260)) + 'px', top: Math.max(12, Math.min(box.bottom + 8, innerHeight - 240)) + 'px' };
        this.colorMenu = true;
      },
      async commitServerColor() {
        if (await this.saveServerColor(this.colorDraft)) this.colorMenu = false;
      },
      async saveServerColor(color) {
        if (!this.server?.permissions?.['settings.rename'] || this.busy) return false;
        const saved = await this.run(() => wire.saveServerColor(color), 'Server color saved');
        if (typeof saved !== 'string' || !/^#[0-9a-f]{6}$/i.test(saved)) return false;
        this.server.color = saved;
        try {
          this.boot.available_colors = await wire.availableServerColors();
        } catch {}
        return true;
      },

      /* Settings, resources and restarts -------------------------------------------------------- */
      async saveSettings() {
        const state = { ...this.settings };
        const resources = this.modal === 'performance';
        if (!resources) for (const key of ['memory_gb', 'disk_mb', 'cpu']) delete state[key];
        const result = await this.run(() => (resources ? wire.saveResourceLimits({ memory_gb: state.memory_gb, cpu: state.cpu, disk_mb: state.disk_mb }) : wire.saveSettings(state)));
        if (result === SKIPPED) return;
        try {
          Object.assign(this.server, await wire.refreshInfo());
        } catch {}
        if (result?.changed === false) this.toast('No changes to save.', 'info');
        else if (result?.pending) this.toast('Changes saved. Stop or restart to apply them.', 'ok');
        else this.toast('Changes applied. They will load at the next start.', 'ok');
      },
      async rename() {
        const name = this.name;
        const result = await this.run(() => wire.rename(name), 'Server renamed');
        if (result !== SKIPPED) this.server.name = name;
      },
      async discard() {
        const result = await this.run(() => wire.discard(), this.server?.pending?.file_only ? 'Restart reminder dismissed' : 'Pending changes discarded');
        if (result !== SKIPPED) this.server.pending = null;
      },
      async restartChanges(minutes) {
        const result = await this.run(() => wire.restartChanges(Number(minutes)), minutes ? 'Restart scheduled' : 'Restart queued. Settings will be applied when the server is stopped.');
        if (result === SKIPPED) return;
        if (this.modal === 'restart') this.modal = null;
        try {
          Object.assign(this.server, await wire.refreshInfo());
        } catch {}
      },
      async toggleWeb(s, value) {
        const result = await this.run(() => wire.saveWebsite({ visible: value }, s.uuid), 'Website visibility updated');
        s.visible = result === SKIPPED ? !value : value;
      },
      async saveWebsite() {
        const state = { ...this.website };
        delete state.display_name;
        await this.run(() => wire.saveWebsite(state), 'Website settings saved');
      },
      async saveSubdomain() {
        await this.run(() => wire.subdomain(this.requestedSubdomain), 'Subdomain request saved. DNS will be connected later.');
      },

      /* Players and packages ------------------------------------------------------------------- */
      async playerAction(action) {
        if (!/^[A-Za-z0-9_]{1,16}$/.test(this.playerName)) {
          this.toast('Enter a valid Minecraft username.', 'warn');
          return;
        }
        if (['OP', 'Ban', 'Kick'].includes(action) && !(await this.ask({ title: action + ' ' + this.playerName + '?', message: action === 'OP' ? 'Operators can run every server command.' : 'The player is removed from the server.', confirmLabel: action, tone: action === 'OP' ? 'primary' : 'danger' }))) return;
        const prefixes = { Whitelist: 'whitelist add', Unwhitelist: 'whitelist remove', OP: 'op', DeOP: 'deop', Kick: 'kick', Ban: 'ban', Unban: 'pardon' };
        this.command = prefixes[action] + ' ' + this.playerName;
        this.sendCommand();
        this.toast('Command sent. The console shows the result.', 'ok');
        setTimeout(async () => {
          try {
            this.playerLists = await wire.players();
          } catch {}
        }, 1800);
      },
      async searchPackages() {
        const result = await this.run(() => wire.packageSearch(this.packageQuery, this.packageVersion));
        if (result === SKIPPED || !result) return;
        this.packageResults = result.items;
        this.packageVersion = result.context.version;
      },
      async installPackage(id) {
        const result = await this.run(() => wire.installPackage(id, this.packageVersion), 'Package installed. Start the server to load it.');
        if (result !== SKIPPED) this.installed = await wire.installedPackages();
      },
      async removePackage(file) {
        if (!(await this.ask({ title: 'Remove ' + file + '?', message: 'A copy is kept in the server package backup folder.', confirmLabel: 'Remove', tone: 'danger' }))) return;
        const result = await this.run(() => wire.removePackage(file), 'Package removed');
        if (result !== SKIPPED) this.installed = await wire.installedPackages();
      },
      tracked(file) {
        return Object.values(this.installed.tracked || {}).find((x) => x.filename === file);
      },
      async searchPacks() {
        const result = await this.run(() => wire.packSearch(this.packQuery));
        this.packs = result === SKIPPED ? [] : result || [];
      },
      async selectPack(p) {
        this.software.modpack_project = p.key;
        this.software.modpack_version = '';
        const result = await this.run(() => wire.packVersions(p.key));
        this.packReleases = result === SKIPPED ? {} : result || {};
        this.packs = [p];
      },
      async changeSoftware() {
        if (this.softwareConfirm !== this.server.name) {
          this.toast('Enter the exact server name.', 'warn');
          return;
        }
        if (!(await this.ask({ title: 'Reinstall this server?', message: 'The selected software is installed. Existing files may change.', confirmLabel: 'Reinstall', tone: 'danger' }))) return;
        await this.run(() => wire.changeSoftware(this.software, this.softwareConfirm), 'Software installation started');
      },
      async deleteServer() {
        if (this.busy) return;
        if (!(await this.ask({ title: 'Delete ' + this.server.name + '?', message: 'The server and all of its files are removed permanently.', confirmLabel: 'Delete server', tone: 'danger' }))) return;
        this.stopServerTimers();
        const result = await this.run(() => wire.deleteServer(this.deleteConfirm));
        if (result === SKIPPED) {
          this.startServerTimers();
          return;
        }
        this.destroyed = true;
        clearTimeout(this.reconnect);
        if (this.ws) {
          this.ws.onclose = null;
          this.ws.close();
        }
        location.href = boot.list_url;
      },

      /* Backups ------------------------------------------------------------------------------------ */
      async saveBackups() {
        const result = await this.run(() => wire.backupOptions(this.backupSettings), 'Backup policy saved');
        if (result !== SKIPPED) this.server.backup_options = { ...this.backupSettings };
      },
      async downloadBackup(uuid) {
        const url = await this.run(() => wire.downloadBackup(uuid));
        if (url !== SKIPPED && url) location.href = this.safeLink(url);
      },
      async restoreBackup(uuid) {
        const confirmation = await window.Simplify?.prompt?.({ title: 'Restore this backup?', message: 'Stop the server first. Current files are replaced by the backup.', requireText: this.server.name, confirmLabel: 'Restore backup', tone: 'danger' });
        if (confirmation === null || confirmation === undefined) return;
        await this.run(() => wire.restoreBackup(uuid, confirmation), 'Backup restoration started.');
      },
      bannerUploadChanged(event) {
        const file = event.target.files?.[0], limit = this.boot.banner_upload_bytes ?? 5242880;
        if (file && file.size > limit) {
          event.stopImmediatePropagation();
          event.target.value = '';
          this.toast('This banner exceeds the panel upload limit (' + this.bytes(limit) + '). See System checks.', 'warn');
        }
      },
      sftpLink() {
        try {
          const url = new URL(this.server?.sftp?.url);
          return url.protocol === 'sftp:' ? url.href : '';
        } catch {
          return '';
        }
      },

      /* Files ---------------------------------------------------------------------------------------- */
      fileEditUrl(url) {
        try {
          const base = new URL(this.server.files_url, location.origin), target = new URL(url, location.origin);
          const prefix = base.pathname.replace(/\/$/, '') + '/edit/';
          return target.origin === base.origin && target.pathname.startsWith(prefix) && target.pathname.length > prefix.length ? target : null;
        } catch {
          return null;
        }
      },
      async openFileEditor(url) {
        if (this.busy) return;
        if (this.fileEditorDirty && !(await this.ask({ title: 'Discard unsaved file changes?', message: 'Open the other file without saving.', confirmLabel: 'Discard changes', tone: 'danger' }))) return;
        url.searchParams.set('simplify_embed', '1');
        this.fileEditorDirty = false;
        this.fileEditorSaving = false;
        this.fileEditorRevision = null;
        const prefix = new URL(this.server.files_url, location.origin).pathname.replace(/\/$/, '') + '/edit/';
        this.fileEditorPath = '/' + decodeURIComponent(url.pathname.slice(prefix.length).replace(/\/$/, ''));
        this.fileEditorUrl = url.href;
        wire.fileRevision(this.fileEditorPath)
          .then((hash) => this.fileEditorUrl === url.href && (this.fileEditorRevision = hash))
          .catch(() => this.toast('The file revision could not be checked. Reopen the editor before saving.', 'warn'));
      },
      filesFrameLoaded(frame) {
        this.styleFrame(frame, 'files');
        try {
          const doc = frame.contentDocument;
          if (doc._psFilesClick) return;
          const click = (event) => {
            if (event.target.closest?.('button,input,select,textarea,label') && !event.target.closest?.('a[href]')) return;
            const anchor = event.target.closest?.('a[href]') || event.target.closest?.('.fi-ta-row')?.querySelector('a[href]');
            const url = anchor && this.fileEditUrl(anchor.href);
            if (!url) return;
            event.preventDefault();
            event.stopImmediatePropagation();
            return this.openFileEditor(url);
          };
          doc.addEventListener('click', click, true);
          doc._psFilesClick = click;
        } catch {
          this.toast('The file workspace could not be opened.', 'danger');
        }
      },
      /** Clicks Pelican's (hidden) "Save and close", which runs the revision check above first. */
      saveFileEditor() {
        try {
          const doc = document.querySelector('.ps-file-editor iframe')?.contentDocument;
          const button = doc && [...doc.querySelectorAll('button[wire\\:click]')].find((b) => (b.getAttribute('wire:click') || '').includes('fm_save_and_close'));
          if (button) return button.click();
        } catch {}
        this.toast('The editor is not ready yet.', 'warn');
      },
      async closeFileEditor() {
        if (this.busy) return;
        if (this.fileEditorDirty && !(await this.ask({ title: 'Discard unsaved file changes?', message: 'Your edits have not been saved.', confirmLabel: 'Discard changes', tone: 'danger' }))) return;
        this.fileEditorUrl = null;
        this.fileEditorDirty = false;
      },
      fileEditorLoaded(frame) {
        try {
          if (!this.fileEditorUrl) return;
          const url = frame.contentWindow.location.href;
          if (url === 'about:blank') return;
          if (!this.fileEditUrl(url)) {
            const target = new URL(url), base = new URL(this.server.files_url, location.origin);
            if (target.origin !== base.origin || (!target.pathname.startsWith(base.pathname.replace(/\/$/, '') + '/') && target.pathname !== base.pathname)) {
              this.toast('The editor could not be opened.', 'danger');
              return;
            }
            this.fileEditorUrl = null;
            this.fileEditorDirty = false;
            if (this.fileEditorSaving) {
              this.toast('File saved', 'ok');
              wire.fileChanged(this.fileEditorPath, this.fileEditorRevision)
                .then((info) => Object.assign(this.server, info))
                .catch(() => this.toast('File saved, but the restart reminder could not be recorded. Refresh the server page.', 'warn'));
              try {
                this.$refs.filesFrame.contentWindow.location.reload();
              } catch {}
            } else this.toast('The file could not be edited. See the file manager warning.', 'warn');
            this.fileEditorSaving = false;
            return;
          }
          this.styleFrame(frame, 'editor');
          const doc = frame.contentDocument;
          const saveButton = () => [...doc.querySelectorAll('button[wire\\:click]')].find((button) => (button.getAttribute('wire:click') || '').includes('fm_save_and_close'));
          // Simplify's header takes over Save/Close; Pelican's own footer only stays when its save button is not found.
          this.fileEditorExternal = !!saveButton();
          if (this.fileEditorExternal) doc.documentElement.dataset.psEditorActions = 'external';
          for (const button of doc.querySelectorAll('button[wire\\:click]')) {
            const action = button.getAttribute('wire:click') || '';
            if ((action.includes('fm_save') && !action.includes('fm_save_and_close')) || action.includes('fm_cancel')) button.style.display = 'none';
          }
          if (doc._psEditorBound) return;
          doc._psEditorBound = true;
          doc.addEventListener('click', async (event) => {
            const button = event.target.closest?.('button[wire\\:click]');
            if (!(button?.getAttribute('wire:click') || '').includes('fm_save_and_close')) return;
            if (button._psVerified) {
              button._psVerified = false;
              this.fileEditorSaving = true;
              return;
            }
            event.preventDefault();
            event.stopImmediatePropagation();
            if (this.fileEditorChecking) return;
            if (!this.fileEditorRevision) {
              this.toast('Wait for the file revision check before saving.', 'warn');
              return;
            }
            this.fileEditorChecking = true;
            try {
              if ((await wire.fileRevision(this.fileEditorPath)) !== this.fileEditorRevision) {
                this.toast('This file changed elsewhere. Reopen it before saving to avoid overwriting another change.', 'danger');
                return;
              }
              button._psVerified = true;
              button.click();
            } catch {
              this.toast('Could not verify the file. Your editor remains open.', 'danger');
            } finally {
              this.fileEditorChecking = false;
            }
          }, true);
          doc.addEventListener('input', (event) => event.target.closest?.('.monaco-editor') && (this.fileEditorDirty = true), true);
          doc.addEventListener('keydown', (event) => {
            if (!(event.ctrlKey || event.metaKey) || event.key.toLowerCase() !== 's') return;
            const button = saveButton();
            if (!button) return;
            event.preventDefault();
            event.stopImmediatePropagation();
            button.click();
          }, true);
          doc.addEventListener('livewire:navigated', () => this.fileEditorLoaded(frame));
        } catch {
          this.toast('The file editor could not be opened.', 'danger');
        }
      },
      /** Marks links back to the overview inside native pages so the popup can hide them. */
      cleanNativeFrame(doc) {
        const overview = new Set();
        for (const href of [this.boot.list_url, this.boot.native_list_url]) {
          if (!href) continue;
          try {
            const u = new URL(href, location.origin);
            if (u.origin === location.origin) overview.add(u.pathname.replace(/\/$/, '') || '/');
          } catch {}
        }
        for (const link of doc.querySelectorAll?.('a[href]') || []) {
          if (link.closest('.fi-ta,.fi-section,.fi-modal,[role="dialog"]')) continue;
          try {
            const u = new URL(link.getAttribute('href'), location.origin);
            if (u.origin === location.origin && overview.has(u.pathname.replace(/\/$/, '') || '/') && !u.search) link.setAttribute('data-ps-embed-navigation', 'overview');
          } catch {}
        }
      },
      /** Embedded pages theme themselves from their <iframe>; this only syncs theme and tidies native links. */
      styleFrame(frame, kind) {
        try {
          if (['native', 'files', 'editor', 'account'].includes(kind) && frame.contentDocument) this.cleanNativeFrame(frame.contentDocument);
          window.SimplifyTheme?.frame(frame, kind, this.currentAccentHex());
        } catch {
          this.toast('The workspace could not be embedded.', 'danger');
        }
      },
    };
  };
})();
