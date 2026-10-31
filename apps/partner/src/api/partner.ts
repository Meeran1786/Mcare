/** Typed calls to the existing Mcare Laravel API for pharmacies (routes/api.php, "vendor" routes). */
import { request } from './client';

export interface User {
  id: number;
  name: string | null;
  mobile_number: string;
  role: string;
  email?: string | null;
  preferred_language?: string | null;
}

export type VerificationStatus = 'pending' | 'approved' | 'rejected' | string;

export interface PharmacyDetails {
  id: number;
  owner_name: string | null;
  pharmacy_name: string;
  license_number: string | null;
  registration_number: string | null;
  gst_number: string | null;
  address_line: string | null;
  area: string | null;
  city: string | null;
  state: string | null;
  pincode: string | null;
  landmark: string | null;
  latitude: number | string | null;
  longitude: number | string | null;
  is_delivery_available: boolean;
  pharmacy_photo_url: string | null;
  license_file_url: string | null;
  gst_file_url: string | null;
  verification_status: VerificationStatus;
  is_verified: boolean;
}

export interface NewPharmacy {
  owner_name: string;
  pharmacy_name: string;
  license_number: string;
  registration_number: string;
  gst_number?: string;
  address_line: string;
  area: string;
  landmark?: string;
  city: string;
  state: string;
  pincode: string;
  latitude: number;
  longitude: number;
}

export interface Address {
  id: number;
  name?: string | null;
  phone?: string | null;
  address_line: string | null;
  city: string | null;
  state?: string | null;
  pincode: string | null;
  latitude?: number | string | null;
  longitude?: number | string | null;
}

export interface Patient {
  id: number;
  name: string | null;
  mobile_number: string;
  address?: Address | null;
}

export interface PrescriptionFile {
  id: number;
  url: string | null;
}

export interface Prescription {
  id: number;
  status: string;
  notes: string | null;
  latitude: number | string | null;
  longitude: number | string | null;
  created_at: string;
  user?: Patient | null;
  address?: Address | null;
  files?: PrescriptionFile[];
  order?: { id: number; status: string; address?: Address | null } | null;
}

/** One prescription sent to this pharmacy (a row of prescription_pharmacies). */
export interface Assignment {
  id: number;
  prescription_id: number;
  status: 'sent' | 'accepted' | 'rejected' | string;
  rejection_reason: string | null;
  distance_km: number | null;
  created_at: string;
  prescription: Prescription;
}

export interface Order {
  id: number;
  status: string;
  total_amount: string | number | null;
  delivery_date: string | null;
  delivery_clock_time: string | null;
  delivery_time: string | null;
  delivery_note: string | null;
  payment_status?: string;
  payment_method?: string;
  created_at: string;
  completed_at?: string | null;
  user?: Patient | null;
  address?: Address | null;
  prescription?: Prescription | null;
}

export interface HistoryOrder {
  id: number;
  status: string;
  created_at: string;
  completed_at: string | null;
  total_amount: string | number | null;
  user: { name: string | null; mobile_number: string | null };
  prescription_image: string | null;
}

