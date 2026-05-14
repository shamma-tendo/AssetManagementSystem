import './bootstrap';

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

/**
 * JSON API fetch with session cookie + CSRF for first-party Blade pages.
 */
window.aemsFetch = function (url, options = {}) {
    const method = (options.method || 'GET').toUpperCase();
    const headers = {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        ...(options.headers || {}),
    };
    if (method !== 'GET' && method !== 'HEAD') {
        headers['X-CSRF-TOKEN'] = csrfToken();
        if (!headers['Content-Type'] && options.body && typeof options.body === 'string') {
            headers['Content-Type'] = 'application/json';
        }
    }

    return fetch(url, {
        credentials: 'same-origin',
        ...options,
        headers,
    });
};
