import axios from 'axios';
import router from '@/router';

// ---------------------------------------------------------------------------
// Request deduplication + TTL cache
// Two GETs with the same URL + query params that arrive within CACHE_TTL of
// each other share the SAME pending promise. This prevents the 429 burst
// when multiple tabs/modules load dashboards at the same time.
//
// IMPORTANT: dedup wraps the `api` instance methods (which run the request
// interceptor that attaches the Bearer token). Earlier code called the raw
// global `axios(config)` here, which skipped the interceptor -> no
// Authorization header -> 401 on every dashboard request.
// ---------------------------------------------------------------------------
const CACHE_TTL = 60_000; // 1 minute
const cache = new Map(); // key -> { promise, expiresAt }

function cacheKey(url, params) {
    const paramsStr = params ? JSON.stringify(params) : '';
    return `GET|${url}|${paramsStr}`;
}

function clearExpiredCache() {
    const now = Date.now();
    for (const [key, entry] of cache.entries()) {
        if (entry.expiresAt <= now) cache.delete(key);
    }
    // Prevent unbounded growth
    if (cache.size > 200) {
        const oldest = [...cache.keys()].slice(0, cache.size - 200);
        oldest.forEach((k) => cache.delete(k));
    }
}

const api = axios.create({
    baseURL: import.meta.env.VITE_API_URL,
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
    },
    withCredentials: true,
});

// Attach Bearer token (and XSRF header when we have one) to every request.
api.interceptors.request.use(
    (config) => {
        const token = localStorage.getItem('token');
        if (token) {
            config.headers = config.headers || {};
            config.headers.Authorization = `Bearer ${token}`;
        }
        // Bearer-token API auth does NOT need X-XSRF-TOKEN. Only send it
        // when we actually have a cookie value, to avoid breaking Sanctum.
        const csrfToken = localStorage.getItem('XSRF-TOKEN');
        if (csrfToken) {
            config.headers = config.headers || {};
            config.headers['X-XSRF-TOKEN'] = csrfToken;
        }
        return config;
    },
    (error) => Promise.reject(error)
);

// 401 -> clear auth once and go to /login (skip if already there or if the
// failing call IS the login call). 429 -> retry once after a short delay.
let redirectingToLogin = false;
api.interceptors.response.use(
    (response) => response,
    async (error) => {
        const status = error.response?.status;
        const config = error.config || {};
        const url = config.url || '';

        if (status === 429 && !config._retry429) {
            config._retry429 = true;
            await new Promise((r) => setTimeout(r, 800));
            // Retry through `api` so the Authorization header is re-applied.
            return api(config);
        }

        if (status === 401) {
            const isUserValidation = url === '/user' || url.endsWith('/user');
            // Only the /user validation endpoint is authoritative for "token
            // is invalid". Dashboard widgets use safeGet() which tolerates
            // null, so a single widget 401 must NEVER wipe the session —
            // otherwise one flaky request logs the user out and every other
            // parallel request then 401s as well (the cascade in the logs).
            if (isUserValidation && !redirectingToLogin) {
                try {
                    const { useAuthStore } = await import('@/stores/auth');
                    const store = useAuthStore();
                    if (store.token || localStorage.getItem('token')) {
                        store.clearAuthData();
                    }
                } catch {
                    localStorage.removeItem('token');
                    localStorage.removeItem('user');
                }
                // Don't cache failed auth responses
                cache.clear();
                if (router.currentRoute.value.path !== '/login') {
                    redirectingToLogin = true;
                    try {
                        await router.push('/login');
                    } finally {
                        setTimeout(() => { redirectingToLogin = false; }, 1000);
                    }
                }
            }
        }

        return Promise.reject(error);
    }
);

// --- Dedup wrapper around api.get (goes through interceptors) ---
const originalGet = api.get.bind(api);
api.get = (url, config = {}) => {
    const key = cacheKey(url, config.params);
    const now = Date.now();
    const entry = cache.get(key);
    if (entry && entry.expiresAt > now) {
        return entry.promise;
    }
    if (entry) cache.delete(key);
    clearExpiredCache();

    const promise = originalGet(url, config)
        .then((res) => {
            cache.set(key, { promise: Promise.resolve(res), expiresAt: Date.now() + CACHE_TTL });
            return res;
        })
        .catch((err) => {
            // Never cache errors (401/429/500) — next call retries for real.
            cache.delete(key);
            return Promise.reject(err);
        });

    cache.set(key, { promise, expiresAt: now + CACHE_TTL });
    return promise;
};

export function clearApiCache() {
    cache.clear();
}

export default api; 