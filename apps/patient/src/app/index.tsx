import { Redirect } from 'expo-router';

import { useSession } from '@/state/session';

/** Sends people to the right first screen: language, then login, then home. */
export default function Index() {
  const { token, languageChosen } = useSession();
  if (token) return <Redirect href="/home" />;
  return <Redirect href={languageChosen ? '/login' : '/language'} />;
}
