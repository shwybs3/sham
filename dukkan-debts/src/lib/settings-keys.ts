// Client-safe constants only — do NOT import prisma or any server-only module here.
// Client components (e.g. AdminSettingsForm) import from this file directly.

export const SETTINGS_KEYS = {
  shopName: "shop_name",
  bannerText: "banner_text",
  bannerEnabled: "banner_enabled",
  publicPageEnabled: "public_page_enabled",
  googleClientId: "google_client_id",
  googleClientSecret: "google_client_secret",
  turnstileSiteKey: "turnstile_site_key",
  turnstileSecretKey: "turnstile_secret_key",
  telegramBotToken: "telegram_bot_token",
  telegramChatId: "telegram_chat_id",
} as const;

export const SETTINGS_DEFAULTS: Record<string, string> = {
  [SETTINGS_KEYS.shopName]: "دفتر الدكان",
  [SETTINGS_KEYS.bannerText]: "",
  [SETTINGS_KEYS.bannerEnabled]: "false",
  [SETTINGS_KEYS.publicPageEnabled]: "true",
  [SETTINGS_KEYS.googleClientId]: "",
  [SETTINGS_KEYS.googleClientSecret]: "",
  [SETTINGS_KEYS.turnstileSiteKey]: "",
  [SETTINGS_KEYS.turnstileSecretKey]: "",
  [SETTINGS_KEYS.telegramBotToken]: "",
  [SETTINGS_KEYS.telegramChatId]: "",
};

export function isTrue(value: string | undefined | null): boolean {
  return value === "true" || value === "1";
}
