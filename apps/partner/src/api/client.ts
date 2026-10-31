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

  const res = await fetch(`${API_URL}${path}${qs}`, {
    method,
    headers,
    body: form ?? (body !== undefined ? JSON.stringify(body) : undefined),
  });

  const text = await res.text();
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

/** First message from a Laravel 422 response, so people see what to fix. */
export function apiErrorText(e: unknown, fallback: string): string {
  if (e instanceof ApiError) {
    const errors = (e.body as { errors?: Record<string, string[]> } | null)?.errors;
    const first = errors && Object.values(errors)[0]?.[0];
    if (first) return first;
  }
  return fallback;
}
