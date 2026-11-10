import { API_URL } from '@/config';

export class ApiError extends Error {
  constructor(
    message: string,
    readonly status: number,
    readonly body: unknown,
  ) {
    super(message);
  }
}

let authToken: string | null = null;
let onUnauthorized: (() => void) | null = null;
let appLanguage = 'en';

/** Sent as X-App-Language so the API answers FAQs and pages in the person's language. */
export function setAppLanguage(code: string) {
  appLanguage = code;
}

export const getAppLanguage = () => appLanguage;

export function setAuthToken(token: string | null) {
  authToken = token;
}

export function setUnauthorizedHandler(handler: (() => void) | null) {
  onUnauthorized = handler;
}

/** Sends a multipart form with React Native's own XMLHttpRequest, which reads files from their uri. */
function sendForm(
  method: string,
  url: string,
  headers: Record<string, string>,
  form: FormData,
): Promise<{ ok: boolean; status: number; text: string }> {
  return new Promise((resolve, reject) => {
    const xhr = new XMLHttpRequest();
    xhr.open(method, url);
    Object.entries(headers).forEach(([k, v]) => xhr.setRequestHeader(k, v));
    xhr.timeout = 120000;
    xhr.onload = () => resolve({ ok: xhr.status >= 200 && xhr.status < 300, status: xhr.status, text: xhr.responseText });
    xhr.onerror = () => reject(new TypeError('Network request failed'));
    xhr.ontimeout = () => reject(new TypeError('Network request timed out'));
    xhr.send(form);
  });
}

type Options = { body?: unknown; form?: FormData; query?: Record<string, string | number | undefined> };

export async function request<T>(method: string, path: string, { body, form, query }: Options = {}): Promise<T> {
  const qs = query
    ? '?' +
      Object.entries(query)
        .filter(([, v]) => v !== undefined)
        .map(([k, v]) => `${encodeURIComponent(k)}=${encodeURIComponent(String(v))}`)
        .join('&')
    : '';
  const headers: Record<string, string> = { Accept: 'application/json', 'X-App-Language': appLanguage };
  if (authToken) headers.Authorization = `Bearer ${authToken}`;
  if (body !== undefined) headers['Content-Type'] = 'application/json';

  const url = `${API_URL}${path}${qs}`;
  // Uploads go through XMLHttpRequest: Expo's fetch cannot send React Native
  // file parts ({ uri, name, type }) and fails with "Unsupported FormDataPart".
  const res = form
    ? await sendForm(method, url, headers, form)
    : await fetch(url, { method, headers, body: body !== undefined ? JSON.stringify(body) : undefined }).then(
        async (r) => ({ ok: r.ok, status: r.status, text: await r.text() }),
      );

  const text = res.text;
  let json: any = null;
  try {
    json = text ? JSON.parse(text) : null;
  } catch {
    // Laravel returns HTML on fatal errors; fall through with json = null.
  }

  if (res.status === 401 && authToken) onUnauthorized?.();
  if (!res.ok || json?.success === false) {
    const message = json?.message ?? `Request failed (${res.status})`;
    throw new ApiError(message, res.status, json);
  }
  return json as T;
}
