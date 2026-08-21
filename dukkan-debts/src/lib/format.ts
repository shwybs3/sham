export const CURRENCY_LABEL = "ل.س";

export function formatMoney(amount: number): string {
  const rounded = Math.round(amount * 100) / 100;
  return rounded.toLocaleString("ar", { maximumFractionDigits: 2 });
}

export function formatMoneyWithCurrency(amount: number): string {
  return `${formatMoney(amount)} ${CURRENCY_LABEL}`;
}

export function formatDate(date: Date | string): string {
  const d = typeof date === "string" ? new Date(date) : date;
  return new Intl.DateTimeFormat("ar-SY", {
    year: "numeric",
    month: "long",
    day: "numeric",
  }).format(d);
}

export function formatDateTime(date: Date | string): string {
  const d = typeof date === "string" ? new Date(date) : date;
  return new Intl.DateTimeFormat("ar-SY", {
    year: "numeric",
    month: "short",
    day: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  }).format(d);
}
