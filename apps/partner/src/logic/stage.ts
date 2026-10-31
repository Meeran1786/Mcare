import type { PharmacyDetails } from '@/api/partner';

/** Where a signed-in vendor is in setting up their pharmacy. */
export type Stage = 'loading' | 'register' | 'documents' | 'pending' | 'ready' | 'error';

export function stageOf(pharmacy: PharmacyDetails | null | undefined): Stage {
  if (pharmacy === undefined) return 'loading';
  if (pharmacy === null) return 'register';
  if (pharmacy.verification_status === 'approved' || pharmacy.is_verified) return 'ready';
  if (!pharmacy.license_file_url) return 'documents';
  return 'pending';
}
