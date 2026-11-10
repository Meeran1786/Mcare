import { request } from '@/api/client';

// Expo's fetch rejects React Native file parts, so uploads must use XMLHttpRequest.
class FakeXhr {
  static last: FakeXhr;
  status = 201;
  responseText = '{"success":true,"data":{"pharmacy_id":7}}';
  timeout = 0;
  sent: unknown;
  opened: string[] = [];
  headers: Record<string, string> = {};
  onload: (() => void) | null = null;
  onerror: (() => void) | null = null;
  ontimeout: (() => void) | null = null;
  constructor() {
    FakeXhr.last = this;
  }
  open(method: string, url: string) {
    this.opened = [method, url];
  }
  setRequestHeader(k: string, v: string) {
    this.headers[k] = v;
  }
  send(body: unknown) {
    this.sent = body;
    this.onload?.();
  }
}

test('a form with a photo is sent with XMLHttpRequest, not fetch', async () => {
  const fetchSpy = jest.fn();
  globalThis.fetch = fetchSpy as unknown as typeof fetch;
  globalThis.XMLHttpRequest = FakeXhr as unknown as typeof XMLHttpRequest;

  const form = new FormData();
  form.append('files[]', { uri: 'file:///shop.jpg', name: 'shop.jpg', type: 'image/jpeg' } as unknown as Blob);
  const res = await request<{ data: { pharmacy_id: number } }>('POST', '/prescriptions', { form });

  expect(res.data.pharmacy_id).toBe(7);
  expect(fetchSpy).not.toHaveBeenCalled();
  expect(FakeXhr.last.opened[0]).toBe('POST');
  expect(FakeXhr.last.sent).toBe(form);
  expect(FakeXhr.last.headers.Accept).toBe('application/json');
});
