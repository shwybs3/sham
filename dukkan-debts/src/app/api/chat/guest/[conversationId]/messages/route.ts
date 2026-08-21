import { NextRequest, NextResponse } from "next/server";
import { z } from "zod";
import { prisma } from "@/lib/prisma";
import { getGuestIdFromCookies } from "@/lib/chat";
import { jsonError } from "@/lib/api-helpers";

const rateLimit = new Map<string, number[]>();
const WINDOW_MS = 5 * 60 * 1000;
const MAX_MESSAGES = 30;
const MIN_GAP_MS = 1200;

function isRateLimited(guestId: string): boolean {
  const now = Date.now();
  const timestamps = (rateLimit.get(guestId) ?? []).filter((t) => now - t < WINDOW_MS);

  if (timestamps.length > 0 && now - timestamps[timestamps.length - 1] < MIN_GAP_MS) {
    return true;
  }
  if (timestamps.length >= MAX_MESSAGES) {
    return true;
  }

  timestamps.push(now);
  rateLimit.set(guestId, timestamps);
  return false;
}

async function assertOwnsConversation(conversationId: string) {
  const guestId = await getGuestIdFromCookies();
  if (!guestId) return null;
  const conversation = await prisma.chatConversation.findUnique({ where: { id: conversationId } });
  if (!conversation || conversation.guestId !== guestId) return null;
  return conversation;
}

export async function GET(_request: NextRequest, ctx: RouteContext<"/api/chat/guest/[conversationId]/messages">) {
  const { conversationId } = await ctx.params;
  const conversation = await assertOwnsConversation(conversationId);
  if (!conversation) return jsonError("المحادثة غير موجودة", 404);

  const messages = await prisma.chatMessage.findMany({
    where: { conversationId },
    orderBy: { createdAt: "asc" },
  });

  await prisma.chatMessage.updateMany({
    where: { conversationId, sender: "admin", readByGuest: false },
    data: { readByGuest: true },
  });

  return NextResponse.json({ messages });
}

const schema = z.object({
  kind: z.enum(["text", "image", "audio"]),
  content: z.string().trim().min(1).max(4000),
});

export async function POST(request: NextRequest, ctx: RouteContext<"/api/chat/guest/[conversationId]/messages">) {
  const { conversationId } = await ctx.params;
  const conversation = await assertOwnsConversation(conversationId);
  if (!conversation) return jsonError("المحادثة غير موجودة", 404);

  if (isRateLimited(conversation.guestId)) {
    return jsonError("يرجى الانتظار قليلاً قبل إرسال رسالة أخرى", 429);
  }

  const body = await request.json().catch(() => null);
  const parsed = schema.safeParse(body);
  if (!parsed.success) return jsonError(parsed.error.issues[0]?.message ?? "بيانات غير صالحة");

  const message = await prisma.chatMessage.create({
    data: {
      conversationId,
      sender: "guest",
      kind: parsed.data.kind,
      content: parsed.data.content,
    },
  });

  await prisma.chatConversation.update({
    where: { id: conversationId },
    data: { status: "open", updatedAt: new Date() },
  });

  return NextResponse.json({ message }, { status: 201 });
}
