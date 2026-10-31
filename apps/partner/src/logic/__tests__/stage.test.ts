import type { PharmacyDetails } from '@/api/partner';

import { stageOf } from '../stage';

const base = { verification_status: 'pending', is_verified: false, license_file_url: null } as unknown as PharmacyDetails;

describe('sign-up stage', () => {
  it('waits while the pharmacy is loading', () => expect(stageOf(undefined)).toBe('loading'));
  it('asks to register when there is no pharmacy', () => expect(stageOf(null)).toBe('register'));
  it('asks for documents before anything is uploaded', () => expect(stageOf(base)).toBe('documents'));
  it('waits for approval once the licence is uploaded', () =>
    expect(stageOf({ ...base, license_file_url: 'https://x/l.jpg?signature=1' })).toBe('pending'));
  it('keeps a rejected pharmacy on the pending screen so it can upload again', () =>
    expect(stageOf({ ...base, license_file_url: 'u', verification_status: 'rejected' })).toBe('pending'));
  it('opens the app once approved', () => {
    expect(stageOf({ ...base, verification_status: 'approved' })).toBe('ready');
    expect(stageOf({ ...base, is_verified: true, license_file_url: 'u' })).toBe('ready');
  });
});
