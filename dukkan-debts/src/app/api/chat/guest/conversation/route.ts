import { NextRequest, NextResponse } from "next/server";
import { z } from "zod";
import { prisma } from "@/lib/prisma";
import { getOrCreateGuestConversation } from "@/lib/chat";
import { jsonError } from "@/lib/api-helpers";

const schema = z.object({
  guestName: z.string().trim().max(60).optional(),
});

export async function POST(request: NextRequest) {
  const body = await request.json().catch(() => ({}));
  const parsed = schema.safeParse(body ?? {});
  if (!parsed.success) return jsonError("بيانات غير صالحة");

  const conversation = await getOrCreateGuestConversation(parsed.data.guestName);

  const messages = await prisma.chatMessage.findMany({
    where: { conversationId: conversation.id },
    orderBy: { createdAt: "asc" },
  });

  await prisma.chatMessage.updateMany({
    where: { conversationId: conversation.id, sender: "admin", readByGuest: false },
    data: { readByGuest: true },
  });

  return NextResponse.json({ conversationId: conversation.id, messages });
}
