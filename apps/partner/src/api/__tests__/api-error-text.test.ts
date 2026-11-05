import { ApiError, apiErrorText } from '@/api/client';

const fallback = 'Something went wrong. Please try again.';

test('shows the first validation message', () => {
  const e = new ApiError('x', 422, { errors: { pharmacy_photo: ['The pharmacy photo is too large.'] } });
  expect(apiErrorText(e, fallback)).toBe('The pharmacy photo is too large.');
});

test('shows a refusal the server explains', () => {
  expect(apiErrorText(new ApiError('x', 403, { message: 'Only vendors can create pharmacy profiles.' }), fallback)).toBe(
    'Only vendors can create pharmacy profiles.',
  );
});

test('keeps the status code on server errors', () => {
  expect(apiErrorText(new ApiError('x', 500, { message: 'Server Error' }), fallback)).toBe(`${fallback} (500)`);
  expect(apiErrorText(new ApiError('x', 413, null), fallback)).toBe(`${fallback} (413)`);
});

test('names a network failure', () => {
  expect(apiErrorText(new TypeError('Network request failed'), fallback)).toBe(`${fallback} (Network request failed)`);
});
