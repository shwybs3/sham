import { NextRequest, NextResponse } from "next/server";
import { nanoid } from "nanoid";
import { buildGoogleAuthUrl, getGoogleCredentials } from "@/lib/google-oauth";

const STATE_COOKIE = "dukkan_bread_oauth_state";

export async function GET(request: NextRequest) {
  const credentials = await getGoogleCredentials();
  if (!credentials) {
    const url = new URL("/bread", request.url);
    url.searchParams.set("error", "google_not_configured");
    return NextResponse.redirect(url);
  }

  const state = nanoid(24);
  const authUrl = buildGoogleAuthUrl(credentials.clientId, state);

  const response = NextResponse.redirect(authUrl);
  response.cookies.set(STATE_COOKIE, state, {
    httpOnly: true,
    secure: process.env.NODE_ENV === "production",
    sameSite: "lax",
    path: "/",
    maxAge: 60 * 10,
  });
  return response;
}
