import { cookies } from "next/headers";
import { nanoid } from "nanoid";
import { prisma } from "@/lib/prisma";

export const CHAT_GUEST_COOKIE = "dukkan_chat_guest_id";

export async function getOrCreateGuestConversation(guestName?: string) {
  const store = await cookies();
  let guestId = store.get(CHAT_GUEST_COOKIE)?.value;

  if (!guestId) {
    guestId = nanoid(24);
    store.set(CHAT_GUEST_COOKIE, guestId, {
      httpOnly: true,
      secure: process.env.NODE_ENV === "production",
      sameSite: "lax",
      path: "/",
      maxAge: 60 * 60 * 24 * 365,
    });
  }

  const conversation = await prisma.chatConversation.upsert({
    where: { guestId },
    update: guestName ? { guestName } : {},
    create: { guestId, guestName: guestName || null },
  });

  return conversation;
}

export async function getGuestIdFromCookies(): Promise<string | null> {
  const store = await cookies();
  return store.get(CHAT_GUEST_COOKIE)?.value ?? null;
}
