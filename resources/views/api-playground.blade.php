<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>BOCS API Playground</title>
    <style>
        :root { color-scheme: dark; --bg:#07111f; --panel:#0e1b2d; --line:#20334d; --text:#e7eef9; --muted:#91a4bf; --brand:#38bdf8; --good:#34d399; --bad:#fb7185; }
        * { box-sizing:border-box; }
        html { min-width:280px; overflow-x:hidden; }
        body { min-height:100dvh; margin:0; background:radial-gradient(circle at top right,#12335b 0,transparent 32%),var(--bg); color:var(--text); font:15px/1.5 Inter,ui-sans-serif,system-ui,sans-serif; overflow-x:hidden; }
        .shell { width:min(1180px,calc(100% - 32px)); margin:clamp(20px,4vw,42px) auto; }
        header { display:flex; justify-content:space-between; align-items:flex-start; gap:20px; margin-bottom:22px; }
        h1 { margin:0; font-size:clamp(26px,4vw,42px); letter-spacing:-.04em; }
        h2 { margin:0 0 16px; font-size:18px; }
        p { margin:6px 0 0; color:var(--muted); }
        .badge { border:1px solid #186b55; background:#0a332c; color:#6ee7b7; border-radius:999px; padding:7px 12px; white-space:nowrap; }
        .grid { display:grid; grid-template-columns:minmax(290px,360px) minmax(0,1fr); gap:18px; align-items:start; }
        .card { min-width:0; background:color-mix(in srgb,var(--panel) 94%,transparent); border:1px solid var(--line); border-radius:16px; padding:clamp(16px,2.2vw,22px); box-shadow:0 18px 60px #0005; }
        label { display:block; margin:12px 0 6px; color:#bed0e8; font-size:13px; font-weight:700; }
        input,select,textarea { width:100%; max-width:100%; border:1px solid var(--line); border-radius:9px; background:#081321; color:var(--text); padding:11px 12px; outline:none; font-size:16px; }
        input:focus,select:focus,textarea:focus { border-color:var(--brand); box-shadow:0 0 0 3px #38bdf822; }
        textarea { min-height:180px; resize:vertical; font:13px/1.55 ui-monospace,SFMono-Regular,Consolas,monospace; }
        button { min-height:44px; border:0; border-radius:9px; padding:11px 15px; background:var(--brand); color:#032033; font-weight:800; cursor:pointer; touch-action:manipulation; }
        button.secondary { background:#1c2d44; color:var(--text); }
        button:disabled { opacity:.55; cursor:wait; }
        .actions,.request-line { display:flex; gap:10px; align-items:center; }
        .actions { margin-top:15px; flex-wrap:wrap; }
        .actions button { flex:1 1 120px; }
        .request-line { display:grid; grid-template-columns:118px minmax(160px,1fr) auto; }
        .request-line select { width:100%; }
        .request-line input { min-width:0; }
        .token { margin-top:15px; padding:11px; background:#081321; border:1px solid var(--line); border-radius:9px; color:var(--muted); overflow-wrap:anywhere; font:12px ui-monospace,monospace; }
        .token.active { border-color:#186b55; color:#6ee7b7; }
        .response-head { display:flex; justify-content:space-between; align-items:center; margin:20px 0 8px; }
        pre { width:100%; min-height:270px; max-height:min(520px,55dvh); overflow:auto; overscroll-behavior:contain; margin:0; padding:16px; border:1px solid var(--line); border-radius:10px; background:#050b13; color:#c9d8eb; font:13px/1.55 ui-monospace,SFMono-Regular,Consolas,monospace; white-space:pre-wrap; overflow-wrap:anywhere; }
        .status { font-weight:800; }.status.good { color:var(--good); }.status.bad { color:var(--bad); }
        .hint { font-size:12px; }
        @media (max-width:900px) {
            .grid { grid-template-columns:1fr; }
            .card:first-child { display:grid; grid-template-columns:1fr 1fr; column-gap:16px; }
            .card:first-child h2,.card:first-child .actions,.card:first-child .token,.card:first-child p { grid-column:1/-1; }
        }
        @media (max-width:620px) {
            .shell { width:min(100% - 20px,1180px); margin:14px auto 24px; }
            header { flex-direction:column; gap:12px; margin-bottom:14px; }
            .badge { align-self:flex-start; }
            .card { border-radius:13px; }
            .card:first-child { display:block; }
            .request-line { grid-template-columns:1fr; align-items:stretch; }
            .request-line button { width:100%; }
            textarea { min-height:150px; }
            pre { min-height:220px; padding:12px; }
            .response-head { align-items:flex-start; gap:8px; }
        }
        @media (max-width:360px) {
            .shell { width:100%; margin:0; }
            header { padding:16px 14px 4px; }
            .grid { gap:10px; }
            .card { padding:14px; border-left:0; border-right:0; border-radius:0; }
            h1 { font-size:27px; }
            .actions { flex-direction:column; align-items:stretch; }
            .actions button { flex-basis:auto; width:100%; }
        }
        @media (prefers-reduced-motion:reduce) { *,*::before,*::after { scroll-behavior:auto !important; transition:none !important; } }
    </style>
</head>
<body>
<main class="shell">
    <header>
        <div><h1>BOCS API Playground</h1><p>Log in, send authenticated requests, and inspect JSON responses.</p></div>
        <span class="badge" id="health">API ready</span>
    </header>

    <div class="grid">
        <section class="card">
            <h2>1. Authentication</h2>
            <label for="email">Email</label>
            <input id="email" type="email" value="bocs@rx931.com" autocomplete="username">
            <label for="password">Password</label>
            <input id="password" type="password" value="BOCSDeveloper" autocomplete="current-password">
            <label><input id="remember" type="checkbox" style="width:auto"> Remember token</label>
            <div class="actions">
                <button id="login">Login</button>
                <button id="clear" class="secondary">Clear token</button>
            </div>
            <div id="token" class="token">No bearer token stored.</div>
            <p class="hint">The token stays only in this browser tab and is automatically attached to test calls.</p>
        </section>

        <section class="card">
            <h2>2. Request builder</h2>
            <label for="preset">Endpoint preset</label>
            <select id="preset">
                <option value="GET|/api">API status</option>
                <option value="GET|/api/advertisers" selected>Advertisers</option>
                <option value="GET|/api/agencies">Agencies</option>
                <option value="GET|/api/contracts">Contracts</option>
                <option value="GET|/api/sales">Sales</option>
                <option value="GET|/api/employees">Employees</option>
                <option value="GET|/api/jobs">Jobs</option>
                <option value="GET|/api/logs">Logs</option>
                <option value="POST|/api/logout">Logout</option>
                <option value="custom">Custom request</option>
            </select>

            <label>Request</label>
            <div class="request-line">
                <select id="method"><option>GET</option><option>POST</option><option>PUT</option><option>PATCH</option><option>DELETE</option></select>
                <input id="path" value="/api/advertisers" aria-label="API path">
                <button id="send">Send</button>
            </div>

            <label for="body">JSON body <span class="hint">(optional)</span></label>
            <textarea id="body" spellcheck="false">{}</textarea>
            <div class="response-head"><strong>Response</strong><span id="status" class="status">Not sent</span></div>
            <pre id="response">Select an endpoint and send a request.</pre>
        </section>
    </div>
</main>

<script>
    const $ = id => document.getElementById(id);
    const appBasePath = @json(rtrim(request()->getBaseUrl(), '/'));
    const apiBaseUrl = `${appBasePath}/api`;
    let accessToken = sessionStorage.getItem('bocs_api_token') || '';

    function resolveRequestUrl(path) {
        const value = path.trim();

        if (/^https?:\/\//i.test(value)) return value;
        if (value === '/api' || value === 'api') return apiBaseUrl;
        if (value.startsWith('/api/')) return `${apiBaseUrl}${value.slice(4)}`;
        if (value.startsWith('api/')) return `${apiBaseUrl}/${value.slice(4)}`;

        return `${appBasePath}/${value.replace(/^\/+/, '')}`;
    }

    function showToken() {
        $('token').textContent = accessToken ? `Bearer ${accessToken.slice(0, 42)}…` : 'No bearer token stored.';
        $('token').classList.toggle('active', Boolean(accessToken));
    }

    function showResponse(status, payload) {
        $('status').textContent = status ? `HTTP ${status}` : 'Request failed';
        $('status').className = `status ${status >= 200 && status < 300 ? 'good' : 'bad'}`;
        $('response').textContent = typeof payload === 'string' ? payload : JSON.stringify(payload, null, 2);
    }

    async function apiRequest(path, options = {}) {
        const headers = { Accept: 'application/json', ...(options.headers || {}) };
        if (accessToken) headers.Authorization = `Bearer ${accessToken}`;
        const response = await fetch(resolveRequestUrl(path), { ...options, headers });
        const text = await response.text();
        let payload;
        try { payload = text ? JSON.parse(text) : {}; } catch { payload = text; }
        showResponse(response.status, payload);
        return { response, payload };
    }

    $('login').addEventListener('click', async () => {
        $('login').disabled = true;
        try {
            const { response, payload } = await apiRequest('/api/login', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email: $('email').value, password: $('password').value, remember: $('remember').checked })
            });
            if (response.ok && payload.access_token) {
                accessToken = payload.access_token;
                sessionStorage.setItem('bocs_api_token', accessToken);
                showToken();
            }
        } catch (error) { showResponse(0, { status:'error', code:'network_error', message:error.message }); }
        finally { $('login').disabled = false; }
    });

    $('clear').addEventListener('click', () => { accessToken=''; sessionStorage.removeItem('bocs_api_token'); showToken(); });
    $('preset').addEventListener('change', event => {
        if (event.target.value === 'custom') return;
        const [method, path] = event.target.value.split('|');
        $('method').value = method; $('path').value = path;
    });
    $('send').addEventListener('click', async () => {
        $('send').disabled = true;
        try {
            const method = $('method').value;
            const options = { method };
            if (!['GET','HEAD'].includes(method)) {
                let body;
                try { body = JSON.parse($('body').value || '{}'); }
                catch { showResponse(0, { status:'error', code:'invalid_json', message:'Request body is not valid JSON.' }); return; }
                options.headers = { 'Content-Type':'application/json' };
                options.body = JSON.stringify(body);
            }
            await apiRequest($('path').value, options);
        } catch (error) { showResponse(0, { status:'error', code:'network_error', message:error.message }); }
        finally { $('send').disabled = false; }
    });

    showToken();
</script>
</body>
</html>