export interface Analytics {
  totals: { completed_deliveries: number; total_earnings: number };
  daily: { label: string; total: number; revenue: number | string }[];
  monthly: { label: string; total: number; revenue: number | string }[];
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

export interface DeliveryUpdate {
  amount?: number;
  delivery_date?: string; // YYYY-MM-DD
  delivery_clock_time?: string; // HH:mm
  delivery_note?: string;
}

type Paginated<T> = { data: T[]; current_page: number; last_page: number };

/** React Native's FormData takes { uri, name, type } objects for files. */
function fileField(uri: string, name: string) {
  const ext = (uri.split('.').pop() ?? 'jpg').toLowerCase().split('?')[0];
  const type = ext === 'pdf' ? 'application/pdf' : ext === 'png' ? 'image/png' : 'image/jpeg';
  return { uri, name: `${name}.${ext === 'pdf' || ext === 'png' ? ext : 'jpg'}`, type } as unknown as Blob;
}

export const api = {
  sendOtp: (mobile: string) =>
    request<{ success: boolean }>('POST', '/auth/send-otp', { body: { mobile_number: mobile, role: 'vendor' } }),

  verifyOtp: (mobile: string, otp: string) =>
    request<{ token: string; is_new_user: boolean; user: User }>('POST', '/auth/verify-otp', {
      body: { mobile_number: mobile, otp, role: 'vendor' },
    }),

  setLanguage: (preferred_language: string) =>
    request('POST', '/update-profile', { body: { preferred_language } }),

  saveFcmToken: (fcm_token: string, platform: string) =>
    request('POST', '/save-fcm-token', { body: { fcm_token, platform } }),

  logout: () => request('POST', '/auth/logout').catch(() => undefined),

  /** null when this vendor has not registered a pharmacy yet. */
  pharmacy: async (): Promise<{ pharmacy: PharmacyDetails; user: Partial<User> } | null> => {
    const head = await request<{ data: { exists: boolean } }>('GET', '/vendor/pharmacy');
    if (!head.data.exists) return null;
    const full = await request<{ data: { pharmacy: PharmacyDetails; user: Partial<User> } }>(
      'GET',
      '/vendor/pharmacy/details',
    );
    return full.data;
  },

  registerPharmacy: (p: NewPharmacy, photoUri?: string) => {
    const form = new FormData();
    Object.entries(p).forEach(([k, v]) => v !== undefined && v !== '' && form.append(k, String(v)));
    if (photoUri) form.append('pharmacy_photo', fileField(photoUri, 'pharmacy'));
    return request<{ data: { pharmacy_id: number } }>('POST', '/vendor/pharmacy', { form });
  },

  uploadDocuments: (licenseUri: string, gstUri?: string) => {
    const form = new FormData();
    form.append('license_file', fileField(licenseUri, 'license'));
    if (gstUri) form.append('gst_file', fileField(gstUri, 'gst'));
    return request<{ data: { verification_status: string } }>('POST', '/vendor/pharmacy/documents', { form });
  },

  updatePhoto: (uri: string) => {
    const form = new FormData();
    form.append('pharmacy_photo', fileField(uri, 'pharmacy'));
    return request('POST', '/vendor/pharmacy/update-photo', { form });
  },

  /** "Receive orders" switch. Offline pharmacies get no new prescriptions. */
  setOnline: (online: boolean) =>
    request<{ data: { is_active: boolean } }>('POST', '/vendor/pharmacy/toggle-status', {
      body: { is_active: online },
    }).then((r) => r.data.is_active),

  inbox: () => request<{ data: Assignment[] }>('GET', '/vendor/prescriptions').then((r) => r.data),

  /** Accepting creates the order and withdraws the prescription from other pharmacies. */
  answerPrescription: (assignmentId: number, status: 'accepted' | 'rejected', rejection_reason?: string) =>
    request('POST', `/vendor/prescriptions/${assignmentId}/status`, { body: { status, rejection_reason } }),

  orders: (status?: string) =>
    request<{ data: Paginated<Order> }>('GET', '/vendor/orders', { query: { status } }).then((r) => r.data.data),

  order: (id: number | string) =>
    request<{ data: Order; distance_km: number | null }>('GET', `/vendor/orders/${id}`),

  updateOrder: (id: number, status: string, delivery: DeliveryUpdate = {}) =>
    request<{ data: Order }>('PUT', `/vendor/orders/${id}/status`, { body: { status, ...delivery } }).then(
      (r) => r.data,
    ),

  history: () => request<{ orders: HistoryOrder[] }>('GET', '/order-history').then((r) => r.orders),

  analytics: () => request<{ data: Analytics }>('GET', '/vendor/analytics').then((r) => r.data),

  faqs: () => request<{ data: Faq[] }>('GET', '/faqs/help').then((r) => r.data),

  sendHelp: (subject: string, message: string) => request('POST', '/help', { body: { subject, message } }),

  notifications: () =>
    request<{ data: Paginated<AppNotification> }>('GET', '/notifications').then((r) => r.data.data),
};
