import { NextRequest, NextResponse } from "next/server";
import { prisma } from "@/lib/prisma";
import { createBreadSession } from "@/lib/auth";
import { exchangeGoogleCode, getGoogleCredentials } from "@/lib/google-oauth";

const STATE_COOKIE = "dukkan_bread_oauth_state";

export async function GET(request: NextRequest) {
  const { searchParams } = request.nextUrl;
  const code = searchParams.get("code");
  const state = searchParams.get("state");
  const savedState = request.cookies.get(STATE_COOKIE)?.value;

  const failUrl = new URL("/bread", request.url);

  if (!code || !state || !savedState || state !== savedState) {
    failUrl.searchParams.set("error", "google_state_mismatch");
    return NextResponse.redirect(failUrl);
  }

  const credentials = await getGoogleCredentials();
  if (!credentials) {
    failUrl.searchParams.set("error", "google_not_configured");
    return NextResponse.redirect(failUrl);
  }

  try {
    const profile = await exchangeGoogleCode(code, credentials.clientId, credentials.clientSecret);

    const customer = await prisma.breadCustomer.upsert({
      where: { googleId: profile.sub },
      update: { email: profile.email, name: profile.name, avatarUrl: profile.picture },
      create: {
        googleId: profile.sub,
        email: profile.email,
        name: profile.name,
        avatarUrl: profile.picture,
      },
    });

    await createBreadSession({
      customerId: customer.id,
      email: customer.email,
      name: customer.name,
    });

    const response = NextResponse.redirect(new URL("/bread", request.url));
    response.cookies.delete(STATE_COOKIE);
    return response;
  } catch {
    failUrl.searchParams.set("error", "google_exchange_failed");
    return NextResponse.redirect(failUrl);
  }
}
