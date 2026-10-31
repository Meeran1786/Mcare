import { useState } from 'react';
import { Pressable } from 'react-native';

import { api } from '@/api/mcare';
import { BigButton, Card, ErrorText, Field, Loading, Screen, T } from '@/components/ui';
import { useLoad } from '@/hooks';
import { useSession } from '@/state/session';
import { colors } from '@/theme';

export default function HelpScreen() {
  const { t } = useSession();
  const { data: faqs } = useLoad(api.faqs);
  const [openId, setOpenId] = useState<number | null>(null);
  const [subject, setSubject] = useState('');
  const [message, setMessage] = useState('');
  const [sent, setSent] = useState(false);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState('');

  async function send() {
    if (!subject.trim() || !message.trim()) return;
    setBusy(true);
    setError('');
    try {
      await api.sendHelp(subject.trim(), message.trim());
      setSent(true);
      setSubject('');
      setMessage('');
    } catch {
      setError(t('somethingWrong'));
    } finally {
      setBusy(false);
    }
  }

  return (
    <Screen>
      <T variant="heading">{t('faqs')}</T>
      {!faqs && <Loading />}
      {faqs?.map((f) => (
        <Pressable key={f.id} accessibilityRole="button" onPress={() => setOpenId(openId === f.id ? null : f.id)}>
          <Card>
            <T variant="heading" color={colors.primaryDark}>
              {f.question}
            </T>
            {openId === f.id && <T>{f.answer}</T>}
          </Card>
        </Pressable>
      ))}

      <T variant="heading">{t('contactUs')}</T>
      {sent && <T color={colors.success}>{t('helpSent')}</T>}
      <Field label={t('subject')} value={subject} onChangeText={setSubject} />
      <Field label={t('yourMessage')} value={message} onChangeText={setMessage} multiline style={{ minHeight: 120 }} />
      <ErrorText>{error}</ErrorText>
      <BigButton label={t('send')} icon="send" onPress={send} loading={busy} disabled={!subject.trim() || !message.trim()} />
    </Screen>
  );
}
