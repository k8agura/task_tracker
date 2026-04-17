export function formatDateTime(value, locale = 'ru-RU') {
  if (!value) return '—';
  return new Date(value).toLocaleString(locale);
}

export function formatDate(value, locale = 'ru-RU') {
  if (!value) return '—';
  return new Date(value).toLocaleDateString(locale);
}

export function formatPeriod(from, to, locale = 'ru-RU') {
  const fromLabel = from ? formatDate(from, locale) : '—';
  const toLabel = to ? formatDate(to, locale) : '—';
  return `${fromLabel} — ${toLabel}`;
}

