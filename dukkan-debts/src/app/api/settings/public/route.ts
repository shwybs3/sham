import { NextResponse } from "next/server";
import { getSettings, SETTINGS_KEYS, isTrue } from "@/lib/settings";

export async function GET() {
  const settings = await getSettings();
  return NextResponse.json({
    shopName: settings[SETTINGS_KEYS.shopName],
    bannerText: settings[SETTINGS_KEYS.bannerText],
    bannerEnabled: isTrue(settings[SETTINGS_KEYS.bannerEnabled]),
    publicPageEnabled: isTrue(settings[SETTINGS_KEYS.publicPageEnabled]),
    googleLoginEnabled: Boolean(settings[SETTINGS_KEYS.googleClientId]),
  });
}
