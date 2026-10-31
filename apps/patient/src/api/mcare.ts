/** Typed calls to the existing Mcare Laravel API (routes/api.php). */
import { getLanguage } from '@/i18n';
import { syncAlarms } from '@/reminders/alarms';
import type { Day } from '@/voice/types';

import { getAppLanguage, request } from './client';

/** After any reminder change, reload the list, which reschedules the phone's alarms. */
function thenResync<T>(p: Promise<T>): Promise<T> {
  return p.then((value) => {
    api.reminders().catch(() => undefined);
    return value;
  });
}

export interface User {
  id: number;
  name: string | null;
  mobile_number: string;
  role: string;
  preferred_language?: string | null;
}

export interface Reminder {
  id: number;
  /** Display time from the server, e.g. "08:00 AM" */
  time: string;
  /** "HH:mm" or "HH:mm:ss" */
  raw_time: string;
  days: Day[];
  note: string | null;
  medicine_name: string | null;
  timezone: string;
  is_active: boolean;
}

export interface Pharmacy {
  id: number;
  pharmacy_name: string;
}

export interface Order {
  id: number;
  status: string;
  created_at: string;
  total_amount?: string | number | null;
  pharmacy?: Pharmacy | null;
}

export interface Address {
  id: number;
  label: string | null;
  address_line: string;
  latitude: string | number;
  longitude: string | number;
  is_default: boolean | number;
}

export interface NewAddress {
  label?: string;
  name: string;
  phone: string;
  address_line: string;
  city?: string;
  pincode?: string;
  latitude: number;
  longitude: number;
}

export interface Faq {
  id: number;
  question: string;
  answer: string;
}

export interface AppNotification {
  id: number;
  title: string;
  body: string;
  is_read?: boolean | number;
  created_at: string;
}

type Paginated<T> = { data: T[]; current_page: number; last_page: number };

export const api = {
  sendOtp: (mobile: string) =>
    request<{ success: boolean; message?: string }>('POST', '/auth/send-otp', {
      body: { mobile_number: mobile, role: 'user' },
    }),

  verifyOtp: (mobile: string, otp: string) =>
    request<{ token: string; is_new_user: boolean; user: User }>('POST', '/auth/verify-otp', {
      body: { mobile_number: mobile, otp, role: 'user' },
    }),

  completeProfile: (name: string) => request<{ user: User }>('POST', '/profile/complete', { body: { name } }),

  setLanguage: (preferred_language: string) =>
    request<{ user: User }>('POST', '/update-profile', { body: { preferred_language } }),

  profile: () => request<{ user: User }>('GET', '/profile').then((r) => r.user),

  /** local_reminders: this phone rings reminders itself, so the server should not push them. */
  saveFcmToken: (fcm_token: string, platform: string) =>
    request('POST', '/save-fcm-token', { body: { fcm_token, platform, local_reminders: true } }),

  logout: () => request('POST', '/auth/logout').catch(() => undefined),

  reminders: () =>
    request<{ data: Reminder[] }>('GET', '/reminders').then((r) => {
      syncAlarms(r.data, getLanguage(getAppLanguage()));
      return r.data;
    }),

  createReminder: (r: { time: string; days: Day[]; medicine_name?: string; note?: string; timezone: string }) =>
    thenResync(request<{ data: Reminder }>('POST', '/reminders', { body: r }).then((x) => x.data)),

  updateReminder: (id: number, r: { time: string; days: Day[]; medicine_name?: string; timezone: string }) =>
    thenResync(request<{ data: Reminder }>('PUT', `/reminders/${id}`, { body: r }).then((x) => x.data)),

  toggleReminder: (id: number) => thenResync(request('PUT', `/reminders/${id}/toggle`)),

  deleteReminder: (id: number) => thenResync(request('DELETE', `/reminders/${id}`)),

  orders: (status?: string) =>
    request<{ data: Paginated<Order> }>('GET', '/user/orders', { query: { status } }).then((r) => r.data.data),

  reorder: (id: number) => request<{ message: string }>('POST', `/orders/${id}/reorder`),

  confirmDelivery: (id: number) => request('POST', `/user/orders/${id}/confirm`),

  addresses: () => request<{ data: Address[] }>('GET', '/addresses').then((r) => r.data),

  addAddress: (a: NewAddress) => request<{ data: Address }>('POST', '/addresses', { body: a }).then((r) => r.data),

  deleteAddress: (id: number) => request('DELETE', `/addresses/${id}`),

  setDefaultAddress: (id: number) => request('PUT', `/addresses/${id}/default`),

  /** The API falls back to English for languages it has no FAQ text for. */
  faqs: () => request<{ data: Faq[] }>('GET', '/faqs/help').then((r) => r.data),

  sendHelp: (subject: string, message: string) => request('POST', '/help', { body: { subject, message } }),

  /** Sends prescription photos to every verified pharmacy within 5 km. */
  uploadPrescription: (p: { uris: string[]; latitude: number; longitude: number; addressId?: number; notes?: string }) => {
    const form = new FormData();
    p.uris.forEach((uri, i) => {
      // React Native's FormData accepts { uri, name, type } for files.
      form.append('files[]', { uri, name: `prescription-${i + 1}.jpg`, type: 'image/jpeg' } as unknown as Blob);
    });
    form.append('mode', 'nearby');
    form.append('latitude', String(p.latitude));
    form.append('longitude', String(p.longitude));
    if (p.addressId) form.append('address_id', String(p.addressId));
    if (p.notes) form.append('notes', p.notes);
    return request<{ message: string }>('POST', '/prescriptions', { form });
  },

  notifications: () =>
    request<{ data: Paginated<AppNotification> }>('GET', '/notifications').then((r) => r.data.data),
};
